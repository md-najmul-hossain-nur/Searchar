import requests
import json

data = {
    "target_image": "c:/xampp/htdocs/Searchar/Website/uploads/cases/test.jpg",
    "video_paths": []
}
try:
    res = requests.post("http://127.0.0.1:5001/api/search_cctv", json=data)
    print(res.status_code)
    print(res.text)
except Exception as e:
    print("Error:", e)
