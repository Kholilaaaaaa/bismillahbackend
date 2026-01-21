@extends('layouts.app')

@section('title', 'Manajemen Jadwal Workout - Gym GenZ Admin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/jadwal-workout.css') }}">
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
        .error-message {
            font-size: 0.8rem;
            margin-top: 0.25rem;
            color: var(--danger-color);
        }
        .schedule-date {
            min-width: 120px;
        }
        .schedule-time-cell {
            min-width: 100px;
        }
        .category-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.75rem;
            background-color: rgba(175, 105, 238, 0.1);
            color: var(--primary-color);
            border: 1px solid rgba(175, 105, 238, 0.2);
        }
        .form-label {
            font-weight: 500;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        .input-group-text {
            background-color: #f8fafc;
            border-color: #e2e8f0;
            color: var(--text-dark);
        }
        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(175, 105, 238, 0.25);
            border-color: var(--primary-color);
        }
        
        /* Quick Stats */
        .quick-stats {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-item {
            text-align: center;
            padding: 0.5rem;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            line-height: 1;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #64748b;
            margin-top: 0.5rem;
        }
        
        /* Responsive styles */
        @media (max-width: 768px) {
            .search-input-container {
                width: 100%;
                margin-bottom: 1rem;
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
            .quick-stats .row > div {
                margin-bottom: 1rem;
            }
        }
        
        @media (max-width: 576px) {
            .row.g-3 .col-md-6 {
                margin-bottom: 1rem;
            }
            .modal-dialog {
                margin: 0.5rem;
            }
            .pagination-container .d-flex {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            .pagination-info {
                order: -1;
            }
            .btn-group {
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            .btn-group .btn {
                flex: 1;
                min-width: 120px;
            }
        }
        
        /* Schedule Status */
        .schedule-status {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-upcoming {
            background-color: rgba(59, 130, 246, 0.1);
            color: #1d4ed8;
        }
        
        .status-today {
            background-color: rgba(16, 185, 129, 0.1);
            color: #047857;
        }
        
        .status-completed {
            background-color: rgba(107, 114, 128, 0.1);
            color: #4b5563;
        }
        
        /* Today's Schedule Highlight */
        .today-schedule {
            background: linear-gradient(90deg, rgba(16, 185, 129, 0.05), transparent);
            border-left: 3px solid #10b981;
        }
        
        /* Date Display */
        .date-display {
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .day-display {
            font-size: 0.9rem;
            color: #64748b;
            text-transform: capitalize;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-2 text-dark">Manajemen Jadwal Workout</h1>
                <p class="text-muted">Kelola jadwal workout member</p>
            </div>
            <button class="btn btn-purple" data-bs-toggle="modal" data-bs-target="#addJadwalModal">
                <i class="fas fa-plus-circle me-2"></i>Tambah Jadwal
            </button>
        </div>

        <!-- Search and Stats -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="search-input-container">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control" id="searchInput" placeholder="Cari jadwal...">
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-end gap-3">
                    <div class="text-end">
                        <h5 class="mb-0">{{ $jadwals->total() }}</h5>
                        <small class="text-muted">Total Jadwal</small>
                    </div>
                    <div class="vr"></div>
                </div>
            </div>
        </div>

        <!-- Jadwal Table View -->
        <div id="tableView" class="view-container">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Daftar Jadwal Workout</h5>
                    <div class="text-muted small">
                        Menampilkan {{ $jadwals->firstItem() ?: 0 }}-{{ $jadwals->lastItem() ?: 0 }} dari {{ $jadwals->total() }} jadwal
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 table-schedule">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>Nama Jadwal</th>
                                    <th>Kategori</th>
                                    <th class="schedule-date">Tanggal</th>
                                    <th class="schedule-time-cell">Jam</th>
                                    <th>Durasi</th>
                                    <th style="width: 150px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="jadwalTableBody">
                                @forelse ($jadwals as $index => $jadwal)
                                    @php
                                        $today = date('Y-m-d');
                                        $tomorrow = date('Y-m-d', strtotime('+1 day'));
                                        $scheduleDate = $jadwal->tanggal;
                                        $statusClass = '';
                                        $statusText = '';
                                        
                                        if ($scheduleDate == $today) {
                                            $statusClass = 'today-schedule';
                                            $statusText = 'HARI INI';
                                        } elseif ($scheduleDate < $today) {
                                            $statusText = 'SELESAI';
                                        } elseif ($scheduleDate == $tomorrow) {
                                            $statusText = 'BESOK';
                                        }
                                    @endphp
                                    <tr data-jadwal-id="{{ $jadwal->id }}" class="{{ $statusClass }}">
                                        <td data-label="No">{{ $jadwals->firstItem() + $index }}</td>
                                        <td data-label="Nama Jadwal">
                                            <div class="fw-medium">{{ $jadwal->nama_jadwal }}</div>
                                            @if($statusText)
                                                <span class="schedule-status status-{{ strtolower(str_replace(' ', '-', $statusText)) }} mt-1">
                                                    {{ $statusText }}
                                                </span>
                                            @endif
                                        </td>
                                        <td data-label="Kategori">
                                            <span class="category-badge">
                                                <i class="fas fa-dumbbell"></i>
                                                {{ $jadwal->kategori_jadwal }}
                                            </span>
                                        </td>
                                        <td data-label="Tanggal" class="schedule-date">
                                            <div class="date-display">{{ date('d M Y', strtotime($jadwal->tanggal)) }}</div>
                                            <div class="day-display">{{ date('l', strtotime($jadwal->tanggal)) }}</div>
                                        </td>
                                        <td data-label="Jam" class="schedule-time-cell">
                                            <div class="fw-medium">{{ date('H:i', strtotime($jadwal->jam)) }}</div>
                                        </td>
                                        <td data-label="Durasi">
                                            <div class="fw-medium">{{ $jadwal->durasi_workout }}</div>
                                        </td>
                                        <td data-label="Aksi">
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-sm btn-purple" onclick="editJadwal({{ $jadwal->id }})">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="showDeleteConfirm({{ $jadwal->id }})">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="no-data">
                                            <i class="fas fa-calendar-times"></i>
                                            <div>Tidak ada data jadwal workout</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    @if($jadwals->hasPages())
                    <div class="pagination-container">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="pagination-info">
                                Halaman {{ $jadwals->currentPage() }} dari {{ $jadwals->lastPage() }}
                            </div>
                            <nav>
                                <ul class="pagination">
                                    {{-- Previous Page Link --}}
                                    @if ($jadwals->onFirstPage())
                                        <li class="page-item disabled">
                                            <span class="page-link">
                                                <i class="fas fa-chevron-left"></i>
                                            </span>
                                        </li>
                                    @else
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $jadwals->previousPageUrl() }}" rel="prev">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    @endif

                                    {{-- Pagination Elements --}}
                                    @php
                                        $start = max(1, $jadwals->currentPage() - 2);
                                        $end = min($jadwals->lastPage(), $jadwals->currentPage() + 2);
                                    @endphp
                                    
                                    @if($start > 1)
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $jadwals->url(1) }}">1</a>
                                        </li>
                                        @if($start > 2)
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        @endif
                                    @endif
                                    
                                    @for ($i = $start; $i <= $end; $i++)
                                        <li class="page-item {{ ($jadwals->currentPage() == $i) ? 'active' : '' }}">
                                            <a class="page-link" href="{{ $jadwals->url($i) }}">{{ $i }}</a>
                                        </li>
                                    @endfor
                                    
                                    @if($end < $jadwals->lastPage())
                                        @if($end < $jadwals->lastPage() - 1)
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        @endif
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $jadwals->url($jadwals->lastPage()) }}">{{ $jadwals->lastPage() }}</a>
                                        </li>
                                    @endif

                                    {{-- Next Page Link --}}
                                    @if ($jadwals->hasMorePages())
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $jadwals->nextPageUrl() }}" rel="next">
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
    </div>

    <!-- Add Jadwal Modal -->
    <div class="modal fade modal-schedule" id="addJadwalModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Jadwal Workout Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addJadwalForm">
                        @csrf
                        <div class="row g-3">
                            <!-- Basic Info -->
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label">Nama Jadwal <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-dumbbell"></i>
                                        </span>
                                        <input type="text" class="form-control" id="nama_jadwal" name="nama_jadwal" 
                                               placeholder="Masukkan nama jadwal workout" required>
                                    </div>
                                    <div id="namaError" class="error-message"></div>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label">Kategori Jadwal <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-tags"></i>
                                        </span>
                                        <input type="text" class="form-control" id="kategori_jadwal" name="kategori_jadwal" 
                                               placeholder="Contoh: Cardio, Strength, Yoga, dll" required>
                                    </div>
                                    <div id="kategoriError" class="error-message"></div>
                                </div>
                            </div>

                            <!-- Date and Time -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-calendar"></i>
                                        </span>
                                        <input type="date" class="form-control" id="tanggal" name="tanggal" 
                                               min="{{ date('Y-m-d') }}" required>
                                    </div>
                                    <div id="tanggalError" class="error-message"></div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Jam <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-clock"></i>
                                        </span>
                                        <input type="time" class="form-control" id="jam" name="jam" required>
                                    </div>
                                    <div id="jamError" class="error-message"></div>
                                </div>
                            </div>

                            <!-- Duration -->
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label">Durasi Workout <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-hourglass-half"></i>
                                        </span>
                                        <input type="text" class="form-control" id="durasi_workout" name="durasi_workout" 
                                               placeholder="Contoh: 60 menit, 1.5 jam, 2 jam" required>
                                    </div>
                                    <div id="durasiError" class="error-message"></div>
                                    <div class="form-text">Contoh: 30 menit, 1 jam, 1.5 jam, 2 jam</div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-success" onclick="addNewJadwal()" id="addJadwalBtn">
                        <i class="fas fa-plus-circle me-2"></i>Tambah Jadwal
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Jadwal Modal -->
    <div class="modal fade modal-schedule" id="editJadwalModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Jadwal Workout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editJadwalForm">
                        @csrf
                        @method('PUT')
                        <input type="hidden" id="editJadwalId">
                        <div class="row g-3">
                            <!-- Basic Info -->
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label">Nama Jadwal <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-dumbbell"></i>
                                        </span>
                                        <input type="text" class="form-control" id="editNamaJadwal" name="nama_jadwal" required>
                                    </div>
                                    <div id="editNamaError" class="error-message"></div>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label">Kategori Jadwal <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-tags"></i>
                                        </span>
                                        <input type="text" class="form-control" id="editKategoriJadwal" name="kategori_jadwal" required>
                                    </div>
                                    <div id="editKategoriError" class="error-message"></div>
                                </div>
                            </div>

                            <!-- Date and Time -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-calendar"></i>
                                        </span>
                                        <input type="date" class="form-control" id="editTanggal" name="tanggal" required>
                                    </div>
                                    <div id="editTanggalError" class="error-message"></div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Jam <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-clock"></i>
                                        </span>
                                        <input type="time" class="form-control" id="editJam" name="jam" required>
                                    </div>
                                    <div id="editJamError" class="error-message"></div>
                                </div>
                            </div>

                            <!-- Duration -->
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label">Durasi Workout <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-hourglass-half"></i>
                                        </span>
                                        <input type="text" class="form-control" id="editDurasiWorkout" name="durasi_workout" required>
                                    </div>
                                    <div id="editDurasiError" class="error-message"></div>
                                    <div class="form-text">Contoh: 30 menit, 1 jam, 1.5 jam, 2 jam</div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-purple" onclick="updateJadwal()" id="updateJadwalBtn">
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
                    <h6>Yakin ingin menghapus jadwal ini?</h6>
                    <p class="text-muted small">Jadwal workout akan dihapus permanen dan tidak dapat dikembalikan.</p>
                    <input type="hidden" id="deleteJadwalId">
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">Batal</button>
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
        // Initialize date inputs
        document.addEventListener('DOMContentLoaded', function() {
            // Set minimum date for add form to today
            document.getElementById('tanggal').min = new Date().toISOString().split('T')[0];
            
            // Set default time for add form to next hour
            const now = new Date();
            const nextHour = new Date(now.getTime() + 60 * 60 * 1000);
            document.getElementById('jam').value = nextHour.getHours().toString().padStart(2, '0') + ':00';
        });

        // Clear error messages
        function clearErrors() {
            const errorElements = document.querySelectorAll('.error-message');
            errorElements.forEach(el => el.textContent = '');
        }

        // Add new jadwal
        async function addNewJadwal() {
            const form = document.getElementById('addJadwalForm');
            const formData = new FormData();
            
            // Collect form data
            const formElements = form.elements;
            for (let element of formElements) {
                if (element.name) {
                    formData.append(element.name, element.value);
                }
            }
            
            // Clear previous errors
            clearErrors();
            
            // Show loading
            const btn = document.getElementById('addJadwalBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
            btn.disabled = true;

            try {
                const response = await fetch('{{ route("manajemen-jadwal.store") }}', {
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
                    
                    // Reset time to next hour
                    const now = new Date();
                    const nextHour = new Date(now.getTime() + 60 * 60 * 1000);
                    document.getElementById('jam').value = nextHour.getHours().toString().padStart(2, '0') + ':00';
                    
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addJadwalModal'));
                    modal.hide();
                    
                    // Show success message
                    showToast(data.message, 'success');
                    
                    // Reload page to show new jadwal
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

        // Edit jadwal
        async function editJadwal(id) {
            try {
                const response = await fetch(`/admin/manajemen-jadwal/${id}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    const jadwal = data.data;
                    
                    // Fill form
                    document.getElementById('editJadwalId').value = jadwal.id;
                    document.getElementById('editNamaJadwal').value = jadwal.nama_jadwal;
                    document.getElementById('editKategoriJadwal').value = jadwal.kategori_jadwal;
                    document.getElementById('editTanggal').value = jadwal.tanggal;
                    document.getElementById('editJam').value = jadwal.jam;
                    document.getElementById('editDurasiWorkout').value = jadwal.durasi_workout;
                    
                    // Clear errors
                    clearErrors();
                    
                    // Show modal
                    const modal = new bootstrap.Modal(document.getElementById('editJadwalModal'));
                    modal.show();
                } else {
                    showToast('Gagal mengambil data jadwal', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Terjadi kesalahan', 'error');
            }
        }

        // Update jadwal
        async function updateJadwal() {
            const id = document.getElementById('editJadwalId').value;
            const form = document.getElementById('editJadwalForm');
            const formData = new FormData();
            
            // Collect form data
            const formElements = form.elements;
            for (let element of formElements) {
                if (element.name) {
                    formData.append(element.name, element.value);
                }
            }
            
            // Add method override
            formData.append('_method', 'PUT');
            
            // Clear previous errors
            clearErrors();
            
            // Show loading
            const btn = document.getElementById('updateJadwalBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
            btn.disabled = true;

            try {
                const response = await fetch(`/admin/manajemen-jadwal/${id}`, {
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
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editJadwalModal'));
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
                            const errorElement = document.getElementById('edit' + key.charAt(0).toUpperCase() + key.slice(1) + 'Error');
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

        // Show delete confirmation
        function showDeleteConfirm(id) {
            document.getElementById('deleteJadwalId').value = id;
            const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
            modal.show();
        }

        // Delete jadwal
        async function confirmDelete() {
            const id = document.getElementById('deleteJadwalId').value;
            
            try {
                const response = await fetch(`/admin/manajemen-jadwal/${id}`, {
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
                    const tableRow = document.querySelector(`tr[data-jadwal-id="${id}"]`);
                    if (tableRow) {
                        tableRow.remove();
                    }
                    
                    // Reload if no rows left
                    setTimeout(() => {
                        const tableRows = document.querySelectorAll('#jadwalTableBody tr');
                        if (tableRows.length === 0) {
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
            
            // Search in table view
            const tableRows = document.querySelectorAll('#jadwalTableBody tr');
            tableRows.forEach(row => {
                const jadwalName = row.querySelector('td:nth-child(2) .fw-medium').textContent.toLowerCase();
                const kategori = row.querySelector('td:nth-child(3) .category-badge').textContent.toLowerCase();
                
                if (jadwalName.includes(searchTerm) || kategori.includes(searchTerm)) {
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
        document.getElementById('addJadwalModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('addJadwalForm').reset();
            
            // Reset time to next hour
            const now = new Date();
            const nextHour = new Date(now.getTime() + 60 * 60 * 1000);
            document.getElementById('jam').value = nextHour.getHours().toString().padStart(2, '0') + ':00';
            
            clearErrors();
        });

        // Reset edit modal when closed
        document.getElementById('editJadwalModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('editJadwalForm').reset();
            clearErrors();
        });

        // Make table responsive on mobile
        function makeTableResponsive() {
            if (window.innerWidth <= 768) {
                const table = document.querySelector('.table-schedule');
                if (!table) return;
                
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