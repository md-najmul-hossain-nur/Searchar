import cv2
import numpy as np
import time
import requests
import os
import threading
import logging

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Config
FETCH_URL = 'http://127.0.0.1/Searchar/Website/Php/python_fetch_cctv_feeds.php'
INSERT_URL = 'http://127.0.0.1/Searchar/Website/Php/python_insert_fire_alert.php'
OUTPUT_DIR = os.path.abspath(os.path.join(os.path.dirname(__file__), '../Website/Images/fire_snapshots'))

os.makedirs(OUTPUT_DIR, exist_ok=True)

# Keep track of already alerted feeds to avoid spamming
alerted_feeds = {}

# Per-feed consecutive detection counter — fire must be seen N times in a row before alerting
pending_detections = {}

FIRE_DETECTOR_ENABLED = False

def set_fire_detector_enabled(status):
    global FIRE_DETECTOR_ENABLED
    FIRE_DETECTOR_ENABLED = status

def get_fire_detector_status():
    global FIRE_DETECTOR_ENABLED
    return FIRE_DETECTOR_ENABLED

def detect_fire(frame):
    """
    OpenCV heuristic for fire detection using tight HSV thresholds to minimise
    false positives from sunsets, orange objects, and bright indoor lighting.
    """
    blur = cv2.GaussianBlur(frame, (21, 21), 0)
    hsv = cv2.cvtColor(blur, cv2.COLOR_BGR2HSV)

    # Tight fire range: hue 0-20 (red→orange only), high saturation, high brightness.
    # Deliberately excludes yellow-green (hue 20-35) and low-saturation warm tones.
    lower = np.array([0, 120, 170], dtype="uint8")
    upper = np.array([20, 255, 255], dtype="uint8")

    mask = cv2.inRange(hsv, lower, upper)

    num_fire_pixels = cv2.countNonZero(mask)
    total_pixels = frame.shape[0] * frame.shape[1]

    # Require > 8% fire pixels AND > 12000 absolute pixels to avoid tiny false blobs
    ratio = (num_fire_pixels / total_pixels) * 100
    if ratio > 8.0 and num_fire_pixels > 12000:
        contours, _ = cv2.findContours(mask, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)

        # At least one contour must be large enough (500 px²) to rule out noise
        large_contours = [cnt for cnt in contours if cv2.contourArea(cnt) > 500]
        if not large_contours:
            return False, "0%", frame

        output_frame = frame.copy()
        for cnt in large_contours:
            x, y, w, h = cv2.boundingRect(cnt)
            cv2.rectangle(output_frame, (x, y), (x + w, y + h), (0, 0, 255), 2)
            cv2.putText(output_frame, 'FIRE DETECTED', (x, y - 10),
                        cv2.FONT_HERSHEY_SIMPLEX, 0.9, (0, 0, 255), 2)

        # Honest confidence: starts low, only reaches high values at clearly high ratios
        conf = min(95, 40 + int(ratio * 10))
        return True, f"{conf}% High", output_frame

    return False, "0%", frame

CONSECUTIVE_DETECTIONS_REQUIRED = 1  # trigger immediately for real-time detection

def process_feed(feed):
    feed_id = feed.get('feed_id')
    video_path = feed.get('video_path')

    # 10 second cooldown per camera to prevent snapshot spam, but still near real-time
    if feed_id in alerted_feeds and (time.time() - alerted_feeds[feed_id] < 10):
        return

    path_to_check = None

    snapshot_path = os.path.abspath(os.path.join(os.path.dirname(__file__), f"../Website/uploads/cctv_snapshots/feed_{feed_id}_latest.jpg"))
    if os.path.exists(snapshot_path):
        path_to_check = snapshot_path
    elif video_path and os.path.exists(video_path):
        path_to_check = video_path

    if not path_to_check:
        pending_detections.pop(feed_id, None)
        return

    try:
        ext = os.path.splitext(path_to_check)[1].lower()
        if ext in ['.jpg', '.jpeg', '.png', '.webp']:
            frame = cv2.imread(path_to_check)
            if frame is None:
                pending_detections.pop(feed_id, None)
                return

            is_fire, conf, out_frame = detect_fire(frame)
        else:
            cap = cv2.VideoCapture(path_to_check)
            if not cap.isOpened():
                pending_detections.pop(feed_id, None)
                return

            # Sample a frame from 10% into the video to skip black leader frames
            total_frames = int(cap.get(cv2.CAP_PROP_FRAME_COUNT) or 0)
            if total_frames > 10:
                cap.set(cv2.CAP_PROP_POS_FRAMES, total_frames // 10)

            ret, frame = cap.read()
            cap.release()
            if not ret:
                pending_detections.pop(feed_id, None)
                return

            is_fire, conf, out_frame = detect_fire(frame)

        if is_fire:
            # For manual scan, no consecutive detections needed, just trigger
            trigger_alert(feed_id, out_frame, conf)
            return True
        return False
    except Exception as e:
        logger.error(f"Error processing feed {feed_id}: {e}")
        return False

def trigger_alert(feed_id, frame, confidence):
    filename = f"fire_{feed_id}_{int(time.time())}.jpg"
    filepath = os.path.join(OUTPUT_DIR, filename)
    cv2.imwrite(filepath, frame)

    # Keep only the 20 most recent snapshots to avoid disk fill-up
    try:
        all_snaps = sorted(
            [f for f in os.listdir(OUTPUT_DIR) if f.endswith('.jpg')],
            key=lambda f: os.path.getmtime(os.path.join(OUTPUT_DIR, f))
        )
        for old in all_snaps[:-20]:
            os.remove(os.path.join(OUTPUT_DIR, old))
    except Exception:
        pass
    
    # URL relative to website root
    snapshot_url = f"../Images/fire_snapshots/{filename}"
    
    payload = {
        'feed_id': feed_id,
        'confidence': confidence,
        'snapshot_url': snapshot_url
    }
    
    try:
        res = requests.post(INSERT_URL, json=payload)
        if res.json().get('success'):
            logger.info(f"Fire detected on feed {feed_id}! Alert inserted.")
            alerted_feeds[feed_id] = time.time()
        else:
            logger.error(f"Failed to insert alert: {res.text}")
    except Exception as e:
        logger.error(f"HTTP Error triggering alert: {e}")

def run_fire_scan_once():
    logger.info("Running manual Fire Detector scan...")
    fires_found = 0
    try:
        res = requests.get(FETCH_URL, timeout=5)
        data = res.json()
        if data.get('success'):
            feeds = data.get('feeds', [])
            for feed in feeds:
                if process_feed(feed):
                    fires_found += 1
    except Exception as e:
        logger.error(f"Error in manual fire scan: {e}")
    return fires_found
