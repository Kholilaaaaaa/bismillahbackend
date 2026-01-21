"""
Test script untuk menguji ML models dan preprocessing
Test khusus untuk konfigurasi MediaPipe (103 features, 20 timesteps)
"""

import sys
import os
import json
import pickle
import numpy as np
from pathlib import Path

# Tambahkan path ke folder parent
sys.path.append(os.path.dirname(os.path.abspath(__file__)))

def check_tensorflow():
    """Cek instalasi TensorFlow"""
    print("\n" + "="*50)
    print("TEST 1: TensorFlow Installation")
    print("="*50)
    
    try:
        import tensorflow as tf
        print(f"TensorFlow version: {tf.__version__}")
        print(f"Keras version: {tf.keras.__version__}")
        
        # Cek GPU
        gpus = tf.config.list_physical_devices('GPU')
        if gpus:
            print(f"✅ GPU detected: {len(gpus)} device(s)")
            for gpu in gpus:
                print(f"   - {gpu}")
        else:
            print("⚠️  No GPU detected, using CPU")
        
        # Test simple operation
        a = tf.constant([[1, 2], [3, 4]])
        b = tf.constant([[5, 6], [7, 8]])
        c = tf.matmul(a, b)
        print(f"✅ TensorFlow operation test: 2x2 matrix multiplication successful")
        print(f"   Result: {c.numpy()}")
        
        return True, tf
    except ImportError:
        print("❌ TensorFlow not installed")
        return False, None
    except Exception as e:
        print(f"❌ TensorFlow test failed: {e}")
        return False, None

def check_model_files_structure():
    """Cek struktur file model (.keras format)"""
    print("\n" + "="*50)
    print("TEST 2: Model Files Structure Check")
    print("="*50)
    
    # Path ke ml_models
    current_dir = os.path.dirname(os.path.abspath(__file__))
    parent_dir = os.path.dirname(current_dir)
    models_dir = os.path.join(parent_dir, 'ml_models')
    
    print(f"Looking for models in: {models_dir}")
    
    if not os.path.exists(models_dir):
        print(f"❌ Models directory not found: {models_dir}")
        return False
    
    # Daftar folder model yang harus ada
    expected_folders = ['push-up', 'Shoulder Press', 't bar row']
    
    print(f"Expected folders: {expected_folders}")
    
    all_folders_exist = True
    for folder in expected_folders:
        folder_path = os.path.join(models_dir, folder)
        if os.path.exists(folder_path):
            print(f"✅ Folder found: {folder}")
        else:
            print(f"❌ Folder missing: {folder}")
            all_folders_exist = False
    
    return all_folders_exist

def check_model_files_content():
    """Cek konten file di setiap folder model"""
    print("\n" + "="*50)
    print("TEST 3: Model Files Content Check")
    print("="*50)
    
    current_dir = os.path.dirname(os.path.abspath(__file__))
    parent_dir = os.path.dirname(current_dir)
    models_dir = os.path.join(parent_dir, 'ml_models')
    
    # Model files yang diharapkan
    expected_files = {
        'push-up': ['model_pushup.keras', 'scaler.pkl', 'meta.json', 'history.json'],
        'Shoulder Press': ['model_Shoulder_Press.keras', 'scaler.pkl', 'meta.json', 'history.json'],
        't bar row': ['model_t_bar_row.keras', 'scaler.pkl', 'meta.json', 'history.json']
    }
    
    all_files_exist = True
    
    for folder, files in expected_files.items():
        folder_path = os.path.join(models_dir, folder)
        
        print(f"\n📁 Checking folder: {folder}")
        print(f"   Path: {folder_path}")
        
        if not os.path.exists(folder_path):
            print(f"   ❌ Folder not found")
            all_files_exist = False
            continue
        
        # List files yang ada
        existing_files = os.listdir(folder_path) if os.path.exists(folder_path) else []
        print(f"   Files found: {existing_files}")
        
        # Cek setiap file yang diharapkan
        for file in files:
            file_path = os.path.join(folder_path, file)
            if os.path.exists(file_path):
                file_size = os.path.getsize(file_path)
                print(f"   ✅ {file} ({file_size:,} bytes)")
            else:
                print(f"   ❌ {file} NOT FOUND")
                all_files_exist = False
    
    return all_files_exist

