import sys
import os
import json
import faiss
import pickle
import torch
import numpy as np
import logging
import pymysql
from datetime import datetime
from sentence_transformers import SentenceTransformer
from transformers import AutoTokenizer, AutoModelForCausalLM
from dotenv import load_dotenv
import tensorflow as tf
from tensorflow import keras

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
    
    # Database Configuration
    DB_HOST = os.getenv("DB_HOST")
    DB_PORT = int(os.getenv("DB_PORT", 3306))
    DB_DATABASE = os.getenv("DB_DATABASE")
    DB_USERNAME = os.getenv("DB_USERNAME")
    DB_PASSWORD = os.getenv("DB_PASSWORD")
    
    # AI Configuration
    HF_TOKEN = os.getenv("HF_TOKEN")
    MODEL_ID = os.getenv("MODEL_ID", "google/gemma-1.1-2b-it")
    USE_H5_MODEL = os.getenv("USE_H5_MODEL", "false").lower() == "true"
    H5_MODEL_PATH = os.getenv("H5_MODEL_PATH", "storage/app/models/chatbot_model.h5")
    
    # RAG Configuration
    RAG_INDEX_PATH = os.getenv("RAG_INDEX_PATH", "storage/app/rag/rag_index.faiss")
    RAG_CHUNKS_PATH = os.getenv("RAG_CHUNKS_PATH", "storage/app/rag/rag_chunks.pkl")
    
    # Model Selection
    MODEL_TYPE = os.getenv("MODEL_TYPE", "llm")  # "llm", "h5", or "hybrid"
    
else:
    logger.error(f"ENV file not found: {env_path}")
    print(json.dumps({"status": "error", "message": "ENV file not found", "data": []}))
    sys.exit(1)

# ==============================
# DATABASE CONNECTION
# ==============================
def get_db_connection():
    """Membuat koneksi ke database"""
    try:
        connection = pymysql.connect(
            host=DB_HOST,
            port=DB_PORT,
            user=DB_USERNAME,
            password=DB_PASSWORD,
            database=DB_DATABASE,
            charset='utf8mb4',
            cursorclass=pymysql.cursors.DictCursor
        )
        logger.info("Database connection established")
        return connection
    except pymysql.Error as e:
        logger.error(f"Database connection failed: {e}")
        raise

# ==============================
# DATABASE FUNCTIONS
# ==============================
def fetch_knowledge_from_db():
    """
    Mengambil data dari tabel chatbot_knowledge
    """
    connection = None
    try:
        connection = get_db_connection()
        with connection.cursor() as cursor:
            # Query untuk mengambil semua knowledge
            sql = """
            SELECT 
                id, 
                question, 
                answer, 
                category, 
                tags, 
                created_at, 
                updated_at 
            FROM chatbot_knowledge 
            WHERE is_active = 1
            ORDER BY category, id
            """
            cursor.execute(sql)
            results = cursor.fetchall()
            
            logger.info(f"Fetched {len(results)} records from chatbot_knowledge table")
            return results
            
    except Exception as e:
        logger.error(f"Error fetching knowledge from database: {e}")
        return []
    finally:
        if connection:
            connection.close()

def save_chat_log(question, answer, model_type="llm", confidence=None):
    """
    Menyimpan log percakapan ke database
    """
    connection = None
    try:
        connection = get_db_connection()
        with connection.cursor() as cursor:
            sql = """
            INSERT INTO chat_logs 
            (question, answer, model_type, confidence, created_at) 
            VALUES (%s, %s, %s, %s, %s)
            """
            cursor.execute(sql, (question, answer, model_type, confidence, datetime.now()))
            connection.commit()
            logger.info(f"Chat log saved to database (model: {model_type})")
    except Exception as e:
        logger.error(f"Error saving chat log: {e}")
    finally:
        if connection:
            connection.close()

def save_generated_answer(question, answer, model_type="generated"):
    """
    Menyimpan jawaban yang di-generate ke tabel chatbot_knowledge
    """
    connection = None
    try:
        connection = get_db_connection()
        with connection.cursor() as cursor:
            sql = """
            INSERT INTO chatbot_knowledge 
            (question, answer, source, is_active, model_type, created_at, updated_at) 
            VALUES (%s, %s, %s, %s, %s, %s, %s)
            """
            cursor.execute(sql, (
                question, 
                answer, 
                'generated', 
                1, 
                model_type,
                datetime.now(), 
                datetime.now()
            ))
            connection.commit()
            logger.info(f"Generated answer saved to knowledge base (model: {model_type})")
            return cursor.lastrowid
    except Exception as e:
        logger.error(f"Error saving generated answer: {e}")
        return None
    finally:
        if connection:
            connection.close()

