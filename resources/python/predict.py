import sys
import json
import numpy as np
import os
import traceback
import pickle
from pathlib import Path

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
# MAIN PREDICTOR CLASS
# ============================================
class MultiModelExercisePredictor:
    def __init__(self, models_dir=None):
        """
        Inisialisasi predictor dengan multiple models
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
        
        # Konfigurasi model - PERBAIKI: Gunakan nama folder yang benar sesuai struktur Anda
        self.model_configs = {
            'pushup': {
                'folder': 'push-up',  # Sesuaikan dengan nama folder sebenarnya
                'model_file': 'model_pushup.keras',
                'scaler_file': 'scaler.pkl',
                'meta': 'meta.json'
            },
            'shoulder_press': {
                'folder': 'Shoulder Press',  # Sesuaikan dengan nama folder sebenarnya
                'model_file': 'model_Shoulder_Press.keras',
                'scaler_file': 'scaler.pkl',
                'meta': 'meta.json'
            },
            't_bar_row': {
                'folder': 't bar row',  # Sesuaikan dengan nama folder sebenarnya
                'model_file': 'model_t_bar_row.keras',
                'scaler_file': 'scaler.pkl',
                'meta': 'meta.json'
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
        
        # **HAPUS DEFAULT VALUES** - akan diambil dari metadata masing-masing model
        self.model_params = {}  # Simpan parameter per model
        
        self.load_all_models()
        self.load_scalers_and_metadata()
    
    def get_full_path(self, folder_name, file_name):
        """Mendapatkan path lengkap ke file"""
        folder_path = os.path.join(self.models_dir, folder_name)
        return os.path.join(folder_path, file_name)
    
    def load_all_models(self):
        """Load semua model ML"""
        if tf is None:
            print("[WARNING] TensorFlow not available, running in mock mode", file=sys.stderr)
            return
            
        loaded_models = 0
        for model_key, config in self.model_configs.items():
            folder_name = config['folder']
            folder_path = os.path.join(self.models_dir, folder_name)
            
            if not os.path.exists(folder_path):
                print(f"[WARNING] Folder not found: {folder_path}", file=sys.stderr)
                # Coba dengan nama folder alternatif
                alt_names = [
                    folder_name.lower().replace(' ', '_'),
                    folder_name.lower().replace(' ', ''),
                    folder_name.upper().replace(' ', '_'),
                ]
                for alt_name in alt_names:
                    alt_path = os.path.join(self.models_dir, alt_name)
                    if os.path.exists(alt_path):
                        folder_path = alt_path
                        folder_name = alt_name
                        print(f"[INFO] Using alternative folder name: {folder_name}", file=sys.stderr)
                        break
                else:
                    print(f"[ERROR] Model folder not found for {model_key}: {folder_name}", file=sys.stderr)
                    continue
            
            # Cari file model
            model_file = config['model_file']
            model_path = self.get_full_path(folder_name, model_file)
            
            if not os.path.exists(model_path):
                print(f"[WARNING] Model file not found: {model_path}", file=sys.stderr)
                # Coba cari file model dengan nama lain
                possible_files = [
                    model_file,
                    model_file.replace('.keras', '.h5'),
                    'model.keras',
                    'model.h5',
                    f'model_{model_key}.keras',
                    f'model_{model_key}.h5',
                ]
                
                found = False
                for test_file in possible_files:
                    test_path = self.get_full_path(folder_name, test_file)
                    if os.path.exists(test_path):
                        model_path = test_path
                        model_file = test_file
                        found = True
                        print(f"[INFO] Found model file: {test_file}", file=sys.stderr)
                        break
                
                if not found:
                    print(f"[ERROR] No model file found for {model_key} in {folder_path}", file=sys.stderr)
                    continue
            
            try:
                print(f"[INFO] Loading model: {model_key} from {model_path}", file=sys.stderr)
                self.models[model_key] = load_model(model_path)
                print(f"[SUCCESS] Model {model_key} loaded", file=sys.stderr)
                
                # Debug model info
                model = self.models[model_key]
                print(f"[DEBUG] Model {model_key} input shape: {model.input_shape}", file=sys.stderr)
                print(f"[DEBUG] Model {model_key} output shape: {model.output_shape}", file=sys.stderr)
                
                loaded_models += 1
            except Exception as e:
                print(f"[ERROR] Failed to load {model_key}: {str(e)}", file=sys.stderr)
                traceback.print_exc(file=sys.stderr)
                
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
                'sequence_length': 20,  # Default sesuai training Anda
                'features_per_frame': 103,  # Default sesuai training Anda
                'threshold': 0.5  # Default threshold
            }
            
            # Load metadata jika ada
            if 'meta' in config:
                meta_path = self.get_full_path(folder_name, config['meta'])
                if os.path.exists(meta_path):
                    try:
                        with open(meta_path, 'r') as f:
                            self.metadata[model_key] = json.load(f)
                        print(f"[INFO] Loaded metadata for {model_key}", file=sys.stderr)
                        
                        # Extract parameters dari metadata
                        meta = self.metadata[model_key]
                        
                        # Ambil sequence_length dari metadata
                        if 'timesteps' in meta:
                            self.model_params[model_key]['sequence_length'] = int(meta['timesteps'])
                            print(f"[INFO] {model_key} sequence_length from metadata: {self.model_params[model_key]['sequence_length']}", file=sys.stderr)
                        elif 'sequence_length' in meta:
                            self.model_params[model_key]['sequence_length'] = int(meta['sequence_length'])
                            print(f"[INFO] {model_key} sequence_length from metadata: {self.model_params[model_key]['sequence_length']}", file=sys.stderr)
                        elif 'input_shape' in meta:
                            # Parse dari input_shape: [None, timesteps, features]
                            input_shape = meta['input_shape']
                            if len(input_shape) >= 2:
                                self.model_params[model_key]['sequence_length'] = int(input_shape[1])
                                print(f"[INFO] {model_key} sequence_length from input_shape: {self.model_params[model_key]['sequence_length']}", file=sys.stderr)
                        
                        # Ambil features_per_frame dari metadata
                        if 'features' in meta:
                            self.model_params[model_key]['features_per_frame'] = int(meta['features'])
                            print(f"[INFO] {model_key} features from metadata: {self.model_params[model_key]['features_per_frame']}", file=sys.stderr)
                        elif 'num_features' in meta:
                            self.model_params[model_key]['features_per_frame'] = int(meta['num_features'])
                            print(f"[INFO] {model_key} features from metadata: {self.model_params[model_key]['features_per_frame']}", file=sys.stderr)
                        elif 'input_shape' in meta:
                            # Parse dari input_shape: [None, timesteps, features]
                            input_shape = meta['input_shape']
                            if len(input_shape) >= 3:
                                self.model_params[model_key]['features_per_frame'] = int(input_shape[2])
                                print(f"[INFO] {model_key} features from input_shape: {self.model_params[model_key]['features_per_frame']}", file=sys.stderr)
                        
                        # Extract threshold jika ada
                        if 'threshold' in meta:
                            self.thresholds[model_key] = float(meta['threshold'])
                            self.model_params[model_key]['threshold'] = float(meta['threshold'])
                            print(f"[INFO] Threshold for {model_key}: {self.thresholds[model_key]}", file=sys.stderr)
                        
                        # Debug info metadata
                        print(f"[DEBUG] Metadata for {model_key}:", file=sys.stderr)
                        for key, value in meta.items():
                            if key not in ['model_summary', 'training_history']:
                                print(f"  {key}: {value}", file=sys.stderr)
                                
                    except Exception as e:
                        print(f"[WARNING] Failed to load metadata for {model_key}: {e}", file=sys.stderr)
                else:
                    print(f"[WARNING] Metadata file not found: {meta_path}", file=sys.stderr)
            
            # Load scaler jika ada
            if 'scaler_file' in config:
                scaler_path = self.get_full_path(folder_name, config['scaler_file'])
                if os.path.exists(scaler_path):
                    try:
                        with open(scaler_path, 'rb') as f:
                            scaler = pickle.load(f)
                            self.scalers[model_key] = scaler
                        print(f"[INFO] Loaded scaler for {model_key}", file=sys.stderr)
                        
                        # Debug scaler info
                        if hasattr(scaler, 'n_features_in_'):
                            print(f"[DEBUG] Scaler expects {scaler.n_features_in_} features for {model_key}", file=sys.stderr)
                            # Update features_per_frame dari scaler jika belum ada dari metadata
                            if 'features_per_frame' not in self.model_params[model_key]:
                                self.model_params[model_key]['features_per_frame'] = scaler.n_features_in_
                        elif hasattr(scaler, 'scale_'):
                            print(f"[DEBUG] Scaler scale shape for {model_key}: {scaler.scale_.shape}", file=sys.stderr)
                            if scaler.scale_.shape[0] != self.model_params[model_key]['features_per_frame']:
                                print(f"[WARNING] Scaler features ({scaler.scale_.shape[0]}) mismatch with model features ({self.model_params[model_key]['features_per_frame']})", file=sys.stderr)
                    except Exception as e:
                        print(f"[WARNING] Failed to load scaler for {model_key}: {e}", file=sys.stderr)
                else:
                    print(f"[WARNING] Scaler file not found: {scaler_path}", file=sys.stderr)
    
    def preprocess_data(self, data, model_key='pushup'):
        """
        Preprocess data dari input JSON sesuai dengan proses training
        """
        try:
            # Konversi ke numpy array
            sequences = np.array(data, dtype=np.float32)
            
            print(f"[DEBUG] Raw input shape: {sequences.shape}", file=sys.stderr)
            
            # Cek jika data kosong
            if sequences.size == 0:
                print("[ERROR] Empty data received", file=sys.stderr)
                return np.array([[]], dtype=np.float32)
            
            # Cek dimensi input
            if sequences.ndim == 1:
                print(f"[WARNING] 1D array received, reshaping to 2D", file=sys.stderr)
                sequences = sequences.reshape(-1, 1)
            
            # Dapatkan parameter untuk model ini
            if model_key not in self.model_params:
                print(f"[ERROR] No parameters found for model: {model_key}", file=sys.stderr)
                return np.array([[]], dtype=np.float32)
                
            model_param = self.model_params[model_key]
            expected_features = model_param['features_per_frame']
            sequence_length = model_param['sequence_length']
            
            print(f"[INFO] Model {model_key} expects: {expected_features} features, {sequence_length} timesteps", file=sys.stderr)
            
            # Tentukan actual features
            if sequences.ndim == 2:
                actual_features = sequences.shape[1]
                print(f"[DEBUG] 2D array: {sequences.shape[0]} frames, {actual_features} features", file=sys.stderr)
            elif sequences.ndim == 3:
                actual_features = sequences.shape[2]
                print(f"[DEBUG] 3D array: {sequences.shape[0]} samples, {sequences.shape[1]} timesteps, {actual_features} features", file=sys.stderr)
            else:
                raise ValueError(f"Invalid input shape: {sequences.shape}")
            
            # Validasi dan adjust feature count
            if actual_features != expected_features:
                print(f"[WARNING] Feature count mismatch: expected {expected_features}, got {actual_features}", file=sys.stderr)
                
                if actual_features < expected_features:
                    # Padding dengan zeros
                    padding_needed = expected_features - actual_features
                    print(f"[INFO] Padding with {padding_needed} zeros", file=sys.stderr)
                    
                    if sequences.ndim == 2:
                        padding = np.zeros((sequences.shape[0], padding_needed), dtype=np.float32)
                        sequences = np.hstack([sequences, padding])
                    elif sequences.ndim == 3:
                        padding = np.zeros((sequences.shape[0], sequences.shape[1], padding_needed), dtype=np.float32)
                        sequences = np.concatenate([sequences, padding], axis=2)
                        
                elif actual_features > expected_features:
                    # Truncate
                    print(f"[INFO] Truncating features from {actual_features} to {expected_features}", file=sys.stderr)
                    if sequences.ndim == 2:
                        sequences = sequences[:, :expected_features]
                    elif sequences.ndim == 3:
                        sequences = sequences[:, :, :expected_features]
            
            # Normalisasi dengan scaler jika ada
            if model_key in self.scalers:
                try:
                    print(f"[DEBUG] Applying scaler for {model_key}", file=sys.stderr)
                    
                    scaler = self.scalers[model_key]
                    
                    if sequences.ndim == 2:
                        # Frame-based normalization
                        sequences = scaler.transform(sequences)
                        print(f"[DEBUG] Frame-based normalization applied", file=sys.stderr)
                            
                    elif sequences.ndim == 3:
                        # Sequence-based normalization
                        original_shape = sequences.shape
                        sequences_flat = sequences.reshape(-1, sequences.shape[-1])
                        sequences_normalized = scaler.transform(sequences_flat)
                        sequences = sequences_normalized.reshape(original_shape)
                        print(f"[DEBUG] Sequence-based normalization applied", file=sys.stderr)
                            
                except Exception as e:
                    print(f"[WARNING] Failed to apply scaler: {e}", file=sys.stderr)
                    traceback.print_exc(file=sys.stderr)
            
            # Reshape untuk model LSTM: (batch_size, timesteps, features)
            # Jika input adalah 2D (timesteps, features) -> reshape ke (1, timesteps, features)
            if sequences.ndim == 2:
                print(f"[DEBUG] Reshaping 2D to 3D: {sequences.shape} -> (1, {sequences.shape[0]}, {sequences.shape[1]})", file=sys.stderr)
                sequences = np.expand_dims(sequences, axis=0)
            
            # Pastikan sequence length sesuai
            current_timesteps = sequences.shape[1]
            if current_timesteps != sequence_length:
                print(f"[WARNING] Sequence length mismatch: got {current_timesteps}, expected {sequence_length}", file=sys.stderr)
                
                if current_timesteps < sequence_length:
                    # Padding
                    pad_width = ((0, 0), (0, sequence_length - current_timesteps), (0, 0))
                    sequences = np.pad(sequences, pad_width, mode='constant', constant_values=0)
                    print(f"[DEBUG] Padded to shape: {sequences.shape}", file=sys.stderr)
                else:
                    # Truncate
                    sequences = sequences[:, :sequence_length, :]
                    print(f"[DEBUG] Truncated to shape: {sequences.shape}", file=sys.stderr)
            
            print(f"[DEBUG] Final processed shape: {sequences.shape}", file=sys.stderr)
            return sequences
                
        except Exception as e:
            print(f"[ERROR] Preprocessing failed: {e}", file=sys.stderr)
            traceback.print_exc(file=sys.stderr)
            return np.array([[]], dtype=np.float32)
    
    def predict_single_model(self, input_data, model_name):
        """
        Predict menggunakan model tertentu dengan threshold dari metadata
        """
        # Jika TensorFlow tidak tersedia, return mock data
        if tf is None or model_name not in self.models:
            print(f"[WARNING] Using mock prediction for {model_name}", file=sys.stderr)
            return self.mock_prediction(model_name)
        
        try:
            # Preprocess data
            processed_data = self.preprocess_data(input_data, model_name)
            
            if processed_data.size == 0:
                return {
                    'success': False,
                    'error': 'Preprocessing failed - empty data after preprocessing',
                    'model': model_name,
                    'exercise_name': self.exercise_names.get(model_name, model_name)
                }
            
            print(f"[DEBUG] Processed data shape: {processed_data.shape}", file=sys.stderr)
            
            # Predict dengan model
            model = self.models[model_name]
            predictions = model.predict(processed_data, verbose=0)
            
            print(f"[DEBUG] Predictions shape: {predictions.shape}", file=sys.stderr)
            print(f"[DEBUG] Raw predictions: {predictions.flatten()[:5]}...", file=sys.stderr)
            
            # Gunakan threshold dari metadata jika ada, default 0.5
            threshold = self.thresholds.get(model_name, 0.5)
            if model_name in self.model_params:
                threshold = self.model_params[model_name]['threshold']
            print(f"[INFO] Using threshold {threshold} for {model_name}", file=sys.stderr)
            
            # Binary classification dengan threshold yang sesuai
            if predictions.shape[1] == 1:  # Sigmoid output
                confidence_scores = predictions.flatten()
                
                # Gunakan threshold
                predicted_classes = (confidence_scores >= threshold).astype(int).flatten()
                
                # Map ke label
                labels = self.model_labels.get(model_name, ['incorrect', 'correct'])
                predicted_labels = [
                    labels[pred] if pred < len(labels) else 'unknown'
                    for pred in predicted_classes
                ]
                
                print(f"[DEBUG] Predictions with threshold {threshold}: {predicted_labels[:5]}...", file=sys.stderr)
                print(f"[DEBUG] Confidence scores: {confidence_scores[:5]}...", file=sys.stderr)
            
            else:  # Multi-class classification (softmax)
                predicted_indices = np.argmax(predictions, axis=1)
                confidence_scores = np.max(predictions, axis=1)
                
                # Map ke label
                labels = self.model_labels.get(model_name, ['incorrect', 'correct'])
                predicted_labels = [
                    labels[idx] if idx < len(labels) else 'unknown' 
                    for idx in predicted_indices
                ]
            
            # Hitung statistik
            total_count = len(predicted_labels)
            correct_count = sum(1 for label in predicted_labels if label == 'correct')
            correctness_percentage = (correct_count / total_count * 100) if total_count > 0 else 0
            
            # Hitung confidence statistics
            avg_confidence = float(np.mean(confidence_scores)) if len(confidence_scores) > 0 else 0.0
            max_confidence = float(np.max(confidence_scores)) if len(confidence_scores) > 0 else 0.0
            min_confidence = float(np.min(confidence_scores)) if len(confidence_scores) > 0 else 0.0
            
            # Dapatkan metadata
            metadata = self.metadata.get(model_name, {})
            model_param = self.model_params.get(model_name, {})
            
            result = {
                'success': True,
                'model': model_name,
                'exercise_name': self.exercise_names.get(model_name, model_name),
                'predictions': predicted_labels,
                'confidence': confidence_scores.tolist(),
                'confidence_stats': {
                    'average': avg_confidence,
                    'max': max_confidence,
                    'min': min_confidence,
                    'std': float(np.std(confidence_scores)) if len(confidence_scores) > 0 else 0.0
                },
                'correct_count': correct_count,
                'total_count': total_count,
                'correctness_percentage': float(correctness_percentage),
                'is_correct_overall': correctness_percentage > 70.0,
                'threshold_used': threshold,
                'sequence_length_used': processed_data.shape[1],
                'features_count': processed_data.shape[2],
                'model_parameters': model_param,
                'model_input_shape': self.models[model_name].input_shape if model_name in self.models else None,
                'metadata': {k: v for k, v in metadata.items() if k not in ['model_summary', 'training_history']}
            }
            
            print(f"[INFO] Prediction successful for {model_name}: {correct_count}/{total_count} correct ({correctness_percentage:.1f}%)", file=sys.stderr)
            return result
            
        except Exception as e:
            print(f"[ERROR] Prediction failed: {str(e)}", file=sys.stderr)
            traceback.print_exc(file=sys.stderr)
            return {
                'success': False,
                'error': str(e),
                'model': model_name,
                'exercise_name': self.exercise_names.get(model_name, model_name),
                'traceback': traceback.format_exc()
            }
    
    def mock_prediction(self, model_name='pushup'):
        """Return mock prediction untuk testing"""
        labels = self.model_labels.get(model_name, ['incorrect', 'correct'])
        
        # Generate mock predictions
        mock_predictions = []
        mock_confidences = []
        
        for i in range(20):  # 20 mock sequences
            if i < 16:
                mock_predictions.append('correct')
                mock_confidences.append(0.75 + (i * 0.01))
            else:
                mock_predictions.append('incorrect')
                mock_confidences.append(0.25 + ((i-16) * 0.05))
        
        correct_count = sum(1 for label in mock_predictions if label == 'correct')
        total_count = len(mock_predictions)
        correctness_percentage = (correct_count / total_count * 100)
        
        return {
            'success': True,
            'model': model_name,
            'exercise_name': self.exercise_names.get(model_name, model_name),
            'predictions': mock_predictions,
            'confidence': mock_confidences,
            'confidence_stats': {
                'average': float(np.mean(mock_confidences)),
                'max': float(np.max(mock_confidences)),
                'min': float(np.min(mock_confidences)),
                'std': float(np.std(mock_confidences))
            },
            'correct_count': correct_count,
            'total_count': total_count,
            'correctness_percentage': float(correctness_percentage),
            'is_correct_overall': correctness_percentage > 70.0,
            'threshold_used': 0.5,
            'sequence_length_used': 20,
            'features_count': 103,
            'note': 'MOCK PREDICTION - TensorFlow not available or model not loaded',
            'metadata': {'is_mock': True, 'timestamp': np.datetime64('now').astype(str)}
        }
    
    def detect_exercise_type(self, data):
        """Deteksi tipe exercise berdasarkan data gerakan"""
        if not self.models:
            print("[WARNING] No models loaded, using mock detection", file=sys.stderr)
            return {
                'success': False,
                'detected_exercise': 'pushup',
                'exercise_name': 'Push Up',
                'note': 'Mock detection - no models loaded'
            }
        
        try:
            exercise_scores = {}
            exercise_details = {}
            
            for model_name in self.models.keys():
                try:
                    # Preprocess data untuk model ini
                    processed_data = self.preprocess_data(data, model_name)
                    
                    if processed_data.size == 0:
                        print(f"[WARNING] Preprocessing failed for {model_name}", file=sys.stderr)
                        exercise_scores[model_name] = 0.0
                        exercise_details[model_name] = {'error': 'Preprocessing failed'}
                        continue
                    
                    # Predict
                    predictions = self.models[model_name].predict(processed_data, verbose=0)
                    
                    # Hitung confidence
                    if predictions.shape[1] == 1:  # Sigmoid
                        avg_confidence = float(np.mean(predictions))
                    else:  # Softmax
                        max_probs = np.max(predictions, axis=1)
                        avg_confidence = float(np.mean(max_probs))
                    
                    exercise_scores[model_name] = avg_confidence
                    exercise_details[model_name] = {'avg_confidence': avg_confidence}
                    
                    print(f"[DEBUG] {model_name}: confidence = {avg_confidence:.4f}", file=sys.stderr)
                    
                except Exception as e:
                    print(f"[WARNING] Failed to evaluate {model_name}: {e}", file=sys.stderr)
                    exercise_scores[model_name] = 0.0
                    exercise_details[model_name] = {'error': str(e)}
            
            # Pilih exercise dengan confidence tertinggi
            if exercise_scores:
                detected_exercise = max(exercise_scores, key=exercise_scores.get)
                max_score = exercise_scores[detected_exercise]
                
                result = {
                    'success': True,
                    'detected_exercise': detected_exercise,
                    'exercise_name': self.exercise_names.get(detected_exercise, detected_exercise),
                    'confidence_scores': exercise_scores,
                    'exercise_details': exercise_details,
                    'recommended_model': detected_exercise,
                    'max_confidence': max_score,
                    'is_confident': max_score > 0.7,
                    'detection_quality': 'high' if max_score > 0.8 else 'medium' if max_score > 0.6 else 'low'
                }
                
                print(f"[INFO] Detected exercise: {detected_exercise} (confidence: {max_score:.4f})", file=sys.stderr)
                return result
            else:
                return {
                    'success': False,
                    'error': 'No models could process the data',
                    'note': 'Fallback to pushup',
                    'detected_exercise': 'pushup',
                    'exercise_name': 'Push Up'
                }
            
        except Exception as e:
            print(f"[ERROR] Detection failed: {str(e)}", file=sys.stderr)
            traceback.print_exc(file=sys.stderr)
            return {
                'success': False,
                'error': str(e),
                'traceback': traceback.format_exc()
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
                'error': 'No input provided',
                'timestamp': np.datetime64('now').astype(str)
            }))
            return
            
        print(f"[DEBUG] Input JSON length: {len(input_json)}", file=sys.stderr)
        
        data = json.loads(input_json)
        
        print(f"[DEBUG] Received input keys: {list(data.keys())}", file=sys.stderr)
        
        # Initialize predictor
        predictor = MultiModelExercisePredictor()
        
        # Check if specific model is requested
        if 'model' in data and 'sequence_data' in data:
            requested_model = data['model']
            if requested_model not in predictor.model_configs:
                print(json.dumps({
                    'success': False,
                    'error': f"Model '{requested_model}' not available",
                    'available_models': list(predictor.model_configs.keys()),
                    'timestamp': np.datetime64('now').astype(str)
                }))
                return
                
            # Predict with specific model
            result = predictor.predict_single_model(data['sequence_data'], requested_model)
                
        elif 'sequence_data' in data:
            # Auto-detect and predict
            detection = predictor.detect_exercise_type(data['sequence_data'])
            
            if detection.get('success', False) and 'detected_exercise' in detection:
                model_name = detection['detected_exercise']
                result = predictor.predict_single_model(data['sequence_data'], model_name)
                
                # Gabungkan detection result
                if result.get('success', False):
                    result['detection_result'] = detection
                else:
                    result = detection
            else:
                result = detection
                
        elif 'test' in data:
            # Mode testing
            test_model = data.get('model', 'pushup')
            result = predictor.mock_prediction(test_model)
            result['test_mode'] = True
            result['note'] = 'Test mode - mock prediction'
            
        else:
            result = {
                'success': False,
                'error': 'Invalid input format. Need "sequence_data" or "model"',
                'valid_formats': [
                    {'model': 'model_name', 'sequence_data': '[...]'},
                    {'sequence_data': '[...]'}
                ]
            }
        
        # Tambah timestamp dan system info
        if isinstance(result, dict):
            result['timestamp'] = np.datetime64('now').astype(str)
            if 'success' not in result:
                result['success'] = 'error' not in result
            
        # Output result sebagai JSON
        output_json = json.dumps(result, indent=2)
        print(output_json)
        
    except json.JSONDecodeError as e:
        error_msg = f'Invalid JSON: {str(e)}'
        print(json.dumps({
            'success': False,
            'error': error_msg,
            'timestamp': np.datetime64('now').astype(str)
        }))
        print(f"[ERROR] {error_msg}", file=sys.stderr)
    except Exception as e:
        error_msg = f'Unexpected error: {str(e)}'
        print(json.dumps({
            'success': False,
            'error': error_msg,
            'timestamp': np.datetime64('now').astype(str),
            'traceback': traceback.format_exc()
        }))
        print(f"[ERROR] {error_msg}", file=sys.stderr)
        traceback.print_exc(file=sys.stderr)

if __name__ == "__main__":
    main()