@extends('layouts.app')

@section('title', 'Manajemen Chatbot - Gym GenZ Admin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/manajemen-chatbot.css') }}">
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
        
        /* Source Badge */
        .source-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge-csv {
            background-color: rgba(16, 185, 129, 0.15);
            color: #047857;
        }
        
        .badge-manual {
            background-color: rgba(59, 130, 246, 0.15);
            color: #1d4ed8;
        }
        
        .badge-sample {
            background-color: rgba(168, 85, 247, 0.15);
            color: #7c3aed;
        }
        
        .badge-import {
            background-color: rgba(245, 158, 11, 0.15);
            color: #b45309;
        }
        
        /* Question Preview */
        .question-preview {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .question-full {
            max-height: 100px;
            overflow-y: auto;
            word-break: break-word;
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
            .question-preview {
                max-width: 150px;
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
        
        /* Preview Modal */
        .preview-content {
            max-height: 300px;
            overflow-y: auto;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 8px;
            margin-top: 1rem;
        }
        
        .preview-content h6 {
            color: #334155;
            margin-bottom: 0.5rem;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 0.5rem;
        }
        
        .preview-content p {
            margin-bottom: 0;
            white-space: pre-wrap;
        }
        
        /* File Upload */
        .file-upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 1rem;
        }
        
        .file-upload-area:hover {
            border-color: var(--primary-color);
            background-color: rgba(175, 105, 238, 0.05);
        }
        
        .file-upload-area i {
            font-size: 3rem;
            color: #94a3b8;
            margin-bottom: 1rem;
        }
        
        .file-upload-area:hover i {
            color: var(--primary-color);
        }
        
        .file-info {
            background: #f1f5f9;
            border-radius: 6px;
            padding: 0.75rem;
            margin-top: 0.5rem;
            font-size: 0.85rem;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid manajemen-chatbot-page">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-2 text-dark">Manajemen Knowledge Chatbot</h1>
                <p class="text-muted">Kelola pengetahuan untuk chatbot Gym GenZ</p>
            </div>
            <div class="btn-group">
                <button class="btn btn-purple" data-bs-toggle="modal" data-bs-target="#importCsvModal">
                    <i class="fas fa-file-import me-2"></i>Import CSV
                </button>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addManualModal">
                    <i class="fas fa-plus-circle me-2"></i>Tambah Manual
                </button>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase">Total Pertanyaan</h6>
                                <h3 id="totalQuestions" class="mb-0">0</h3>
                            </div>
                            <div class="bg-primary bg-opacity-10 p-3 rounded">
                                <i class="fas fa-question-circle text-primary fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase">Total Sumber</h6>
                                <h3 id="totalSources" class="mb-0">0</h3>
                            </div>
                            <div class="bg-success bg-opacity-10 p-3 rounded">
                                <i class="fas fa-database text-success fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase">Update Terakhir</h6>
                                <h5 id="lastUpdate" class="mb-0">-</h5>
                            </div>
                            <div class="bg-info bg-opacity-10 p-3 rounded">
                                <i class="fas fa-history text-info fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase">CSV Import</h6>
                                <h3 id="csvCount" class="mb-0">0</h3>
                            </div>
                            <div class="bg-warning bg-opacity-10 p-3 rounded">
                                <i class="fas fa-file-csv text-warning fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="search-input-container">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control" id="searchInput" placeholder="Cari pertanyaan atau jawaban...">
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-end gap-3 align-items-center">
                    <select class="form-select w-auto" id="sourceFilter">
                        <option value="">Semua Sumber</option>
                        <option value="csv">CSV</option>
                        <option value="manual">Manual</option>
                        <option value="sample">Sample</option>
                        <option value="csv_import">CSV Import</option>
                    </select>
                    <div class="text-end">
                        <h5 class="mb-0" id="filteredCount">0</h5>
                        <small class="text-muted">Tampil</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Knowledge Table -->
        <div id="tableView" class="view-container">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Daftar Knowledge</h5>
                    <div class="text-muted small">
                        <span id="showingInfo">Menampilkan 0-0 dari 0 knowledge</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 table-chatbot">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 60px;">ID</th>
                                    <th>Pertanyaan</th>
                                    <th>Jawaban Preview</th>
                                    <th style="width: 120px;">Sumber</th>
                                    <th style="width: 150px;">Dibuat</th>
                                    <th style="width: 150px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="knowledgeTableBody">
                                <!-- Data will be loaded via JavaScript -->
                                <tr>
                                    <td colspan="6" class="text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="pagination-container">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="pagination-info" id="paginationInfo">
                                Halaman 1 dari 1
                            </div>
                            <nav>
                                <ul class="pagination" id="pagination">
                                    <!-- Pagination will be loaded via JavaScript -->
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Import CSV Modal -->
    <div class="modal fade modal-chatbot" id="importCsvModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Import Data dari CSV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="importCsvForm">
                        @csrf
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Format CSV harus memiliki header: <strong>question,answer</strong>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="file-upload-area" onclick="document.getElementById('csvFile').click()">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <div class="mb-2">
                                        <strong>Klik untuk upload file CSV</strong>
                                    </div>
                                    <div class="text-muted small">
                                        Format: .csv, .txt | Maks: 10MB
                                    </div>
                                </div>
                                <input type="file" class="d-none" id="csvFile" name="file" accept=".csv,.txt">
                                
                                <div id="fileInfo" class="file-info d-none">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-file-csv me-2"></i>
                                            <span id="fileName"></span>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFile()">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-purple" onclick="importCSV()" id="importCSVBtn" disabled>
                        <i class="fas fa-file-import me-2"></i>Import CSV
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Manual Modal -->
    <div class="modal fade modal-chatbot" id="addManualModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Knowledge Manual</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addManualForm">
                        @csrf
                        <div class="row g-3">
                            <!-- Question -->
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label">Pertanyaan <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-question-circle"></i>
                                        </span>
                                        <input type="text" class="form-control" id="question" name="question" 
                                               placeholder="Masukkan pertanyaan" required maxlength="500">
                                    </div>
                                    <div class="form-text text-end">
                                        <span id="questionCounter">0/500</span> karakter
                                    </div>
                                    <div id="questionError" class="error-message"></div>
                                </div>
                            </div>

                            <!-- Answer -->
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label">Jawaban <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-comment-dots"></i>
                                        </span>
                                        <textarea class="form-control" id="answer" name="answer" 
                                                  rows="6" placeholder="Masukkan jawaban" required maxlength="5000"></textarea>
                                    </div>
                                    <div class="form-text text-end">
                                        <span id="answerCounter">0/5000</span> karakter
                                    </div>
                                    <div id="answerError" class="error-message"></div>
                                </div>
                            </div>

                            <!-- Source -->
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label">Sumber</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-database"></i>
                                        </span>
                                        <input type="text" class="form-control" id="source" name="source" 
                                               placeholder="manual" value="manual">
                                    </div>
                                    <div id="sourceError" class="error-message"></div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-success" onclick="addManualKnowledge()" id="addManualBtn">
                        <i class="fas fa-save me-2"></i>Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade modal-chatbot" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Knowledge</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm">
                        @csrf
                        @method('PUT')
                        <input type="hidden" id="editId">
                        <div class="row g-3">
                            <!-- Question -->
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label">Pertanyaan <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-question-circle"></i>
                                        </span>
                                        <input type="text" class="form-control" id="editQuestion" name="question" required maxlength="500">
                                    </div>
                                    <div class="form-text text-end">
                                        <span id="editQuestionCounter">0/500</span> karakter
                                    </div>
                                    <div id="editQuestionError" class="error-message"></div>
                                </div>
                            </div>

                            <!-- Answer -->
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label">Jawaban <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-comment-dots"></i>
                                        </span>
                                        <textarea class="form-control" id="editAnswer" name="answer" 
                                                  rows="6" required maxlength="5000"></textarea>
                                    </div>
                                    <div class="form-text text-end">
                                        <span id="editAnswerCounter">0/5000</span> karakter
                                    </div>
                                    <div id="editAnswerError" class="error-message"></div>
                                </div>
                            </div>

                            <!-- Source -->
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label">Sumber</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-database"></i>
                                        </span>
                                        <input type="text" class="form-control" id="editSource" name="source" maxlength="100">
                                    </div>
                                    <div id="editSourceError" class="error-message"></div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-purple" onclick="updateKnowledge()" id="updateBtn">
                        <i class="fas fa-save me-2"></i>Update
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <div class="modal fade modal-chatbot" id="previewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Knowledge</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="preview-content">
                        <h6>Pertanyaan:</h6>
                        <p id="previewQuestion"></p>
                        
                        <h6 class="mt-4">Jawaban:</h6>
                        <p id="previewAnswer"></p>
                        
                        <h6 class="mt-4">Informasi:</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>ID:</strong> <span id="previewId"></span></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Sumber:</strong> <span id="previewSource"></span></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Dibuat:</strong> <span id="previewCreated"></span></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Diupdate:</strong> <span id="previewUpdated"></span></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">Tutup</button>
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
                    <h6>Yakin ingin menghapus knowledge ini?</h6>
                    <p class="text-muted small">Knowledge akan dihapus permanen dan tidak dapat dikembalikan.</p>
                    <input type="hidden" id="deleteId">
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
let currentPage = 1;
let perPage = 20;
let totalItems = 0;
let totalPages = 1;
let searchQuery = '';
let sourceFilter = '';

// Base URLs - GANTI DENGAN ROUTE YANG BENAR
const baseURL = {
    dashboard: '{{ route("manajemen-chatbot.dashboard") }}',
    list: '{{ route("manajemen-chatbot.list") }}',
    storeCSV: '{{ route("manajemen-chatbot.storeCSV") }}',
    storeManual: '{{ route("manajemen-chatbot.storeManual") }}',
    show: (id) => `{{ url("admin/manajemen-chatbot") }}/${id}`,
    update: (id) => `{{ url("admin/manajemen-chatbot") }}/${id}`,
    destroy: (id) => `{{ url("admin/manajemen-chatbot") }}/${id}`,
};

// Initialize
$(document).ready(function() {
    console.log('Initializing Manajemen Chatbot...');
    console.log('Routes:', baseURL);
    console.log('CSRF Token:', '{{ csrf_token() }}');
    
    loadDashboard();
    loadKnowledge();
    setupEventListeners();
});

// Setup event listeners
function setupEventListeners() {
    // Search input with debounce
    let searchTimer;
    $('#searchInput').on('keyup', function(e) {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            searchQuery = $(this).val();
            currentPage = 1;
            loadKnowledge();
        }, 500);
    });

    // Source filter
    $('#sourceFilter').on('change', function() {
        sourceFilter = $(this).val();
        currentPage = 1;
        loadKnowledge();
    });

    // Character counters
    $('#question').on('input', function() {
        $('#questionCounter').text($(this).val().length + '/500');
    });

    $('#answer').on('input', function() {
        $('#answerCounter').text($(this).val().length + '/5000');
    });

    $('#editQuestion').on('input', function() {
        $('#editQuestionCounter').text($(this).val().length + '/500');
    });

    $('#editAnswer').on('input', function() {
        $('#editAnswerCounter').text($(this).val().length + '/5000');
    });

    // File upload
    $('#csvFile').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            $('#fileName').text(file.name + ' (' + formatBytes(file.size) + ')');
            $('#fileInfo').removeClass('d-none');
            $('#importCSVBtn').prop('disabled', false);
        }
    });

    // Modal reset
    $('#importCsvModal').on('hidden.bs.modal', function() {
        $('#csvFile').val('');
        $('#fileInfo').addClass('d-none');
        $('#importCSVBtn').prop('disabled', true);
    });

    $('#addManualModal').on('hidden.bs.modal', function() {
        $('#addManualForm')[0].reset();
        $('#questionCounter').text('0/500');
        $('#answerCounter').text('0/5000');
        clearErrors(['question', 'answer', 'source']);
    });

    $('#editModal').on('hidden.bs.modal', function() {
        clearErrors(['editQuestion', 'editAnswer', 'editSource']);
    });
}

