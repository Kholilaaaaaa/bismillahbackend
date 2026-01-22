@echo off
echo Installing Python dependencies for GYMZ Chatbot...
echo.

REM Upgrade pip
python -m pip install --upgrade pip

REM Install basic dependencies
pip install numpy scipy scikit-learn
pip install torch torchvision torchaudio --index-url https://download.pytorch.org/whl/cpu
pip install sentence-transformers
pip install transformers
pip install tensorflow
pip install pymysql
pip install python-dotenv
pip install pickle5

REM Try to install faiss for Windows
pip install faiss-cpu-windows

REM If above fails, try alternative
if errorlevel 1 (
    echo faiss-cpu-windows failed, trying faiss-cpu...
    pip install faiss-cpu
)

echo.
echo Installation complete!
pause