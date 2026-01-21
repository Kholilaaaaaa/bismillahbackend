"""
Test script untuk menguji predict.py dengan berbagai skenario
Mendukung format .keras dan konfigurasi terbaru
"""

import sys
import os
import json
import numpy as np
from pathlib import Path

# Tambahkan path ke predict.py
sys.path.append(os.path.dirname(os.path.abspath(__file__)))

# Import predictor
try:
    from predict import MultiModelExercisePredictor
    print("✅ Berhasil mengimpor MultiModelExercisePredictor")
except ImportError as e:
    print(f"❌ Gagal mengimpor: {e}")
    sys.exit(1)

def test_predictor_initialization():
    """Test inisialisasi predictor"""
    print("\n" + "="*50)
    print("TEST 1: Inisialisasi Predictor")
    print("="*50)
    
    try:
        predictor = MultiModelExercisePredictor()
        print(f"✅ Predictor berhasil diinisialisasi")
        print(f"   Models directory: {predictor.models_dir}")
        print(f"   Models loaded: {len(predictor.models)} dari {len(predictor.model_configs)}")
        print(f"   Available models: {list(predictor.model_configs.keys())}")
        print(f"   SEQUENCE_LENGTH: {predictor.SEQUENCE_LENGTH}")
        print(f"   FEATURES_PER_FRAME: {predictor.FEATURES_PER_FRAME}")
        
        return predictor
    except Exception as e:
        print(f"❌ Gagal menginisialisasi predictor: {e}")
        return None

def test_model_configs(predictor):
    """Test konfigurasi model"""
    print("\n" + "="*50)
    print("TEST 2: Konfigurasi Model")
    print("="*50)
    
    if not predictor:
        print("❌ Predictor tidak tersedia")
        return
    
    print("📋 Model configurations:")
    for model_key, config in predictor.model_configs.items():
        print(f"\n  Model: {model_key}")
        print(f"    Folder: {config['folder']}")
        print(f"    Model file: {config['model_file']} (format .keras)")
        print(f"    Scaler file: {config.get('scaler_file', 'N/A')}")
        print(f"    Meta file: {config.get('meta', 'N/A')}")
        
        # Cek labels
        labels = predictor.model_labels.get(model_key, [])
        print(f"    Labels: {labels} (index 0={labels[0] if labels else 'N/A'}, 1={labels[1] if len(labels)>1 else 'N/A'})")

def test_model_loading(predictor):
    """Test loading model"""
    print("\n" + "="*50)
    print("TEST 3: Pengecekan Model yang Terload")
    print("="*50)
    
    if not predictor:
        print("❌ Predictor tidak tersedia")
        return
    
    for model_key in predictor.model_configs.keys():
        if model_key in predictor.models:
            print(f"✅ Model '{model_key}' berhasil di-load")
            model = predictor.models[model_key]
            print(f"   Model type: {type(model)}")
            print(f"   Input shape: {model.input_shape}")
            print(f"   Output shape: {model.output_shape}")
            
            # Cek apakah input shape sesuai dengan SEQUENCE_LENGTH
            if len(model.input_shape) >= 2:
                expected_timesteps = predictor.SEQUENCE_LENGTH
                actual_timesteps = model.input_shape[1]
                if actual_timesteps is not None:
                    if actual_timesteps == expected_timesteps:
                        print(f"   ✅ Timesteps cocok: {actual_timesteps}")
                    else:
                        print(f"   ⚠️  Timesteps mismatch: expected {expected_timesteps}, got {actual_timesteps}")
        else:
            print(f"❌ Model '{model_key}' GAGAL di-load")
    
    # Cek metadata
    print(f"\n📊 Metadata tersedia untuk {len(predictor.metadata)} model")
    for model_key, meta in predictor.metadata.items():
        print(f"   - {model_key}:")
        print(f"     Features: {meta.get('features', 'N/A')}")
        print(f"     Timesteps: {meta.get('timesteps', meta.get('sequence_length', 'N/A'))}")
        print(f"     Threshold: {meta.get('threshold', 'N/A')}")
        if 'threshold' in meta:
            predictor_threshold = predictor.thresholds.get(model_key, 'N/A')
            print(f"     Threshold loaded: {predictor_threshold}")

