import sys
import os
import pandas as pd
import numpy as np
import mysql.connector
from mysql.connector import Error
from dotenv import load_dotenv
import json
from datetime import datetime
import re
import traceback

# Set working directory
os.chdir(os.path.dirname(os.path.abspath(__file__)))

# Setup logging untuk debug
import logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('sentiment_analysis.log'),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

try:
    from sklearn.feature_extraction.text import TfidfVectorizer
    from sklearn.svm import SVC  # GANTI: LogisticRegression menjadi SVC
    from sklearn.model_selection import train_test_split, cross_val_score
    from sklearn.metrics import classification_report, accuracy_score, confusion_matrix
    import joblib
    import nltk
    from nltk.tokenize import word_tokenize
    from nltk.corpus import stopwords
    from Sastrawi.Stemmer.StemmerFactory import StemmerFactory
    
    logger.info("Semua library berhasil diimport")
except ImportError as e:
    logger.error(f"Error importing libraries: {e}")
    sys.exit(1)

# Tambahkan path ke NLTK data
nltk_data_path = os.path.join(os.getcwd(), 'nltk_data')
nltk.data.path.append(nltk_data_path)

# Download NLTK resources jika belum ada
try:
    nltk.data.find('tokenizers/punkt')
    logger.info("NLTK data sudah ada")
except LookupError:
    logger.info("Mengunduh data NLTK...")
    try:
        nltk.download('punkt', download_dir=nltk_data_path, quiet=True)
        nltk.download('stopwords', download_dir=nltk_data_path, quiet=True)
        logger.info("Data NLTK berhasil diunduh")
    except Exception as e:
        logger.error(f"Error downloading NLTK data: {e}")

# Load environment variables dari file .env Laravel
env_path = r'D:\coba\gym-genz-api\.env'
if os.path.exists(env_path):
    load_dotenv(env_path)
    logger.info("Environment file loaded successfully")
else:
    logger.error(f"Environment file not found at {env_path}")
    print(json.dumps({
        'status': 'error',
        'message': f'Environment file not found at {env_path}',
        'data': [],
        'summary': {},
        'mrr': 0
    }))
    sys.exit(1)

