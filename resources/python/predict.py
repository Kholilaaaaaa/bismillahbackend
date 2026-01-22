import sys
import json
import numpy as np
import os
import traceback
import pickle
from pathlib import Path
from collections import deque
import time
import random

# ============================================
# IMPORTS DENGAN ERROR HANDLING
# ============================================
try:
    import tensorflow as tf
    print(f"[INFO] TensorFlow version: {tf.__version__}", file=sys.stderr)
    
    # Coba import dari tf.keras
    from tensorflow.keras.models import load_model
        
except ImportError as e:
    print(f"[ERROR] TensorFlow import failed: {e}", file=sys.stderr)
    print("[INFO] Using mock mode", file=sys.stderr)
    tf = None

# ============================================
# REP COUNTING LOGIC UNTUK SEMUA EXERCISE
# ============================================
class RepCounter:
    def __init__(self, exercise_type='pushup'):
        self.exercise_type = exercise_type
        self.rep_count = 0
        self.is_in_rep = False
        self.last_state = None
        self.rep_history = []
        self.start_time = None
        
        # Thresholds untuk deteksi rep SEMUA EXERCISE
        self.rep_thresholds = {
            'pushup': {
                'down_threshold': 0.3,    # Confidence rendah = posisi bawah pushup
                'up_threshold': 0.7,      # Confidence tinggi = posisi atas pushup
                'min_rep_duration': 0.8,  # minimum detik per rep pushup
                'max_rep_duration': 5.0,  # maksimum detik per rep pushup
                'states': ['down', 'up']   # urutan state pushup
            },
            'shoulder_press': {
                'down_threshold': 0.4,    # Confidence rendah = posisi bawah shoulder press
                'up_threshold': 0.8,      # Confidence tinggi = posisi atas shoulder press  
                'min_rep_duration': 0.5,  # minimum detik per rep shoulder press
                'max_rep_duration': 3.0,  # maksimum detik per rep shoulder press
                'states': ['down', 'up']   # urutan state shoulder press
            },
            't_bar_row': {
                'down_threshold': 0.35,   # Confidence rendah = posisi bawah t-bar row
                'up_threshold': 0.75,     # Confidence tinggi = posisi atas t-bar row
                'min_rep_duration': 0.6,  # minimum detik per rep t-bar row
                'max_rep_duration': 4.0,  # maksimum detik per rep t-bar row
                'states': ['extended', 'contracted']  # urutan state t-bar row
            }
        }
    
    def update(self, confidence, timestamp=None):
        """
        Update rep counter berdasarkan confidence score untuk SEMUA EXERCISE
        Returns: (rep_detected, rep_completed)
        """
        if timestamp is None:
            timestamp = time.time()
            
        thresholds = self.rep_thresholds.get(self.exercise_type, self.rep_thresholds['pushup'])
        states = thresholds['states']
        
        # State machine untuk deteksi rep
        if not self.is_in_rep and confidence > thresholds['up_threshold']:
            # Mulai rep baru (posisi atas/contracted)
            self.is_in_rep = True
            self.start_time = timestamp
            self.last_state = states[1]  # 'up' atau 'contracted'
            return False, False
            
        elif self.is_in_rep and confidence < thresholds['down_threshold']:
            # Pindah ke posisi bawah/extended
            self.last_state = states[0]  # 'down' atau 'extended'
            return True, False
            
        elif self.is_in_rep and self.last_state == states[0] and confidence > thresholds['up_threshold']:
            # Kembali ke posisi atas/contracted - rep selesai
            duration = timestamp - self.start_time
            if thresholds['min_rep_duration'] <= duration <= thresholds['max_rep_duration']:
                self.rep_count += 1
                self.rep_history.append({
                    'rep_number': self.rep_count,
                    'exercise': self.exercise_type,
                    'duration': round(duration, 2),
                    'end_time': timestamp,
                    'avg_confidence': confidence
                })
                self.is_in_rep = False
                self.last_state = states[1]  # 'up' atau 'contracted'
                return True, True
            
        return False, False
    
    def reset(self):
        """Reset counter untuk exercise ini"""
        self.rep_count = 0
        self.is_in_rep = False
        self.last_state = None
        self.rep_history = []
        self.start_time = None