def test_scalers_loading(predictor):
    """Test loading scalers"""
    print("\n" + "="*50)
    print("TEST 4: Pengecekan Scaler yang Terload")
    print("="*50)
    
    if not predictor:
        print("❌ Predictor tidak tersedia")
        return
    
    print(f"📊 Scalers tersedia untuk {len(predictor.scalers)} model")
    for model_key, scaler in predictor.scalers.items():
        print(f"   - {model_key}: {type(scaler)}")
        if hasattr(scaler, 'scale_'):
            print(f"     Scale shape: {scaler.scale_.shape}")
            expected_features = predictor.FEATURES_PER_FRAME
            actual_features = scaler.scale_.shape[0]
            if actual_features == expected_features:
                print(f"     ✅ Features cocok: {actual_features}")
            else:
                print(f"     ⚠️  Features mismatch: expected {expected_features}, got {actual_features}")
        if hasattr(scaler, 'mean_'):
            print(f"     Mean shape: {scaler.mean_.shape}")

def test_mock_prediction(predictor):
    """Test prediksi dengan mode mock"""
    print("\n" + "="*50)
    print("TEST 5: Mock Prediction")
    print("="*50)
    
    test_data = [
        [1.0, 2.0, 3.0, 4.0, 5.0, 6.0],
        [1.5, 2.5, 3.5, 4.5, 5.5, 6.5],
        [0.5, 1.5, 2.5, 3.5, 4.5, 5.5]
    ]
    
    for model_key in predictor.model_configs.keys():
        try:
            result = predictor.mock_prediction(model_key)
            print(f"\n📈 Mock prediction untuk '{model_key}':")
            print(f"   Exercise: {result['exercise_name']}")
            print(f"   Labels order: {predictor.model_labels.get(model_key)}")
            print(f"   Correct: {result['correct_count']}/{result['total_count']}")
            print(f"   Percentage: {result['correctness_percentage']:.1f}%")
            print(f"   Is correct overall: {result['is_correct_overall']}")
            print(f"   Predictions: {result['predictions']}")
            if 'note' in result:
                print(f"   Note: {result['note']}")
        except Exception as e:
            print(f"❌ Gagal mock prediction untuk '{model_key}': {e}")

def test_preprocessing_logic():
    """Test logika preprocessing"""
    print("\n" + "="*50)
    print("TEST 6: Preprocessing Logic Test")
    print("="*50)
    
    try:
        from predict import MultiModelExercisePredictor
        predictor = MultiModelExercisePredictor()
        
        # Test 1: Reshape 2D ke 3D
        print("\n🧪 Test 1: Reshape 2D ke 3D")
        test_data_2d = np.random.randn(20, 103).astype(np.float32)  # 20 timesteps, 103 features
        print(f"   Input shape: {test_data_2d.shape}")
        
        # Simulate preprocessing
        if test_data_2d.ndim == 2:
            processed = np.expand_dims(test_data_2d, axis=0)
            print(f"   Processed shape: {processed.shape}")
            if processed.shape == (1, 20, 103):
                print("   ✅ Reshape berhasil")
            else:
                print(f"   ❌ Reshape gagal: expected (1, 20, 103), got {processed.shape}")
        
        # Test 2: Feature truncation
        print("\n🧪 Test 2: Feature truncation (lebih banyak fitur)")
        test_data_extra = np.random.randn(20, 110).astype(np.float32)  # 110 features
        print(f"   Input shape: {test_data_extra.shape}")
        
        # Simulate truncation
        if test_data_extra.shape[1] > 103:
            truncated = test_data_extra[:, :103]
            print(f"   Truncated shape: {truncated.shape}")
            print(f"   ✅ Truncation berhasil: {truncated.shape[1]} features")
        
        # Test 3: Padding
        print("\n🧪 Test 3: Sequence padding (lebih pendek)")
        test_data_short = np.random.randn(15, 103).astype(np.float32)  # 15 timesteps
        print(f"   Input shape: {test_data_short.shape}")
        
        sequence_length = predictor.SEQUENCE_LENGTH
        if test_data_short.shape[0] < sequence_length:
            pad_width = ((0, sequence_length - test_data_short.shape[0]), (0, 0))
            padded = np.pad(test_data_short, pad_width, mode='constant', constant_values=0)
            print(f"   Padded shape: {padded.shape}")
            print(f"   ✅ Padding berhasil")
        
        # Test 4: Truncate panjang
        print("\n🧪 Test 4: Sequence truncation (lebih panjang)")
        test_data_long = np.random.randn(25, 103).astype(np.float32)  # 25 timesteps
        print(f"   Input shape: {test_data_long.shape}")
        
        if test_data_long.shape[0] > sequence_length:
            truncated = test_data_long[:sequence_length, :]
            print(f"   Truncated shape: {truncated.shape}")
            print(f"   ✅ Truncation berhasil")
        
        return True
    except Exception as e:
        print(f"❌ Preprocessing test failed: {e}")
        return False

