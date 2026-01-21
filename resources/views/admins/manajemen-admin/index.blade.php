@extends('layouts.app')

@section('title', 'Manajemen Admin - Gym GenZ Admin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/manajemen-admin.css') }}">
    <style>
        .search-input-container {
            position: relative;
            width: 400px;
        }

        .search-input-container i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            z-index: 10;
        }

        .search-input-container input {
            padding-left: 40px;
        }

        .no-data {
            text-align: center;
            padding: 3rem;
            color: #64748b;
        }

        .no-data i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #cbd5e1;
        }

        .loading-spinner {
            text-align: center;
            padding: 2rem;
        }

        .avatar-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .avatar-large {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
        }

        .initials-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #AF69EE, #7C3AED);
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .initials-large {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #AF69EE, #7C3AED);
            color: white;
            font-weight: 700;
            font-size: 2rem;
        }

        .error-message {
            font-size: 0.8rem;
            margin-top: 0.25rem;
        }

        .password-toggle {
            cursor: pointer;
            user-select: none;
        }

        /* Responsive pagination */
        @media (max-width: 576px) {
            .search-input-container {
                width: 100% !important;
            }

            .table-responsive {
                font-size: 0.85rem;
            }

            .card-header {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 1rem;
            }

            .card-header .text-muted {
                align-self: flex-start;
            }

            .pagination-container .d-flex {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .pagination-info {
                order: -1;
            }
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-2 text-dark">Manajemen Admin</h1>
                <p class="text-muted">Kelola data administrator sistem</p>
            </div>
            <button class="btn btn-purple" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                <i class="fas fa-user-plus me-2"></i>Tambah Admin Baru
            </button>
        </div>

        <!-- Search and Stats -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="search-input-container">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control" id="searchInput"
                        placeholder="Cari admin berdasarkan nama atau email...">
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-end gap-3">
                    <div class="text-end">
                        <h5 class="mb-0">{{ $admins->total() }}</h5>
                        <small class="text-muted">Total Admin</small>
                    </div>
                    <div class="vr"></div>
                </div>
            </div>
        </div>

        <!-- Admin Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Daftar Admin</h5>
                <div class="text-muted small">
                    Menampilkan {{ $admins->firstItem() ?: 0 }}-{{ $admins->lastItem() ?: 0 }} dari {{ $admins->total() }}
                    admin
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th style="width: 60px;">Foto</th>
                                <th>Nama Lengkap</th>
                                <th>Email</th>
                                <th>No. Telepon</th>
                                <th>Tanggal Bergabung</th>
                                <th style="width: 150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="adminTableBody">
                            @forelse ($admins as $index => $admin)
                                <tr data-admin-id="{{ $admin->id }}">
                                    <td data-label="No">{{ $admins->firstItem() + $index }}</td>
                                    <td data-label="Foto">
                                        @if ($admin->foto_profile)
                                            <img src="{{ asset('admins/' . $admin->foto_profile) }}" class="avatar-img"
                                                alt="{{ $admin->nama_lengkap }}">
                                        @else
                                            @php
                                                $initials = '';
                                                $nameParts = explode(' ', $admin->nama_lengkap);
                                                if (count($nameParts) >= 2) {
                                                    $initials = strtoupper(
                                                        substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1),
                                                    );
                                                } else {
                                                    $initials = strtoupper(substr($admin->nama_lengkap, 0, 2));
                                                }
                                            @endphp
                                            <div class="initials-avatar">
                                                {{ $initials }}
                                            </div>
                                        @endif
                                    </td>
                                    <td data-label="Nama Lengkap">
                                        <div class="fw-medium">{{ $admin->nama_lengkap }}</div>
                                        <div class="text-muted small">ID:
                                            ADM{{ str_pad($admin->id, 3, '0', STR_PAD_LEFT) }}</div>
                                    </td>
                                    <td data-label="Email">{{ $admin->email }}</td>
                                    <td data-label="No. Telepon">{{ $admin->nomor_telfon ?? '-' }}</td>
                                    <td data-label="Tanggal Bergabung">
                                        <div class="text-muted small">
                                            {{ $admin->created_at->format('d M Y') }}
                                        </div>
                                    </td>
                                    <td data-label="Aksi">
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-sm btn-purple" onclick="editAdmin({{ $admin->id }})">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            @if ($admins->count() > 1)
                                                <button class="btn btn-sm btn-danger"
                                                    onclick="showDeleteConfirm({{ $admin->id }})">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="no-data">
                                        <i class="fas fa-users-slash"></i>
                                        <div>Tidak ada data admin</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if ($admins->hasPages())
                    <div class="pagination-container">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="pagination-info">
                                Halaman {{ $admins->currentPage() }} dari {{ $admins->lastPage() }}
                            </div>
                            <nav>
                                <ul class="pagination">
                                    {{-- Previous Page Link --}}
                                    @if ($admins->onFirstPage())
                                        <li class="page-item disabled">
                                            <span class="page-link">
                                                <i class="fas fa-chevron-left"></i>
                                            </span>
                                        </li>
                                    @else
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $admins->previousPageUrl() }}" rel="prev">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    @endif

                                    {{-- Pagination Elements --}}
                                    @php
                                        $start = max(1, $admins->currentPage() - 2);
                                        $end = min($admins->lastPage(), $admins->currentPage() + 2);
                                    @endphp

                                    @if ($start > 1)
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $admins->url(1) }}">1</a>
                                        </li>
                                        @if ($start > 2)
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        @endif
                                    @endif

                                    @for ($i = $start; $i <= $end; $i++)
                                        <li class="page-item {{ $admins->currentPage() == $i ? 'active' : '' }}">
                                            <a class="page-link" href="{{ $admins->url($i) }}">{{ $i }}</a>
                                        </li>
                                    @endfor

                                    @if ($end < $admins->lastPage())
                                        @if ($end < $admins->lastPage() - 1)
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        @endif
                                        <li class="page-item">
                                            <a class="page-link"
                                                href="{{ $admins->url($admins->lastPage()) }}">{{ $admins->lastPage() }}</a>
                                        </li>
                                    @endif

                                    {{-- Next Page Link --}}
                                    @if ($admins->hasMorePages())
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $admins->nextPageUrl() }}" rel="next">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    @else
                                        <li class="page-item disabled">
                                            <span class="page-link">
                                                <i class="fas fa-chevron-right"></i>
                                            </span>
                                        </li>
                                    @endif
                                </ul>
                            </nav>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Add Admin Modal -->
    <div class="modal fade" id="addAdminModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Admin Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addAdminForm" enctype="multipart/form-data">
                        @csrf
                        <div class="row g-3">
                            <!-- Profile Photo -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Foto Profil (Opsional)</label>
                                    <div class="profile-upload-container">
                                        <div class="profile-preview" id="profilePreview">
                                            <i class="fas fa-user-circle fa-4x text-muted"></i>
                                        </div>
                                        <label for="adminPhoto" class="upload-label btn btn-sm btn-purple mt-2">
                                            <i class="fas fa-upload me-1"></i>Upload Foto
                                        </label>
                                        <input type="file" id="adminPhoto" name="foto_profile" accept="image/*"
                                            hidden>
                                        <div id="photoError" class="error-message text-danger"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Basic Info -->
                            <div class="col-md-8">
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="form-label">Nama Lengkap<span
                                                    class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="fas fa-user"></i>
                                                </span>
                                                <input type="text" class="form-control" id="nama_lengkap"
                                                    name="nama_lengkap" placeholder="Masukkan nama lengkap" required>
                                            </div>
                                            <div id="namaError" class="error-message text-danger"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="form-label">Email <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="fas fa-envelope"></i>
                                                </span>
                                                <input type="email" class="form-control" id="email" name="email"
                                                    placeholder="contoh@email.com" required>
                                            </div>
                                            <div id="emailError" class="error-message text-danger"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="form-label">Nomor Telepon <span
                                                    class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="fas fa-phone"></i>
                                                </span>
                                                <input type="tel" class="form-control" id="nomor_telfon"
                                                    name="nomor_telfon" placeholder="081234567890" required>
                                            </div>
                                            <div id="phoneError" class="error-message text-danger"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Password Section -->
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        <input type="password" class="form-control" id="password" name="password"
                                            placeholder="Masukkan password" required>
                                        <span class="input-group-text password-toggle"
                                            onclick="togglePassword('password', this)">
                                            <i class="fas fa-eye"></i>
                                        </span>
                                    </div>
                                    <div id="passwordError" class="error-message text-danger"></div>
                                    <div class="form-text">Minimal 6 karakter</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Konfirmasi Password <span
                                            class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        <input type="password" class="form-control" id="password_confirmation"
                                            name="password_confirmation" placeholder="Ulangi password" required>
                                        <span class="input-group-text password-toggle"
                                            onclick="togglePassword('password_confirmation', this)">
                                            <i class="fas fa-eye"></i>
                                        </span>
                                    </div>
                                    <div id="passwordConfirmationError" class="error-message text-danger"></div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-purple" onclick="addNewAdmin()" id="addAdminBtn">
                        <i class="fas fa-user-plus me-2"></i>Tambah Admin
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Admin Modal -->
    <div class="modal fade" id="editAdminModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Data Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editAdminForm" enctype="multipart/form-data">
                        @csrf
                        @method('POST')
                        <input type="hidden" id="editAdminId">
                        <div class="row g-3">
                            <!-- Profile Photo -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Foto Profil</label>
                                    <div class="profile-upload-container">
                                        <div class="profile-preview" id="editProfilePreview">
                                            <i class="fas fa-user-circle fa-4x text-muted"></i>
                                        </div>
                                        <div class="d-flex gap-2 mt-2">
                                            <label for="editAdminPhoto" class="upload-label btn btn-sm btn-purple">
                                                <i class="fas fa-upload me-1"></i>Ganti Foto
                                            </label>
                                            <button type="button" class="btn btn-sm btn-danger"
                                                onclick="removeAdminPhoto()">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                        <input type="file" id="editAdminPhoto" name="foto_profile" accept="image/*"
                                            hidden>
                                        <div id="editPhotoError" class="error-message text-danger"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Basic Info -->
                            <div class="col-md-8">
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="form-label">Nama Lengkap <span
                                                    class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="fas fa-user"></i>
                                                </span>
                                                <input type="text" class="form-control" id="editNamaLengkap"
                                                    name="nama_lengkap" required>
                                            </div>
                                            <div id="editNamaError" class="error-message text-danger"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="form-label">Email <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="fas fa-envelope"></i>
                                                </span>
                                                <input type="email" class="form-control" id="editEmail" name="email"
                                                    required>
                                            </div>
                                            <div id="editEmailError" class="error-message text-danger"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="form-label">Nomor Telepon <span
                                                    class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="fas fa-phone"></i>
                                                </span>
                                                <input type="tel" class="form-control" id="editNomorTelfon"
                                                    name="nomor_telfon" required>
                                            </div>
                                            <div id="editPhoneError" class="error-message text-danger"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Password Section (Optional) -->
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Password Baru (Opsional)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        <input type="password" class="form-control" id="editPassword" name="password"
                                            placeholder="Kosongkan jika tidak ingin mengubah">
                                        <span class="input-group-text password-toggle"
                                            onclick="togglePassword('editPassword', this)">
                                            <i class="fas fa-eye"></i>
                                        </span>
                                    </div>
                                    <div id="editPasswordError" class="error-message text-danger"></div>
                                    <div class="form-text">Minimal 6 karakter</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Konfirmasi Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        <input type="password" class="form-control" id="editPasswordConfirmation"
                                            name="password_confirmation" placeholder="Konfirmasi password baru">
                                        <span class="input-group-text password-toggle"
                                            onclick="togglePassword('editPasswordConfirmation', this)">
                                            <i class="fas fa-eye"></i>
                                        </span>
                                    </div>
                                    <div id="editPasswordConfirmationError" class="error-message text-danger"></div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-purple" onclick="updateAdmin()" id="updateAdminBtn">
                        <i class="fas fa-save me-2"></i>Simpan Perubahan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title text-danger">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning"></i>
                    </div>
                    <h6>Yakin ingin menghapus?</h6>
                    <p class="text-muted small">Data admin akan dihapus permanen dan tidak dapat dikembalikan.</p>
                    <input type="hidden" id="deleteAdminId">
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                        <i class="fas fa-trash me-2"></i>Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Toggle password visibility
        function togglePassword(inputId, element) {
            const input = document.getElementById(inputId);
            const icon = element.querySelector('i');

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

        // Profile photo preview for add modal
        document.getElementById('adminPhoto').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file size (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    showToast('Ukuran file maksimal 2MB', 'warning');
                    this.value = '';
                    return;
                }

                // Validate file type
                const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    showToast('Format file harus JPG, PNG, atau GIF', 'warning');
                    this.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('profilePreview');
                    preview.innerHTML = `<img src="${e.target.result}" class="avatar-large">`;
                }
                reader.readAsDataURL(file);
            }
        });

        // Profile photo preview for edit modal
        document.getElementById('editAdminPhoto').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file size (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    showToast('Ukuran file maksimal 2MB', 'warning');
                    this.value = '';
                    return;
                }

                // Validate file type
                const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    showToast('Format file harus JPG, PNG, atau GIF', 'warning');
                    this.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('editProfilePreview');
                    preview.innerHTML = `<img src="${e.target.result}" class="avatar-large">`;
                }
                reader.readAsDataURL(file);
            }
        });

        // Clear error messages
        function clearErrors() {
            const errorElements = document.querySelectorAll('.error-message');
            errorElements.forEach(el => el.textContent = '');
        }

        // Add new admin
        async function addNewAdmin() {
            const form = document.getElementById('addAdminForm');
            const formData = new FormData(form);

            // Clear previous errors
            clearErrors();

            // Show loading
            const btn = document.getElementById('addAdminBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
            btn.disabled = true;

            try {
                const response = await fetch('{{ route('manajemen-admin.store') }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    // Reset form
                    form.reset();
                    document.getElementById('profilePreview').innerHTML =
                        '<i class="fas fa-user-circle fa-4x text-muted"></i>';

                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addAdminModal'));
                    modal.hide();

                    // Show success message
                    showToast(data.message, 'success');

                    // Reload page to show new admin
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    // Show validation errors
                    if (data.errors) {
                        Object.keys(data.errors).forEach(key => {
                            const errorElement = document.getElementById(key + 'Error');
                            if (errorElement) {
                                errorElement.textContent = data.errors[key][0];
                            }
                        });
                    }
                    showToast(data.message || 'Terjadi kesalahan validasi', 'warning');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Terjadi kesalahan pada server', 'error');
            } finally {
                // Restore button
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        // Edit admin
        async function editAdmin(id) {
            try {
                const response = await fetch(`/admin/manajemen-admin/${id}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    const admin = data.data;

                    // Fill form
                    document.getElementById('editAdminId').value = admin.id;
                    document.getElementById('editNamaLengkap').value = admin.nama_lengkap;
                    document.getElementById('editEmail').value = admin.email;
                    document.getElementById('editNomorTelfon').value = admin.nomor_telfon || '';

                    // Set photo preview
                    const preview = document.getElementById('editProfilePreview');
                    if (admin.foto_profile) {
                        preview.innerHTML = `<img src="/admins/${admin.foto_profile}" class="avatar-large">`;
                    } else {
                        const initials = getInitials(admin.nama_lengkap);
                        preview.innerHTML = `<div class="initials-large">${initials}</div>`;
                    }

                    // Clear errors
                    clearErrors();

                    // Show modal
                    const modal = new bootstrap.Modal(document.getElementById('editAdminModal'));
                    modal.show();
                } else {
                    showToast('Gagal mengambil data admin', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Terjadi kesalahan', 'error');
            }
        }

        // Get initials from name
        function getInitials(name) {
            const nameParts = name.split(' ');
            if (nameParts.length >= 2) {
                return (nameParts[0].charAt(0) + nameParts[1].charAt(0)).toUpperCase();
            }
            return name.substring(0, 2).toUpperCase();
        }

        // Update admin
        // Update admin - Gunakan route yang benar
        async function updateAdmin() {
            const id = document.getElementById('editAdminId').value;
            const form = document.getElementById('editAdminForm');
            const formData = new FormData(form);

            // Clear previous errors
            clearErrors();

            // Show loading
            const btn = document.getElementById('updateAdminBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
            btn.disabled = true;

            try {
                
                const response = await fetch(`/admin/manajemen-admin/${id}`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editAdminModal'));
                    modal.hide();

                    // Show success message
                    showToast(data.message, 'success');

                    // Reload page to show updated data
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    // Show validation errors
                    if (data.errors) {
                        Object.keys(data.errors).forEach(key => {
                            const errorElement = document.getElementById('edit' + key.charAt(0).toUpperCase() +
                                key.slice(1) + 'Error');
                            if (errorElement) {
                                errorElement.textContent = data.errors[key][0];
                            }
                        });
                    }
                    showToast(data.message || 'Terjadi kesalahan validasi', 'warning');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Terjadi kesalahan pada server', 'error');
            } finally {
                // Restore button
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        // Remove admin photo
        async function removeAdminPhoto() {
            const id = document.getElementById('editAdminId').value;

            if (!confirm('Yakin ingin menghapus foto profil?')) {
                return;
            }

            try {
                const response = await fetch(`/admin/manajemen-admin/${id}/remove-photo`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    // Update preview
                    const adminName = document.getElementById('editNamaLengkap').value;
                    const initials = getInitials(adminName);
                    document.getElementById('editProfilePreview').innerHTML =
                        `<div class="initials-large">${initials}</div>`;
                    showToast(data.message, 'success');
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Terjadi kesalahan', 'error');
            }
        }

        // Show delete confirmation
        function showDeleteConfirm(id) {
            document.getElementById('deleteAdminId').value = id;
            const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
            modal.show();
        }

        // Delete admin
        async function confirmDelete() {
            const id = document.getElementById('deleteAdminId').value;

            try {
                const response = await fetch(`/admin/manajemen-admin/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal'));
                    modal.hide();

                    // Show success message
                    showToast(data.message, 'success');

                    // Remove row from table
                    const row = document.querySelector(`tr[data-admin-id="${id}"]`);
                    if (row) {
                        row.remove();
                    }

                    // Reload if no rows left
                    setTimeout(() => {
                        const rows = document.querySelectorAll('#adminTableBody tr');
                        if (rows.length === 0) {
                            window.location.reload();
                        }
                    }, 500);
                } else {
                    showToast(data.message, 'error');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal'));
                    modal.hide();
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Terjadi kesalahan', 'error');
            }
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#adminTableBody tr');

            rows.forEach(row => {
                const name = row.querySelector('td:nth-child(3) .fw-medium').textContent.toLowerCase();
                const email = row.querySelector('td:nth-child(4)').textContent.toLowerCase();

                if (name.includes(searchTerm) || email.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Toast notification
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast-notification toast-${type}`;
            toast.innerHTML = `
                <div class="toast-content">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 
                                    type === 'warning' ? 'exclamation-triangle' : 
                                    'info-circle'} me-2"></i>
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

        // Reset add modal when closed
        document.getElementById('addAdminModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('addAdminForm').reset();
            document.getElementById('profilePreview').innerHTML =
                '<i class="fas fa-user-circle fa-4x text-muted"></i>';
            clearErrors();
        });

        // Reset edit modal when closed
        document.getElementById('editAdminModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('editAdminForm').reset();
            clearErrors();
        });

        // Make table responsive on mobile
        function makeTableResponsive() {
            if (window.innerWidth <= 576) {
                const table = document.querySelector('.table');
                const headers = table.querySelectorAll('thead th');
                const rows = table.querySelectorAll('tbody tr');

                headers.forEach((header, index) => {
                    const label = header.textContent;
                    rows.forEach(row => {
                        const cell = row.querySelector(`td:nth-child(${index + 1})`);
                        if (cell) {
                            cell.setAttribute('data-label', label);
                        }
                    });
                });
            }
        }

        // Initialize responsive table
        document.addEventListener('DOMContentLoaded', function() {
            makeTableResponsive();
            window.addEventListener('resize', makeTableResponsive);
        });
    </script>
@endpush
