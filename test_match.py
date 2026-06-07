import os
import glob
from deepface import DeepFace
import json

target_dir = r"c:\xampp\htdocs\Searchar\Website\uploads\ai_targets\*.jpg"
latest_target = max(glob.glob(target_dir), key=os.path.getctime)
snapshot = r"c:\xampp\htdocs\Searchar\Website\uploads\cctv_snapshots\feed_1_latest.jpg"

try:
    result = DeepFace.verify(
        img1_path=latest_target,
        img2_path=snapshot,
        model_name="VGG-Face",
        enforce_detection=False,
        detector_backend="opencv"
    )
    with open("c:\\xampp\\htdocs\\Searchar\\deepface_debug.txt", "w") as f:
        f.write(json.dumps(result))
except Exception as e:
    with open("c:\\xampp\\htdocs\\Searchar\\deepface_debug.txt", "w") as f:
        f.write("Error: " + str(e))