def test_real_prediction(predictor):
    """Test prediksi dengan data real"""
    print("\n" + "="*50)
    print("TEST 7: Real Prediction")
    print("="*50)
    
    if not predictor or len(predictor.models) == 0:
        print("⚠️  Tidak ada model yang terload, menggunakan mock data")
        test_mock_prediction(predictor)
        return
    
    # Generate synthetic data berdasarkan metadata atau default
    for model_key in predictor.model_configs.keys():
        # Cek apakah model terload
        if model_key not in predictor.models:
            print(f"\n⚠️  Model '{model_key}' tidak terload, skip")
            continue
        
        # Ambil metadata
        meta = predictor.metadata.get(model_key, {})
        
        # Tentukan jumlah fitur
        if 'features' in meta:
            features = int(meta['features'])
            print(f"\n🧪 Testing model '{model_key}' dengan {features} features dari metadata")
        else:
            features = predictor.FEATURES_PER_FRAME
            print(f"\n🧪 Testing model '{model_key}' dengan default {features} features")
        
        # Tentukan sequence length
        if 'timesteps' in meta:
            timesteps = int(meta['timesteps'])
        elif 'sequence_length' in meta:
            timesteps = int(meta['sequence_length'])
        else:
            timesteps = predictor.SEQUENCE_LENGTH
        
        print(f"   Sequence length: {timesteps}")
        
        # Generate synthetic data yang mirip dengan data training
        # Data training biasanya dari MediaPipe landmarks (103 features)
        test_data = []
        
        # Generate beberapa sequences
        for seq_idx in range(3):
            sequence = []
            for t in range(timesteps):
                # Generate data yang menyerupai MediaPipe landmarks
                # 33 landmarks * 3 (x,y,z) + 4 angles = 103 features
                frame_data = []
                
                # Landmarks (0-98)
                for i in range(33):
                    # Simulasi data landmarks yang normal
                    frame_data.append(np.sin(t * 0.1 + i * 0.3) * 0.5)  # x
                    frame_data.append(np.cos(t * 0.1 + i * 0.3) * 0.5)  # y
                    frame_data.append(np.sin(t * 0.2 + i * 0.2) * 0.3)  # z
                
                # Angles (99-102)
                for i in range(4):
                    frame_data.append(np.sin(t * 0.05 + i) * 0.2)  # angle features
                
                # Pastikan panjangnya benar
                if len(frame_data) > features:
                    frame_data = frame_data[:features]
                elif len(frame_data) < features:
                    frame_data.extend([0.0] * (features - len(frame_data)))
                
                sequence.append(frame_data)
            
            test_data.append(sequence)
        
        # Flatten untuk input (sesuai dengan predict.py)
        flattened_data = []
        for sequence in test_data:
            for timestep in sequence:
                flattened_data.append(timestep)
        
        print(f"   Generated data shape: {len(flattened_data)} frames, {len(flattened_data[0])} features")
        
        try:
            result = predictor.predict_single_model(flattened_data, model_key)
            
            if 'error' in result:
                print(f"   ❌ Error: {result['error']}")
            else:
                print(f"   ✅ Prediction successful")
                print(f"   Exercise: {result['exercise_name']}")
                print(f"   Threshold used: {result.get('threshold_used', 'N/A')}")
                print(f"   Sequence length used: {result.get('sequence_length_used', 'N/A')}")
                print(f"   Correct: {result['correct_count']}/{result['total_count']}")
                print(f"   Percentage: {result['correctness_percentage']:.1f}%")
                print(f"   Avg confidence: {result['average_confidence']:.3f}")
                print(f"   Is correct overall: {result['is_correct_overall']}")
                print(f"   Predictions: {result['predictions'][:3]}...")  # Tampilkan 3 pertama
                
        except Exception as e:
            print(f"   ❌ Prediction failed: {e}")
            import traceback
            traceback.print_exc()