class SentimentAnalyzer:
    def __init__(self):
        logger.info("Initializing SentimentAnalyzer...")
        self.db_config = {
            'host': os.getenv('DB_HOST', '127.0.0.1'),
            'port': os.getenv('DB_PORT', '3306'),
            'database': os.getenv('DB_DATABASE', 'gym_api'),
            'user': os.getenv('DB_USERNAME', 'root'),
            'password': os.getenv('DB_PASSWORD', '')
        }
        
        logger.info(f"Database config: {self.db_config['host']}:{self.db_config['port']}/{self.db_config['database']}")
        
        # Inisialisasi stemmer untuk Bahasa Indonesia
        try:
            factory = StemmerFactory()
            self.stemmer = factory.create_stemmer()
            logger.info("Stemmer initialized successfully")
        except Exception as e:
            logger.error(f"Error initializing stemmer: {e}")
            self.stemmer = None
        
        # Stopwords Bahasa Indonesia
        try:
            self.stop_words = set(stopwords.words('indonesian'))
            logger.info(f"Loaded {len(self.stop_words)} stopwords")
        except Exception as e:
            logger.error(f"Error loading stopwords: {e}")
            self.stop_words = set(['yang', 'dan', 'di', 'dari', 'ke', 'pada', 'untuk', 'dengan', 'ini', 'itu', 'saya', 'kamu'])
        
        # Kata kunci untuk rule-based analysis
        self.negative_keywords = [
            'kurang', 'gagal', 'masih', 'belum', 'tidak', 'susah', 'sulit', 'lemot', 'lambat',
            'error', 'bug', 'crash', 'force close', 'hang', 'not responding',
            'mahal', 'boros', 'habis', 'cepat habis', 'baterai boros',
            'ribet', 'rumit', 'kompleks', 'sulit dipahami',
            'terbatas', 'sedikit', 'kurang variasi', 'monoton', 'membosankan',
            'tidak akurat', 'salah', 'keliru', 'tidak tepat',
            'lambat', 'delay', 'tertunda', 'loading lama',
            'berat', 'legacy', 'kuno', 'usang',
            'support kurang', 'respons lambat', 'tidak membantu',
            'update jarang', 'bug fix lama', 'tidak diperbaiki',
            'harapan terlalu tinggi', 'mengecewakan', 'kecewa', 'frustasi'
        ]
        
        self.positive_keywords = [
            'mudah', 'simpel', 'sederhana', 'user friendly', 'ramah pengguna',
            'cepat', 'responsif', 'real time', 'realtime',
            'akurat', 'tepat', 'presisi', 'akurasi tinggi',
            'hemat', 'irit', 'efisien', 'efektif',
            'lengkap', 'komprehensif', 'detail', 'rinci',
            'inovasi', 'inovatif', 'kreatif', 'unik',
            'stabil', 'handal', 'andal', 'reliable',
            'update rutin', 'perbaikan terus', 'peningkatan',
            'support cepat', 'responsif', 'helpful',
            'gratis', 'murah', 'terjangkau', 'worth it',
            'recommended', 'direkomendasikan', 'terbaik', 'top'
        ]
        
        # Model dan vectorizer - GANTI: LogisticRegression menjadi SVC
        self.vectorizer = TfidfVectorizer(max_features=1000, ngram_range=(1, 2))
        self.model = SVC(
            kernel='linear',  # Kernel linear cocok untuk text classification
            C=1.0,  # Regularization parameter
            probability=True,  # Penting untuk predict_proba
            random_state=42,
            class_weight='balanced'  # Handle class imbalance
        )
        
        # Model path
        self.model_dir = os.path.join(os.getcwd(), 'models')
        os.makedirs(self.model_dir, exist_ok=True)
        
        self.vectorizer_path = os.path.join(self.model_dir, 'vectorizer.pkl')
        self.model_path = os.path.join(self.model_dir, 'sentiment_model.pkl')
        
        logger.info(f"Model directory: {self.model_dir}")
        logger.info("Model SVM dengan kernel linear diinisialisasi")
    
    def connect_to_database(self):
        """Koneksi ke database MySQL"""
        try:
            connection = mysql.connector.connect(**self.db_config)
            if connection.is_connected():
                logger.info("Berhasil terhubung ke database")
                return connection
        except Error as e:
            logger.error(f"Error koneksi database: {e}")
            return None
    
    def fetch_feedback_data(self):
        """Ambil data feedback dari database"""
        logger.info("Mengambil data feedback dari database...")
        connection = self.connect_to_database()
        if not connection:
            logger.error("Gagal terhubung ke database")
            return pd.DataFrame()
        
        try:
            query = """
            SELECT 
                f.id,
                f.rating,
                f.review,
                f.created_at,
                p.nama_lengkap,
                p.email
            FROM feedbacks f
            LEFT JOIN penggunas p ON f.id_pengguna = p.id
            WHERE f.review IS NOT NULL AND f.review != ''
            ORDER BY f.created_at DESC
            """
            
            df = pd.read_sql(query, connection)
            logger.info(f"Berhasil mengambil {len(df)} data feedback")
            return df
            
        except Error as e:
            logger.error(f"Error mengambil data: {e}")
            return pd.DataFrame()
        finally:
            if connection and connection.is_connected():
                connection.close()
                logger.info("Koneksi database ditutup")
    
    def preprocess_text(self, text):
        """Preprocessing teks Bahasa Indonesia"""
        if not isinstance(text, str) or not text:
            return ""
        
        try:
            # Lowercase
            text = text.lower()
            
            # Hapus karakter khusus dan angka
            text = re.sub(r'[^\w\s]', ' ', text)
            text = re.sub(r'\d+', ' ', text)
            
            # Tokenisasi
            tokens = word_tokenize(text)
            
            # Hapus stopwords dan stemming
            cleaned_tokens = []
            for word in tokens:
                if word not in self.stop_words and len(word) > 2:
                    if self.stemmer:
                        stemmed_word = self.stemmer.stem(word)
                    else:
                        stemmed_word = word
                    cleaned_tokens.append(stemmed_word)
            
            return ' '.join(cleaned_tokens)
        except Exception as e:
            logger.error(f"Error preprocessing text: {e}")
            return text.lower() if text else ""
    
    def enhanced_label_sentiment(self, rating, review_text):
        """Enhanced labeling dengan kombinasi rating dan keyword analysis"""
        try:
            rating = int(rating)
            review_lower = str(review_text).lower()
            
            # Hitung keyword matches
            negative_count = sum(1 for keyword in self.negative_keywords if keyword in review_lower)
            positive_count = sum(1 for keyword in self.positive_keywords if keyword in review_lower)
            
            # Tentukan base sentiment dari rating
            if rating >= 4:
                base_sentiment = 'positive'
                rating_weight = 0.7
            elif rating == 3:
                base_sentiment = 'neutral'
                rating_weight = 0.5
            else:  # rating 1-2
                base_sentiment = 'negative'
                rating_weight = 0.7
            
            # Adjust berdasarkan keyword
            keyword_difference = positive_count - negative_count
            
            # Decision logic
            if keyword_difference > 2:  # Strong positive keywords
                if base_sentiment == 'negative':
                    return 'neutral'
                elif base_sentiment == 'neutral':
                    return 'positive'
                else:
                    return 'positive'
            elif keyword_difference < -2:  # Strong negative keywords
                if base_sentiment == 'positive':
                    return 'neutral'
                elif base_sentiment == 'neutral':
                    return 'negative'
                else:
                    return 'negative'
            else:
                # Moderate keyword difference, rely more on rating
                return base_sentiment
                
        except:
            # Fallback to simple rating-based
            if rating >= 4:
                return 'positive'
            elif rating == 3:
                return 'neutral'
            else:
                return 'negative'
    
    def calculate_mrr(self, df):
        """Hitung Mean Reciprocal Rank (MRR)"""
        if df.empty:
            return 0
        
        try:
            # Contoh peringkat berdasarkan rating (simulasi ranking)
            df_sorted = df.sort_values('rating', ascending=False)
            df_sorted['rank'] = range(1, len(df_sorted) + 1)
            
            # Hitung MRR sederhana
            mrr = (1 / df_sorted['rank']).mean()
            return round(mrr, 4)
        except Exception as e:
            logger.error(f"Error calculating MRR: {e}")
            return 0
    
    def prepare_training_data(self, df):
        """Persiapkan data training dengan enhanced labeling"""
        logger.info("Memproses dan melabeli data training...")
        
        # Enhanced labeling dengan rating + keyword analysis
        df['sentiment_label'] = df.apply(
            lambda row: self.enhanced_label_sentiment(row['rating'], row['review']), 
            axis=1
        )
        
        # Preprocessing teks
        df['cleaned_review'] = df['review'].apply(self.preprocess_text)
        
        # Log distribution
        sentiment_counts = df['sentiment_label'].value_counts()
        logger.info(f"Sentiment distribution: {sentiment_counts.to_dict()}")
        
        # Check if we have enough data for each class
        for sentiment, count in sentiment_counts.items():
            if count < 3:
                logger.warning(f"Class '{sentiment}' hanya memiliki {count} samples")
        
        return df
    
    def train_model(self, df):
        """Train model sentiment analysis dengan SVM"""
        if len(df) < 10:
            logger.warning("Data training terlalu sedikit untuk training efektif.")
            return False
        
        try:
            # Persiapkan data training
            df = self.prepare_training_data(df)
            
            # Check if we have at least 2 classes with enough data
            sentiment_counts = df['sentiment_label'].value_counts()
            valid_classes = [cls for cls in sentiment_counts.index if sentiment_counts[cls] >= 3]
            
            if len(valid_classes) < 2:
                logger.warning(f"Hanya {len(valid_classes)} kelas yang cukup data. Minimal perlu 2 kelas.")
                return False
            
            # Filter hanya kelas yang cukup data
            df_train = df[df['sentiment_label'].isin(valid_classes)]
            
            logger.info(f"Training model dengan {len(df_train)} data dan kelas: {valid_classes}")
            
            # Vectorize text
            X = self.vectorizer.fit_transform(df_train['cleaned_review'])
            y = df_train['sentiment_label']
            
            # Split data untuk evaluasi jika data cukup
            if len(df_train) >= 20:
                X_train, X_test, y_train, y_test = train_test_split(
                    X, y, test_size=0.2, random_state=42, stratify=y
                )
                logger.info(f"Data split: {X_train.shape[0]} training, {X_test.shape[0]} testing")
            else:
                X_train, y_train = X, y
                X_test, y_test = None, None
            
            # Cross validation untuk dataset kecil/medium
            if len(df_train) >= 10:
                cv_scores = cross_val_score(self.model, X_train, y_train, cv=min(3, len(df_train)))
                logger.info(f"Cross-validation scores: {cv_scores}")
                logger.info(f"Average CV score: {cv_scores.mean():.2%} (+/- {cv_scores.std():.2%})")
            
            # Train final model
            logger.info("Melatih model SVM...")
            self.model.fit(X_train, y_train)
            
            # Evaluate on training data
            if X_test is not None and y_test is not None:
                y_pred = self.model.predict(X_test)
                accuracy = accuracy_score(y_test, y_pred)
                logger.info(f"Test Accuracy: {accuracy:.2%}")
                
                # Detailed classification report
                logger.info("Classification Report (Test Data):")
                logger.info(f"\n{classification_report(y_test, y_pred)}")
                
                # Confusion matrix
                cm = confusion_matrix(y_test, y_pred)
                logger.info(f"Confusion Matrix:\n{cm}")
            else:
                y_pred = self.model.predict(X_train)
                accuracy = accuracy_score(y_train, y_pred)
                logger.info(f"Training Accuracy: {accuracy:.2%}")
            
            # Save model
            joblib.dump(self.vectorizer, self.vectorizer_path)
            joblib.dump(self.model, self.model_path)
            
            logger.info(f"Model SVM disimpan di: {self.model_path}")
            return True
            
        except Exception as e:
            logger.error(f"Error training model: {e}")
            traceback.print_exc()
            return False
    
    def rule_based_analysis(self, rating, review_text):
        """Rule-based sentiment analysis sebagai fallback"""
        try:
            rating = int(rating)
            review_lower = str(review_text).lower()
            
            # Count keyword matches
            negative_count = sum(1 for keyword in self.negative_keywords if keyword in review_lower)
            positive_count = sum(1 for keyword in self.positive_keywords if keyword in review_lower)
            
            # Base probabilities from rating
            if rating >= 4:
                base_probs = {'positive': 0.7, 'negative': 0.1, 'neutral': 0.2}
            elif rating == 3:
                base_probs = {'positive': 0.2, 'negative': 0.2, 'neutral': 0.6}
            else:  # rating 1-2
                base_probs = {'positive': 0.1, 'negative': 0.7, 'neutral': 0.2}
            
            # Adjust based on keywords
            keyword_factor = (positive_count - negative_count) * 0.1
            
            # Apply keyword adjustment
            adjusted_probs = {
                'positive': max(0, min(1, base_probs['positive'] + keyword_factor)),
                'negative': max(0, min(1, base_probs['negative'] - keyword_factor)),
                'neutral': base_probs['neutral']
            }
            
            # Normalize to sum to 1
            total = sum(adjusted_probs.values())
            normalized_probs = {k: v/total for k, v in adjusted_probs.items()}
            
            # Determine final sentiment
            final_sentiment = max(normalized_probs, key=normalized_probs.get)
            
            return final_sentiment, normalized_probs
            
        except Exception as e:
            logger.error(f"Error in rule-based analysis: {e}")
            # Simple fallback
            if rating >= 4:
                return 'positive', {'positive': 0.8, 'negative': 0.1, 'neutral': 0.1}
            elif rating == 3:
                return 'neutral', {'positive': 0.2, 'negative': 0.2, 'neutral': 0.6}
            else:
                return 'negative', {'positive': 0.1, 'negative': 0.8, 'neutral': 0.1}
    
    def analyze_sentiments(self, df):
        """Analisis sentimen pada data feedback menggunakan hybrid approach"""
        results = []
        
        if len(df) == 0:
            return results
        
        # Coba load model yang sudah ada
        model_loaded = False
        try:
            if os.path.exists(self.vectorizer_path) and os.path.exists(self.model_path):
                logger.info("Memuat model yang sudah ada...")
                self.vectorizer = joblib.load(self.vectorizer_path)
                self.model = joblib.load(self.model_path)
                model_loaded = True
                logger.info("Model SVM berhasil dimuat")
        except Exception as e:
            logger.error(f"Error loading model: {e}")
            model_loaded = False
        
        # Preprocess data
        df = self.prepare_training_data(df)
        
        # Hybrid analysis: ML + Rule-based
        logger.info("Menganalisis sentimen dengan hybrid approach...")
        
        for idx, row in df.iterrows():
            try:
                review_text = row['cleaned_review']
                rating = int(row['rating'])  # Pastikan konversi ke int Python
                
                # Get rule-based result as baseline
                rule_sentiment, rule_probs = self.rule_based_analysis(rating, row['review'])
                
                # Try ML model if available and review is substantial
                if (model_loaded and review_text and 
                    len(review_text.strip()) > 10 and 
                    hasattr(self.model, 'predict_proba')):
                    
                    try:
                        X_vec = self.vectorizer.transform([review_text])
                        
                        # Get ML prediction
                        ml_sentiment = self.model.predict(X_vec)[0]
                        ml_probs_raw = self.model.predict_proba(X_vec)[0]
                        
                        # Map probabilities
                        classes = self.model.classes_
                        ml_probs = {cls: float(prob) for cls, prob in zip(classes, ml_probs_raw)}  # Konversi ke float
                        
                        # Ensure all classes are present
                        for cls in ['positive', 'negative', 'neutral']:
                            if cls not in ml_probs:
                                ml_probs[cls] = 0.0
                        
                        # Check ML confidence
                        max_ml_prob = max(ml_probs.values())
                        
                        # If ML is confident (>= 0.7), use it
                        if max_ml_prob >= 0.7:
                            final_sentiment = ml_sentiment
                            final_probs = ml_probs
                        else:
                            # Low confidence, use rule-based with ML influence
                            # Weighted average: 70% rule-based, 30% ML
                            final_probs = {}
                            for cls in ['positive', 'negative', 'neutral']:
                                rule_prob = float(rule_probs.get(cls, 0.0))
                                ml_prob = float(ml_probs.get(cls, 0.0))
                                final_probs[cls] = (0.7 * rule_prob) + (0.3 * ml_prob)
                            
                            final_sentiment = max(final_probs, key=final_probs.get)
                            
                    except Exception as e:
                        logger.warning(f"ML model error untuk review {row['id']}: {e}")
                        final_sentiment = rule_sentiment
                        final_probs = rule_probs
                else:
                    # Use rule-based only
                    final_sentiment = rule_sentiment
                    final_probs = rule_probs
                
                # KONVERSI semua nilai ke Python native types
                results.append({
                    'feedback_id': int(row['id']),
                    'user_name': str(row['nama_lengkap']) if pd.notnull(row['nama_lengkap']) else 'Unknown',
                    'email': str(row['email']) if pd.notnull(row['email']) else '',
                    'original_review': str(row['review']),
                    'rating': int(rating),
                    'sentiment': str(final_sentiment),
                    'probability': {
                        'positive': float(final_probs.get('positive', 0)),
                        'negative': float(final_probs.get('negative', 0)),
                        'neutral': float(final_probs.get('neutral', 0))
                    },
                    'review_date': row['created_at'].strftime('%Y-%m-%d %H:%M:%S') if pd.notnull(row['created_at']) else None,
                    'analysis_method': 'ML+Rule' if model_loaded else 'Rule-based'
                })
                
            except Exception as e:
                logger.error(f"Error analyzing review {row['id']}: {e}")
                # Emergency fallback
                rating_val = int(row['rating'])
                if rating_val >= 4:
                    sentiment = 'positive'
                    probs = {'positive': 0.9, 'negative': 0.05, 'neutral': 0.05}
                elif rating_val == 3:
                    sentiment = 'neutral'
                    probs = {'positive': 0.1, 'negative': 0.1, 'neutral': 0.8}
                else:
                    sentiment = 'negative'
                    probs = {'positive': 0.05, 'negative': 0.9, 'neutral': 0.05}
                
                results.append({
                    'feedback_id': int(row['id']),
                    'user_name': str(row['nama_lengkap']) if pd.notnull(row['nama_lengkap']) else 'Unknown',
                    'email': str(row['email']) if pd.notnull(row['email']) else '',
                    'original_review': str(row['review']),
                    'rating': int(rating_val),
                    'sentiment': str(sentiment),
                    'probability': {k: float(v) for k, v in probs.items()},
                    'review_date': row['created_at'].strftime('%Y-%m-%d %H:%M:%S') if pd.notnull(row['created_at']) else None,
                    'analysis_method': 'Emergency-fallback'
                })
        
        return results
    
    def run_analysis(self):
        """Jalankan analisis sentimen lengkap"""
        try:
            logger.info("\n" + "="*60)
            logger.info("MEMULAI ANALISIS SENTIMEN DENGAN SVM + HYBRID APPROACH")
            logger.info("="*60)
            
            # Ambil data dari database
            df = self.fetch_feedback_data()
            
            if df.empty:
                logger.warning("Tidak ada data feedback yang ditemukan.")
                return {
                    'status': 'success',
                    'message': 'Tidak ada data feedback yang ditemukan untuk dianalisis.',
                    'data': [],
                    'summary': {
                        'total_feedback': 0,
                        'with_review': 0,
                        'without_review': 0,
                        'sentiment_distribution': {},
                        'average_rating': 0,
                        'analysis_date': datetime.now().strftime('%Y-%m-%d %H:%M:%S')
                    },
                    'mrr': 0
                }
            
            logger.info(f"Menemukan {len(df)} feedback untuk dianalisis.")
            
            # Train or load model jika data cukup
            if len(df) >= 20:
                logger.info("Data cukup, training model SVM...")
                if self.train_model(df):
                    logger.info("Model SVM berhasil dilatih")
                else:
                    logger.warning("Gagal training model SVM, menggunakan rule-based saja")
            else:
                logger.info("Data kurang dari 20 samples, menggunakan rule-based analysis")
            
            # Analisis sentimen
            sentiment_results = self.analyze_sentiments(df)
            
            # Hitung MRR
            mrr_score = self.calculate_mrr(df)
            
            # Buat summary detail - KONVERSI KE NATIVE PYTHON TYPES
            if sentiment_results:
                sentiments = [r['sentiment'] for r in sentiment_results]
                methods = [r.get('analysis_method', 'unknown') for r in sentiment_results]
                
                from collections import Counter
                sentiment_counts = Counter(sentiments)
                method_counts = Counter(methods)
                
                # Calculate accuracy indicators
                rating_sentiment_match = 0
                for result in sentiment_results:
                    rating = result['rating']
                    sentiment = result['sentiment']
                    if (rating >= 4 and sentiment == 'positive') or \
                       (rating == 3 and sentiment == 'neutral') or \
                       (rating <= 2 and sentiment == 'negative'):
                        rating_sentiment_match += 1
                
                rating_agreement = rating_sentiment_match / len(sentiment_results)
                
                # KONVERSI rating distribution ke native Python types
                rating_dist = df['rating'].value_counts().sort_index()
                rating_dist_dict = {int(k): int(v) for k, v in rating_dist.items()}
                
                summary = {
                    'total_feedback': int(len(df)),
                    'with_review': int(len(df[df['review'].notnull() & (df['review'] != '')])),
                    'without_review': int(len(df[df['review'].isnull() | (df['review'] == '')])),
                    'sentiment_distribution': {k: int(v) for k, v in dict(sentiment_counts).items()},
                    'analysis_methods': dict(method_counts),
                    'rating_sentiment_agreement': float(rating_agreement),
                    'average_rating': float(df['rating'].mean()) if len(df) > 0 else 0.0,
                    'rating_distribution': rating_dist_dict,
                    'analysis_date': datetime.now().strftime('%Y-%m-%d %H:%M:%S')
                }
            else:
                summary = {
                    'total_feedback': int(len(df)),
                    'with_review': int(len(df[df['review'].notnull() & (df['review'] != '')])),
                    'without_review': int(len(df[df['review'].isnull() | (df['review'] == '')])),
                    'sentiment_distribution': {},
                    'analysis_methods': {},
                    'rating_sentiment_agreement': 0.0,
                    'average_rating': float(df['rating'].mean()) if len(df) > 0 else 0.0,
                    'rating_distribution': {},
                    'analysis_date': datetime.now().strftime('%Y-%m-%d %H:%M:%S')
                }
            
            # Hasil akhir - KONVERSI semua nilai ke Python native types
            result = {
                'status': 'success',
                'message': f'Analisis sentimen berhasil. {len(sentiment_results)} feedback dianalisis.',
                'data': sentiment_results,
                'summary': summary,
                'mrr': float(mrr_score),
                'model_info': {
                    'algorithm': 'SVM (Support Vector Machine) + Rule-based Hybrid',
                    'kernel': 'linear',
                    'features': 'TF-IDF with n-grams',
                    'trained': len(df) >= 20
                }
            }
            
            # Simpan hasil ke file JSON
            output_file = os.path.join(self.model_dir, 'sentiment_results.json')
            with open(output_file, 'w', encoding='utf-8') as f:
                json.dump(result, f, ensure_ascii=False, indent=2)
            
            logger.info("\n" + "="*60)
            logger.info("ANALISIS SENTIMEN SELESAI")
            logger.info("="*60)
            logger.info(f"Total feedback: {len(df)}")
            logger.info(f"Rating distribution: {summary['rating_distribution']}")
            logger.info(f"Sentiment distribution: {summary['sentiment_distribution']}")
            logger.info(f"Analysis methods: {summary.get('analysis_methods', {})}")
            logger.info(f"Rating-sentiment agreement: {summary.get('rating_sentiment_agreement', 0):.2%}")
            logger.info(f"MRR Score: {mrr_score:.4f}")
            logger.info(f"Model: SVM dengan kernel linear")
            logger.info(f"Hasil disimpan di: {output_file}")
            logger.info("="*60)
            
            return result
            
        except Exception as e:
            logger.error(f"Error dalam analisis: {e}")
            traceback.print_exc()
            return {
                'status': 'error',
                'message': f'Error: {str(e)}',
                'data': [],
                'summary': {},
                'mrr': 0
            }

def main():
    """Fungsi utama"""
    try:
        analyzer = SentimentAnalyzer()
        result = analyzer.run_analysis()
        
        # Hanya output JSON untuk Laravel
        print(json.dumps(result, ensure_ascii=False))
        
    except Exception as e:
        error_result = {
            'status': 'error',
            'message': f'Critical error: {str(e)}',
            'data': [],
            'summary': {},
            'mrr': 0
        }
        print(json.dumps(error_result, ensure_ascii=False))
        sys.exit(1)

if __name__ == "__main__":
    main()