def get_predefined_answer(question):
    """
    Mencari jawaban yang sudah ada di database dengan similarity matching
    """
    connection = None
    try:
        connection = get_db_connection()
        with connection.cursor() as cursor:
            # Simple keyword matching untuk percobaan
            # Untuk production, gunakan embedding similarity
            sql = """
            SELECT question, answer, category 
            FROM chatbot_knowledge 
            WHERE is_active = 1 
            AND answer IS NOT NULL 
            AND answer != ''
            """
            cursor.execute(sql)
            results = cursor.fetchall()
            
            # Simple similarity check (bisa diganti dengan cosine similarity)
            best_match = None
            best_score = 0
            
            for item in results:
                # Hitung similarity sederhana
                question_lower = question.lower()
                item_question_lower = item['question'].lower()
                
                # Hitung kata yang sama
                common_words = set(question_lower.split()) & set(item_question_lower.split())
                similarity = len(common_words) / max(len(question_lower.split()), 1)
                
                if similarity > best_score and similarity > 0.3:  # Threshold 30%
                    best_score = similarity
                    best_match = item
            
            if best_match:
                logger.info(f"Found predefined answer with similarity: {best_score:.2f}")
                return best_match['answer'], best_score
            
            return None, 0
            
    except Exception as e:
        logger.error(f"Error getting predefined answer: {e}")
        return None, 0
    finally:
        if connection:
            connection.close()

# ==============================
# H5 MODEL LOADER
# ==============================
class H5ChatbotModel:
    def __init__(self, model_path):
        logger.info(f"Loading H5 model from: {model_path}")
        
        if not os.path.exists(model_path):
            logger.error(f"H5 model not found at: {model_path}")
            raise FileNotFoundError(f"H5 model not found: {model_path}")
        
        # Load model
        self.model = keras.models.load_model(model_path)
        
        # Load tokenizer jika ada
        tokenizer_path = model_path.replace('.h5', '_tokenizer.pkl')
        if os.path.exists(tokenizer_path):
            import pickle
            with open(tokenizer_path, 'rb') as f:
                self.tokenizer = pickle.load(f)
            logger.info("Tokenizer loaded")
        else:
            self.tokenizer = None
            logger.warning("Tokenizer not found, using simple text processing")
        
        # Load label encoder jika ada
        label_path = model_path.replace('.h5', '_labels.pkl')
        if os.path.exists(label_path):
            import pickle
            with open(label_path, 'rb') as f:
                self.labels = pickle.load(f)
            logger.info(f"Labels loaded: {len(self.labels)} labels")
        else:
            self.labels = None
        
        logger.info("H5 model loaded successfully")
    
    def preprocess_text(self, text, max_length=50):
        """Preprocess text untuk input model H5"""
        if self.tokenizer:
            # Gunakan tokenizer jika ada
            sequence = self.tokenizer.texts_to_sequences([text])
            padded = keras.preprocessing.sequence.pad_sequences(sequence, maxlen=max_length)
            return padded
        else:
            # Fallback: simple one-hot encoding
            words = text.lower().split()[:max_length]
            # Implementasi sederhana, sesuaikan dengan arsitektur model Anda
            return np.zeros((1, max_length, 100))  # Placeholder
    
    def predict(self, text, threshold=0.7):
        """Memprediksi jawaban dari model H5"""
        try:
            # Preprocess input
            processed_input = self.preprocess_text(text)
            
            # Predict
            prediction = self.model.predict(processed_input, verbose=0)
            
            # Get confidence and class
            confidence = float(np.max(prediction))
            predicted_class = int(np.argmax(prediction))
            
            logger.info(f"H5 Model Prediction - Class: {predicted_class}, Confidence: {confidence:.2f}")
            
            # Check if confidence meets threshold
            if confidence < threshold:
                return None, confidence
            
            # Map to answer jika ada labels
            if self.labels and predicted_class < len(self.labels):
                answer = self.labels[predicted_class]
                return answer, confidence
            
            # Fallback: return class index
            return f"Response class {predicted_class}", confidence
            
        except Exception as e:
            logger.error(f"Error in H5 model prediction: {e}")
            return None, 0

