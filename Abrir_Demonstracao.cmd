@echo off
cd /d "%~dp0"
python app.py --demonstracao
if errorlevel 1 pause