// Load dashboard statistics
async function loadDashboard() {
    try {
        console.log('Loading dashboard from:', baseURL.dashboard);
        
        if (!baseURL.dashboard || baseURL.dashboard.includes('route(')) {
            console.error('Route not defined: manajemen-chatbot.dashboard');
            showToast('Route dashboard belum didefinisikan', 'error');
            return;
        }
        
        const response = await fetch(baseURL.dashboard, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        console.log('Dashboard response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Dashboard response data:', data);

        if (data.status === 'success') {
            $('#totalQuestions').text(data.total_questions.toLocaleString());
            $('#totalSources').text(data.total_sources.toLocaleString());
            
            if (data.terbaru_ditambahkan && data.terbaru_ditambahkan.length > 0) {
                $('#lastUpdate').text(data.terbaru_ditambahkan[0].ditambahkan);
            }
            
            // Count CSV items
            const csvCount = data.statistik_source?.find(s => s.source === 'csv')?.total || 0;
            $('#csvCount').text(csvCount.toLocaleString());
        } else {
            console.error('Dashboard error:', data.message);
            showToast(data.message || 'Gagal memuat dashboard', 'error');
        }
    } catch (error) {
        console.error('Error loading dashboard:', error);
        showToast('Gagal memuat data dashboard: ' + error.message, 'error');
    }
}

// Load knowledge list
async function loadKnowledge() {
    try {
        showLoading(true);
        
        console.log('Loading knowledge list...');
        
        if (!baseURL.list || baseURL.list.includes('route(')) {
            console.error('Route not defined: manajemen-chatbot.list');
            showToast('Route list belum didefinisikan', 'error');
            showLoading(false);
            return;
        }
        
        const url = new URL(baseURL.list);
        url.searchParams.append('per_page', perPage);
        url.searchParams.append('page', currentPage);
        
        if (searchQuery) {
            url.searchParams.append('search', searchQuery);
        }
        
        if (sourceFilter) {
            url.searchParams.append('source', sourceFilter);
        }

        console.log('Loading knowledge from:', url.toString());

        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        console.log('Knowledge response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Knowledge response data:', data);

        if (data.status === 'success') {
            totalItems = data.pagination?.total || 0;
            totalPages = data.pagination?.last_page || 1;
            
            updateKnowledgeTable(data.data || []);
            updatePagination();
            updateShowingInfo();
            updateFilteredCount();
        } else {
            console.error('Knowledge error:', data.message);
            showToast(data.message || 'Gagal memuat data knowledge', 'error');
        }
    } catch (error) {
        console.error('Error loading knowledge:', error);
        showToast('Gagal memuat data knowledge: ' + error.message, 'error');
    } finally {
        showLoading(false);
    }
}

// Update knowledge table
function updateKnowledgeTable(items) {
    const tbody = $('#knowledgeTableBody');
    tbody.empty();

    if (!items || items.length === 0) {
        tbody.html(`
            <tr>
                <td colspan="6" class="text-center text-muted py-5">
                    <i class="fas fa-inbox fa-3x mb-3 text-muted"></i>
                    <p class="mb-0">Tidak ada data knowledge</p>
                    <p class="small text-muted">Klik "Tambah Manual" atau "Import CSV" untuk menambahkan data</p>
                </td>
            </tr>
        `);
        return;
    }

    items.forEach(item => {
        const row = `
            <tr data-knowledge-id="${item.id}">
                <td><span class="badge bg-secondary">#${item.id}</span></td>
                <td class="question-preview" title="${escapeHtml(item.question)}">
                    ${truncateText(item.question, 60)}
                </td>
                <td class="question-preview" title="${escapeHtml(item.answer)}">
                    ${truncateText(item.answer, 80)}
                </td>
                <td>
                    <span class="source-badge badge-${getSourceClass(item.source)}">
                        ${item.source || 'csv'}
                    </span>
                </td>
                <td class="text-muted small">
                    ${formatDate(item.created_at)}
                </td>
                <td>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-primary" title="Detail" onclick="showPreview(${item.id})">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-warning" title="Edit" onclick="editKnowledge(${item.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" title="Hapus" onclick="showDeleteConfirm(${item.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

// Update pagination
function updatePagination() {
    const pagination = $('#pagination');
    pagination.empty();

    if (totalPages <= 1) return;

    // Previous button
    const prevDisabled = currentPage === 1 ? 'disabled' : '';
    const prevUrl = currentPage > 1 ? `javascript:goToPage(${currentPage - 1})` : '#';
    
    pagination.append(`
        <li class="page-item ${prevDisabled}">
            <a class="page-link" href="${prevUrl}" aria-label="Previous">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
    `);

    // Page numbers
    const start = Math.max(1, currentPage - 2);
    const end = Math.min(totalPages, currentPage + 2);

    if (start > 1) {
        pagination.append(`
            <li class="page-item">
                <a class="page-link" href="javascript:goToPage(1)">1</a>
            </li>
        `);
        if (start > 2) {
            pagination.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
        }
    }

    for (let i = start; i <= end; i++) {
        const active = i === currentPage ? 'active' : '';
        pagination.append(`
            <li class="page-item ${active}">
                <a class="page-link" href="javascript:goToPage(${i})">${i}</a>
            </li>
        `);
    }

    if (end < totalPages) {
        if (end < totalPages - 1) {
            pagination.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
        }
        pagination.append(`
            <li class="page-item">
                <a class="page-link" href="javascript:goToPage(${totalPages})">${totalPages}</a>
            </li>
        `);
    }

    // Next button
    const nextDisabled = currentPage === totalPages ? 'disabled' : '';
    const nextUrl = currentPage < totalPages ? `javascript:goToPage(${currentPage + 1})` : '#';
    
    pagination.append(`
        <li class="page-item ${nextDisabled}">
            <a class="page-link" href="${nextUrl}" aria-label="Next">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
    `);

    // Update pagination info
    $('#paginationInfo').text(`Halaman ${currentPage} dari ${totalPages}`);
}

// Go to specific page
function goToPage(page) {
    if (page >= 1 && page <= totalPages && page !== currentPage) {
        currentPage = page;
        loadKnowledge();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

// Update showing info
function updateShowingInfo() {
    const start = totalItems > 0 ? ((currentPage - 1) * perPage) + 1 : 0;
    const end = Math.min(currentPage * perPage, totalItems);
    $('#showingInfo').text(`Menampilkan ${start}-${end} dari ${totalItems} knowledge`);
}

// Update filtered count
function updateFilteredCount() {
    $('#filteredCount').text(totalItems);
}

// Import CSV
async function importCSV() {
    const fileInput = document.getElementById('csvFile');
    const file = fileInput.files[0];
    
    if (!file) {
        showToast('Pilih file CSV terlebih dahulu', 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('file', file);
    formData.append('_token', '{{ csrf_token() }}');

    const btn = $('#importCSVBtn');
    const originalText = btn.html();
    btn.html('<i class="fas fa-spinner fa-spin me-2"></i>Mengimport...');
    btn.prop('disabled', true);

    try {
        const response = await fetch(baseURL.storeCSV, {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();
        console.log('Import CSV response:', data);

        if (data.status === 'success') {
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('importCsvModal'));
            modal.hide();
            
            // Show success message
            showToast(`${data.rows} data berhasil diimport`, 'success');
            
            // Reload dashboard and knowledge list
            setTimeout(() => {
                loadDashboard();
                loadKnowledge();
            }, 1000);
        } else {
            showToast(data.message || 'Gagal mengimport CSV', 'error');
        }
    } catch (error) {
        console.error('Error importing CSV:', error);
        showToast('Terjadi kesalahan saat mengimport: ' + error.message, 'error');
    } finally {
        btn.html(originalText);
        btn.prop('disabled', false);
    }
}

// Add manual knowledge
async function addManualKnowledge() {
    const form = $('#addManualForm')[0];
    const formData = new FormData(form);
    formData.append('_token', '{{ csrf_token() }}');

    // Clear errors
    clearErrors(['question', 'answer', 'source']);

    const btn = $('#addManualBtn');
    const originalText = btn.html();
    btn.html('<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan...');
    btn.prop('disabled', true);

    try {
        const response = await fetch(baseURL.storeManual, {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();
        console.log('Add manual response:', data);

        if (data.status === 'success') {
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('addManualModal'));
            modal.hide();
            
            // Show success message
            showToast('Knowledge berhasil ditambahkan', 'success');
            
            // Reload dashboard and knowledge list
            setTimeout(() => {
                loadDashboard();
                loadKnowledge();
            }, 1000);
        } else {
            // Show validation errors
            if (data.errors) {
                Object.keys(data.errors).forEach(key => {
                    $(`#${key}Error`).text(data.errors[key][0]);
                });
            }
            showToast(data.message || 'Gagal menambahkan knowledge', 'warning');
        }
    } catch (error) {
        console.error('Error adding manual knowledge:', error);
        showToast('Terjadi kesalahan: ' + error.message, 'error');
    } finally {
        btn.html(originalText);
        btn.prop('disabled', false);
    }
}