# ==============================
# HYBRID CHATBOT CLASS
# ==============================
class HybridGymGenZChatbot:
    def __init__(self, model_type="auto"):
        """
        Initialize hybrid chatbot dengan multiple model support
        
        Args:
            model_type: "auto", "llm", "h5", "hybrid"
        """
        logger.info(f"Initializing HybridGymGenZChatbot with type: {model_type}")
        
        self.model_type = model_type if model_type != "auto" else MODEL_TYPE
        self.h5_model = None
        self.llm_model = None
        self.rag_index = None
        self.all_chunks = None
        self.embed_model = None
        
        # Load models berdasarkan tipe
        if self.model_type in ["llm", "hybrid"]:
            self._load_llm_model()
        
        if self.model_type in ["h5", "hybrid"] and USE_H5_MODEL:
            self._load_h5_model()
        
        if self.model_type in ["llm", "hybrid"]:
            self._load_rag_model()
        
        logger.info(f"HybridGymGenZChatbot initialized with type: {self.model_type}")
    
    def _load_llm_model(self):
        """Load LLM model (Gemma)"""
        try:
            if not HF_TOKEN:
                logger.warning("HF_TOKEN not found, skipping LLM model")
                return
            
            logger.info("Loading LLM model...")
            self.llm_tokenizer = AutoTokenizer.from_pretrained(MODEL_ID, token=HF_TOKEN)
            self.llm_model = AutoModelForCausalLM.from_pretrained(
                MODEL_ID,
                token=HF_TOKEN,
                torch_dtype=torch.bfloat16,
                device_map="auto"
            )
            logger.info("LLM model loaded successfully")
        except Exception as e:
            logger.error(f"Error loading LLM model: {e}")
            self.llm_model = None
    
    def _load_h5_model(self):
        """Load H5 model (Keras/TensorFlow)"""
        try:
            logger.info("Loading H5 model...")
            self.h5_model = H5ChatbotModel(H5_MODEL_PATH)
            logger.info("H5 model loaded successfully")
        except Exception as e:
            logger.error(f"Error loading H5 model: {e}")
            self.h5_model = None
    
    def _load_rag_model(self):
        """Load RAG model for context retrieval"""
        try:
            logger.info("Loading RAG model...")
            
            if not os.path.exists(RAG_INDEX_PATH):
                logger.warning(f"RAG index not found at: {RAG_INDEX_PATH}")
                return
            
            if not os.path.exists(RAG_CHUNKS_PATH):
                logger.warning(f"RAG chunks not found at: {RAG_CHUNKS_PATH}")
                return
            
            self.rag_index = faiss.read_index(RAG_INDEX_PATH)
            with open(RAG_CHUNKS_PATH, "rb") as f:
                self.all_chunks = pickle.load(f)
            
            self.embed_model = SentenceTransformer("sentence-transformers/all-MiniLM-L6-v2")
            logger.info(f"RAG model loaded with {len(self.all_chunks)} chunks")
        except Exception as e:
            logger.error(f"Error loading RAG model: {e}")
            self.rag_index = None
    
    def retrieve_context(self, query, top_k=3):
        """Retrieve relevant context from FAISS index"""
        if not self.rag_index or not self.embed_model:
            return ""
        
        try:
            query_vector = self.embed_model.encode([query]).astype("float32")
            distances, indices = self.rag_index.search(query_vector, top_k)
            
            contexts = []
            for idx in indices[0]:
                if idx < len(self.all_chunks):
                    contexts.append(self.all_chunks[idx])
            
            combined_context = "\n\n".join(contexts)
            logger.info(f"Retrieved {len(contexts)} contexts from RAG")
            return combined_context.strip()
        except Exception as e:
            logger.error(f"Error retrieving context: {e}")
            return ""
    
    def generate_with_llm(self, query, context=""):
        """Generate answer using LLM"""
        if not self.llm_model:
            return None
        
        try:
            if context:
                prompt = f"""Kamu adalah asisten gym AI yang bernama GYMZ. 
Jawablah pertanyaan dengan ramah dan informatif berdasarkan konteks yang diberikan.

KONTEKS:
{context}

PERTANYAAN: {query}

JAWABAN:"""
            else:
                prompt = f"""Kamu adalah asisten gym AI yang bernama GYMZ. 
Jawablah pertanyaan dengan ramah dan informatif.

PERTANYAAN: {query}

JAWABAN:"""
            
            inputs = self.llm_tokenizer(prompt, return_tensors="pt").to(self.llm_model.device)
            outputs = self.llm_model.generate(
                **inputs,
                max_new_tokens=250,
                temperature=0.7,
                do_sample=True,
                top_p=0.9
            )
            
            answer = self.llm_tokenizer.decode(outputs[0], skip_special_tokens=True)
            
            # Extract answer part
            if "JAWABAN:" in answer:
                answer = answer.split("JAWABAN:")[-1].strip()
            
            logger.info(f"LLM generated answer ({len(answer)} chars)")
            return answer
            
        except Exception as e:
            logger.error(f"Error generating with LLM: {e}")
            return None
    
    def predict_with_h5(self, query, threshold=0.7):
        """Predict answer using H5 model"""
        if not self.h5_model:
            return None, 0
        
        try:
            answer, confidence = self.h5_model.predict(query, threshold)
            if answer:
                logger.info(f"H5 model predicted answer with confidence: {confidence:.2f}")
            return answer, confidence
        except Exception as e:
            logger.error(f"Error predicting with H5: {e}")
            return None, 0
    
    def chat(self, question, strategy="auto"):
        """
        Main chat function dengan multiple strategies
        
        Args:
            question: Pertanyaan dari user
            strategy: "auto", "h5_first", "llm_first", "hybrid"
        
        Returns:
            Dictionary dengan hasil chat
        """
        logger.info(f"Processing question: {question[:100]}...")
        
        final_strategy = strategy if strategy != "auto" else self.model_type
        answer = None
        confidence = None
        used_model = None
        
        try:
            # Step 1: Cek database terlebih dahulu
            predefined_answer, sim_score = get_predefined_answer(question)
            if predefined_answer and sim_score > 0.5:
                answer = predefined_answer
                confidence = sim_score
                used_model = "database"
                logger.info(f"Using predefined answer from database (similarity: {sim_score:.2f})")
            
            # Step 2: Jika tidak ada di database, gunakan model
            if not answer:
                if final_strategy == "h5_first":
                    # Coba H5 model dulu
                    answer, confidence = self.predict_with_h5(question, threshold=0.6)
                    used_model = "h5"
                    
                    # Jika H5 tidak confident, coba LLM
                    if not answer and self.llm_model:
                        context = self.retrieve_context(question)
                        answer = self.generate_with_llm(question, context)
                        used_model = "llm"
                        confidence = 0.8  # Default confidence untuk LLM
                
                elif final_strategy == "llm_first":
                    # Coba LLM dulu
                    context = self.retrieve_context(question)
                    answer = self.generate_with_llm(question, context)
                    used_model = "llm"
                    confidence = 0.8
                    
                    # Jika LLM gagal, coba H5
                    if not answer and self.h5_model:
                        answer, confidence = self.predict_with_h5(question, threshold=0.5)
                        used_model = "h5"
                
                elif final_strategy == "hybrid":
                    # Gunakan keduanya dan pilih yang terbaik
                    h5_answer, h5_confidence = self.predict_with_h5(question, threshold=0.5)
                    llm_context = self.retrieve_context(question)
                    llm_answer = self.generate_with_llm(question, llm_context)
                    
                    # Pilih berdasarkan confidence
                    if h5_answer and h5_confidence > 0.7:
                        answer = h5_answer
                        confidence = h5_confidence
                        used_model = "h5"
                    elif llm_answer:
                        answer = llm_answer
                        confidence = 0.8
                        used_model = "llm"
                    elif h5_answer:  # Fallback ke H5 meski confidence rendah
                        answer = h5_answer
                        confidence = h5_confidence
                        used_model = "h5"
                
                else:  # auto - gunakan berdasarkan ketersediaan model
                    if self.h5_model:
                        answer, confidence = self.predict_with_h5(question, threshold=0.6)
                        used_model = "h5"
                    
                    if not answer and self.llm_model:
                        context = self.retrieve_context(question)
                        answer = self.generate_with_llm(question, context)
                        used_model = "llm"
                        confidence = 0.8
            
            # Step 3: Fallback jika semua model gagal
            if not answer:
                answer = "Maaf, saya belum bisa menjawab pertanyaan tersebut. Coba tanyakan hal lain tentang gym atau fitness."
                used_model = "fallback"
                confidence = 0.0
            
            # Step 4: Simpan log
            save_chat_log(question, answer, used_model, confidence)
            
            # Step 5: Return response
            response = {
                "success": True,
                "question": question,
                "answer": answer,
                "model_used": used_model,
                "confidence": confidence,
                "strategy": final_strategy,
                "timestamp": datetime.now().isoformat()
            }
            
            logger.info(f"Chat completed using {used_model} model (confidence: {confidence})")
            return response
            
        except Exception as e:
            logger.error(f"Error in chat function: {e}")
            return {
                "success": False,
                "error": str(e),
                "question": question,
                "answer": "Maaf, terjadi kesalahan dalam memproses permintaan Anda.",
                "model_used": "error",
                "timestamp": datetime.now().isoformat()
            }