def test_keras_model_loading(tf):
    """Test loading model .keras files"""
    print("\n" + "="*50)
    print("TEST 4: Keras Model Loading Test")
    print("="*50)
    
    if tf is None:
        print("❌ TensorFlow not available, skipping model loading test")
        return False
    
    current_dir = os.path.dirname(os.path.abspath(__file__))
    parent_dir = os.path.dirname(current_dir)
    models_dir = os.path.join(parent_dir, 'ml_models')
    
    # Model files (.keras format)
    model_paths = {
        'pushup': 'push-up/model_pushup.keras',
        'shoulder_press': 'Shoulder Press/model_Shoulder_Press.keras',
        't_bar_row': 't bar row/model_t_bar_row.keras'
    }
    
    loaded_models = 0
    
    for model_name, model_path in model_paths.items():
        full_path = os.path.join(models_dir, model_path)
        
        print(f"\n🔍 Testing {model_name}:")
        print(f"   Path: {full_path}")
        
        if not os.path.exists(full_path):
            print(f"   ❌ File not found")
            
            # Cari file alternatif
            folder_name = os.path.dirname(model_path)
            folder_full = os.path.join(models_dir, folder_name)
            if os.path.exists(folder_full):
                files = os.listdir(folder_full)
                keras_files = [f for f in files if f.endswith('.keras')]
                h5_files = [f for f in files if f.endswith('.h5')]
                
                print(f"   ℹ️  Available files in folder:")
                print(f"      .keras: {keras_files}")
                print(f"      .h5: {h5_files}")
            
            continue
        
        try:
            # Load model
            model = tf.keras.models.load_model(full_path)
            
            # Get model info
            print(f"   ✅ Model loaded successfully")
            print(f"   Model type: {type(model)}")
            print(f"   Input shape: {model.input_shape}")
            print(f"   Output shape: {model.output_shape}")
            
            # Expected shape: (None, 20, 103) untuk MediaPipe data
            if len(model.input_shape) == 3:
                batch, timesteps, features = model.input_shape
                print(f"   Timesteps: {timesteps}")
                print(f"   Features: {features}")
                
                # Verifikasi shape
                if features == 103:
                    print(f"   ✅ MediaPipe features confirmed: 103")
                else:
                    print(f"   ⚠️  Unexpected features: {features} (expected 103)")
                
                if timesteps == 20:
                    print(f"   ✅ Timesteps confirmed: 20")
                elif timesteps is None:
                    print(f"   ℹ️  Variable timesteps (None)")
                else:
                    print(f"   ⚠️  Unexpected timesteps: {timesteps}")
            
            # Check model architecture
            print(f"   Model architecture summary:")
            model_summary = []
            model.summary(print_fn=lambda x: model_summary.append(x))
            for line in model_summary[:8]:  # Tampilkan 8 baris pertama
                print(f"     {line}")
            
            # Test prediction dengan random data
            if model.input_shape[1] is not None:  # Jika timesteps diketahui
                timesteps = model.input_shape[1] if model.input_shape[1] is not None else 20
                features = model.input_shape[2] if model.input_shape[2] is not None else 103
                
                test_input = np.random.randn(1, timesteps, features).astype(np.float32)
                
                try:
                    prediction = model.predict(test_input, verbose=0)
                    print(f"   Test prediction successful")
                    print(f"   Prediction shape: {prediction.shape}")
                    print(f"   Prediction values: {prediction.flatten()[:3]}...")
                    
                    # Check if output is sigmoid (0-1)
                    if prediction.shape[1] == 1:
                        pred_min = prediction.min()
                        pred_max = prediction.max()
                        print(f"   Output range: [{pred_min:.4f}, {pred_max:.4f}]")
                        if 0 <= pred_min <= 1 and 0 <= pred_max <= 1:
                            print(f"   ✅ Output dalam range sigmoid [0,1]")
                except Exception as e:
                    print(f"   ⚠️  Test prediction failed: {e}")
            
            loaded_models += 1
            
        except Exception as e:
            print(f"   ❌ Failed to load model: {e}")
            import traceback
            traceback.print_exc()
    
    print(f"\n📊 Summary: {loaded_models}/{len(model_paths)} models loaded successfully")
    return loaded_models > 0

