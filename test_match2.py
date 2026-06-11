import requests
import json
from deepface import DeepFace

try:
    faces = DeepFace.extract_faces("c:/xampp/htdocs/Searchar/Website/uploads/cctv_snapshots/feed_1_latest.jpg", detector_backend="retinaface", enforce_detection=False)
    print("Found", len(faces), "faces with retinaface")
except Exception as e:
    print("Error:", e)
