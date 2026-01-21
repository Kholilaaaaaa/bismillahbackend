import sys
import os
import json
import logging
import traceback
from datetime import datetime
from dotenv import load_dotenv

# ==============================
# ABSOLUTE PATHS
# ==============================
BASE_DIR = r"D:\coba\gym-genz-api"
os.chdir(BASE_DIR)

# ==============================
# LOGGING
# ==============================
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(levelname)s - %(message)s",
    handlers=[
        logging.FileHandler(os.path.join(BASE_DIR, "chatbot.log"), encoding="utf-8"),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

# ==============================
# LOAD ENV
# ==============================
env_path = os.path.join(BASE_DIR, ".env")
if os.path.exists(env_path):
    load_dotenv(env_path)
    logger.info("Environment file loaded")
else:
    logger.error(f"ENV file not found: {env_path}")
    print(json.dumps({"status": "error","message": "ENV file not found","data":[]}))
    sys.exit(1)

# ==============================
# IMPORT LANGCHAIN
# ==============================
try:
    from langchain_text_splitters import RecursiveCharacterTextSplitter
    from langchain_community.document_loaders import TextLoader
    from langchain_huggingface import HuggingFaceEmbeddings
    from langchain_community.vectorstores import FAISS
    from langchain_community.llms import Ollama
    from langchain.chains import RetrievalQA
    logger.info("LangChain imported")
except Exception as e:
    logger.error(f"LangChain import failed: {e}")
    sys.exit(1)

# ==============================
# CONFIG
# ==============================
DATA_PATH = os.getenv("CHATBOT_DATA_PATH", os.path.join(BASE_DIR, "chatbot_data"))
VECTOR_PATH = os.getenv("CHATBOT_VECTOR_PATH", os.path.join(BASE_DIR, "chatbot_vectorstore"))
EMBEDDING_MODEL = os.getenv("CHATBOT_EMBEDDING","sentence-transformers/all-MiniLM-L6-v2")
LLM_MODEL = os.getenv("CHATBOT_LLM","llama3")

# ==============================
# CHATBOT CLASS
# ==============================
class GymChatbot:
    def __init__(self):
        logger.info("Initializing GymChatbot")
        self.embeddings = HuggingFaceEmbeddings(model_name=EMBEDDING_MODEL)
        self.llm = Ollama(model=LLM_MODEL)

    def load_documents(self):
        documents = []
        if not os.path.exists(DATA_PATH):
            logger.error(f"Data path not found: {DATA_PATH}")
            return documents
        for file in os.listdir(DATA_PATH):
            if file.endswith(".txt"):
                loader = TextLoader(os.path.join(DATA_PATH, file), encoding="utf-8")
                documents.extend(loader.load())
        logger.info(f"Loaded {len(documents)} documents")
        return documents

    def build_vectorstore(self):
        docs = self.load_documents()
        if not docs:
            raise Exception("No documents found")
        splitter = RecursiveCharacterTextSplitter(chunk_size=500, chunk_overlap=50)
        chunks = splitter.split_documents(docs)
        db = FAISS.from_documents(chunks, self.embeddings)
        os.makedirs(VECTOR_PATH, exist_ok=True)
        db.save_local(VECTOR_PATH)
        logger.info("Vectorstore created")
        return db

    def load_vectorstore(self):
        return FAISS.load_local(VECTOR_PATH, self.embeddings, allow_dangerous_deserialization=True)

    def answer(self, question: str):
        try:
            if not os.path.exists(VECTOR_PATH):
                db = self.build_vectorstore()
            else:
                db = self.load_vectorstore()

            qa = RetrievalQA.from_chain_type(
                llm=self.llm,
                chain_type="stuff",
                retriever=db.as_retriever(search_kwargs={"k":3}),
                return_source_documents=False
            )
            response = qa.invoke({"query": question})
            return {"status":"success","question":question,"answer":response["result"],"timestamp":datetime.now().strftime("%Y-%m-%d %H:%M:%S")}
        except Exception as e:
            logger.error(f"Chatbot error: {e}")
            traceback.print_exc()
            return {"status":"error","message":str(e),"question":question}

# ==============================
# MAIN
# ==============================
def main():
    try:
        if len(sys.argv) < 2:
            print(json.dumps({"status":"error","message":"No question provided"}))
            sys.exit(1)

        # Gabung semua argumen jadi satu string, aman untuk spasi
        question = " ".join(sys.argv[1:])
        chatbot = GymChatbot()
        result = chatbot.answer(question)
        print(json.dumps(result, ensure_ascii=False))

    except Exception as e:
        print(json.dumps({"status":"error","message":f"Critical: {str(e)}"}))
        sys.exit(1)

if __name__ == "__main__":
    main()
