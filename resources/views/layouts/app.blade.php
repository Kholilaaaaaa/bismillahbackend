<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', 'Dashboard - Gym GenZ Admin')</title>
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
    
    <!-- Toast Notification Styles -->
    <style>
        .toast-container {
            z-index: 9999;
        }

        .toast-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            min-width: 300px;
            max-width: 400px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            padding: 16px;
            margin-bottom: 10px;
            transform: translateX(150%);
            transition: transform 0.3s ease;
            z-index: 9999;
            border-left: 4px solid;
        }

        .toast-notification.show {
            transform: translateX(0);
        }

        .toast-success {
            border-left-color: #28a745;
        }

        .toast-error {
            border-left-color: #dc3545;
        }

        .toast-warning {
            border-left-color: #ffc107;
        }

        .toast-info {
            border-left-color: #17a2b8;
        }

        .toast-content {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }

        .toast-content i {
            font-size: 1.2rem;
            margin-right: 10px;
        }

        .toast-success .toast-content i {
            color: #28a745;
        }

        .toast-error .toast-content i {
            color: #dc3545;
        }

        .toast-warning .toast-content i {
            color: #ffc107;
        }

        .toast-info .toast-content i {
            color: #17a2b8;
        }

        .toast-close {
            position: absolute;
            top: 8px;
            right: 8px;
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            padding: 4px;
            font-size: 0.8rem;
        }

        .toast-close:hover {
            color: #343a40;
        }

        /* Additional custom styles for chatbot pages */
        .chatbot-icon {
            color: #6f42c1;
            font-size: 1.1rem;
        }

        .chatbot-badge {
            background: linear-gradient(135deg, #AF69EE, #7C3AED);
            color: white;
        }

        .file-upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .file-upload-area:hover {
            border-color: #7C3AED;
            background-color: rgba(124, 58, 237, 0.05);
        }

        .file-upload-area i {
            font-size: 3rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }

        .file-upload-area:hover i {
            color: #7C3AED;
        }

        /* Progress bar animation */
        @keyframes progress-bar-stripes {
            from { background-position: 1rem 0; }
            to { background-position: 0 0; }
        }

        .progress-bar-animated {
            animation: progress-bar-stripes 1s linear infinite;
        }

        /* Badge styles */
        .badge-category {
            background-color: #e9ecef;
            color: #495057;
            font-weight: 500;
            padding: 0.35em 0.65em;
            border-radius: 6px;
        }

        .badge-tag {
            background-color: #d1e7dd;
            color: #0f5132;
            font-size: 0.75em;
            padding: 0.25em 0.5em;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <div class="row">
            <!-- Sidebar -->
            @include('partials.sidebar')
            
            <!-- Main Content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <!-- Header -->
                @include('partials.header')
                
                <!-- Main Content Area -->
                <main class="py-4">
                    @yield('content')
                </main>

                <!-- Footer -->
                <footer class="footer mt-auto py-3 border-top">
                    <div class="container-fluid">
                        <div class="row align-items-center">
                            <div class="col-md-6 text-muted">
                                &copy; {{ date('Y') }} Gym GenZ Admin. All rights reserved.
                            </div>
                            <div class="col-md-6 text-md-end">
                                <span class="badge bg-light text-dark">
                                    <i class="fas fa-robot me-1"></i> Chatbot v1.0
                                </span>
                                <span class="ms-2 text-muted small">
                                    Last updated: {{ date('d M Y') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </footer>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const sidebarClose = document.getElementById('sidebarClose');
            
            // Fungsi untuk membuka sidebar
            function openSidebar() {
                sidebar.classList.add('active');
                sidebarOverlay.classList.add('active');
                document.body.style.overflow = 'hidden'; // Mencegah scroll background
            }
            
            // Fungsi untuk menutup sidebar
            function closeSidebar() {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
                document.body.style.overflow = ''; // Mengembalikan scroll
            }
            
            // Toggle sidebar saat tombol diklik
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function(e) {
                    e.stopPropagation(); // Mencegah event bubbling
                    openSidebar();
                });
            }
            
            // Tutup sidebar saat overlay diklik
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', function() {
                    closeSidebar();
                });
            }

            if (sidebarClose) {
                sidebarClose.addEventListener('click', function() {
                    closeSidebar();
                });
            }
            
            // Tutup sidebar saat item menu diklik (untuk mobile)
            const sidebarLinks = document.querySelectorAll('#sidebar .nav-link');
            sidebarLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth < 768) {
                        closeSidebar();
                    }
                });
            });
            
            // Tutup sidebar saat window di-resize ke ukuran desktop
            function handleResize() {
                if (window.innerWidth >= 768) {
                    closeSidebar();
                }
            }
            
            // Event listener untuk resize
            window.addEventListener('resize', handleResize);
            
            // Tutup sidebar dengan tombol ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeSidebar();
                }
            });

            // Toast Notification Function (Global)
            window.showToast = function(message, type = 'info', duration = 3000) {
                // Remove existing toasts
                const existingToasts = document.querySelectorAll('.toast-notification');
                existingToasts.forEach(toast => {
                    toast.remove();
                });

                // Create toast element
                const toast = document.createElement('div');
                toast.className = `toast-notification toast-${type}`;
                toast.innerHTML = `
                    <div class="toast-content">
                        <i class="fas fa-${getToastIcon(type)} me-2"></i>
                        <div>
                            <strong>${getToastTitle(type)}</strong><br>
                            <small>${message}</small>
                        </div>
                    </div>
                    <button class="toast-close" onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                `;

                document.body.appendChild(toast);

                // Animate in
                setTimeout(() => {
                    toast.classList.add('show');
                }, 10);

                // Auto remove
                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => {
                        if (toast.parentElement) {
                            toast.parentElement.removeChild(toast);
                        }
                    }, 300);
                }, duration);

                // Return toast element for manual control
                return toast;
            };

            // Helper function for toast icons
            function getToastIcon(type) {
                switch(type) {
                    case 'success': return 'check-circle';
                    case 'error': return 'exclamation-circle';
                    case 'warning': return 'exclamation-triangle';
                    case 'info': return 'info-circle';
                    default: return 'info-circle';
                }
            }

            // Helper function for toast titles
            function getToastTitle(type) {
                switch(type) {
                    case 'success': return 'Berhasil!';
                    case 'error': return 'Error!';
                    case 'warning': return 'Peringatan!';
                    case 'info': return 'Informasi';
                    default: return 'Info';
                }
            }

            // Global confirm function with sweet alert style
            window.showConfirm = function(title, message, confirmText = 'Ya', cancelText = 'Tidak') {
                return new Promise((resolve) => {
                    // Create modal
                    const modalId = 'confirm-modal-' + Date.now();
                    const modal = document.createElement('div');
                    modal.className = 'modal fade';
                    modal.id = modalId;
                    modal.innerHTML = `
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header border-0">
                                    <h5 class="modal-title text-dark">${title}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body text-center py-4">
                                    <i class="fas fa-question-circle fa-3x text-warning mb-3"></i>
                                    <p class="mb-0">${message}</p>
                                </div>
                                <div class="modal-footer border-0 justify-content-center">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                        ${cancelText}
                                    </button>
                                    <button type="button" class="btn btn-warning" id="confirmBtn">
                                        ${confirmText}
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;

                    document.body.appendChild(modal);
                    
                    const modalInstance = new bootstrap.Modal(modal);
                    modalInstance.show();

                    // Handle confirm button
                    document.getElementById('confirmBtn').addEventListener('click', function() {
                        modalInstance.hide();
                        resolve(true);
                    });

                    // Handle modal hidden
                    modal.addEventListener('hidden.bs.modal', function() {
                        setTimeout(() => {
                            modal.remove();
                        }, 300);
                    });
                });
            };

            // File upload helper function
            window.handleFileUpload = function(inputId, previewId, maxSizeMB = 2) {
                return new Promise((resolve, reject) => {
                    const input = document.getElementById(inputId);
                    const preview = document.getElementById(previewId);
                    
                    input.addEventListener('change', function(e) {
                        const file = e.target.files[0];
                        if (!file) {
                            reject(new Error('No file selected'));
                            return;
                        }

                        // Validate file size
                        if (file.size > maxSizeMB * 1024 * 1024) {
                            reject(new Error(`Ukuran file maksimal ${maxSizeMB}MB`));
                            input.value = '';
                            return;
                        }

                        // Create preview
                        if (preview) {
                            if (file.type.startsWith('image/')) {
                                const reader = new FileReader();
                                reader.onload = function(e) {
                                    preview.innerHTML = `<img src="${e.target.result}" class="img-fluid rounded">`;
                                    resolve(file);
                                };
                                reader.readAsDataURL(file);
                            } else {
                                preview.innerHTML = `
                                    <div class="text-center">
                                        <i class="fas fa-file-alt fa-3x text-muted"></i>
                                        <p class="mt-2 mb-1">${file.name}</p>
                                        <small class="text-muted">${(file.size / 1024 / 1024).toFixed(2)} MB</small>
                                    </div>
                                `;
                                resolve(file);
                            }
                        } else {
                            resolve(file);
                        }
                    });

                    // Trigger file selection
                    input.click();
                });
            };

            // Format bytes helper
            window.formatBytes = function(bytes, decimals = 2) {
                if (bytes === 0) return '0 Bytes';
                
                const k = 1024;
                const dm = decimals < 0 ? 0 : decimals;
                const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
                
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                
                return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
            };

            // Initialize tooltips
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
        });
    </script>
    
    @stack('scripts')
</body>
</html>