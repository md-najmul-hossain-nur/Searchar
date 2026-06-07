@echo off
set PYTHONIOENCODING=utf-8
echo Starting SearchAR Python AI Engine...
cd /d "%~dp0"
.\.venv\Scripts\python.exe .\PythonAI\app.py
