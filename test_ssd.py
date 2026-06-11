import cv2
from deepface import DeepFace

cctv_img = "c:/xampp/htdocs/Searchar/Website/uploads/cctv_snapshots/feed_1_latest.jpg"

try:
    faces = DeepFace.extract_faces(img_path=cctv_img, detector_backend="ssd", enforce_detection=False)
    for face_obj in faces:
        try:
            facial_area = face_obj["facial_area"]
            x, y, w, h = facial_area["x"], facial_area["y"], facial_area["w"], facial_area["h"]
            print(f"Face crop area: x={x}, y={y}, w={w}, h={h}")
        except Exception as ex:
            print("Error:", ex)
except Exception as e:
    print("Error:", e)
