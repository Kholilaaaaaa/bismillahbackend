# create_mock_model.py
import numpy as np
from tensorflow.keras.models import Sequential
from tensorflow.keras.layers import Dense, LSTM, Dropout
import os

def create_mock_model(model_name, output_path):
    """Buat model sederhana untuk testing"""
    
    # Model LSTM sederhana
    model = Sequential([
        LSTM(64, input_shape=(100, 5), return_sequences=True),
        Dropout(0.2),
        LSTM(32),
        Dropout(0.2),
        Dense(16, activation='relu'),
        Dense(3, activation='softmax')  # 3 kelas untuk setiap exercise
    ])
    
    model.compile(
        optimizer='adam',
        loss='categorical_crossentropy',
        metrics=['accuracy']
    )
    
    # Save model
    model.save(output_path)
    print(f"Mock model saved to: {output_path}")
    print(f"Model summary:")
    model.summary()
    
    return model

if __name__ == "__main__":
    # Pastikan folder ml_models ada
    models_dir = os.path.join('..', 'ml_models')
    os.makedirs(models_dir, exist_ok=True)
    
    # Buat mock models
    models = {
        'pushup': os.path.join(models_dir, 'model_pushup.h5'),
        'squat': os.path.join(models_dir, 'model_Squat.h5'),
        't_bar_row': os.path.join(models_dir, 'model_t_bar_row.h5')
    }
    
    for name, path in models.items():
        print(f"\nCreating mock model for {name}...")
        create_mock_model(name, path)
    
    print("\n✅ All mock models created successfully!")