# ==============================
# MODEL TRAINING FUNCTIONS
# ==============================
def train_h5_model_from_db():
    """Train H5 model dari data di database"""
    logger.info("Starting H5 model training from database...")
    
    try:
        # Fetch data from database
        knowledge_data = fetch_knowledge_from_db()
        
        if not knowledge_data:
            logger.warning("No knowledge data found in database")
            return {"success": False, "message": "No data to train"}
        
        # Prepare training data
        questions = []
        answers = []
        
        for item in knowledge_data:
            if item['question'] and item['answer']:
                questions.append(item['question'])
                answers.append(item['answer'])
        
        logger.info(f"Prepared {len(questions)} training samples")
        
        # TODO: Implement H5 model training logic
        # Ini placeholder - sesuaikan dengan arsitektur model Anda
        
        # Example: Simple text classification model
        from sklearn.feature_extraction.text import TfidfVectorizer
        from sklearn.preprocessing import LabelEncoder
        from sklearn.model_selection import train_test_split
        import pickle
        
        # Encode answers as labels
        label_encoder = LabelEncoder()
        encoded_answers = label_encoder.fit_transform(answers)
        
        # Create TF-IDF features
        vectorizer = TfidfVectorizer(max_features=1000)
        X = vectorizer.fit_transform(questions).toarray()
        y = encoded_answers
        
        # Split data
        X_train, X_test, y_train, y_test = train_test_split(
            X, y, test_size=0.2, random_state=42
        )
        
        # Build simple neural network
        model = keras.Sequential([
            keras.layers.Dense(128, activation='relu', input_shape=(X_train.shape[1],)),
            keras.layers.Dropout(0.3),
            keras.layers.Dense(64, activation='relu'),
            keras.layers.Dropout(0.3),
            keras.layers.Dense(len(label_encoder.classes_), activation='softmax')
        ])
        
        model.compile(
            optimizer='adam',
            loss='sparse_categorical_crossentropy',
            metrics=['accuracy']
        )
        
        # Train model
        history = model.fit(
            X_train, y_train,
            epochs=20,
            batch_size=32,
            validation_split=0.1,
            verbose=1
        )
        
        # Evaluate
        test_loss, test_acc = model.evaluate(X_test, y_test, verbose=0)
        logger.info(f"Model trained - Test accuracy: {test_acc:.4f}")
        
        # Save model
        model_dir = os.path.dirname(H5_MODEL_PATH)
        os.makedirs(model_dir, exist_ok=True)
        
        model.save(H5_MODEL_PATH)
        
        # Save tokenizer (vectorizer) dan labels
        with open(H5_MODEL_PATH.replace('.h5', '_tokenizer.pkl'), 'wb') as f:
            pickle.dump(vectorizer, f)
        
        with open(H5_MODEL_PATH.replace('.h5', '_labels.pkl'), 'wb') as f:
            pickle.dump(label_encoder.classes_, f)
        
        return {
            "success": True,
            "message": f"H5 model trained with {len(questions)} samples",
            "accuracy": test_acc,
            "model_path": H5_MODEL_PATH
        }
        
    except Exception as e:
        logger.error(f"Error training H5 model: {e}")
        return {"success": False, "error": str(e)}

