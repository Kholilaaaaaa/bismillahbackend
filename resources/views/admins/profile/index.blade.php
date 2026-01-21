@extends('layouts.app')

@section('title', 'Profile - Gym GenZ Admin')

@push('styles')
    <style>
        .profile-card {
            border-radius: 15px;
            overflow: hidden;
        }

        .profile-photo-container {
            position: relative;
        }

        .profile-photo-wrapper {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto;
            position: relative;
            border: 5px solid #f8f9fa;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .profile-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .photo-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
            cursor: pointer;
        }

        .profile-photo-wrapper:hover .photo-overlay {
            opacity: 1;
        }

        .upload-btn {
            color: white;
            font-size: 24px;
            background: rgba(175, 105, 238, 0.8);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .btn-purple {
            color: #AF69EE;
            border-color: #AF69EE;
        }

        .btn-purple:hover {
            background: #AF69EE;
            color: white;
        }

        .is-invalid {
            border-color: #dc3545;
        }

        .invalid-feedback {
            display: none;
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 0.25rem;
        }

        .was-validated .form-control:invalid~.invalid-feedback {
            display: block;
        }

        .toast-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border-radius: 10px;
            padding: 15px 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-width: 300px;
            z-index: 1050;
            transform: translateX(400px);
            transition: transform 0.3s ease;
        }

        .toast-notification.show {
            transform: translateX(0);
        }

        .toast-success {
            border-left: 4px solid #28a745;
        }

        .toast-error {
            border-left: 4px solid #dc3545;
        }

        .toast-warning {
            border-left: 4px solid #ffc107;
        }

        .toast-info {
            border-left: 4px solid #17a2b8;
        }

        .toast-close {
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            padding: 0;
            margin-left: 10px;
        }

        .modal-header {
            background: #AF69EE;
            color: white;
        }

        .btn-purple {
            background: #AF69EE;
            border-color: #AF69EE;
            color: white;
        }

        .btn-purple:hover {
            background: #9a52e8;
            border-color: #9a52e8;
            color: white;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-2 text-dark">Profile Settings</h1>
                <p class="text-muted">Manage your personal information and account settings</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
                <button class="btn btn-purple" onclick="saveProfile()">
                    <i class="fas fa-save me-2"></i>Save Changes
                </button>
            </div>
        </div>

        <div class="row">
            <!-- Left Column - Profile Info -->
            <div class="col-lg-4">
                <!-- Profile Card -->
                <div class="card profile-card mb-4">
                    <div class="card-body text-center p-4">
                        <!-- Profile Photo -->
                        <div class="profile-photo-container mb-4">
                            <div class="profile-photo-wrapper">
                                <img src="{{ $admin->foto_profile ? asset('admins/' . $admin->foto_profile) : 'https://ui-avatars.com/api/?name=' . urlencode($admin->nama_lengkap) . '&background=AF69EE&color=fff&size=200' }}"
                                    alt="Profile Photo" class="profile-photo" id="profileImage">
                                <div class="photo-overlay">
                                    <label for="photoUpload" class="upload-btn">
                                        <i class="fas fa-camera"></i>
                                    </label>
                                    <input type="file" id="photoUpload" accept="image/*" hidden>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button class="btn btn-sm btn-purple" onclick="removePhoto()">
                                    <i class="fas fa-trash me-1"></i>Remove Photo
                                </button>
                            </div>
                        </div>

                        <!-- User Info -->
                        <h3 class="h4 mb-1">{{ $admin->nama_lengkap }}</h3>
                        <p class="text-muted mb-3">Administrator</p>
                    </div>
                </div>
            </div>

            <!-- Right Column - Edit Forms -->
            <div class="col-lg-8">
                <!-- Personal Information Card -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Personal Information</h5>
                        <span class="badge bg-purple">Required</span>
                    </div>
                    <div class="card-body">
                        <form id="personalInfoForm" novalidate>
                            @csrf
                            <div class="row g-2">
                                <!-- Full Name -->
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-user"></i>
                                            </span>
                                            <input type="text" name="nama_lengkap" class="form-control"
                                                value="{{ $admin->nama_lengkap }}" placeholder="Enter your full name"
                                                required>
                                        </div>
                                        <div class="invalid-feedback">
                                            Please enter your full name.
                                        </div>
                                    </div>
                                </div>

                                <!-- Email -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-envelope"></i>
                                            </span>
                                            <input type="email" name="email" class="form-control"
                                                value="{{ $admin->email }}" placeholder="Enter your email" required>
                                        </div>
                                        <div class="invalid-feedback">
                                            Please enter a valid email address.
                                        </div>
                                    </div>
                                </div>

                                <!-- Phone Number -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Phone Number</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-phone"></i>
                                            </span>
                                            <input type="tel" name="nomor_telfon" class="form-control"
                                                value="{{ $admin->nomor_telfon }}" placeholder="Enter your phone number">
                                        </div>
                                        <div class="invalid-feedback">
                                            Please enter a valid phone number.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <button type="button" class="list-group-item list-group-item-action" data-bs-toggle="modal"
                                data-bs-target="#changePasswordModal">
                                <i class="fas fa-key me-3"></i>Change Password
                            </button>
                            <form method="POST" action="{{ route('logout') }}"
                                class="list-group-item list-group-item-action p-0">
                                @csrf
                                <button type="submit"
                                    class="btn btn-link text-danger text-decoration-none w-100 text-start ps-3">
                                    <i class="fas fa-sign-out-alt me-3"></i>Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="changePasswordForm" novalidate>
                        @csrf
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password <span
                                    class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="current_password" class="form-control"
                                    id="current_password" required>
                                <button type="button" class="btn btn-outline-secondary"
                                    onclick="togglePassword('current_password', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback">
                                Please enter your current password.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password <span
                                    class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="new_password" class="form-control" id="new_password"
                                    minlength="6" required>
                                <button type="button" class="btn btn-outline-secondary"
                                    onclick="togglePassword('new_password', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback">
                                Password must be at least 6 characters.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="new_password_confirmation" class="form-label">Confirm New Password <span
                                    class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="new_password_confirmation" class="form-control"
                                    id="new_password_confirmation" required>
                                <button type="button" class="btn btn-outline-secondary"
                                    onclick="togglePassword('new_password_confirmation', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback">
                                Please confirm your new password.
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-purple" onclick="changePassword()">Change Password</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // CSRF Token untuk AJAX
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Handle profile photo upload
        document.getElementById('photoUpload').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validasi ukuran file (max 2MB)
                if (file.size > 2 * 1024 * 1024) {
                    showToast('File size should not exceed 2MB', 'error');
                    return;
                }

                // Validasi tipe file
                const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    showToast('Only JPEG, PNG, JPG, and GIF files are allowed', 'error');
                    return;
                }

                uploadPhoto(file);
            }
        });

        // Upload foto profile
        function uploadPhoto(file) {
            const formData = new FormData();
            formData.append('foto_profile', file);
            formData.append('_token', csrfToken);

            // Tambahkan data form lainnya
            formData.append('nama_lengkap', document.querySelector('input[name="nama_lengkap"]').value);
            formData.append('email', document.querySelector('input[name="email"]').value);
            formData.append('nomor_telfon', document.querySelector('input[name="nomor_telfon"]').value);

            const btn = document.querySelector('button[onclick="saveProfile()"]');
            const originalText = btn.innerHTML;

            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Uploading...';
            btn.disabled = true;

            fetch('{{ route('admin.profile.update') }}', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('profileImage').src = data.data.foto_profile ?
                            '{{ asset('admins/') }}/' + data.data.foto_profile + '?t=' + new Date().getTime() :
                            'https://ui-avatars.com/api/?name=' + encodeURIComponent(data.data.nama_lengkap) +
                            '&background=AF69EE&color=fff&size=200';

                        showToast(data.message, 'success');
                        updateHeaderProfile(data.data);
                    } else {
                        if (data.errors) {
                            Object.keys(data.errors).forEach(field => {
                                showToast(data.errors[field][0], 'error');
                            });
                        } else {
                            showToast(data.message || 'Upload failed', 'error');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('An error occurred while uploading', 'error');
                })
                .finally(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
        }

        // Remove profile photo
        function removePhoto() {
            if (!confirm('Are you sure you want to remove your profile photo?')) {
                return;
            }

            fetch('{{ route('admin.profile.remove-photo') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('profileImage').src = data.avatar_url;
                        showToast(data.message, 'success');
                        updateHeaderProfile();
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('An error occurred', 'error');
                });
        }

        // Save profile
        function saveProfile() {
            const form = document.getElementById('personalInfoForm');

            // Validasi form
            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                return;
            }

            const formData = new FormData(form);

            // Tambahkan foto jika ada
            const photoInput = document.getElementById('photoUpload');
            if (photoInput.files[0]) {
                formData.append('foto_profile', photoInput.files[0]);
            }

            const btn = document.querySelector('button[onclick="saveProfile()"]');
            const originalText = btn.innerHTML;

            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
            btn.disabled = true;

            fetch('{{ route('admin.profile.update') }}', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        updateHeaderProfile(data.data);
                    } else {
                        if (data.errors) {
                            // Tampilkan error untuk setiap field
                            Object.keys(data.errors).forEach(field => {
                                const input = document.querySelector(`[name="${field}"]`);
                                if (input) {
                                    input.classList.add('is-invalid');
                                    const feedback = input.parentElement.querySelector('.invalid-feedback') ||
                                        input.parentElement.parentElement.querySelector('.invalid-feedback');
                                    if (feedback) {
                                        feedback.textContent = data.errors[field][0];
                                        feedback.style.display = 'block';
                                    }
                                }
                            });
                        } else {
                            showToast(data.message || 'Update failed', 'error');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('An error occurred', 'error');
                })
                .finally(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
        }

        // Change password
        function changePassword() {
            const form = document.getElementById('changePasswordForm');

            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                return;
            }

            const formData = new FormData(form);

            const btn = document.querySelector('#changePasswordModal .btn-purple');
            const originalText = btn.innerHTML;

            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Changing...';
            btn.disabled = true;

            fetch('{{ route('admin.profile.password') }}', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        // Reset form dan tutup modal
                        form.reset();
                        form.classList.remove('was-validated');
                        const modal = bootstrap.Modal.getInstance(document.getElementById('changePasswordModal'));
                        modal.hide();
                    } else {
                        if (data.errors) {
                            Object.keys(data.errors).forEach(field => {
                                const input = document.querySelector(`[name="${field}"]`);
                                if (input) {
                                    input.classList.add('is-invalid');
                                    const feedback = input.parentElement.querySelector('.invalid-feedback') ||
                                        input.parentElement.parentElement.querySelector('.invalid-feedback');
                                    if (feedback) {
                                        feedback.textContent = data.errors[field][0];
                                        feedback.style.display = 'block';
                                    }
                                }
                            });
                        } else {
                            showToast(data.message || 'Password change failed', 'error');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('An error occurred', 'error');
                })
                .finally(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
        }

        // Toggle password visibility
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Update header profile
        function updateHeaderProfile(data = null) {
            // Update nama di header
            const nameElement = document.querySelector('.user-name');
            if (nameElement && data) {
                nameElement.textContent = data.nama_lengkap;
            }

            // Update foto di header
            const avatarElement = document.querySelector('.user-avatar i');
            if (avatarElement && data && data.foto_profile) {
                // Ganti icon dengan gambar
                const img = document.createElement('img');
                img.src = '{{ asset('admins/') }}/' + data.foto_profile + '?t=' + new Date().getTime();
                img.className = 'rounded-circle';
                img.style.width = '32px';
                img.style.height = '32px';
                avatarElement.parentElement.innerHTML = '';
                avatarElement.parentElement.appendChild(img);
            }
        }

        // Toast notification
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast-notification toast-${type}`;
            toast.innerHTML = `
                <div class="toast-content">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 
                                     type === 'error' ? 'exclamation-circle' : 
                                     type === 'warning' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                    ${message}
                </div>
                <button class="toast-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;

            document.body.appendChild(toast);

            setTimeout(() => {
                toast.classList.add('show');
            }, 10);

            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    if (toast.parentElement) {
                        toast.parentElement.removeChild(toast);
                    }
                }, 300);
            }, 3000);
        }

        // Reset validation on input
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('is-invalid');
                const feedback = this.parentElement.querySelector('.invalid-feedback') ||
                    this.parentElement.parentElement.querySelector('.invalid-feedback');
                if (feedback) {
                    feedback.style.display = 'none';
                }
            });
        });

        // Reset modal form when hidden
        document.getElementById('changePasswordModal').addEventListener('hidden.bs.modal', function() {
            const form = document.getElementById('changePasswordForm');
            form.reset();
            form.classList.remove('was-validated');

            // Reset semua input
            form.querySelectorAll('.form-control').forEach(input => {
                input.classList.remove('is-invalid');
            });
        });
    </script>
@endpush