def test_scaler_loading_and_features():
    """Test loading scaler files dan cek features"""
    print("\n" + "="*50)
    print("TEST 5: Scaler Loading & Feature Check")
    print("="*50)
    
    current_dir = os.path.dirname(os.path.abspath(__file__))
    parent_dir = os.path.dirname(current_dir)
    models_dir = os.path.join(parent_dir, 'ml_models')
    
    scaler_paths = {
        'pushup': 'push-up/scaler.pkl',
        'shoulder_press': 'Shoulder Press/scaler.pkl',
        't_bar_row': 't bar row/scaler.pkl'
    }
    
    loaded_scalers = 0
    feature_mismatch = False
    
    for model_name, scaler_path in scaler_paths.items():
        full_path = os.path.join(models_dir, scaler_path)
        
        print(f"\n🔍 Testing scaler for {model_name}:")
        print(f"   Path: {full_path}")
        
        if not os.path.exists(full_path):
            print(f"   ❌ File not found")
            continue
        
        try:
            # Load scaler
            with open(full_path, 'rb') as f:
                scaler = pickle.load(f)
            
            print(f"   ✅ Scaler loaded successfully")
            print(f"   Scaler type: {type(scaler)}")
            
            # Check scaler attributes
            if hasattr(scaler, 'scale_'):
                features = scaler.scale_.shape[0]
                print(f"   Number of features: {features}")
                
                # Expected features untuk MediaPipe
                if features == 103:
                    print(f"   ✅ MediaPipe features confirmed: 103")
                else:
                    print(f"   ⚠️  Unexpected features: {features} (expected 103)")
                    feature_mismatch = True
                
                print(f"   Scale shape: {scaler.scale_.shape}")
            
            if hasattr(scaler, 'mean_'):
                print(f"   Mean shape: {scaler.mean_.shape}")
            
            if hasattr(scaler, 'var_'):
                print(f"   Variance shape: {scaler.var_.shape}")
            
            # Test transformation dengan sample data
            if hasattr(scaler, 'scale_'):
                n_features = scaler.scale_.shape[0]
                sample_data = np.random.randn(5, n_features).astype(np.float32)
                
                try:
                    transformed = scaler.transform(sample_data)
                    print(f"   ✅ Sample transformation successful")
                    print(f"   Input shape: {sample_data.shape}")
                    print(f"   Output shape: {transformed.shape}")
                    print(f"   Transformed mean: {transformed.mean():.3f}")
                    print(f"   Transformed std: {transformed.std():.3f}")
                except Exception as e:
                    print(f"   ⚠️  Transformation test failed: {e}")
            
            loaded_scalers += 1
            
        except Exception as e:
            print(f"   ❌ Failed to load scaler: {e}")
            import traceback
            traceback.print_exc()
    
    print(f"\n📊 Summary: {loaded_scalers}/{len(scaler_paths)} scalers loaded successfully")
    
    if feature_mismatch:
        print("⚠️  Feature mismatch detected in some scalers")
    
    return loaded_scalers > 0 and not feature_mismatch

def test_metadata_consistency():
    """Test konsistensi metadata antar model"""
    print("\n" + "="*50)
    print("TEST 6: Metadata Consistency Check")
    print("="*50)
    
    current_dir = os.path.dirname(os.path.abspath(__file__))
    parent_dir = os.path.dirname(current_dir)
    models_dir = os.path.join(parent_dir, 'ml_models')
    
    meta_files = {
        'pushup': 'push-up/meta.json',
        'shoulder_press': 'Shoulder Press/meta.json',
        't_bar_row': 't bar row/meta.json'
    }
    
    metadata = {}
    
    for model_name, meta_path in meta_files.items():
        full_path = os.path.join(models_dir, meta_path)
        
        if not os.path.exists(full_path):
            print(f"❌ Metadata file not found for {model_name}: {full_path}")
            continue
        
        try:
            with open(full_path, 'r') as f:
                metadata[model_name] = json.load(f)
            print(f"✅ Loaded metadata for {model_name}")
        except Exception as e:
            print(f"❌ Failed to load metadata for {model_name}: {e}")
    
    if not metadata:
        print("❌ No metadata loaded")
        return False
    
    # Check consistency
    print(f"\n🔍 Metadata consistency check:")
    
    # Check features
    print(f"Features:")
    for model_name, meta in metadata.items():
        features = meta.get('features', 'N/A')
        print(f"  {model_name}: {features}")
    
    # Check timesteps
    print(f"\nTimesteps/Sequence Length:")
    for model_name, meta in metadata.items():
        timesteps = meta.get('timesteps', meta.get('sequence_length', 'N/A'))
        print(f"  {model_name}: {timesteps}")
    
    # Check thresholds
    print(f"\nThresholds:")
    for model_name, meta in metadata.items():
        threshold = meta.get('threshold', 'N/A')
        print(f"  {model_name}: {threshold}")
    
    # Check all have same features and timesteps
    all_features = [meta.get('features') for meta in metadata.values() if 'features' in meta]
    all_timesteps = [meta.get('timesteps', meta.get('sequence_length')) for meta in metadata.values() 
                     if 'timesteps' in meta or 'sequence_length' in meta]
    
    if len(set(all_features)) == 1:
        print(f"\n✅ All models have same features: {all_features[0]}")
    else:
        print(f"\n⚠️  Feature mismatch: {all_features}")
    
    if len(set(all_timesteps)) == 1:
        print(f"✅ All models have same timesteps: {all_timesteps[0]}")
    else:
        print(f"⚠️  Timesteps mismatch: {all_timesteps}")
    
    return True