def train_rag_model():
    """Train/Update RAG model from database"""
    logger.info("Starting RAG model training...")
    
    try:
        # Fetch knowledge from database
        knowledge_data = fetch_knowledge_from_db()
        
        if not knowledge_data:
            logger.warning("No knowledge data found in database")
            return {"success": False, "message": "No data to train"}
        
        # Prepare documents
        documents = []
        for item in knowledge_data:
            if item['question'] and item['answer']:
                doc = f"Pertanyaan: {item['question']}\n\nJawaban: {item['answer']}"
                documents.append(doc.strip())
        
        # Create embeddings
        logger.info(f"Creating embeddings for {len(documents)} documents...")
        embed_model = SentenceTransformer("sentence-transformers/all-MiniLM-L6-v2")
        embeddings = embed_model.encode(documents, convert_to_numpy=True)
        
        # Create FAISS index
        dimension = embeddings.shape[1]
        index = faiss.IndexFlatL2(dimension)
        index.add(embeddings)
        
        # Save to storage
        rag_dir = os.path.dirname(RAG_INDEX_PATH)
        os.makedirs(rag_dir, exist_ok=True)
        
        faiss.write_index(index, RAG_INDEX_PATH)
        
        with open(RAG_CHUNKS_PATH, "wb") as f:
            pickle.dump(documents, f)
        
        logger.info(f"RAG model trained with {len(documents)} documents")
        return {
            "success": True,
            "message": f"RAG model trained with {len(documents)} documents",
            "documents": len(documents)
        }
        
    except Exception as e:
        logger.error(f"Error training RAG model: {e}")
        return {"success": False, "error": str(e)}