def test_error_cases(predictor):
    """Test kasus error"""
    print("\n" + "="*50)
    print("TEST 8: Error Cases")
    print("="*50)
    
    if not predictor:
        print("❌ Predictor tidak tersedia")
        return
    
    # Test 1: Model tidak tersedia
    print("\n1. Model tidak tersedia:")
    try:
        result = predictor.predict_single_model([[1]*103 for _ in range(20)], 'squat')
        print(f"   Result error: {result.get('error', 'No error')}")
    except Exception as e:
        print(f"   Exception: {e}")
    
    # Test 2: Data kosong
    print("\n2. Data kosong:")
    try:
        result = predictor.predict_single_model([], 'pushup')
        print(f"   Result error: {result.get('error', 'No error')}")
    except Exception as e:
        print(f"   Exception: {e}")
    
    # Test 3: Data dengan fitur terlalu sedikit
    print("\n3. Data dengan fitur terlalu sedikit:")
    try:
        # 103 adalah default, coba dengan 50
        result = predictor.predict_single_model([[1]*50 for _ in range(20)], 'pushup')
        print(f"   Result error: {result.get('error', 'No error')}")
    except Exception as e:
        print(f"   Exception: {e}")
    
    # Test 4: Data dengan fitur terlalu banyak (harusnya di-truncate)
    print("\n4. Data dengan fitur terlalu banyak (120 fitur):")
    try:
        # Coba dengan 120 fitur (lebih dari 103)
        result = predictor.predict_single_model([[1]*120 for _ in range(20)], 'pushup')
        if 'error' in result:
            print(f"   ❌ Error: {result['error']}")
        else:
            print(f"   ✅ Prediction successful (features truncated)")
    except Exception as e:
        print(f"   Exception: {e}")

def test_exercise_detection(predictor):
    """Test deteksi otomatis exercise"""
    print("\n" + "="*50)
    print("TEST 9: Exercise Detection")
    print("="*50)
    
    if not predictor or len(predictor.models) == 0:
        print("⚠️  Tidak ada model yang terload, menggunakan mock detection")
        result = predictor.detect_exercise_type([]) if predictor else {'error': 'No predictor'}
        print(f"Mock detection: {result}")
        return
    
    # Generate test data yang mirip dengan pushup
    timesteps = predictor.SEQUENCE_LENGTH
    features = predictor.FEATURES_PER_FRAME
    
    print(f"Generating test data: {timesteps} timesteps, {features} features")
    
    test_data = []
    for t in range(timesteps):
        frame_data = []
        
        # Simulasi data MediaPipe
        # Landmarks
        for i in range(33):
            # Pushup pattern: vertical movement
            vertical_pos = np.sin(t * 0.3) * 0.8
            frame_data.append(np.sin(t * 0.1 + i) * 0.2)      # x
            frame_data.append(vertical_pos + np.random.normal(0, 0.1))  # y (vertical)
            frame_data.append(np.cos(t * 0.1 + i) * 0.2)      # z
        
        # Angles (elbow angles untuk pushup)
        for i in range(4):
            if i < 2:  # Elbow angles
                angle = 150 + np.sin(t * 0.5) * 40  # 110-190 degrees
                frame_data.append(angle / 180.0)  # Normalized
            else:  # Knee angles
                frame_data.append(0.8 + np.random.normal(0, 0.05))
        
        # Pastikan panjangnya benar
        if len(frame_data) > features:
            frame_data = frame_data[:features]
        
        test_data.append(frame_data)
    
    print(f"Test data shape: {len(test_data)} frames, {len(test_data[0])} features")
    
    try:
        detection = predictor.detect_exercise_type(test_data)
        
        if 'error' in detection:
            print(f"❌ Detection error: {detection['error']}")
        else:
            print(f"✅ Detection successful")
            print(f"   Detected exercise: {detection['detected_exercise']}")
            print(f"   Exercise name: {detection['exercise_name']}")
            print(f"   Is confident: {detection.get('is_confident', False)}")
            
            if 'confidence_scores' in detection:
                print(f"   Confidence scores:")
                for model, score in detection['confidence_scores'].items():
                    print(f"     - {model}: {score:.4f}")
    
    except Exception as e:
        print(f"❌ Detection failed: {e}")
        import traceback
        traceback.print_exc()

