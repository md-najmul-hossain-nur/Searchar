# SearchAR 🚀

SearchAR is an advanced, community-driven web platform designed to help locate missing persons, report crimes, and ensure public safety through the power of Artificial Intelligence. 

By bridging the gap between everyday citizens, volunteers, police forces, and AI technology, SearchAR creates a collaborative ecosystem for rapid emergency response.

## 🌟 Key Features

### 🧠 AI-Powered Facial Recognition
- Upload a photo of a missing person, and the integrated **Python AI Engine** (powered by DeepFace) will automatically scan thousands of community posts, uploaded photos, and connected CCTV/Webcam feeds to find potential matches.
- Returns high-confidence AI matches directly to the Admin dashboard for verification.

### 🔥 Real-Time Fire & Incident Detection
- Connect CCTV feeds and run the **Fire Detection Scan**. The AI engine analyzes frames using OpenCV and contour detection algorithms to identify potential fire hazards.
- Instantly alerts authorities with a snapshot, confidence score, and location data.

### 👥 Multi-Tier User Roles
- **General Public:** Submit missing person reports, upload crime clues, view live public camera feeds, and donate to causes.
- **Volunteers:** Dedicated heroes who verify clues, perform ground checks, earn achievement badges, and level up their ranks.
- **Police / Law Enforcement:** Exclusive dashboard to review verified evidence, manage emergency broadcast requests, and close cases officially.
- **Admin:** Complete oversight over the platform, user management, withdrawal requests, and AI monitoring.

### 📹 Community Camera Broadcasting
- Users can contribute to public safety by sharing their CCTV or webcam feeds.
- The AI Engine processes these feeds in the background to automatically identify missing people or hazards.

### 💬 Interactive Support Chatbot
- Built-in smart chatbot on the homepage to assist users with navigation, donations, and reporting.
- Features a seamless "Admin Reply" integration, allowing admins to respond to user queries directly from the dashboard.

## 🛠️ Technology Stack

- **Frontend:** HTML5, Vanilla CSS, JavaScript (ES6+)
- **Backend:** PHP 8+
- **AI Microservice:** Python 3 (Flask, OpenCV, DeepFace)
- **Database:** MySQL
- **Environment:** Designed for XAMPP (Windows/Linux)

## 🚀 Getting Started

### Prerequisites
1. Install **XAMPP** (with PHP and MySQL).
2. Install **Python 3.8+**.
3. Install required Python packages for the AI Engine:
   ```bash
   pip install flask flask-cors opencv-python requests deepface tf-keras
   ```

### Installation

1. **Clone the Repository**
   Place the `Searchar` folder inside your XAMPP `htdocs` directory (`C:\xampp\htdocs\Searchar`).

2. **Database Setup**
   - Open phpMyAdmin (`http://localhost/phpmyadmin`).
   - Create a new database named `searchar`.
   - Import the `db_schema_dump.sql` file provided in the repository to create the tables.

3. **Start the Platform**
   - Start **Apache** and **MySQL** from the XAMPP Control Panel.
   - Navigate to `http://localhost/Searchar/Website/Html/index.html` in your browser.

4. **Running the AI Engine**
   - Go to the Admin Dashboard and click **"Start Python AI Engine"**.
   - Alternatively, you can run the `start_ai.bat` script manually. The AI server runs on `http://127.0.0.1:5001`.

## 📁 Project Structure

- `/PythonAI`: Contains the Flask server (`app.py`), facial recognition logic, and fire detection algorithms.
- `/Website/Html`: All the frontend UI pages for Admins, Users, Police, and Volunteers.
- `/Website/Css`: Styling and animations.
- `/Website/javascrpit`: Frontend logic, API calling, and interactivity.
- `/Website/Php`: Backend endpoints handling database operations, authentication, and communication with the Python AI.
- `/Website/Images & /Website/Uploads`: Assets and user-uploaded media.

## 🛡️ Security & Privacy
SearchAR is built with security in mind. Sensitive crime reports and volunteer proofs are securely stored and only accessible by verified Law Enforcement officers and Admins. Passwords are securely hashed before storage.

---
*Built to make the world a safer place.* 💛