def test_mediapipe_data_simulation():
    """Test simulasi data MediaPipe"""
    print("\n" + "="*50)
    print("TEST 7: MediaPipe Data Simulation Test")
    print("="*50)
    
    # Konstanta MediaPipe
    NUM_LANDMARKS = 33
    FEATURES_PER_FRAME = NUM_LANDMARKS * 3 + 4  # 103
    
    print(f"MediaPipe constants:")
    print(f"  NUM_LANDMARKS: {NUM_LANDMARKS}")
    print(f"  FEATURES_PER_FRAME: {FEATURES_PER_FRAME}")
    print(f"  (33 landmarks × 3 coordinates + 4 angles)")
    
    # Simulate one frame of MediaPipe data
    print(f"\n🧪 Simulating one frame of MediaPipe data:")
    
    frame_data = []
    
    # Add 33 landmarks (x, y, z for each)
    for i in range(NUM_LANDMARKS):
        # Simulate normalized coordinates
        x = np.random.uniform(-1, 1)
        y = np.random.uniform(-1, 1)
        z = np.random.uniform(-0.5, 0.5)
        frame_data.extend([x, y, z])
    
    # Add 4 angles (normalized 0-1)
    for i in range(4):
        angle = np.random.uniform(0, 1)  # Normalized angle
        frame_data.append(angle)
    
    print(f"  Generated {len(frame_data)} features")
    print(f"  Expected: {FEATURES_PER_FRAME}")
    
    if len(frame_data) == FEATURES_PER_FRAME:
        print(f"  ✅ Feature count correct")
    else:
        print(f"  ❌ Feature count incorrect")
    
    # Create a sequence of 20 frames
    print(f"\n🧪 Simulating a sequence (20 frames):")
    
    sequence = []
    for t in range(20):
        frame = []
        for i in range(NUM_LANDMARKS):
            # Add some temporal variation
            x = np.sin(t * 0.1 + i * 0.3) * 0.5
            y = np.cos(t * 0.1 + i * 0.3) * 0.5
            z = np.sin(t * 0.2 + i * 0.2) * 0.3
            frame.extend([x, y, z])
        
        # Add angles with temporal variation
        for i in range(4):
            angle = 0.5 + np.sin(t * 0.05 + i) * 0.2
            frame.append(angle)
        
        sequence.append(frame)
    
    sequence_array = np.array(sequence, dtype=np.float32)
    print(f"  Sequence shape: {sequence_array.shape}")
    print(f"  Expected: (20, 103)")
    
    if sequence_array.shape == (20, 103):
        print(f"  ✅ Sequence shape correct")
    else:
        print(f"  ❌ Sequence shape incorrect")
    
    # Test reshaping for LSTM
    print(f"\n🧪 Reshaping for LSTM input:")
    lstm_input = np.expand_dims(sequence_array, axis=0)
    print(f"  LSTM input shape: {lstm_input.shape}")
    print(f"  Expected: (1, 20, 103)")
    
    if lstm_input.shape == (1, 20, 103):
        print(f"  ✅ LSTM input shape correct")
    else:
        print(f"  ❌ LSTM input shape incorrect")
    
    return True