# ============================================
# REAL-TIME FRAME PROCESSOR UNTUK SEMUA EXERCISE
# ============================================
class RealTimeProcessor:
    def __init__(self, sequence_length=20):
        self.sequence_length = sequence_length
        self.frame_buffers = {
            'pushup': deque(maxlen=sequence_length),
            'shoulder_press': deque(maxlen=sequence_length),
            't_bar_row': deque(maxlen=sequence_length)
        }
        self.last_predictions = {}
        self.prediction_history = deque(maxlen=100)
        self.rep_counters = {}
        
    def add_frame(self, frame_data, exercise_type='pushup'):
        """Tambah frame ke buffer untuk exercise tertentu"""
        if exercise_type in self.frame_buffers:
            self.frame_buffers[exercise_type].append(frame_data)
        
    def get_sequence(self, exercise_type='pushup'):
        """Dapatkan sequence untuk prediction exercise tertentu"""
        if exercise_type not in self.frame_buffers:
            return None
            
        buffer = self.frame_buffers[exercise_type]
        
        if len(buffer) < self.sequence_length:
            # Jika buffer belum penuh, ulangi frame terakhir
            if len(buffer) > 0:
                last_frame = buffer[-1]
                while len(buffer) < self.sequence_length:
                    buffer.append(last_frame)
            else:
                return None
        
        return list(buffer)
    
    def get_rep_counter(self, exercise_type):
        """Dapatkan atau buat rep counter untuk exercise tertentu"""
        if exercise_type not in self.rep_counters:
            self.rep_counters[exercise_type] = RepCounter(exercise_type)
        return self.rep_counters[exercise_type]
    
    def reset_exercise(self, exercise_type):
        """Reset buffer dan counter untuk exercise tertentu"""
        if exercise_type in self.frame_buffers:
            self.frame_buffers[exercise_type].clear()
        if exercise_type in self.rep_counters:
            self.rep_counters[exercise_type].reset()
    
    def get_exercise_stats(self):
        """Dapatkan statistik untuk semua exercise"""
        stats = {}
        for exercise, counter in self.rep_counters.items():
            stats[exercise] = {
                'rep_count': counter.rep_count,
                'is_in_rep': counter.is_in_rep,
                'last_state': counter.last_state,
                'rep_history': counter.rep_history[-5:]  # 5 rep terakhir
            }
        return stats

