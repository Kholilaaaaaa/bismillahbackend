@echo off
echo Menginstal dependensi Python untuk Analisis Sentimen...
cd /d "D:\coba\gym-genz-api\scripts"

REM Buat virtual environment jika belum ada
if not exist "venv" (
    echo Membuat virtual environment...
    python -m venv venv
)

REM Aktifkan virtual environment
call venv\Scripts\activate.bat

REM Install dependencies
pip install --upgrade pip
pip install -r requirements.txt

echo.
echo Unduh data NLTK...
python -c "import nltk; nltk.download('punkt', download_dir='./nltk_data'); nltk.download('stopwords', download_dir='./nltk_data')"

echo.
echo Instalasi selesai!
pause