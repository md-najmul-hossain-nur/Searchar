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
alerted_feeds = set()

def detect_fire(frame):
    """
    OpenCV heuristic for fire detection.
    Converts frame to HSV and masks out bright orange/yellow/red areas.
    """
    blur = cv2.GaussianBlur(frame, (21, 21), 0)
    hsv = cv2.cvtColor(blur, cv2.COLOR_BGR2HSV)
    
    # Balanced Fire color range in HSV to detect lighter flames but reduce false positives
    lower = np.array([0, 80, 140], dtype="uint8")
    upper = np.array([35, 255, 255], dtype="uint8")
    
    mask = cv2.inRange(hsv, lower, upper)
    
    # Calculate area
    num_fire_pixels = cv2.countNonZero(mask)
    total_pixels = frame.shape[0] * frame.shape[1]
    
    # Trigger if > 0.08% or > 800 pixels
    ratio = (num_fire_pixels / total_pixels) * 100
    if ratio > 0.08 or num_fire_pixels > 800:
        # Find contours to draw a bounding box
        contours, _ = cv2.findContours(mask, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
        output_frame = frame.copy()
        for cnt in contours:
            if cv2.contourArea(cnt) > 150: # Only draw box around decent sized blobs
                x, y, w, h = cv2.boundingRect(cnt)
                cv2.rectangle(output_frame, (x, y), (x+w, y+h), (0, 0, 255), 2)
                cv2.putText(output_frame, 'FIRE DETECTED', (x, y-10), cv2.FONT_HERSHEY_SIMPLEX, 0.9, (0, 0, 255), 2)
                
        # Confidence score scaling
        conf = min(99, int(ratio * 50) + 75)
        return True, f"{conf}% High", output_frame
    
    return False, "0%", frame

def process_feed(feed):
    feed_id = feed.get('feed_id')
    video_path = feed.get('video_path')
    
    # 15 second cooldown per camera so it keeps coming!
    if feed_id in alerted_feeds and (time.time() - alerted_feeds[feed_id] < 15):
        return
        
    path_to_check = None
    
    # Priority 1: Check if a live snapshot exists in uploads/cctv_snapshots/
    snapshot_path = os.path.abspath(os.path.join(os.path.dirname(__file__), f"../Website/uploads/cctv_snapshots/feed_{feed_id}_latest.jpg"))
    if os.path.exists(snapshot_path):
        path_to_check = snapshot_path
    elif video_path and os.path.exists(video_path):
        path_to_check = video_path
        
    if not path_to_check:
        return
        
    try:
        ext = os.path.splitext(path_to_check)[1].lower()
        if ext in ['.jpg', '.jpeg', '.png', '.webp']:
            frame = cv2.imread(path_to_check)
            if frame is None: return
            
            is_fire, conf, out_frame = detect_fire(frame)
            if is_fire:
                trigger_alert(feed_id, out_frame, conf)
        else:
            cap = cv2.VideoCapture(path_to_check)
            if not cap.isOpened(): return
            
            ret, frame = cap.read()
            if ret:
                is_fire, conf, out_frame = detect_fire(frame)
                if is_fire:
                    trigger_alert(feed_id, out_frame, conf)
            cap.release()
            
    except Exception as e:
        logger.error(f"Error processing feed {feed_id}: {e}")

def trigger_alert(feed_id, frame, confidence):
    filename = f"fire_{feed_id}_{int(time.time())}.jpg"
    filepath = os.path.join(OUTPUT_DIR, filename)
    cv2.imwrite(filepath, frame)
    
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

def fire_detector_loop():
    logger.info("Starting Fire Detector background loop...")
    while True:
        try:
            res = requests.get(FETCH_URL, timeout=5)
            data = res.json()
            if data.get('success'):
                feeds = data.get('feeds', [])
                for feed in feeds:
                    process_feed(feed)
        except Exception as e:
            pass # Silently retry on next tick
            
        time.sleep(5) # Poll every 5 seconds

def start_fire_detector():
    t = threading.Thread(target=fire_detector_loop, daemon=True)
    t.start()