// Show preview
async function showPreview(id) {
    try {
        const response = await fetch(baseURL.show(id), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();
        console.log('Show preview response:', data);

        if (data.status === 'success') {
            const item = data.data;
            
            $('#previewQuestion').text(item.question);
            $('#previewAnswer').text(item.answer);
            $('#previewId').text(item.id);
            $('#previewSource').text(item.source || '-');
            $('#previewCreated').text(formatDate(item.created_at));
            $('#previewUpdated').text(formatDate(item.updated_at));
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('previewModal'));
            modal.show();
        } else {
            showToast(data.message || 'Gagal memuat detail', 'error');
        }
    } catch (error) {
        console.error('Error loading preview:', error);
        showToast('Gagal memuat detail knowledge: ' + error.message, 'error');
    }
}

// Edit knowledge
async function editKnowledge(id) {
    try {
        const response = await fetch(baseURL.show(id), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();
        console.log('Edit knowledge response:', data);

        if (data.status === 'success') {
            const item = data.data;
            
            $('#editId').val(item.id);
            $('#editQuestion').val(item.question);
            $('#editAnswer').val(item.answer);
            $('#editSource').val(item.source || '');
            
            // Update character counters
            $('#editQuestionCounter').text(item.question?.length || 0 + '/500');
            $('#editAnswerCounter').text(item.answer?.length || 0 + '/5000');
            
            // Clear errors
            clearErrors(['editQuestion', 'editAnswer', 'editSource']);
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('editModal'));
            modal.show();
        } else {
            showToast(data.message || 'Gagal memuat data untuk edit', 'error');
        }
    } catch (error) {
        console.error('Error loading edit form:', error);
        showToast('Gagal memuat data untuk edit: ' + error.message, 'error');
    }
}

