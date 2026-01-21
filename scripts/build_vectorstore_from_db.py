import os
import pymysql
import pickle
import faiss
import numpy as np
from sentence_transformers import SentenceTransformer

# ==============================
# KONFIGURASI
# ==============================

DB_CONFIG = {
    "host": "127.0.0.1",
    "user": "root",
    "password": "",
    "database": "gym_api",
    "port": 3306,
}

TABLE_NAME = "chatbot_knowledge"

VECTOR_DIR = "vectorstore"
VECTOR_INDEX_FILE = os.path.join(VECTOR_DIR, "faiss.index")
VECTOR_META_FILE = os.path.join(VECTOR_DIR, "metadata.pkl")

EMBEDDING_MODEL = "sentence-transformers/all-MiniLM-L6-v2"

# ==============================
# AMBIL DATA DARI DATABASE
# ==============================

def fetch_chatbot_data():
    connection = pymysql.connect(**DB_CONFIG)
    cursor = connection.cursor(pymysql.cursors.DictCursor)

    query = f"SELECT question, answer FROM {TABLE_NAME}"
    cursor.execute(query)
    results = cursor.fetchall()

    cursor.close()
    connection.close()

    return results

# ==============================
# BUILD VECTORSTORE
# ==============================

def build_vectorstore():
    print("📥 Mengambil data dari database...")
    data = fetch_chatbot_data()

    if not data:
        raise Exception("❌ Data chatbot kosong!")

    texts = []
    metadatas = []

    for row in data:
        content = f"Pertanyaan: {row['question']}\nJawaban: {row['answer']}"
        texts.append(content)
        metadatas.append(row)

    print(f"📄 Total dokumen: {len(texts)}")

    print("🧠 Load embedding model...")
    model = SentenceTransformer(EMBEDDING_MODEL)

    print("🔢 Membuat embeddings...")
    embeddings = model.encode(texts, show_progress_bar=True)

    embeddings = np.array(embeddings).astype("float32")

    dim = embeddings.shape[1]
    index = faiss.IndexFlatL2(dim)
    index.add(embeddings)

    os.makedirs(VECTOR_DIR, exist_ok=True)

    print("💾 Menyimpan FAISS index...")
    faiss.write_index(index, VECTOR_INDEX_FILE)

    print("💾 Menyimpan metadata...")
    with open(VECTOR_META_FILE, "wb") as f:
        pickle.dump(metadatas, f)

    print("✅ VECTORSTORE BERHASIL DIBUAT!")
    print(f"📁 Index : {VECTOR_INDEX_FILE}")
    print(f"📁 Meta  : {VECTOR_META_FILE}")

# ==============================
# MAIN
# ==============================

if __name__ == "__main__":
    build_vectorstore()
