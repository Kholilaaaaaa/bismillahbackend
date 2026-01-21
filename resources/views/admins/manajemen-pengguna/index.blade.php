@extends('layouts.app')

@section('title', 'Manajemen Pengguna - Gym GenZ Admin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/manajemen-pengguna.css') }}">
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

        /* Gender icons */
        .gender-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            margin-right: 5px;
        }

        .gender-male {
            background-color: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .gender-female {
            background-color: rgba(236, 72, 153, 0.1);
            color: #ec4899;
        }

        /* Blood type badge */
        .blood-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.8rem;
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        /* Allergy tag */
        .allergy-tag {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
            margin: 2px;
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

        /* Simplified Modal Styles */
        .modal-content {
            border: none;
            border-radius: 16px;
            overflow: hidden;
        }

        .modal-header {
            background: linear-gradient(90deg, #f8fafc, #ffffff);
            border-bottom: 1px solid #e2e8f0;
            padding: 1.25rem 1.5rem;
        }

        .modal-header .btn-close {
            padding: 0.75rem;
            margin: -0.75rem -0.75rem -0.75rem auto;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            border-top: 1px solid #e2e8f0;
            padding: 1.25rem 1.5rem;
            background: #f8fafc;
        }

        /* Profile Header */
        .detail-modal-header {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            padding: 1rem 0 1.5rem;
        }

        #detailProfilePreview .avatar-large {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        #detailProfilePreview .initials-large {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #AF69EE, #7C3AED);
            color: white;
            font-weight: 600;
            font-size: 1.5rem;
            border: 3px solid white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        /* Info Cards */
        .card.bg-light {
            background-color: #f8fafc !important;
            border-radius: 12px;
            transition: transform 0.2s ease;
        }

        .card-body {
            padding: 1.25rem;
        }

        .card-title {
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
        }

        /* BMI Status Colors */
        .bmi-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .bmi-underweight {
            background-color: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .bmi-normal {
            background-color: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .bmi-overweight {
            background-color: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .bmi-obesity {
            background-color: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        /* Blood Type */
        .blood-type {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Allergy Tags */
        .allergy-tag {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
            border-radius: 20px;
            font-size: 0.75rem;
            margin: 0.125rem;
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        /* Gender Icon */
        .gender-icon-sm {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.5rem;
            font-size: 0.75rem;
        }

        /* Responsive Modal */
        @media (max-width: 768px) {
            .modal-dialog.modal-lg {
                margin: 0.5rem;
                max-width: calc(100% - 1rem);
            }

            .modal-body {
                padding: 1rem;
            }

            #detailProfilePreview .avatar-large,
            #detailProfilePreview .initials-large {
                width: 70px;
                height: 70px;
                font-size: 1.25rem;
            }

            .card-body {
                padding: 1rem;
            }

            .row.g-3 {
                gap: 1rem !important;
            }
        }

        /* Hover Effects */
        .btn-secondary:hover {
            background-color: #f1f5f9;
            border-color: #cbd5e1;
            color: #334155;
        }

        /* Animation for modal */
        .modal.fade .modal-content {
            transform: translateY(-50px);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .modal.show .modal-content {
            transform: translateY(0);
            opacity: 1;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-2 text-dark">Manajemen Pengguna</h1>
                <p class="text-muted">Kelola data pengguna sistem</p>
            </div>
        </div>

        <!-- Search -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="search-input-container">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control" id="searchInput"
                        placeholder="Cari pengguna berdasarkan nama atau email...">
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-end gap-3">
                    <div class="text-end">
                        <h5 class="mb-0">{{ $penggunas->total() }}</h5>
                        <small class="text-muted">Total Pengguna</small>
                    </div>
                    <div class="vr"></div>
                </div>
            </div>
        </div>

        <!-- Pengguna Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Daftar Pengguna</h5>
                <div class="text-muted small">
                    Menampilkan {{ $penggunas->firstItem() ?: 0 }}-{{ $penggunas->lastItem() ?: 0 }} dari
                    {{ $penggunas->total() }} pengguna
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
                                <th>Jenis Kelamin</th>
                                <th>BMI</th>
                                <th style="width: 150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="penggunaTableBody">
                            @forelse ($penggunas as $index => $pengguna)
                                <tr data-pengguna-id="{{ $pengguna->id }}">
                                    <td data-label="No">{{ $penggunas->firstItem() + $index }}</td>
                                    <td data-label="Foto">
                                        @if ($pengguna->foto_profile && file_exists(public_path('profile/' . $pengguna->foto_profile)))
                                            <img src="{{ asset('profile/' . $pengguna->foto_profile) }}" class="avatar-img"
                                                alt="{{ $pengguna->nama_lengkap }}"
                                                onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode($pengguna->nama_lengkap) }}&background=AF69EE&color=fff&size=200'">
                                        @else
                                            @php
                                                $initials = '';
                                                $nameParts = explode(' ', $pengguna->nama_lengkap);
                                                if (count($nameParts) >= 2) {
                                                    $initials = strtoupper(
                                                        substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1),
                                                    );
                                                } else {
                                                    $initials = strtoupper(substr($pengguna->nama_lengkap, 0, 2));
                                                }
                                            @endphp
                                            <div class="initials-avatar">
                                                {{ $initials }}
                                            </div>
                                        @endif
                                    </td>
                                    <td data-label="Nama Lengkap">
                                        <div class="fw-medium">{{ $pengguna->nama_lengkap }}</div>
                                        <div class="text-muted small">ID:
                                            USR{{ str_pad($pengguna->id, 4, '0', STR_PAD_LEFT) }}</div>
                                    </td>
                                    <td data-label="Email">{{ $pengguna->email }}</td>
                                    <td data-label="Jenis Kelamin">
                                        @if ($pengguna->jenis_kelamin == 'L')
                                            <span class="d-flex align-items-center">
                                                <span class="gender-icon gender-male">
                                                    <i class="fas fa-mars"></i>
                                                </span>
                                                Laki-laki
                                            </span>
                                        @else
                                            <span class="d-flex align-items-center">
                                                <span class="gender-icon gender-female">
                                                    <i class="fas fa-venus"></i>
                                                </span>
                                                Perempuan
                                            </span>
                                        @endif
                                    </td>
                                    <td data-label="BMI">
                                        @if ($pengguna->bmi)
                                            <div class="fw-medium">{{ $pengguna->bmi }}</div>
                                            <small class="text-muted">{{ $pengguna->bmi_category }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td data-label="Aksi">
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-sm btn-purple"
                                                onclick="showDetail({{ $pengguna->id }})">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger"
                                                onclick="showDeleteConfirm({{ $pengguna->id }})">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="no-data">
                                        <i class="fas fa-users-slash mt-3"></i>
                                        <p class="text-muted">Tidak ada data pengguna</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if ($penggunas->hasPages())
                    <div class="pagination-container">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="pagination-info">
                                Halaman {{ $penggunas->currentPage() }} dari {{ $penggunas->lastPage() }}
                            </div>
                            <nav>
                                <ul class="pagination">
                                    {{-- Previous Page Link --}}
                                    @if ($penggunas->onFirstPage())
                                        <li class="page-item disabled">
                                            <span class="page-link">
                                                <i class="fas fa-chevron-left"></i>
                                            </span>
                                        </li>
                                    @else
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $penggunas->previousPageUrl() }}" rel="prev">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    @endif

                                    {{-- Pagination Elements --}}
                                    @php
                                        $start = max(1, $penggunas->currentPage() - 2);
                                        $end = min($penggunas->lastPage(), $penggunas->currentPage() + 2);
                                    @endphp

                                    @if ($start > 1)
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $penggunas->url(1) }}">1</a>
                                        </li>
                                        @if ($start > 2)
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        @endif
                                    @endif

                                    @for ($i = $start; $i <= $end; $i++)
                                        <li class="page-item {{ $penggunas->currentPage() == $i ? 'active' : '' }}">
                                            <a class="page-link" href="{{ $penggunas->url($i) }}">{{ $i }}</a>
                                        </li>
                                    @endfor

                                    @if ($end < $penggunas->lastPage())
                                        @if ($end < $penggunas->lastPage() - 1)
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        @endif
                                        <li class="page-item">
                                            <a class="page-link"
                                                href="{{ $penggunas->url($penggunas->lastPage()) }}">{{ $penggunas->lastPage() }}</a>
                                        </li>
                                    @endif

                                    {{-- Next Page Link --}}
                                    @if ($penggunas->hasMorePages())
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $penggunas->nextPageUrl() }}" rel="next">
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

    <!-- Detail Pengguna Modal -->
    <!-- Detail Pengguna Modal -->
    <div class="modal fade" id="detailPenggunaModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Pengguna</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Profile Header - Simplified -->
                    <div class="detail-modal-header text-center mb-4">
                        <div id="detailProfilePreview" class="mb-3">
                            <!-- Avatar will be dynamically inserted here -->
                        </div>
                        <h4 id="detailNamaLengkap" class="mb-1"></h4>
                        <p class="text-muted mb-3" id="detailEmail"></p>
                        <span
                            class="badge bg-purple bg-opacity-10 text-white border border-purple border-opacity-25 px-3 py-2 rounded-pill"
                            id="detailUserId"></span>
                    </div>

                    <!-- User Info Grid - More Compact -->
                    <div class="row g-3">
                        <!-- Column 1 -->
                        <div class="col-md-6">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <h6 class="card-title mb-3 text-dark">Informasi Pribadi</h6>

                                    <div class="mb-3">
                                        <small class="text-muted d-block mb-1">Jenis Kelamin</small>
                                        <div id="detailJenisKelamin" class="fw-medium"></div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <small class="text-muted d-block mb-1">Tinggi Badan</small>
                                            <div id="detailTinggiBadan" class="fw-medium">-</div>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block mb-1">Berat Badan</small>
                                            <div id="detailBeratBadan" class="fw-medium">-</div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <small class="text-muted d-block mb-1">Golongan Darah</small>
                                        <div id="detailGolonganDarah" class="fw-medium">-</div>
                                    </div>

                                    <div class="mb-3">
                                        <small class="text-muted d-block mb-1">Alergi</small>
                                        <div id="detailAlergi" class="fw-medium">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Column 2 -->
                        <div class="col-md-6">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <h6 class="card-title mb-3 text-dark">BMI & Status</h6>

                                    <div class="mb-3">
                                        <small class="text-muted d-block mb-1">Body Mass Index (BMI)</small>
                                        <div id="detailBmi" class="fw-medium fs-5">-</div>
                                    </div>

                                    <div class="mb-4">
                                        <small class="text-muted d-block mb-1">Status BMI</small>
                                        <div id="detailBmiCategory" class="fw-medium">-</div>
                                    </div>

                                    <div class="border-top pt-3">
                                        <small class="text-muted d-block mb-1">Terdaftar Sejak</small>
                                        <div id="detailCreatedAt" class="fw-medium"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
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
                    <h6>Yakin ingin menghapus pengguna ini?</h6>
                    <p class="text-muted small">Data pengguna akan dihapus permanen dan tidak dapat dikembalikan.</p>
                    <input type="hidden" id="deletePenggunaId">
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
        // Get initials from name
        function getInitials(name) {
            const nameParts = name.split(' ');
            if (nameParts.length >= 2) {
                return (nameParts[0].charAt(0) + nameParts[1].charAt(0)).toUpperCase();
            }
            return name.substring(0, 2).toUpperCase();
        }

        // Format date
        function formatDate(dateString) {
            const date = new Date(dateString);
            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            return date.toLocaleDateString('id-ID', options);
        }

        // Get BMI class
        function getBmiClass(bmi) {
            if (!bmi) return '';
            if (bmi < 18.5) return 'bmi-underweight';
            if (bmi < 25) return 'bmi-normal';
            if (bmi < 30) return 'bmi-overweight';
            return 'bmi-obesity';
        }

        // Get BMI category text
        function getBmiCategoryText(category) {
            switch (category) {
                case 'Underweight':
                    return 'Underweight (Kekurangan berat badan)';
                case 'Normal':
                    return 'Normal (Berat badan ideal)';
                case 'Overweight':
                    return 'Overweight (Kelebihan berat badan)';
                case 'Obesity':
                    return 'Obesitas (Kegemukan)';
                default:
                    return category;
            }
        }

        // Show pengguna detail
        async function showDetail(id) {
            try {
                const response = await fetch(`/admin/manajemen-pengguna/${id}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    const pengguna = data.data;

                    // Set profile preview
                    const preview = document.getElementById('detailProfilePreview');
                    if (pengguna.foto_profile) {
                        // Check if file exists
                        const img = new Image();
                        img.src = `/profile/${pengguna.foto_profile}`;
                        img.onload = () => {
                            preview.innerHTML =
                                `<img src="/profile/${pengguna.foto_profile}" class="avatar-large" alt="${pengguna.nama_lengkap}">`;
                        };
                        img.onerror = () => {
                            const initials = getInitials(pengguna.nama_lengkap);
                            preview.innerHTML = `<div class="initials-large">${initials}</div>`;
                        };
                    } else {
                        const initials = getInitials(pengguna.nama_lengkap);
                        preview.innerHTML = `<div class="initials-large">${initials}</div>`;
                    }

                    // Set basic info
                    document.getElementById('detailNamaLengkap').textContent = pengguna.nama_lengkap;
                    document.getElementById('detailEmail').textContent = pengguna.email;
                    document.getElementById('detailUserId').textContent =
                        `ID: USR${String(pengguna.id).padStart(4, '0')}`;

                    // Set personal info
                    const jenisKelamin = pengguna.jenis_kelamin === 'L' ?
                        `<span class="d-flex align-items-center"><span class="gender-icon gender-male"><i class="fas fa-mars"></i></span>Laki-laki</span>` :
                        `<span class="d-flex align-items-center"><span class="gender-icon gender-female"><i class="fas fa-venus"></i></span>Perempuan</span>`;
                    document.getElementById('detailJenisKelamin').innerHTML = jenisKelamin;

                    document.getElementById('detailTinggiBadan').textContent = pengguna.tinggi_badan ?
                        `${pengguna.tinggi_badan} cm` :
                        '-';

                    document.getElementById('detailBeratBadan').textContent = pengguna.berat_badan ?
                        `${pengguna.berat_badan} kg` :
                        '-';

                    if (pengguna.bmi) {
                        const bmiClass = getBmiClass(pengguna.bmi);
                        document.getElementById('detailBmi').innerHTML =
                            `<span class="${bmiClass} bmi-indicator">${pengguna.bmi}</span>`;
                        document.getElementById('detailBmiCategory').innerHTML =
                            `<span class="${bmiClass} bmi-indicator">${getBmiCategoryText(pengguna.bmi_category)}</span>`;
                    } else {
                        document.getElementById('detailBmi').textContent = '-';
                        document.getElementById('detailBmiCategory').textContent = '-';
                    }

                    if (pengguna.golongan_darah) {
                        document.getElementById('detailGolonganDarah').innerHTML =
                            `<span class="blood-badge">${pengguna.golongan_darah}</span>`;
                    } else {
                        document.getElementById('detailGolonganDarah').textContent = '-';
                    }

                    document.getElementById('detailAlergi').innerHTML = pengguna.alergi ?
                        pengguna.alergi.split(',').map(allergy =>
                            `<span class="allergy-tag">${allergy.trim()}</span>`
                        ).join('') :
                        '-';

                    document.getElementById('detailCreatedAt').textContent = formatDate(pengguna.created_at);

                    // Show modal
                    const modal = new bootstrap.Modal(document.getElementById('detailPenggunaModal'));
                    modal.show();
                } else {
                    showToast('Gagal mengambil data pengguna', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Terjadi kesalahan', 'error');
            }
        }

        // Show delete confirmation
        function showDeleteConfirm(id) {
            document.getElementById('deletePenggunaId').value = id;
            const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
            modal.show();
        }

        // Delete pengguna
        async function confirmDelete() {
            const id = document.getElementById('deletePenggunaId').value;

            try {
                const response = await fetch(`/admin/manajemen-pengguna/${id}`, {
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
                    const row = document.querySelector(`tr[data-pengguna-id="${id}"]`);
                    if (row) {
                        row.remove();
                    }

                    // Reload if no rows left
                    setTimeout(() => {
                        const rows = document.querySelectorAll('#penggunaTableBody tr');
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
            const rows = document.querySelectorAll('#penggunaTableBody tr');

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