// Update knowledge
async function updateKnowledge() {
    const id = $('#editId').val();
    const formData = new FormData();
    
    formData.append('_method', 'PUT');
    formData.append('_token', '{{ csrf_token() }}');
    formData.append('question', $('#editQuestion').val());
    formData.append('answer', $('#editAnswer').val());
    formData.append('source', $('#editSource').val());

    // Clear errors
    clearErrors(['editQuestion', 'editAnswer', 'editSource']);

    const btn = $('#updateBtn');
    const originalText = btn.html();
    btn.html('<i class="fas fa-spinner fa-spin me-2"></i>Mengupdate...');
    btn.prop('disabled', true);

    try {
        const response = await fetch(baseURL.update(id), {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();
        console.log('Update response:', data);

        if (data.status === 'success') {
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('editModal'));
            modal.hide();
            
            // Show success message
            showToast('Knowledge berhasil diupdate', 'success');
            
            // Reload knowledge list
            setTimeout(() => {
                loadKnowledge();
            }, 500);
        } else {
            // Show validation errors
            if (data.errors) {
                Object.keys(data.errors).forEach(key => {
                    $(`#edit${key.charAt(0).toUpperCase() + key.slice(1)}Error`).text(data.errors[key][0]);
                });
            }
            showToast(data.message || 'Gagal mengupdate knowledge', 'warning');
        }
    } catch (error) {
        console.error('Error updating knowledge:', error);
        showToast('Terjadi kesalahan: ' + error.message, 'error');
    } finally {
        btn.html(originalText);
        btn.prop('disabled', false);
    }
}

// Show delete confirmation
function showDeleteConfirm(id) {
    $('#deleteId').val(id);
    const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    modal.show();
}

// Confirm delete
async function confirmDelete() {
    const id = $('#deleteId').val();
    
    try {
        const response = await fetch(baseURL.destroy(id), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();
        console.log('Delete response:', data);

        if (data.status === 'success') {
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal'));
            modal.hide();
            
            // Show success message
            showToast('Knowledge berhasil dihapus', 'success');
            
            // Reload dashboard and check if table is empty
            setTimeout(() => {
                loadDashboard();
                const remainingRows = $('#knowledgeTableBody tr[data-knowledge-id]').length;
                if (remainingRows === 0) {
                    loadKnowledge();
                }
            }, 500);
        } else {
            showToast(data.message || 'Gagal menghapus knowledge', 'error');
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal'));
            modal.hide();
        }
    } catch (error) {
        console.error('Error deleting knowledge:', error);
        showToast('Terjadi kesalahan: ' + error.message, 'error');
    }
}

// Helper functions
function showLoading(show) {
    if (show) {
        $('#knowledgeTableBody').html(`
            <tr>
                <td colspan="6" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Memuat data...</p>
                </td>
            </tr>
        `);
    }
}

function clearErrors(fields) {
    fields.forEach(field => {
        $(`#${field}Error`).text('');
    });
}

function getSourceClass(source) {
    if (!source) return 'csv';
    
    const sourceMap = {
        'csv': 'csv',
        'manual': 'manual',
        'sample': 'sample',
        'csv_import': 'import'
    };
    return sourceMap[source.toLowerCase()] || 'manual';
}

function truncateText(text, maxLength) {
    if (!text) return '';
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    if (!dateString) return '-';
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return dateString;
        
        return date.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (e) {
        return dateString;
    }
}

function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

function removeFile() {
    $('#csvFile').val('');
    $('#fileInfo').addClass('d-none');
    $('#importCSVBtn').prop('disabled', true);
}

// Toast notification
function showToast(message, type = 'info') {
    // Use existing toast function from layouts/app.blade.php
    if (typeof window.showToast === 'function') {
        window.showToast(message, type);
    } else {
        // Fallback using Bootstrap Toast
        const toastHtml = `
            <div class="toast align-items-center text-white bg-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info'} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas ${type === 'error' ? 'fa-exclamation-circle' : type === 'success' ? 'fa-check-circle' : 'fa-info-circle'} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        
        const toastContainer = $('#toast-container');
        if (toastContainer.length === 0) {
            $('body').append('<div id="toast-container" class="toast-container position-fixed bottom-0 end-0 p-3"></div>');
        }
        
        $('#toast-container').append(toastHtml);
        const toastElement = $('#toast-container .toast').last();
        const toast = new bootstrap.Toast(toastElement[0]);
        toast.show();
        
        // Remove toast after it's hidden
        toastElement.on('hidden.bs.toast', function() {
            $(this).remove();
        });
    }
}

// Make table responsive on mobile
function makeTableResponsive() {
    if (window.innerWidth <= 768) {
        const table = $('.table-chatbot');
        if (!table.length) return;
        
        const headers = table.find('thead th');
        const rows = table.find('tbody tr');
        
        headers.each((index, header) => {
            const label = $(header).text();
            rows.each((i, row) => {
                const cell = $(row).find(`td:nth-child(${index + 1})`);
                if (cell.length) {
                    cell.attr('data-label', label);
                }
            });
        });
    }
}

// Initialize responsive table
$(document).ready(function() {
    makeTableResponsive();
    $(window).on('resize', makeTableResponsive);
    
    // Initial debug log
    console.log('Manajemen Chatbot page loaded successfully');
});
</script>
@endpush