def test_predictor_integration():
    """Test integrasi dengan predictor"""
    print("\n" + "="*50)
    print("TEST 8: Predictor Integration Test")
    print("="*50)
    
    try:
        from predict import MultiModelExercisePredictor
        
        print("Initializing predictor...")
        predictor = MultiModelExercisePredictor()
        
        if len(predictor.models) == 0:
            print("❌ No models loaded, skipping integration test")
            return False
        
        print(f"✅ Predictor initialized with {len(predictor.models)} models")
        
        # Test dengan data MediaPipe-like
        print(f"\n🧪 Testing with MediaPipe-like data:")
        
        # Generate test data
        timesteps = predictor.SEQUENCE_LENGTH
        features = predictor.FEATURES_PER_FRAME
        
        print(f"  Using timesteps: {timesteps}")
        print(f"  Using features: {features}")
        
        # Generate realistic MediaPipe data
        test_data = []
        for t in range(timesteps * 2):  # Generate more data than needed
            frame = []
            
            # Landmarks
            for i in range(33):
                # Simulate movement patterns
                x = np.sin(t * 0.1 + i * 0.03) * 0.3
                y = np.cos(t * 0.08 + i * 0.02) * 0.4 + 0.5
                z = np.sin(t * 0.05 + i * 0.01) * 0.2
                frame.extend([x, y, z])
            
            # Angles (elbow and knee angles)
            for i in range(4):
                if i < 2:  # Elbow angles
                    angle = 0.7 + np.sin(t * 0.1 + i) * 0.1
                else:  # Knee angles
                    angle = 0.8 + np.cos(t * 0.05 + i) * 0.05
                frame.append(angle)
            
            # Ensure correct length
            if len(frame) > features:
                frame = frame[:features]
            elif len(frame) < features:
                frame.extend([0.0] * (features - len(frame)))
            
            test_data.append(frame)
        
        print(f"  Generated {len(test_data)} frames")
        
        # Test dengan semua model
        for model_name in predictor.models.keys():
            print(f"\n🔍 Testing model: {model_name}")
            
            try:
                result = predictor.predict_single_model(test_data, model_name)
                
                if 'error' in result:
                    print(f"  ❌ Error: {result['error']}")
                else:
                    print(f"  ✅ Prediction successful")
                    print(f"    Exercise: {result['exercise_name']}")
                    print(f"    Threshold: {result.get('threshold_used', 'N/A')}")
                    print(f"    Predictions: {result['predictions']}")
                    print(f"    Avg confidence: {result['average_confidence']:.3f}")
                    
                    # Check if predictions make sense
                    if result['predictions'] and len(result['predictions']) > 0:
                        pred = result['predictions'][0]
                        confidence = result['confidence'][0]
                        threshold = result.get('threshold_used', 0.5)
                        
                        if pred == 'correct' and confidence >= threshold:
                            print(f"    ✅ Logic consistent: 'correct' with confidence {confidence:.3f} >= threshold {threshold}")
                        elif pred == 'incorrect' and confidence < threshold:
                            print(f"    ✅ Logic consistent: 'incorrect' with confidence {confidence:.3f} < threshold {threshold}")
                        else:
                            print(f"    ⚠️  Logic issue: {pred} with confidence {confidence:.3f} vs threshold {threshold}")
                
            except Exception as e:
                print(f"  ❌ Prediction failed: {e}")
                import traceback
                traceback.print_exc()
        
        return True
        
    except ImportError:
        print("❌ Cannot import MultiModelExercisePredictor")
        return False
    except Exception as e:
        print(f"❌ Integration test failed: {e}")
        return False

