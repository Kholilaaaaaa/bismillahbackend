@extends('layouts.app')

@section('title', 'Manajemen Workout - Gym GenZ Admin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/workout.css') }}">
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
        .jadwal-info {
            background: #f8fafc;
            border-radius: 8px;
            padding: 0.5rem;
            margin-top: 0.5rem;
            font-size: 0.85rem;
            color: #64748b;
        }
        .jadwal-info i {
            margin-right: 0.25rem;
            color: var(--primary-color);
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
        .form-control:focus,
        .form-select:focus {
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
        
        /* Workout Status */
        .workout-status {
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
        
        .status-belum {
            background-color: rgba(107, 114, 128, 0.1);
            color: #4b5563;
        }
        
        .status-sedang-dilakukan {
            background-color: rgba(59, 130, 246, 0.1);
            color: #1d4ed8;
        }
        
        .status-selesai {
            background-color: rgba(16, 185, 129, 0.1);
            color: #047857;
        }
        
        /* Category Display */
        .category-display {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .category-without-equipment {
            background-color: rgba(59, 130, 246, 0.1);
            color: #1d4ed8;
        }
        
        .category-with-equipment {
            background-color: rgba(245, 158, 11, 0.1);
            color: #b45309;
        }
        
        /* Exercise Count */
        .exercise-count-display {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.5rem;
            background-color: #f8fafc;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        /* Equipment Display */
        .equipment-display-inline {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.5rem;
            background-color: #f1f5f9;
            border-radius: 8px;
            font-size: 0.8rem;
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-2 text-dark">Manajemen Workout</h1>
                <p class="text-muted">Kelola workout member</p>
            </div>
            <button class="btn btn-purple" data-bs-toggle="modal" data-bs-target="#addWorkoutModal">
                <i class="fas fa-plus-circle me-2"></i>Tambah Workout
            </button>
        </div>

        <!-- Search and Stats -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="search-input-container">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control" id="searchInput" placeholder="Cari workout...">
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-end gap-3">
                    <div class="text-end">
                        <h5 class="mb-0">{{ $workouts->total() }}</h5>
                        <small class="text-muted">Total Workout</small>
                    </div>
                    <div class="vr"></div>
                </div>
            </div>
        </div>

        <!-- Workout Table View -->
        <div id="tableView" class="view-container">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Daftar Workout</h5>
                    <div class="text-muted small">
                        Menampilkan {{ $workouts->firstItem() ?: 0 }}-{{ $workouts->lastItem() ?: 0 }} dari {{ $workouts->total() }} workout
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 table-workout">
                            <thead class="table-light text-center">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th class="workout-name-cell">Nama Workout</th>
                                    <th>Deskripsi</th>
                                    <th>Jadwal</th>
                                    <th>Equipment</th>
                                    <th>Kategori</th>
                                    <th class="workout-exercises-cell">Exercises</th>
                                    <th>Status</th>
                                    <th style="width: 150px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="workoutTableBody" class="text-center">
                                @forelse ($workouts as $index => $workout)
                                    <tr data-workout-id="{{ $workout->id }}">
                                        <td data-label="No">{{ $workouts->firstItem() + $index }}</td>
                                        <td data-label="Nama Workout" class="workout-name-cell">
                                            <div class="fw-medium">{{ $workout->nama_workout }}</div>
                                        </td>
                                        <td data-label="Deskripsi" class="workout-description-cell">
                                            <div class="description-display">{{ $workout->deskripsi }}</div>
                                        </td>
                                        <td data-label="Jadwal">
                                            @if($workout->jadwalWorkout)
                                                <div class="fw-medium">{{ $workout->jadwalWorkout->nama_jadwal }}</div>
                                                <div class="text-muted small">
                                                    {{ date('d M Y', strtotime($workout->jadwalWorkout->tanggal)) }}
                                                    {{ date('H:i', strtotime($workout->jadwalWorkout->jam)) }}
                                                </div>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td data-label="Equipment" class="workout-equipment-cell">
                                            <div class="equipment-display-inline">
                                                {{ $workout->equipment }}
                                            </div>
                                        </td>
                                        <td data-label="Kategori">
                                            <span class="category-display category-{{ strtolower(str_replace(' ', '-', $workout->kategori)) }}">
                                                {{ $workout->kategori }}
                                            </span>
                                        </td>
                                        <td data-label="Exercises" class="workout-exercises-cell">
                                            <div class="exercise-count-display">
                                                {{ $workout->exercises }}
                                            </div>
                                        </td>
                                        <td data-label="Status">
                                            <span class="workout-status status-{{ str_replace(' ', '-', $workout->status) }}">
                                                {{ $workout->status }}
                                            </span>
                                        </td>
                                        <td data-label="Aksi">
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-sm btn-purple" onclick="editWorkout({{ $workout->id }})">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="showDeleteConfirm({{ $workout->id }})">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="no-data">
                                            <i class="fas fa-dumbbell"></i>
                                            <div>Tidak ada data workout</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    @if($workouts->hasPages())
                    <div class="pagination-container">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="pagination-info">
                                Halaman {{ $workouts->currentPage() }} dari {{ $workouts->lastPage() }}
                            </div>
                            <nav>
                                <ul class="pagination">
                                    {{-- Previous Page Link --}}
                                    @if ($workouts->onFirstPage())
                                        <li class="page-item disabled">
                                            <span class="page-link">
                                                <i class="fas fa-chevron-left"></i>
                                            </span>
                                        </li>
                                    @else
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $workouts->previousPageUrl() }}" rel="prev">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    @endif

                                    {{-- Pagination Elements --}}
                                    @php
                                        $start = max(1, $workouts->currentPage() - 2);
                                        $end = min($workouts->lastPage(), $workouts->currentPage() + 2);
                                    @endphp
                                    
                                    @if($start > 1)
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $workouts->url(1) }}">1</a>
                                        </li>
                                        @if($start > 2)
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        @endif
                                    @endif
                                    
                                    @for ($i = $start; $i <= $end; $i++)
                                        <li class="page-item {{ ($workouts->currentPage() == $i) ? 'active' : '' }}">
                                            <a class="page-link" href="{{ $workouts->url($i) }}">{{ $i }}</a>
                                        </li>
                                    @endfor
                                    
                                    @if($end < $workouts->lastPage())
                                        @if($end < $workouts->lastPage() - 1)
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        @endif
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $workouts->url($workouts->lastPage()) }}">{{ $workouts->lastPage() }}</a>
                                        </li>
                                    @endif

                                    {{-- Next Page Link --}}
                                    @if ($workouts->hasMorePages())
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $workouts->nextPageUrl() }}" rel="next">
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

    <!-- Add Workout Modal -->
    <div class="modal fade modal-workout" id="addWorkoutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Workout Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addWorkoutForm">
                        @csrf
                        <div class="row g-3">
                            <!-- Jadwal Selection -->
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label">Pilih Jadwal <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-calendar-alt"></i>
                                        </span>
                                        <select class="form-select jadwal-dropdown" id="jadwal_workout_id" name="jadwal_workout_id" required>
                                            <option value="">Pilih Jadwal</option>
                                            @foreach($jadwals as $jadwal)
                                                <option value="{{ $jadwal->id }}">
                                                    {{ $jadwal->nama_jadwal }} - 
                                                    {{ date('d M Y', strtotime($jadwal->tanggal)) }} 
                                                    {{ date('H:i', strtotime($jadwal->jam)) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div id="jadwalError" class="error-message"></div>
                                    @if($jadwals->isEmpty())
                                    <div class="alert alert-warning mt-2" role="alert">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        Tidak ada jadwal yang tersedia. Silakan tambah jadwal terlebih dahulu.
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Workout Name -->
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label">Nama Workout <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-dumbbell"></i>
                                        </span>
                                        <input type="text" class="form-control" id="nama_workout" name="nama_workout" 
                                               placeholder="Masukkan nama workout" required>
                                    </div>
                                    <div id="namaError" class="error-message"></div>
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label">Deskripsi <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-align-left"></i>
                                        </span>
                                        <textarea class="form-control description-textarea" id="deskripsi" name="deskripsi" 
                                                  rows="3" placeholder="Masukkan deskripsi workout" required></textarea>
                                    </div>
                                    <div id="deskripsiError" class="error-message"></div>
                                </div>
                            </div>

                            <!-- Equipment and Category -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Equipment <span class="text-danger">*</span></label>
                                    <div class="input-group equipment-input">
                                        <span class="input-group-text">
                                            <i class="fas fa-tools"></i>
                                        </span>
                                        <input type="text" class="form-control" id="equipment" name="equipment" 
                                               placeholder="Contoh: Dumbbell, Treadmill, dll" required>
                                    </div>
                                    <div id="equipmentError" class="error-message"></div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Kategori <span class="text-danger">*</span></label>
                                    <div class="input-group category-selector">
                                        <span class="input-group-text">
                                            <i class="fas fa-tags"></i>
                                        </span>
                                        <select class="form-select" id="kategori" name="kategori" required>
                                            <option value="">Pilih Kategori</option>
                                            <option value="Without Equipment">Without Equipment</option>
                                            <option value="With Equipment">With Equipment</option>
                                        </select>
                                    </div>
                                    <div id="kategoriError" class="error-message"></div>
                                </div>
                            </div>

                            <!-- Exercises and Status -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Jumlah Exercises <span class="text-danger">*</span></label>
                                    <div class="input-group exercise-input">
                                        <span class="input-group-text">
                                            <i class="fas fa-list-ol"></i> Exercises
                                        </span>
                                        <input type="number" class="form-control" id="exercises" name="exercises" 
                                               min="1" placeholder="0" required>
                                    </div>
                                    <div id="exercisesError" class="error-message"></div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Status <span class="text-danger">*</span></label>
                                    <div class="input-group status-selector">
                                        <span class="input-group-text">
                                            <i class="fas fa-info-circle"></i>
                                        </span>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="">Pilih Status</option>
                                            <option value="belum">Belum</option>
                                            <option value="sedang dilakukan">Sedang Dilakukan</option>
                                            <option value="selesai">Selesai</option>
                                        </select>
                                    </div>
                                    <div id="statusError" class="error-message"></div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-purple" onclick="addNewWorkout()" id="addWorkoutBtn" {{ $jadwals->isEmpty() ? 'disabled' : '' }}>
                        <i class="fas fa-plus-circle me-2"></i>Tambah Workout
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Workout Modal -->
    <div class="modal fade modal-workout" id="editWorkoutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Workout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editWorkoutForm">
                        @csrf
                        @method('PUT')
                        <input type="hidden" id="editWorkoutId">
                        <div class="row g-3">
                            <!-- Jadwal Selection -->
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label">Pilih Jadwal <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-calendar-alt"></i>
                                        </span>
                                        <select class="form-select jadwal-dropdown" id="editJadwalWorkoutId" name="jadwal_workout_id" required>
                                            <option value="">Pilih Jadwal</option>
                                            @foreach($jadwals as $jadwal)
                                                <option value="{{ $jadwal->id }}">
                                                    {{ $jadwal->nama_jadwal }} - 
                                                    {{ date('d M Y', strtotime($jadwal->tanggal)) }} 
                                                    {{ date('H:i', strtotime($jadwal->jam)) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div id="editJadwalError" class="error-message"></div>
                                </div>
                            </div>

                            <!-- Workout Name -->
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label">Nama Workout <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-dumbbell"></i>
                                        </span>
                                        <input type="text" class="form-control" id="editNamaWorkout" name="nama_workout" required>
                                    </div>
                                    <div id="editNamaError" class="error-message"></div>
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label">Deskripsi <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-align-left"></i>
                                        </span>
                                        <textarea class="form-control description-textarea" id="editDeskripsi" name="deskripsi" 
                                                  rows="3" required></textarea>
                                    </div>
                                    <div id="editDeskripsiError" class="error-message"></div>
                                </div>
                            </div>

                            <!-- Equipment and Category -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Equipment <span class="text-danger">*</span></label>
                                    <div class="input-group equipment-input">
                                        <span class="input-group-text">
                                            <i class="fas fa-tools"></i>
                                        </span>
                                        <input type="text" class="form-control" id="editEquipment" name="equipment" required>
                                    </div>
                                    <div id="editEquipmentError" class="error-message"></div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Kategori <span class="text-danger">*</span></label>
                                    <div class="input-group category-selector">
                                        <span class="input-group-text">
                                            <i class="fas fa-tags"></i>
                                        </span>
                                        <select class="form-select" id="editKategori" name="kategori" required>
                                            <option value="">Pilih Kategori</option>
                                            <option value="Without Equipment">Without Equipment</option>
                                            <option value="With Equipment">With Equipment</option>
                                        </select>
                                    </div>
                                    <div id="editKategoriError" class="error-message"></div>
                                </div>
                            </div>

                            <!-- Exercises and Status -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Jumlah Exercises <span class="text-danger">*</span></label>
                                    <div class="input-group exercise-input">
                                        <span class="input-group-text">
                                            <i class="fas fa-list-ol"></i> Exercises
                                        </span>
                                        <input type="number" class="form-control" id="editExercises" name="exercises" 
                                               min="1" required>
                                    </div>
                                    <div id="editExercisesError" class="error-message"></div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Status <span class="text-danger">*</span></label>
                                    <div class="input-group status-selector">
                                        <span class="input-group-text">
                                            <i class="fas fa-info-circle"></i>
                                        </span>
                                        <select class="form-select" id="editStatus" name="status" required>
                                            <option value="">Pilih Status</option>
                                            <option value="belum">Belum</option>
                                            <option value="sedang dilakukan">Sedang Dilakukan</option>
                                            <option value="selesai">Selesai</option>
                                        </select>
                                    </div>
                                    <div id="editStatusError" class="error-message"></div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-purple" onclick="updateWorkout()" id="updateWorkoutBtn">
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
                    <h6>Yakin ingin menghapus workout ini?</h6>
                    <p class="text-muted small">Workout akan dihapus permanen dan tidak dapat dikembalikan.</p>
                    <input type="hidden" id="deleteWorkoutId">
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
        // Clear error messages
        function clearErrors() {
            const errorElements = document.querySelectorAll('.error-message');
            errorElements.forEach(el => el.textContent = '');
        }

        // Load available jadwals for edit form
        async function loadAvailableJadwals() {
            try {
                const response = await fetch('{{ route("manajemen-workout.get-jadwals") }}', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();
                if (data.success) {
                    const select = document.getElementById('editJadwalWorkoutId');
                    const currentValue = select.value;
                    
                    // Clear options
                    select.innerHTML = '<option value="">Pilih Jadwal</option>';
                    
                    // Add new options
                    data.data.forEach(jadwal => {
                        const option = document.createElement('option');
                        option.value = jadwal.id;
                        option.textContent = `${jadwal.nama_jadwal} - ${formatDate(jadwal.tanggal)} ${jadwal.jam}`;
                        select.appendChild(option);
                    });
                    
                    // Restore current value if exists
                    if (currentValue) {
                        select.value = currentValue;
                    }
                }
            } catch (error) {
                console.error('Error loading jadwals:', error);
            }
        }

        // Format date helper
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('id-ID', {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });
        }

        // Add new workout
        async function addNewWorkout() {
            const form = document.getElementById('addWorkoutForm');
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
            const btn = document.getElementById('addWorkoutBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
            btn.disabled = true;

            try {
                const response = await fetch('{{ route("manajemen-workout.store") }}', {
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
                    
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addWorkoutModal'));
                    modal.hide();
                    
                    // Show success message
                    showToast(data.message, 'success');
                    
                    // Reload page to show new workout
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

        // Edit workout
        async function editWorkout(id) {
            try {
                const response = await fetch(`/admin/manajemen-workout/${id}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    const workout = data.data;
                    
                    // Fill form
                    document.getElementById('editWorkoutId').value = workout.id;
                    document.getElementById('editNamaWorkout').value = workout.nama_workout;
                    document.getElementById('editDeskripsi').value = workout.deskripsi;
                    document.getElementById('editEquipment').value = workout.equipment;
                    document.getElementById('editKategori').value = workout.kategori;
                    document.getElementById('editExercises').value = workout.exercises;
                    document.getElementById('editStatus').value = workout.status;
                    
                    // Load available jadwals
                    await loadAvailableJadwals();
                    
                    // Set jadwal value
                    if (workout.jadwal_workout_id) {
                        document.getElementById('editJadwalWorkoutId').value = workout.jadwal_workout_id;
                    }
                    
                    // Clear errors
                    clearErrors();
                    
                    // Show modal
                    const modal = new bootstrap.Modal(document.getElementById('editWorkoutModal'));
                    modal.show();
                } else {
                    showToast('Gagal mengambil data workout', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Terjadi kesalahan', 'error');
            }
        }

        // Update workout
        async function updateWorkout() {
            const id = document.getElementById('editWorkoutId').value;
            const form = document.getElementById('editWorkoutForm');
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
            const btn = document.getElementById('updateWorkoutBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
            btn.disabled = true;

            try {
                const response = await fetch(`/admin/manajemen-workout/${id}`, {
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
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editWorkoutModal'));
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
            document.getElementById('deleteWorkoutId').value = id;
            const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
            modal.show();
        }

        // Delete workout
        async function confirmDelete() {
            const id = document.getElementById('deleteWorkoutId').value;
            
            try {
                const response = await fetch(`/admin/manajemen-workout/${id}`, {
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
                    const tableRow = document.querySelector(`tr[data-workout-id="${id}"]`);
                    if (tableRow) {
                        tableRow.remove();
                    }
                    
                    // Reload if no rows left
                    setTimeout(() => {
                        const tableRows = document.querySelectorAll('#workoutTableBody tr');
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
            const tableRows = document.querySelectorAll('#workoutTableBody tr');
            tableRows.forEach(row => {
                const workoutName = row.querySelector('td:nth-child(2) .fw-medium').textContent.toLowerCase();
                const jadwalName = row.querySelector('td:nth-child(4) .fw-medium').textContent.toLowerCase();
                
                if (workoutName.includes(searchTerm) || jadwalName.includes(searchTerm)) {
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
        document.getElementById('addWorkoutModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('addWorkoutForm').reset();
            clearErrors();
        });

        // Reset edit modal when closed
        document.getElementById('editWorkoutModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('editWorkoutForm').reset();
            clearErrors();
        });

        // Make table responsive on mobile
        function makeTableResponsive() {
            if (window.innerWidth <= 768) {
                const table = document.querySelector('.table-workout');
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
            
            // Set default exercises value
            document.getElementById('exercises').value = 1;
            document.getElementById('editExercises').value = 1;
        });
    </script>
@endpush