# ==============================
# MAIN EXECUTION
# ==============================
if __name__ == "__main__":
    import argparse
    
    parser = argparse.ArgumentParser(description="HYBRID GYMZ Chatbot API")
    parser.add_argument("--question", type=str, help="Question to ask the chatbot")
    parser.add_argument("--train", type=str, choices=["h5", "rag", "all"], help="Train model: h5, rag, or all")
    parser.add_argument("--strategy", type=str, choices=["auto", "h5_first", "llm_first", "hybrid"], 
                       default="auto", help="Chat strategy")
    parser.add_argument("--test", action="store_true", help="Run test questions")
    
    args = parser.parse_args()
    
    if args.train:
        # Train models
        if args.train == "h5" or args.train == "all":
            result = train_h5_model_from_db()
            print(json.dumps(result, indent=2, ensure_ascii=False))
        
        if args.train == "rag" or args.train == "all":
            result = train_rag_model()
            print(json.dumps(result, indent=2, ensure_ascii=False))
    
    elif args.test:
        # Run test questions
        chatbot = HybridGymGenZChatbot()
        test_questions = [
            "Apa manfaat gym?",
            "Bagaimana cara melakukan bench press yang benar?",
            "Apa itu ZYM AI?",
            "Berapa kali idealnya gym dalam seminggu?"
        ]
        
        for q in test_questions:
            print(f"\n{'='*60}")
            print(f"QUESTION: {q}")
            result = chatbot.chat(q, strategy=args.strategy)
            print(f"ANSWER: {result['answer'][:200]}...")
            print(f"MODEL: {result['model_used']}, CONFIDENCE: {result.get('confidence', 0):.2f}")
            print(f"STATUS: {'SUCCESS' if result['success'] else 'FAILED'}")
    
    elif args.question:
        # Process single question
        chatbot = HybridGymGenZChatbot()
        result = chatbot.chat(args.question, strategy=args.strategy)
        print(json.dumps(result, indent=2, ensure_ascii=False))
    
    else:
        # Interactive mode
        print("\n" + "="*60)
        print("HYBRID GYMZ CHATBOT - Interactive Mode")
        print(f"Available strategies: auto, h5_first, llm_first, hybrid")
        print("Type 'exit' to quit, 'strategy <name>' to change strategy")
        print("="*60)
        
        chatbot = HybridGymGenZChatbot()
        current_strategy = "auto"
        
        while True:
            try:
                user_input = input("\n📝 Anda: ").strip()
                
                if user_input.lower() in ['exit', 'quit', 'keluar']:
                    print("👋 Sampai jumpa!")
                    break
                
                if user_input.lower().startswith('strategy '):
                    new_strategy = user_input.split(' ', 1)[1].lower()
                    if new_strategy in ['auto', 'h5_first', 'llm_first', 'hybrid']:
                        current_strategy = new_strategy
                        print(f"🔄 Strategy changed to: {current_strategy}")
                    else:
                        print(f"❌ Invalid strategy. Choose from: auto, h5_first, llm_first, hybrid")
                    continue
                
                if not user_input:
                    continue
                
                print("\n🤖 GYMZ: ", end="", flush=True)
                result = chatbot.chat(user_input, strategy=current_strategy)
                
                if result['success']:
                    print(result['answer'])
                    print(f"   [Model: {result['model_used']}, Confidence: {result.get('confidence', 0):.2f}]")
                else:
                    print("Maaf, terjadi kesalahan. Coba lagi nanti.")
                    
            except KeyboardInterrupt:
                print("\n\n👋 Interrupted. Goodbye!")
                break
            except Exception as e:
                print(f"\n❌ Error: {e}")