def test_integration():
    """Test integrasi dengan input/output seperti dari PHP"""
    print("\n" + "="*50)
    print("TEST 10: Integration Test (PHP-like)")
    print("="*50)
    
    # Ambil predictor untuk mendapatkan konfigurasi
    try:
        from predict import MultiModelExercisePredictor
        predictor = MultiModelExercisePredictor()
        
        # Cari model yang terload
        loaded_models = [m for m in predictor.model_configs.keys() if m in predictor.models]
        if not loaded_models:
            print("❌ Tidak ada model yang terload")
            return
            
        model_name = loaded_models[0]
        meta = predictor.metadata.get(model_name, {})
        features = meta.get('features', predictor.FEATURES_PER_FRAME)
        timesteps = predictor.SEQUENCE_LENGTH
        
        # Simulasikan input dari PHP
        test_inputs = [
            # Case 1: Specific model request dengan data MediaPipe-like
            {
                'model': model_name,
                'sequence_data': [[float((i + j) % 100) / 100.0 for j in range(features)] 
                                 for i in range(timesteps * 2)]  # Lebih banyak data
            },
            # Case 2: Auto-detection
            {
                'sequence_data': [[np.sin(i * 0.1 + j * 0.01) for j in range(features)] 
                                 for i in range(timesteps)]
            },
            # Case 3: Invalid model
            {
                'model': 'invalid_model',
                'sequence_data': [[1.0] * features for _ in range(10)]
            },
            # Case 4: No sequence data
            {
                'model': model_name
            }
        ]
        
        for i, test_input in enumerate(test_inputs):
            print(f"\nTest case {i+1}:")
            
            # Simulate predict.py behavior
            predictor_local = MultiModelExercisePredictor()
            
            # Check if specific model is requested
            if 'model' in test_input and 'sequence_data' in test_input:
                if test_input['model'] not in predictor_local.model_configs:
                    result = {
                        'error': f"Model '{test_input['model']}' not available",
                        'available_models': list(predictor_local.model_configs.keys())
                    }
                else:
                    result = predictor_local.predict_single_model(
                        test_input['sequence_data'], 
                        test_input['model']
                    )
            elif 'sequence_data' in test_input:
                detection = predictor_local.detect_exercise_type(test_input['sequence_data'])
                if 'detected_exercise' in detection:
                    result = predictor_local.predict_single_model(
                        test_input['sequence_data'], 
                        detection['detected_exercise']
                    )
                    result['detection_result'] = detection
                else:
                    result = detection
            else:
                result = {'error': 'Invalid input format. Need "sequence_data" or "model"'}
            
            # Print result summary
            if 'error' in result:
                print(f"  ❌ Error: {result['error'][:100]}...")
            else:
                print(f"  ✅ Success: {result['exercise_name']}")
                print(f"     Correctness: {result.get('correctness_percentage', 0):.1f}%")
                print(f"     Threshold: {result.get('threshold_used', 'N/A')}")
                print(f"     Sequence length: {result.get('sequence_length_used', 'N/A')}")
                
    except Exception as e:
        print(f"❌ Integration test failed: {e}")

def main():
    """Main test function"""
    print("🚀 STARTING PREDICT.PY TEST SUITE (KERAS FORMAT)")
    print("="*60)
    
    # Test 1: Inisialisasi
    predictor = test_predictor_initialization()
    
    if predictor:
        # Test 2-10 hanya jika predictor berhasil
        test_model_configs(predictor)
        test_model_loading(predictor)
        test_scalers_loading(predictor)
        test_mock_prediction(predictor)
        test_preprocessing_logic()
        test_real_prediction(predictor)
        test_error_cases(predictor)
        test_exercise_detection(predictor)
        test_integration()
    else:
        print("⚠️  Tidak dapat melanjutkan test karena predictor gagal diinisialisasi")
    
    print("\n" + "="*60)
    print("🎉 TEST SUITE COMPLETED")
    
    # Summary
    print("\n📋 SUMMARY:")
    print("-"*30)
    if predictor:
        print(f"✅ Predictor initialized successfully")
        print(f"✅ {len(predictor.models)}/{len(predictor.model_configs)} models loaded")
        print(f"✅ Metadata: {len(predictor.metadata)} models")
        print(f"✅ Scalers: {len(predictor.scalers)} models")
        print(f"✅ Sequence length: {predictor.SEQUENCE_LENGTH}")
        
        # Check label order
        print("\n🔍 Label order check:")
        for model_key, labels in predictor.model_labels.items():
            if labels[0] == 'incorrect' and labels[1] == 'correct':
                print(f"  ✅ {model_key}: correct order ['incorrect', 'correct']")
            else:
                print(f"  ❌ {model_key}: wrong order {labels}")
    else:
        print("❌ Predictor initialization failed")
    
    print("\n💡 Recommendations:")
    print("1. Pastikan file model .keras ada di folder yang benar")
    print("2. Periksa metadata.json untuk features, timesteps, dan threshold")
    print("3. Test dengan data real dari aplikasi")
    print("4. Periksa label order di predict.py")

if __name__ == "__main__":
    main()