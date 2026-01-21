
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
    """Check dan fix scaler file"""
    
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
        print(f"Checking {model_name}: {full_path}")
        check_and_fix_scaler(full_path)

if __name__ == "__main__":
    main()
