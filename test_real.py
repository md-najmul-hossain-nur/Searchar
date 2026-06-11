import cv2
from deepface import DeepFace

target_img = "c:/xampp/htdocs/Searchar/Website/uploads/missing_person/missing_6a2aabe26da4f1.02675634.jpg"
cctv_img = "c:/xampp/htdocs/Searchar/Website/uploads/cctv_snapshots/feed_1_latest.jpg"

try:
    print("Testing target image against feed_1_latest.jpg")
    res = DeepFace.verify(
        img1_path=target_img,
        img2_path=cctv_img,
        model_name="VGG-Face",
        enforce_detection=False,
        detector_backend="opencv"
    )
    print("Direct Verify:")
    print("Distance:", res.get('distance'))
    print("Verified:", res.get('verified'))

    print("\nTesting extract_faces then verify:")
    faces = DeepFace.extract_faces(img_path=cctv_img, detector_backend="opencv", enforce_detection=False)
    for face_obj in faces:
        try:
            facial_area = face_obj["facial_area"]
            x, y, w, h = facial_area["x"], facial_area["y"], facial_area["w"], facial_area["h"]
            print(f"Face crop area: x={x}, y={y}, w={w}, h={h}")
            
            img2_bgr = cv2.imread(cctv_img)
            y1, y2 = max(0, y), min(img2_bgr.shape[0], y+h)
            x1, x2 = max(0, x), min(img2_bgr.shape[1], x+w)
            face_crop = img2_bgr[y1:y2, x1:x2]

            res2 = DeepFace.verify(
                img1_path=target_img,
                img2_path=face_crop,
                model_name="VGG-Face",
                enforce_detection=False,
                detector_backend="opencv"
            )
            print("Distance (crop):", res2.get('distance'))
            print("Verified (crop):", res2.get('verified'))
        except Exception as ex:
            print("Crop verify error:", ex)

except Exception as e:
    print("Error:", e)