# ============================================
# MAIN PREDICTOR CLASS UNTUK SEMUA EXERCISE
# ============================================
class MultiModelExercisePredictor:
    def __init__(self, models_dir=None):
        """
        Inisialisasi predictor dengan multiple models untuk SEMUA EXERCISE
        """
        # Determine models directory
        if models_dir is None:
            current_dir = os.path.dirname(os.path.abspath(__file__))
            parent_dir = os.path.dirname(current_dir)
            self.models_dir = os.path.join(parent_dir, 'ml_models')
        else:
            self.models_dir = models_dir
            
        print(f"[INFO] Models directory: {self.models_dir}", file=sys.stderr)
        
        # Cek apakah folder model ada
        if not os.path.exists(self.models_dir):
            print(f"[ERROR] Models directory not found: {self.models_dir}", file=sys.stderr)
            alt_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', 'ml_models')
            self.models_dir = os.path.abspath(alt_path)
            print(f"[INFO] Trying alternative path: {self.models_dir}", file=sys.stderr)
        
        self.models = {}
        self.scalers = {}
        self.metadata = {}
        self.thresholds = {}
        
        # Konfigurasi model UNTUK SEMUA EXERCISE
        self.model_configs = {
            'pushup': {
                'folder': 'push-up',
                'model_file': 'model_pushup.keras',
                'scaler_file': 'scaler.pkl',
                'meta': 'meta.json',
                'display_name': 'Push Up',
                'muscle_groups': ['Chest', 'Triceps', 'Shoulders']
            },
            'shoulder_press': {
                'folder': 'Shoulder Press',
                'model_file': 'model_Shoulder_Press.keras',
                'scaler_file': 'scaler.pkl',
                'meta': 'meta.json',
                'display_name': 'Shoulder Press',
                'muscle_groups': ['Shoulders', 'Triceps']
            },
            't_bar_row': {
                'folder': 't bar row',
                'model_file': 'model_t_bar_row.keras',
                'scaler_file': 'scaler.pkl',
                'meta': 'meta.json',
                'display_name': 'T Bar Row',
                'muscle_groups': ['Back', 'Biceps', 'Rear Delts']
            }
        }
        
        # Label untuk setiap model
        self.model_labels = {
            'pushup': ['incorrect', 'correct'],
            'shoulder_press': ['incorrect', 'correct'],
            't_bar_row': ['incorrect', 'correct']
        }
        
        # Nama latihan yang user-friendly
        self.exercise_names = {
            'pushup': 'Push Up',
            'shoulder_press': 'Shoulder Press',
            't_bar_row': 'T Bar Row'
        }
        
        # Form feedback untuk SEMUA EXERCISE
        self.form_feedback = {
            'pushup': {
                'correct': [
                    "Push Up form bagus!",
                    "Jaga punggung tetap lurus",
                    "Gerakan terkontrol naik turun",
                    "Siku tidak terlalu terbuka",
                    "Dada turun mendekati lantai"
                ],
                'incorrect': [
                    "Punggung terlalu melengkung",
                    "Siku terlalu terbuka lebar",
                    "Gerakan terlalu cepat",
                    "Tidak turun cukup dalam",
                    "Pinggang turun lebih dulu"
                ]
            },
            'shoulder_press': {
                'correct': [
                    "Shoulder Press form sempurna!",
                    "Siku stabil di depan tubuh",
                    "Kontrol bagus naik turun",
                    "Core tetap kencang",
                    "Punggung lurus menempel bangku"
                ],
                'incorrect': [
                    "Menggunakan momentum tubuh",
                    "Siku terlalu terbuka",
                    "Punggung melengkung",
                    "Tidak lockout di atas",
                    "Turun terlalu cepat"
                ]
            },
            't_bar_row': {
                'correct': [
                    "T Bar Row form excellent!",
                    "Back engagement maksimal",
                    "Punggung lurus sejajar lantai",
                    "Tarik beban ke arah dada",
                    "Kontrol gerakan dengan baik"
                ],
                'incorrect': [
                    "Mengangkat dengan punggung bawah",
                    "Punggung melengkung",
                    "Tarik terlalu tinggi",
                    "Menggunakan momentum",
                    "Gerakan tidak terkontrol"
                ]
            }
        }
        
        # Exercise-specific parameters
        self.exercise_params = {
            'pushup': {
                'optimal_rep_duration': 2.0,  # 2 detik per rep
                'rest_between_sets': 60,      # 60 detik
                'target_reps': 12             # target 12 reps
            },
            'shoulder_press': {
                'optimal_rep_duration': 1.5,
                'rest_between_sets': 90,
                'target_reps': 10
            },
            't_bar_row': {
                'optimal_rep_duration': 2.0,
                'rest_between_sets': 75,
                'target_reps': 10
            }
        }
        
        self.model_params = {}
        self.load_all_models()
        self.load_scalers_and_metadata()
        
        # Inisialisasi real-time processor untuk semua exercise
        self.realtime_processor = RealTimeProcessor(sequence_length=20)
    
    def get_full_path(self, folder_name, file_name):
        """Mendapatkan path lengkap ke file"""
        folder_path = os.path.join(self.models_dir, folder_name)
        return os.path.join(folder_path, file_name)
    
    def load_all_models(self):
        """Load semua model ML untuk SEMUA EXERCISE"""
        if tf is None:
            print("[WARNING] TensorFlow not available, running in mock mode", file=sys.stderr)
            return
            
        loaded_models = 0
        for model_key, config in self.model_configs.items():
            folder_name = config['folder']
            folder_path = os.path.join(self.models_dir, folder_name)
            
            if not os.path.exists(folder_path):
                print(f"[WARNING] Folder not found: {folder_path}", file=sys.stderr)
                # Coba cari dengan nama alternatif
                alt_folders = [
                    folder_name,
                    folder_name.lower(),
                    folder_name.replace(' ', '_'),
                    folder_name.replace(' ', '')
                ]
                for alt_folder in alt_folders:
                    alt_path = os.path.join(self.models_dir, alt_folder)
                    if os.path.exists(alt_path):
                        folder_path = alt_path
                        folder_name = alt_folder
                        break
                else:
                    print(f"[ERROR] Cannot find folder for {model_key}", file=sys.stderr)
                    continue
            
            # Cari file model
            model_file = config['model_file']
            model_path = self.get_full_path(folder_name, model_file)
            
            if not os.path.exists(model_path):
                print(f"[WARNING] Model file not found: {model_path}", file=sys.stderr)
                # Coba cari file model alternatif
                alt_files = [
                    model_file,
                    model_file.replace('.keras', '.h5'),
                    f'model_{model_key}.keras',
                    f'model_{model_key}.h5',
                    'model.keras',
                    'model.h5'
                ]
                for alt_file in alt_files:
                    alt_path = self.get_full_path(folder_name, alt_file)
                    if os.path.exists(alt_path):
                        model_path = alt_path
                        model_file = alt_file
                        break
                else:
                    print(f"[ERROR] Cannot find model file for {model_key}", file=sys.stderr)
                    continue
            
            try:
                print(f"[INFO] Loading model: {model_key} from {model_path}", file=sys.stderr)
                self.models[model_key] = load_model(model_path)
                print(f"[SUCCESS] Model {model_key} loaded", file=sys.stderr)
                loaded_models += 1
            except Exception as e:
                print(f"[ERROR] Failed to load {model_key}: {str(e)}", file=sys.stderr)
                
        print(f"[INFO] Total models loaded: {loaded_models}/{len(self.model_configs)}", file=sys.stderr)
    
    def load_scalers_and_metadata(self):
        """Load scalers dan metadata untuk setiap model"""
        for model_key, config in self.model_configs.items():
            folder_name = config['folder']
            folder_path = os.path.join(self.models_dir, folder_name)
            
            if not os.path.exists(folder_path):
                print(f"[WARNING] Folder not found for metadata: {folder_path}", file=sys.stderr)
                continue
            
            # Initialize model parameters
            self.model_params[model_key] = {
                'sequence_length': 20,
                'features_per_frame': 103,
                'threshold': 0.5
            }
            
            # Load metadata jika ada
            if 'meta' in config:
                meta_path = self.get_full_path(folder_name, config['meta'])
                if os.path.exists(meta_path):
                    try:
                        with open(meta_path, 'r') as f:
                            self.metadata[model_key] = json.load(f)
                        
                        # Extract parameters dari metadata
                        meta = self.metadata[model_key]
                        
                        if 'timesteps' in meta:
                            self.model_params[model_key]['sequence_length'] = int(meta['timesteps'])
                        elif 'sequence_length' in meta:
                            self.model_params[model_key]['sequence_length'] = int(meta['sequence_length'])
                        
                        if 'features' in meta:
                            self.model_params[model_key]['features_per_frame'] = int(meta['features'])
                        
                        if 'threshold' in meta:
                            self.thresholds[model_key] = float(meta['threshold'])
                            self.model_params[model_key]['threshold'] = float(meta['threshold'])
                            
                    except Exception as e:
                        print(f"[WARNING] Failed to load metadata for {model_key}: {e}", file=sys.stderr)
            
            # Load scaler jika ada
            if 'scaler_file' in config:
                scaler_path = self.get_full_path(folder_name, config['scaler_file'])
                if os.path.exists(scaler_path):
                    try:
                        with open(scaler_path, 'rb') as f:
                            scaler = pickle.load(f)
                            self.scalers[model_key] = scaler
                    except Exception as e:
                        print(f"[WARNING] Failed to load scaler for {model_key}: {e}", file=sys.stderr)
    
    def preprocess_data(self, data, model_key='pushup'):
        """
        Preprocess data dari input JSON
        """
        try:
            # Konversi ke numpy array
            sequences = np.array(data, dtype=np.float32)
            
            if sequences.size == 0:
                return np.array([[]], dtype=np.float32)
            
            # Cek dimensi input
            if sequences.ndim == 1:
                sequences = sequences.reshape(-1, 1)
            
            # Dapatkan parameter untuk model ini
            if model_key not in self.model_params:
                return np.array([[]], dtype=np.float32)
                
            model_param = self.model_params[model_key]
            expected_features = model_param['features_per_frame']
            
            # Validasi dan adjust feature count
            if sequences.ndim == 2:
                actual_features = sequences.shape[1]
            elif sequences.ndim == 3:
                actual_features = sequences.shape[2]
            else:
                raise ValueError(f"Invalid input shape: {sequences.shape}")
            
            if actual_features != expected_features:
                if actual_features < expected_features:
                    # Padding
                    padding_needed = expected_features - actual_features
                    if sequences.ndim == 2:
                        padding = np.zeros((sequences.shape[0], padding_needed), dtype=np.float32)
                        sequences = np.hstack([sequences, padding])
                    elif sequences.ndim == 3:
                        padding = np.zeros((sequences.shape[0], sequences.shape[1], padding_needed), dtype=np.float32)
                        sequences = np.concatenate([sequences, padding], axis=2)
                elif actual_features > expected_features:
                    # Truncate
                    if sequences.ndim == 2:
                        sequences = sequences[:, :expected_features]
                    elif sequences.ndim == 3:
                        sequences = sequences[:, :, :expected_features]
            
            # Normalisasi dengan scaler jika ada
            if model_key in self.scalers:
                try:
                    scaler = self.scalers[model_key]
                    
                    if sequences.ndim == 2:
                        sequences = scaler.transform(sequences)
                    elif sequences.ndim == 3:
                        original_shape = sequences.shape
                        sequences_flat = sequences.reshape(-1, sequences.shape[-1])
                        sequences_normalized = scaler.transform(sequences_flat)
                        sequences = sequences_normalized.reshape(original_shape)
                            
                except Exception as e:
                    print(f"[WARNING] Failed to apply scaler: {e}", file=sys.stderr)
            
            # Reshape untuk model LSTM
            if sequences.ndim == 2:
                sequences = np.expand_dims(sequences, axis=0)
            
            # Pastikan sequence length sesuai
            sequence_length = model_param['sequence_length']
            current_timesteps = sequences.shape[1]
            if current_timesteps != sequence_length:
                if current_timesteps < sequence_length:
                    pad_width = ((0, 0), (0, sequence_length - current_timesteps), (0, 0))
                    sequences = np.pad(sequences, pad_width, mode='constant', constant_values=0)
                else:
                    sequences = sequences[:, :sequence_length, :]
            
            return sequences
                
        except Exception as e:
            print(f"[ERROR] Preprocessing failed: {e}", file=sys.stderr)
            return np.array([[]], dtype=np.float32)
    
    def predict_single_model(self, input_data, model_name):
        """
        Predict menggunakan model tertentu
        """
        if tf is None or model_name not in self.models:
            return self.mock_prediction(model_name)
        
        try:
            # Preprocess data
            processed_data = self.preprocess_data(input_data, model_name)
            
            if processed_data.size == 0:
                return {
                    'success': False,
                    'error': 'Preprocessing failed',
                    'model': model_name
                }
            
            # Predict dengan model
            model = self.models[model_name]
            predictions = model.predict(processed_data, verbose=0)
            
            # Gunakan threshold
            threshold = self.thresholds.get(model_name, 0.5)
            
            # Binary classification
            if predictions.shape[1] == 1:
                confidence_scores = predictions.flatten()
                predicted_classes = (confidence_scores >= threshold).astype(int).flatten()
                
                labels = self.model_labels.get(model_name, ['incorrect', 'correct'])
                predicted_labels = [
                    labels[pred] if pred < len(labels) else 'unknown'
                    for pred in predicted_classes
                ]
            else:
                predicted_indices = np.argmax(predictions, axis=1)
                confidence_scores = np.max(predictions, axis=1)
                
                labels = self.model_labels.get(model_name, ['incorrect', 'correct'])
                predicted_labels = [
                    labels[idx] if idx < len(labels) else 'unknown' 
                    for idx in predicted_indices
                ]
            
            # Hitung statistik
            total_count = len(predicted_labels)
            correct_count = sum(1 for label in predicted_labels if label == 'correct')
            correctness_percentage = (correct_count / total_count * 100) if total_count > 0 else 0
            
            # Dapatkan feedback
            feedback = self.get_form_feedback(model_name, predicted_labels, confidence_scores)
            
            result = {
                'success': True,
                'model': model_name,
                'exercise_name': self.exercise_names.get(model_name, model_name),
                'predictions': predicted_labels,
                'confidence': confidence_scores.tolist(),
                'confidence_score': float(np.mean(confidence_scores)) if len(confidence_scores) > 0 else 0.0,
                'correct_count': correct_count,
                'total_count': total_count,
                'correctness_percentage': float(correctness_percentage),
                'is_correct': predicted_labels[-1] == 'correct' if predicted_labels else False,
                'current_form': predicted_labels[-1] if predicted_labels else 'unknown',
                'feedback': feedback,
                'threshold_used': threshold,
                'rep_ready': correctness_percentage > 70  # Siap untuk dihitung rep
            }
            
            return result
            
        except Exception as e:
            print(f"[ERROR] Prediction failed: {str(e)}", file=sys.stderr)
            return {
                'success': False,
                'error': str(e),
                'model': model_name
            }
    
    def get_form_feedback(self, exercise, predictions, confidences):
        """Dapatkan feedback form berdasarkan prediksi"""
        if not predictions:
            return "Menunggu data..."
        
        last_pred = predictions[-1]
        last_conf = confidences[-1] if len(confidences) > 0 else 0.5
        
        feedback_pool = self.form_feedback.get(exercise, self.form_feedback['pushup'])
        
        if last_pred == 'correct':
            if last_conf > 0.8:
                feedbacks = feedback_pool['correct']
            else:
                feedbacks = [f"Form cukup baik (confidence: {last_conf:.2f})"]
        else:
            feedbacks = feedback_pool['incorrect']
        
        import random
        return random.choice(feedbacks)
    
    def process_real_time_frame(self, frame_data, expected_exercise):
        """
        Process single frame untuk real-time untuk SEMUA EXERCISE
        """
        try:
            # Validasi exercise type
            if expected_exercise not in ['pushup', 'shoulder_press', 't_bar_row']:
                return {
                    'success': False,
                    'error': f'Invalid exercise type: {expected_exercise}',
                    'frame_analysis': {
                        'prediction': {'exercise_detected': expected_exercise},
                        'confidence_score': 0.5,
                        'form_check': {'is_correct': False, 'issues': []},
                        'rep_detection': {'rep_completed': False, 'total_reps': 0}
                    }
                }
            
            # Tambah frame ke buffer untuk exercise tertentu
            self.realtime_processor.add_frame(frame_data, expected_exercise)
            
            # Dapatkan sequence untuk prediction exercise tertentu
            sequence = self.realtime_processor.get_sequence(expected_exercise)
            if not sequence:
                return {
                    'success': False,
                    'error': 'Not enough frames in buffer',
                    'frame_analysis': {
                        'prediction': {'exercise_detected': expected_exercise},
                        'confidence_score': 0.5,
                        'form_check': {'is_correct': False, 'issues': []},
                        'rep_detection': {'rep_completed': False, 'total_reps': 0}
                    }
                }
            
            # Predict dengan model yang sesuai
            prediction_result = self.predict_single_model(sequence, expected_exercise)
            
            if not prediction_result['success']:
                return {
                    'success': False,
                    'error': prediction_result.get('error', 'Prediction failed'),
                    'frame_analysis': {
                        'prediction': {'exercise_detected': expected_exercise},
                        'confidence_score': 0.5,
                        'form_check': {'is_correct': False, 'issues': []},
                        'rep_detection': {'rep_completed': False, 'total_reps': 0}
                    }
                }
            
            # Update rep counter untuk exercise ini
            rep_counter = self.realtime_processor.get_rep_counter(expected_exercise)
            confidence = prediction_result['confidence_score']
            rep_detected, rep_completed = rep_counter.update(confidence)
            
            # Prepare response khusus untuk exercise ini
            exercise_display_name = self.exercise_names.get(expected_exercise, expected_exercise)
            muscle_groups = self.model_configs.get(expected_exercise, {}).get('muscle_groups', [])
            
            frame_analysis = {
                'prediction': {
                    'exercise_detected': expected_exercise,
                    'exercise_display_name': exercise_display_name,
                    'is_correct_exercise': True,
                    'model_used': expected_exercise,
                    'prediction_label': prediction_result['current_form'],
                    'confidence': confidence,
                    'muscle_groups': muscle_groups
                },
                'confidence_score': confidence,
                'form_check': {
                    'is_correct': prediction_result['is_correct'],
                    'issues': [] if prediction_result['is_correct'] else [f'{exercise_display_name}: Form needs improvement'],
                    'feedback': prediction_result['feedback']
                },
                'rep_detection': {
                    'rep_detected': rep_detected,
                    'rep_completed': rep_completed,
                    'total_reps': rep_counter.rep_count,
                    'current_rep_state': rep_counter.last_state or 'none',
                    'rep_in_progress': rep_counter.is_in_rep,
                    'exercise_type': expected_exercise
                }
            }
            
            return {
                'success': True,
                'frame_analysis': frame_analysis,
                'feedback': prediction_result['feedback'],
                'rep_count': rep_counter.rep_count,
                'is_rep_completed': rep_completed,
                'exercise_info': {
                    'type': expected_exercise,
                    'name': exercise_display_name,
                    'muscle_groups': muscle_groups,
                    'target_reps': self.exercise_params.get(expected_exercise, {}).get('target_reps', 10)
                }
            }
            
        except Exception as e:
            print(f"[ERROR] Real-time frame processing failed for {expected_exercise}: {str(e)}", file=sys.stderr)
            return {
                'success': False,
                'error': str(e),
                'frame_analysis': {
                    'prediction': {'exercise_detected': expected_exercise},
                    'confidence_score': 0.5,
                    'form_check': {'is_correct': False, 'issues': []},
                    'rep_detection': {'rep_completed': False, 'total_reps': 0}
                }
            }
    
    def batch_process_frames(self, frames_data, expected_exercise):
        """
        Process batch frames untuk exercise tertentu
        """
        try:
            batch_results = []
            total_correct = 0
            rep_counter = RepCounter(expected_exercise)
            
            for i, frame_data in enumerate(frames_data):
                # Process each frame
                result = self.process_real_time_frame(frame_data, expected_exercise)
                batch_results.append(result)
                
                if result.get('success') and result.get('frame_analysis', {}).get('form_check', {}).get('is_correct'):
                    total_correct += 1
                
                # Update rep counter dari frame analysis
                if result.get('success'):
                    frame_analysis = result.get('frame_analysis', {})
                    rep_info = frame_analysis.get('rep_detection', {})
                    if rep_info.get('rep_completed'):
                        # Sudah dihitung di process_real_time_frame
                        pass
            
            total_frames = len(frames_data)
            success_rate = (total_correct / total_frames * 100) if total_frames > 0 else 0
            
            # Analyze form issues
            form_issues_summary = []
            for result in batch_results:
                if result.get('success'):
                    issues = result.get('frame_analysis', {}).get('form_check', {}).get('issues', [])
                    for issue in issues:
                        if issue not in form_issues_summary:
                            form_issues_summary.append(issue)
            
            exercise_name = self.exercise_names.get(expected_exercise, expected_exercise)
            
            return {
                'success': True,
                'batch_analysis': {
                    'total_frames': total_frames,
                    'successful_frames': total_correct,
                    'success_rate': success_rate,
                    'correct_exercise_rate': 100.0,
                    'correct_form_rate': success_rate,
                    'total_reps_detected': rep_counter.rep_count,
                    'average_confidence': np.mean([r.get('frame_analysis', {}).get('confidence_score', 0) 
                                                  for r in batch_results if r.get('success')]) if batch_results else 0,
                    'form_issues_summary': form_issues_summary,
                    'exercise_analyzed': exercise_name
                },
                'frame_results': batch_results
            }
            
        except Exception as e:
            print(f"[ERROR] Batch processing failed for {expected_exercise}: {str(e)}", file=sys.stderr)
            return {
                'success': False,
                'error': str(e),
                'batch_analysis': {
                    'total_frames': 0,
                    'successful_frames': 0,
                    'success_rate': 0,
                    'correct_exercise_rate': 0,
                    'correct_form_rate': 0,
                    'total_reps_detected': 0,
                    'average_confidence': 0,
                    'form_issues_summary': [],
                    'exercise_analyzed': expected_exercise
                }
            }
    
    def get_all_exercises_info(self):
        """Dapatkan informasi semua exercise yang tersedia"""
        exercises_info = []
        
        for exercise_key, config in self.model_configs.items():
            exercise_info = {
                'key': exercise_key,
                'display_name': config.get('display_name', exercise_key),
                'folder': config['folder'],
                'model_loaded': exercise_key in self.models,
                'scaler_loaded': exercise_key in self.scalers,
                'metadata_loaded': exercise_key in self.metadata,
                'muscle_groups': config.get('muscle_groups', []),
                'target_reps': self.exercise_params.get(exercise_key, {}).get('target_reps', 10)
            }
            exercises_info.append(exercise_info)
        
        return exercises_info
    
    def reset_all_counters(self):
        """Reset semua rep counters"""
        for exercise in ['pushup', 'shoulder_press', 't_bar_row']:
            self.realtime_processor.reset_exercise(exercise)
    
    def mock_prediction(self, model_name='pushup'):
        """Mock prediction untuk semua exercise"""
        labels = self.model_labels.get(model_name, ['incorrect', 'correct'])
        
        # Generate mock predictions berdasarkan exercise
        if model_name == 'pushup':
            mock_predictions = ['correct', 'correct', 'incorrect', 'correct']
            mock_confidences = [0.85, 0.88, 0.45, 0.82]
        elif model_name == 'shoulder_press':
            mock_predictions = ['correct', 'incorrect', 'correct', 'correct']
            mock_confidences = [0.78, 0.35, 0.81, 0.79]
        elif model_name == 't_bar_row':
            mock_predictions = ['correct', 'correct', 'correct', 'incorrect']
            mock_confidences = [0.82, 0.85, 0.80, 0.40]
        else:
            mock_predictions = ['correct', 'correct']
            mock_confidences = [0.75, 0.78]
        
        correct_count = sum(1 for label in mock_predictions if label == 'correct')
        total_count = len(mock_predictions)
        correctness_percentage = (correct_count / total_count * 100)
        
        exercise_name = self.exercise_names.get(model_name, model_name)
        muscle_groups = self.model_configs.get(model_name, {}).get('muscle_groups', [])
        
        return {
            'success': True,
            'model': model_name,
            'exercise_name': exercise_name,
            'muscle_groups': muscle_groups,
            'predictions': mock_predictions,
            'confidence': mock_confidences,
            'confidence_score': float(np.mean(mock_confidences)),
            'correct_count': correct_count,
            'total_count': total_count,
            'correctness_percentage': float(correctness_percentage),
            'is_correct': mock_predictions[-1] == 'correct',
            'current_form': mock_predictions[-1],
            'feedback': f'Mock feedback for {exercise_name}',
            'threshold_used': 0.5,
            'rep_ready': True,
            'note': f'MOCK PREDICTION FOR {exercise_name.upper()}'
        }

