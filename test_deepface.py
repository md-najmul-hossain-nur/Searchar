from deepface import DeepFace
import json

try:
    result = DeepFace.verify(
        img1_path="c:\\xampp\\htdocs\\Searchar\\uploads\\cctv_snapshots\\feed_1_latest.jpg",
        img2_path="c:\\xampp\\htdocs\\Searchar\\uploads\\cctv_snapshots\\feed_1_latest.jpg", # test against itself first
        model_name="VGG-Face",
        enforce_detection=False,
        detector_backend="opencv"
    )
    print(json.dumps(result))
except Exception as e:
    print("Error:", str(e))
