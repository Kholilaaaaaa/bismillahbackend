@echo off
echo Menginstal dependensi Python untuk CHATBOT GYMZ AI...
cd /d "D:\coba\gym-genz-api\scripts"

REM ============================
REM Buat virtual environment
REM ============================
if not exist "venv" (
    echo Membuat virtual environment...
    python -m venv venv
)

REM ============================
REM Aktifkan virtual environment
REM ============================
call venv\Scripts\activate.bat

REM ============================
REM Upgrade pip
REM ============================
pip install --upgrade pip

REM ============================
REM Install chatbot dependencies
REM ============================
pip install ^
langchain ^
langchain-community ^
langchain-huggingface ^
faiss-cpu ^
sentence-transformers ^
ollama ^
python-dotenv

REM ============================
REM Selesai
REM ============================
echo.
echo ============================================
echo Instalasi CHATBOT GYMZ AI selesai!
echo Pastikan Ollama sudah terinstall dan berjalan
echo ============================================
pause