# ============================================
# MAIN FUNCTION
# ============================================
def main():
    """Main function untuk menerima input dari PHP"""
    try:
        # Read input data from stdin
        input_json = sys.stdin.read().strip()
        
        if not input_json:
            print(json.dumps({
                'success': False,
                'error': 'No input provided'
            }))
            return
            
        data = json.loads(input_json)
        
        # Initialize predictor
        predictor = MultiModelExercisePredictor()
        
        # Check mode
        mode = data.get('mode', 'predict')
        
        if mode == 'realtime_frame':
            # Real-time single frame processing
            frame_data = data.get('frame_data', [])
            expected_exercise = data.get('expected_exercise', 'pushup')
            
            result = predictor.process_real_time_frame(frame_data, expected_exercise)
            
        elif mode == 'batch_frames':
            # Batch frames processing
            frames_data = data.get('frames_data', [])
            expected_exercise = data.get('expected_exercise', 'pushup')
            
            result = predictor.batch_process_frames(frames_data, expected_exercise)
            
        elif mode == 'predict_single':
            # Single prediction with specific model
            sequence_data = data.get('sequence_data', [])
            model_name = data.get('model', 'pushup')
            
            result = predictor.predict_single_model(sequence_data, model_name)
            
        elif mode == 'get_exercises':
            # Get all available exercises info
            result = {
                'success': True,
                'available_exercises': predictor.get_all_exercises_info(),
                'total_models': len(predictor.models),
                'models_loaded': list(predictor.models.keys())
            }
            
        elif mode == 'reset_counters':
            # Reset all rep counters
            predictor.reset_all_counters()
            result = {
                'success': True,
                'message': 'All rep counters have been reset'
            }
            
        elif mode == 'test':
            # Test mode for specific exercise
            test_model = data.get('model', 'pushup')
            result = predictor.mock_prediction(test_model)
            result['test_mode'] = True
            
        else:
            result = {
                'success': False,
                'error': f'Unknown mode: {mode}',
                'available_modes': ['realtime_frame', 'batch_frames', 'predict_single', 'get_exercises', 'reset_counters', 'test'],
                'available_exercises': ['pushup', 'shoulder_press', 't_bar_row']
            }
        
        # Tambahkan timestamp
        if isinstance(result, dict):
            result['timestamp'] = time.time()
            result['python_version'] = sys.version
        
        # Output result sebagai JSON
        output_json = json.dumps(result, indent=2)
        print(output_json)
        
    except json.JSONDecodeError as e:
        error_msg = f'Invalid JSON: {str(e)}'
        print(json.dumps({
            'success': False,
            'error': error_msg
        }))
    except Exception as e:
        error_msg = f'Unexpected error: {str(e)}'
        print(json.dumps({
            'success': False,
            'error': error_msg,
            'traceback': traceback.format_exc()
        }))

if __name__ == "__main__":
    main()