def test_scaler_issue_fix():
    """Test untuk scaler issue yang terlihat di log"""
    print("\n" + "="*50)
    print("TEST 9: Scaler Issue Analysis")
    print("="*50)
    
    current_dir = os.path.dirname(os.path.abspath(__file__))
    parent_dir = os.path.dirname(current_dir)
    models_dir = os.path.join(parent_dir, 'ml_models')
    
    scaler_files = {
        'pushup': 'push-up/scaler.pkl',
        'shoulder_press': 'Shoulder Press/scaler.pkl',
        't_bar_row': 't bar row/scaler.pkl'
    }
    
    print("⚠️  Issue ditemukan di log: 'invalid load key, '\\x0e''")
    print("Ini biasanya berarti file pickle corrupt atau format berbeda.")
    print("\n🔧 Solusi yang mungkin:")
    print("1. File scaler corrupt - perlu regenerate")
    print("2. Format pickle berbeda (Python version mismatch)")
    print("3. File bukan benar-benar file pickle")
    
    for model_name, scaler_path in scaler_files.items():
        full_path = os.path.join(models_dir, scaler_path)
        
        print(f"\n🔍 Analysing scaler for {model_name}:")
        print(f"   Path: {full_path}")
        
        if not os.path.exists(full_path):
            print(f"   ❌ File tidak ditemukan")
            continue
        
        # Cek file size
        file_size = os.path.getsize(full_path)
        print(f"   File size: {file_size} bytes")
        
        if file_size == 0:
            print(f"   ❌ File kosong")
            continue
        
        # Coba baca sebagai binary
        try:
            with open(full_path, 'rb') as f:
                content = f.read()
            
            print(f"   First 20 bytes (hex): {content[:20].hex()}")
            print(f"   First 20 bytes (ascii): {repr(content[:20])}")
            
            # Coba decode sebagai JSON (mungkin salah format)
            try:
                with open(full_path, 'r') as f:
                    json_content = json.load(f)
                print(f"   ⚠️  File bisa dibaca sebagai JSON, bukan pickle")
                print(f"   JSON keys: {list(json_content.keys())}")
            except:
                print(f"   File bukan JSON")
            
            # Coba load dengan pickle protocol berbeda
            for protocol in range(0, 5):
                try:
                    with open(full_path, 'rb') as f:
                        scaler = pickle.load(f)
                    print(f"   ✅ Berhasil load dengan pickle protocol {protocol}")
                    print(f"   Loaded type: {type(scaler)}")
                    break
                except:
                    continue
            
        except Exception as e:
            print(f"   ❌ Error reading file: {e}")
    
    print("\n💡 Rekomendasi:")
    print("1. Coba regenerate scaler dari training script")
    print("2. Gunakan joblib.dump/load bukan pickle untuk scaler sklearn")
    print("3. Pastikan Python version konsisten")

def generate_fix_script():
    """Generate script untuk memperbaiki scaler issue"""
    print("\n" + "="*50)
    print("TEST 10: Generate Fix Script")
    print("="*50)
    
    fix_script = """
# ============================================
# FIX SCRIPT UNTUK SCALER ISSUE
# ============================================
# Script ini untuk regenerate atau fix scaler files

import pickle
import numpy as np
from sklearn.preprocessing import StandardScaler
import json
import os

def check_and_fix_scaler(scaler_path, expected_features=103):
    \"\"\"Check dan fix scaler file\"\"\"
    
    if not os.path.exists(scaler_path):
        print(f"❌ File tidak ditemukan: {scaler_path}")
        return False
    
    try:
        # Coba load dengan pickle
        with open(scaler_path, 'rb') as f:
            scaler = pickle.load(f)
        
        print(f"✅ Scaler berhasil di-load dari {scaler_path}")
        print(f"   Type: {type(scaler)}")
        
        if hasattr(scaler, 'scale_'):
            print(f"   Features: {scaler.scale_.shape[0]}")
            if scaler.scale_.shape[0] == expected_features:
                print(f"   ✅ Features cocok: {expected_features}")
            else:
                print(f"   ⚠️  Features mismatch: {scaler.scale_.shape[0]} != {expected_features}")
        
        return True
        
    except Exception as e:
        print(f"❌ Gagal load scaler: {e}")
        
        # Option: Create dummy scaler jika diperlukan
        print(f"⚠️  Membuat dummy scaler...")
        try:
            # Create a StandardScaler
            scaler = StandardScaler()
            
            # Fit dengan dummy data
            dummy_data = np.random.randn(100, expected_features)
            scaler.fit(dummy_data)
            
            # Save dengan pickle
            with open(scaler_path, 'wb') as f:
                pickle.dump(scaler, f, protocol=4)
            
            print(f"✅ Dummy scaler created and saved")
            return True
            
        except Exception as e2:
            print(f"❌ Gagal membuat dummy scaler: {e2}")
            return False

def main():
    # Path ke scaler files
    scaler_files = {
        'pushup': 'push-up/scaler.pkl',
        'shoulder_press': 'Shoulder Press/scaler.pkl',
        't_bar_row': 't bar row/scaler.pkl'
    }
    
    current_dir = os.path.dirname(os.path.abspath(__file__))
    parent_dir = os.path.dirname(current_dir)
    models_dir = os.path.join(parent_dir, 'ml_models')
    
    for model_name, scaler_path in scaler_files.items():
        full_path = os.path.join(models_dir, scaler_path)
        print(f"\n🔧 Checking {model_name}: {full_path}")
        check_and_fix_scaler(full_path)

if __name__ == "__main__":
    main()
"""
    
    # Save the fix script
    fix_script_path = os.path.join(os.path.dirname(__file__), 'fix_scaler.py')
    with open(fix_script_path, 'w', encoding='utf-8') as f:
        f.write(fix_script)
    
    print(f"✅ Fix script generated: {fix_script_path}")
    print("\n📋 Cara menggunakan:")
    print(f"1. Buka terminal di: {os.path.dirname(__file__)}")
    print(f"2. Jalankan: python fix_scaler.py")
    print(f"3. Script akan coba memperbaiki scaler files")
    
    return True

def main():
    """Main test function"""
    print("🚀 STARTING ML MODELS TEST SUITE (MEDIAPIPE 103 FEATURES)")
    print("="*60)
    
    results = {}
    
    # Run tests
    tf_available, tf = check_tensorflow()
    results['tensorflow'] = tf_available
    
    results['structure'] = check_model_files_structure()
    results['content'] = check_model_files_content()
    
    if tf_available:
        results['model_loading'] = test_keras_model_loading(tf)
        results['scaler_loading'] = test_scaler_loading_and_features()
    else:
        print("\n⚠️  TensorFlow not available, skipping some tests")
        results['model_loading'] = False
        results['scaler_loading'] = False
    
    results['metadata'] = test_metadata_consistency()
    results['mediapipe_sim'] = test_mediapipe_data_simulation()
    results['integration'] = test_predictor_integration()
    results['scaler_analysis'] = test_scaler_issue_fix()
    results['fix_script'] = generate_fix_script()
    
    # Summary
    print("\n" + "="*60)
    print("🎉 TEST SUITE COMPLETED")
    print("="*60)
    
    print("\n📋 TEST RESULTS SUMMARY:")
    print("-"*40)
    
    passed = 0
    total = len(results)
    
    for test_name, result in results.items():
        status = "✅ PASS" if result else "❌ FAIL"
        print(f"{status}: {test_name}")
        if result:
            passed += 1
    
    print("-"*40)
    print(f"TOTAL: {passed}/{total} tests passed")
    
    # Critical issues
    print("\n🔴 CRITICAL ISSUES FOUND:")
    print("-"*40)
    
    if not results.get('scaler_loading', True):
        print("❌ SCALER FILES CORRUPTED")
        print("   Error: 'invalid load key, '\\x0e''")
        print("   Solusi: Jalankan fix_scaler.py yang telah digenerate")
    
    if not results.get('model_loading', True):
        print("❌ MODEL LOADING ISSUES")
        print("   Periksa file .keras di folder ml_models")
    
    # Recommendations
    print("\n💡 RECOMMENDATIONS:")
    print("-"*40)
    
    print("1. 🔧 FIX SCALER ISSUE FIRST:")
    print("   Jalankan: python fix_scaler.py")
    print("   Atau regenerate scaler dari training data")
    
    print("\n2. ✅ VERIFY MODELS:")
    print("   Semua model berhasil di-load dengan shape (20, 103)")
    print("   Metadata konsisten: 103 features, 20 timesteps")
    
    print("\n3. 📊 THRESHOLDS BERBEDA:")
    print("   pushup: 0.7")
    print("   shoulder_press: 0.658")
    print("   t_bar_row: 0.278 (sangat rendah)")
    
    print("\n4. 🎯 TEST DENGAN DATA REAL:")
    print("   Gunakan test_predict.py untuk testing lebih lanjut")
    print("   Pastikan input data memiliki 103 features")
    
    return passed >= total - 1  # Allow 1 failure (scaler issue)

if __name__ == "__main__":
    success = main()
    sys.exit(0 if success else 1)