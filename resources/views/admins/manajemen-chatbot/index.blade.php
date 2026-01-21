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
        }

        .stat-card {
            border: none;
            border-radius: 10px;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-title {
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.8;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0;
        }

        .preview-text {
            max-height: 100px;
            overflow: hidden;
            position: relative;
        }

        .preview-text:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 20px;
            background: linear-gradient(transparent, white);
        }

        .progress-bar-animated {
            animation: progress-bar-stripes 1s linear infinite;
        }

        @keyframes progress-bar-stripes {
            from { background-position: 1rem 0; }
            to { background-position: 0 0; }
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
                <h1 class="h3 mb-2 text-dark">Manajemen Chatbot Knowledge</h1>
                <p class="text-muted">Kelola knowledge base chatbot Q&A</p>
            </div>
            <div>
                <button class="btn btn-purple" data-bs-toggle="modal" data-bs-target="#tambahCSVModal">
                    <i class="fas fa-file-csv me-2"></i>Import CSV
                </button>
                <button class="btn btn-purple ms-2" data-bs-toggle="modal" data-bs-target="#tambahManualModal">
                    <i class="fas fa-plus me-2"></i>Tambah Manual
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="stat-title">Total Q&A</h6>
                                <h2 class="stat-number" id="totalQuestions">0</h2>
                            </div>
                            <i class="fas fa-database fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="stat-title">Sources</h6>
                                <h2 class="stat-number" id="totalSources">0</h2>
                            </div>
                            <i class="fas fa-tags fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="stat-title">Terakhir Diupdate</h6>
                                <h5 class="stat-number" id="lastUpdated">-</h5>
                            </div>
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="stat-title">Data Terbaru</h6>
                                <h5 class="stat-number" id="latestData">0</h5>
                            </div>
                            <i class="fas fa-history fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Quick Actions -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="search-input-container">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control" id="searchInput"
                        placeholder="Cari berdasarkan pertanyaan atau jawaban...">
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-end gap-3">
                    <div class="text-end">
                        <h5 class="mb-0" id="showingTotal">0</h5>
                        <small class="text-muted">Total Data</small>
                    </div>
                    <div class="vr"></div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-success btn-sm" onclick="backupKnowledgeBase()">
                            <i class="fas fa-download me-1"></i>Backup
                        </button>
                        <button class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#restoreModal">
                            <i class="fas fa-upload me-1"></i>Restore
                        </button>
                        <button class="btn btn-outline-primary btn-sm" onclick="importSampleData()">
                            <i class="fas fa-magic me-1"></i>Sample Data
                        </button>
                        <button class="btn btn-outline-danger ms-2 btn-sm" onclick="showClearAllModal()">
                            <i class="fas fa-trash me-1"></i>Hapus Semua
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Knowledge Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="d-flex gap-3 align-items-center">
                    <h5 class="mb-0">Knowledge Base Q&A</h5>
                    <div>
                        <select class="form-select form-select-sm w-auto" id="sourceFilter">
                            <option value="">Semua Source</option>
                        </select>
                    </div>
                </div>
                <div class="text-muted small" id="showingCount">
                    Menampilkan 0 data
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th style="width: 100px;">Source</th>
                                <th>Pertanyaan</th>
                                <th>Jawaban</th>
                                <th style="width: 150px;">Dibuat</th>
                                <th style="width: 100px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="knowledgeTableBody">
                            <!-- Data akan dimuat via AJAX -->
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Memuat...</span>
                                    </div>
                                    <p class="mt-2">Memuat data knowledge...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="pagination-container mt-3">
                    <div class="d-flex justify-content-between align-items-center px-3 pb-3">
                        <div class="pagination-info">
                            Halaman <span id="currentPage">1</span> dari <span id="totalPages">1</span>
                        </div>
                        <nav>
                            <ul class="pagination mb-0" id="paginationContainer">
                                <!-- Pagination akan dimuat via JavaScript -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Import CSV -->
    <div class="modal fade" id="tambahCSVModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-file-csv me-2"></i>Import CSV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Format CSV: <strong>question,answer</strong> (tanpa header)
                    </div>
                    <form id="tambahCSVForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Pilih File CSV</label>
                            <input type="file" class="form-control" name="file" id="csvInput" accept=".csv,.txt" required>
                            <div class="form-text">
                                Format yang didukung: CSV, TXT. Maksimal 10MB.
                            </div>
                        </div>
                    </form>
                    <div id="uploadProgress" class="d-none">
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" style="width: 0%"></div>
                        </div>
                        <p class="text-center mt-2">Memproses CSV...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-purple" onclick="uploadCSV()" id="uploadCSVBtn">
                        <i class="fas fa-upload me-2"></i>Import CSV
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Manual -->
    <div class="modal fade" id="tambahManualModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Tambah Knowledge Manual</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="tambahManualForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Pertanyaan</label>
                            <input type="text" class="form-control" name="question" placeholder="Masukkan pertanyaan..." required>
                            <div class="form-text">
                                Maksimal 500 karakter
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jawaban</label>
                            <textarea class="form-control" name="answer" rows="6" placeholder="Masukkan jawaban..." required></textarea>
                            <div class="form-text">
                                Maksimal 5000 karakter
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Source</label>
                            <input type="text" class="form-control" name="source" placeholder="contoh: manual, faq, gym_info" value="manual">
                            <div class="form-text">
                                Sumber knowledge (opsional)
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-purple" onclick="uploadManual()" id="uploadManualBtn">
                        <i class="fas fa-save me-2"></i>Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Detail Knowledge -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-eye me-2"></i>Detail Knowledge</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ID</label>
                        <input type="text" class="form-control" id="detailId" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Pertanyaan</label>
                        <input type="text" class="form-control" id="detailQuestion">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jawaban</label>
                        <textarea class="form-control" id="detailAnswer" rows="6"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Source</label>
                        <input type="text" class="form-control" id="detailSource">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Dibuat</label>
                                <input type="text" class="form-control" id="detailCreatedAt" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Diupdate</label>
                                <input type="text" class="form-control" id="detailUpdatedAt" readonly>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-purple" onclick="saveKnowledgeChanges()" id="saveKnowledgeBtn">
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
                    <h6>Yakin ingin menghapus knowledge ini?</h6>
                    <p class="text-muted small">Data akan dihapus permanen dan tidak dapat dikembalikan.</p>
                    <input type="hidden" id="deleteKnowledgeId">
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" onclick="confirmDelete()" id="deleteBtn">
                        <i class="fas fa-trash me-2"></i>Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Restore Modal -->
    <div class="modal fade" id="restoreModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-upload me-2"></i>Restore Knowledge Base</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Peringatan:</strong> Restore akan menimpa data yang ada. Backup terlebih dahulu jika perlu.
                    </div>
                    <form id="restoreForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Pilih File Backup</label>
                            <input type="file" class="form-control" name="backup_file" accept=".json" required>
                            <div class="form-text">
                                Pilih file backup (.json) yang sebelumnya telah diekspor
                            </div>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="clear_existing" id="clearExisting">
                            <label class="form-check-label" for="clearExisting">
                                Hapus semua data yang ada sebelum restore
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-warning" onclick="restoreBackup()" id="restoreBtn">
                        <i class="fas fa-upload me-2"></i>Restore
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Clear All Confirmation Modal -->
    <div class="modal fade" id="clearAllModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title text-danger">Konfirmasi Hapus Semua</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-exclamation-triangle fa-3x text-danger"></i>
                    </div>
                    <h6>PERINGATAN: Hapus Semua Data!</h6>
                    <p class="text-muted small">Semua data knowledge base akan dihapus permanen dan tidak dapat dikembalikan.</p>
                    <div class="mb-3">
                        <input type="text" class="form-control" id="clearAllConfirmation" placeholder="Ketik 'DELETE_ALL'">
                        <div id="clearAllError" class="error-message text-danger"></div>
                    </div>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" onclick="confirmClearAll()" id="clearAllBtn">
                        <i class="fas fa-trash me-2"></i>Hapus Semua
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let currentPage = 1;
        let totalPages = 1;
        let currentSearch = '';

        document.addEventListener('DOMContentLoaded', function() {
            loadStats();
            loadKnowledge();
            
            // Search input event
            document.getElementById('searchInput').addEventListener('input', function(e) {
                currentSearch = e.target.value;
                loadKnowledge(1);
            });
            
            // Source filter event
            document.getElementById('sourceFilter').addEventListener('change', function() {
                loadKnowledge(1);
            });
        });

        async function loadStats() {
            try {
                const response = await fetch('{{ route("manajemen-chatbot.index") }}');
                const data = await response.json();
                
                if (data.status === 'success') {
                    document.getElementById('totalQuestions').textContent = data.total_questions;
                    document.getElementById('totalSources').textContent = data.total_sources;
                    document.getElementById('showingTotal').textContent = data.total_questions;
                    
                    // Update source filter
                    const sourceFilter = document.getElementById('sourceFilter');
                    sourceFilter.innerHTML = '<option value="">Semua Source</option>';
                    data.statistik_source.forEach(source => {
                        const option = document.createElement('option');
                        option.value = source.source;
                        option.textContent = `${source.source} (${source.total})`;
                        sourceFilter.appendChild(option);
                    });
                    
                    // Update last updated and latest data
                    if (data.terbaru_ditambahkan && data.terbaru_ditambahkan.length > 0) {
                        document.getElementById('latestData').textContent = data.terbaru_ditambahkan.length;
                        document.getElementById('lastUpdated').textContent = data.terbaru_ditambahkan[0].ditambahkan;
                    }
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }

        async function loadKnowledge(page = 1) {
            try {
                currentPage = page;
                
                let url = `{{ route('manajemen-chatbot.list') }}?page=${page}&per_page=20`;
                if (currentSearch) {
                    url += `&search=${currentSearch}`;
                }
                
                // Get selected source
                const sourceFilter = document.getElementById('sourceFilter');
                if (sourceFilter && sourceFilter.value) {
                    url += `&source=${sourceFilter.value}`;
                }
                
                document.getElementById('knowledgeTableBody').innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Memuat...</span>
                            </div>
                            <p class="mt-2">Memuat data knowledge...</p>
                        </td>
                    </tr>
                `;
                
                const response = await fetch(url);
                const data = await response.json();
                
                if (data.status === 'success') {
                    const tbody = document.getElementById('knowledgeTableBody');
                    tbody.innerHTML = '';
                    
                    if (data.data.length === 0) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="6" class="no-data">
                                    <i class="fas fa-database"></i>
                                    <div>Tidak ada data knowledge</div>
                                </td>
                            </tr>
                        `;
                    } else {
                        data.data.forEach(item => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${item.id}</td>
                                <td>
                                    <span class="badge bg-primary">${item.source || 'unknown'}</span>
                                </td>
                                <td>
                                    <div class="preview-text">
                                        ${item.question ? item.question.substring(0, 80) + (item.question.length > 80 ? '...' : '') : ''}
                                    </div>
                                </td>
                                <td>
                                    <div class="preview-text">
                                        ${item.answer ? item.answer.substring(0, 100) + (item.answer.length > 100 ? '...' : '') : ''}
                                    </div>
                                </td>
                                <td>
                                    <div class="text-muted small">
                                        ${new Date(item.created_at).toLocaleDateString('id-ID')}
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-purple" onclick="viewKnowledge(${item.id})">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="showDeleteConfirm(${item.id})">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            `;
                            tbody.appendChild(row);
                        });
                    }
                    
                    // Update pagination
                    updatePagination(data.pagination);
                    
                    // Update showing count
                    const start = (data.pagination.current_page - 1) * data.pagination.per_page + 1;
                    const end = Math.min(data.pagination.total, start + data.data.length - 1);
                    document.getElementById('showingCount').textContent = 
                        `Menampilkan ${start}-${end} dari ${data.pagination.total} data`;
                    
                    // Update page info
                    document.getElementById('currentPage').textContent = data.pagination.current_page;
                    document.getElementById('totalPages').textContent = data.pagination.last_page;
                    totalPages = data.pagination.last_page;
                }
            } catch (error) {
                console.error('Error loading knowledge:', error);
                document.getElementById('knowledgeTableBody').innerHTML = `
                    <tr>
                        <td colspan="6" class="no-data">
                            <i class="fas fa-exclamation-triangle text-danger"></i>
                            <div>Gagal memuat data</div>
                        </td>
                    </tr>
                `;
            }
        }

        function updatePagination(pagination) {
            const container = document.getElementById('paginationContainer');
            container.innerHTML = '';
            
            const totalPages = pagination.last_page;
            const currentPage = pagination.current_page;
            
            // Previous button
            const prevLi = document.createElement('li');
            prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
            prevLi.innerHTML = `
                <a class="page-link" href="#" onclick="loadKnowledge(${currentPage - 1})">
                    <i class="fas fa-chevron-left"></i>
                </a>
            `;
            container.appendChild(prevLi);
            
            // Page numbers
            const start = Math.max(1, currentPage - 2);
            const end = Math.min(totalPages, currentPage + 2);
            
            if (start > 1) {
                const firstLi = document.createElement('li');
                firstLi.className = 'page-item';
                firstLi.innerHTML = `<a class="page-link" href="#" onclick="loadKnowledge(1)">1</a>`;
                container.appendChild(firstLi);
                
                if (start > 2) {
                    const ellipsisLi = document.createElement('li');
                    ellipsisLi.className = 'page-item disabled';
                    ellipsisLi.innerHTML = '<span class="page-link">...</span>';
                    container.appendChild(ellipsisLi);
                }
            }
            
            for (let i = start; i <= end; i++) {
                const pageLi = document.createElement('li');
                pageLi.className = `page-item ${i === currentPage ? 'active' : ''}`;
                pageLi.innerHTML = `<a class="page-link" href="#" onclick="loadKnowledge(${i})">${i}</a>`;
                container.appendChild(pageLi);
            }
            
            if (end < totalPages) {
                if (end < totalPages - 1) {
                    const ellipsisLi = document.createElement('li');
                    ellipsisLi.className = 'page-item disabled';
                    ellipsisLi.innerHTML = '<span class="page-link">...</span>';
                    container.appendChild(ellipsisLi);
                }
                
                const lastLi = document.createElement('li');
                lastLi.className = 'page-item';
                lastLi.innerHTML = `<a class="page-link" href="#" onclick="loadKnowledge(${totalPages})">${totalPages}</a>`;
                container.appendChild(lastLi);
            }
            
            // Next button
            const nextLi = document.createElement('li');
            nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
            nextLi.innerHTML = `
                <a class="page-link" href="#" onclick="loadKnowledge(${currentPage + 1})">
                    <i class="fas fa-chevron-right"></i>
                </a>
            `;
            container.appendChild(nextLi);
        }

        async function uploadCSV() {
            const form = document.getElementById('tambahCSVForm');
            const formData = new FormData(form);
            
            const progressBar = document.querySelector('#uploadProgress .progress-bar');
            const progressContainer = document.getElementById('uploadProgress');
            const btn = document.getElementById('uploadCSVBtn');
            
            progressContainer.classList.remove('d-none');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
            
            let progress = 0;
            const progressInterval = setInterval(() => {
                progress += 10;
                progressBar.style.width = Math.min(progress, 90) + '%';
            }, 500);
            
            try {
                const response = await fetch('{{ route("manajemen-chatbot.store.csv") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                
                const data = await response.json();
                
                clearInterval(progressInterval);
                progressBar.style.width = '100%';
                
                setTimeout(() => {
                    if (data.status === 'success') {
                        showToast('success', 'CSV berhasil diproses!', 
                            `Berhasil menambahkan ${data.data.rows_processed} data ke knowledge base.`);
                        document.getElementById('tambahCSVModal').querySelector('.btn-close').click();
                        form.reset();
                        loadStats();
                        loadKnowledge();
                    } else {
                        showToast('error', 'Gagal memproses CSV', data.message || 'Terjadi kesalahan');
                    }
                    
                    progressContainer.classList.add('d-none');
                    progressBar.style.width = '0%';
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-upload me-2"></i>Import CSV';
                }, 500);
                
            } catch (error) {
                clearInterval(progressInterval);
                progressContainer.classList.add('d-none');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-upload me-2"></i>Import CSV';
                showToast('error', 'Terjadi kesalahan', error.message);
            }
        }

        async function uploadManual() {
            const form = document.getElementById('tambahManualForm');
            const formData = new FormData(form);
            const btn = document.getElementById('uploadManualBtn');
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan...';
            
            try {
                const response = await fetch('{{ route("manajemen-chatbot.store.manual") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    showToast('success', 'Data berhasil ditambahkan!', 
                        `Berhasil menambahkan data ke knowledge base.`);
                    document.getElementById('tambahManualModal').querySelector('.btn-close').click();
                    form.reset();
                    loadStats();
                    loadKnowledge();
                } else {
                    showToast('error', 'Gagal menyimpan data', data.message || 'Terjadi kesalahan');
                }
            } catch (error) {
                showToast('error', 'Terjadi kesalahan', error.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save me-2"></i>Simpan';
            }
        }

        async function importSampleData() {
            if (!confirm('Import sample data? Ini akan menambahkan 5 data contoh ke knowledge base.')) return;
            
            try {
                const response = await fetch('{{ route("manajemen-chatbot.import.sample") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    showToast('success', 'Sample data berhasil diimport!', 
                        `Berhasil menambahkan ${data.data.total_imported} data contoh.`);
                    loadStats();
                    loadKnowledge();
                } else {
                    showToast('error', 'Gagal import sample data', data.message || 'Terjadi kesalahan');
                }
            } catch (error) {
                showToast('error', 'Terjadi kesalahan', error.message);
            }
        }

        async function viewKnowledge(id) {
            try {
                const response = await fetch(`{{ route('manajemen-chatbot.show', '') }}/${id}`);
                const data = await response.json();
                
                if (data.status === 'success') {
                    const knowledge = data.data;
                    document.getElementById('detailId').value = knowledge.id;
                    document.getElementById('detailQuestion').value = knowledge.question;
                    document.getElementById('detailAnswer').value = knowledge.answer;
                    document.getElementById('detailSource').value = knowledge.source;
                    document.getElementById('detailCreatedAt').value = knowledge.created_at;
                    document.getElementById('detailUpdatedAt').value = knowledge.updated_at;
                    
                    const modal = new bootstrap.Modal(document.getElementById('detailModal'));
                    modal.show();
                }
            } catch (error) {
                showToast('error', 'Gagal memuat detail', error.message);
            }
        }

        async function saveKnowledgeChanges() {
            const id = document.getElementById('detailId').value;
            const formData = new FormData();
            formData.append('_method', 'PUT');
            formData.append('question', document.getElementById('detailQuestion').value);
            formData.append('answer', document.getElementById('detailAnswer').value);
            formData.append('source', document.getElementById('detailSource').value);
            
            const btn = document.getElementById('saveKnowledgeBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan...';
            
            try {
                const response = await fetch(`{{ route('manajemen-chatbot.update', '') }}/${id}`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    showToast('success', 'Berhasil!', 'Knowledge berhasil diperbarui.');
                    document.getElementById('detailModal').querySelector('.btn-close').click();
                    loadKnowledge();
                } else {
                    showToast('error', 'Gagal!', data.message || 'Terjadi kesalahan');
                }
            } catch (error) {
                showToast('error', 'Terjadi kesalahan', error.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save me-2"></i>Simpan Perubahan';
            }
        }

        function showDeleteConfirm(id) {
            document.getElementById('deleteKnowledgeId').value = id;
            const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
            modal.show();
        }

        async function confirmDelete() {
            const id = document.getElementById('deleteKnowledgeId').value;
            const btn = document.getElementById('deleteBtn');
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menghapus...';
            
            try {
                const response = await fetch(`{{ route('manajemen-chatbot.destroy', '') }}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    showToast('success', 'Berhasil!', 'Knowledge berhasil dihapus.');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal'));
                    modal.hide();
                    loadStats();
                    loadKnowledge();
                } else {
                    showToast('error', 'Gagal!', data.message || 'Terjadi kesalahan');
                }
            } catch (error) {
                showToast('error', 'Terjadi kesalahan', error.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-trash me-2"></i>Hapus';
            }
        }

        async function backupKnowledgeBase() {
            if (!confirm('Buat backup knowledge base?')) return;
            
            try {
                const response = await fetch('{{ route("manajemen-chatbot.backup") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    showToast('success', 'Backup berhasil!', 
                        `File backup: ${data.data.filename} (${data.data.file_size})`);
                } else {
                    showToast('error', 'Gagal backup', data.message || 'Terjadi kesalahan');
                }
            } catch (error) {
                showToast('error', 'Terjadi kesalahan', error.message);
            }
        }

        async function restoreBackup() {
            const form = document.getElementById('restoreForm');
            const formData = new FormData(form);
            const btn = document.getElementById('restoreBtn');
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
            
            try {
                const response = await fetch('{{ route("manajemen-chatbot.restore") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    showToast('success', 'Restore berhasil!', 
                        `Berhasil restore ${data.data.total_restored} data.`);
                    document.getElementById('restoreModal').querySelector('.btn-close').click();
                    form.reset();
                    loadStats();
                    loadKnowledge();
                } else {
                    showToast('error', 'Gagal restore', data.message || 'Terjadi kesalahan');
                }
            } catch (error) {
                showToast('error', 'Terjadi kesalahan', error.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-upload me-2"></i>Restore';
            }
        }

        function showClearAllModal() {
            document.getElementById('clearAllConfirmation').value = '';
            document.getElementById('clearAllError').textContent = '';
            const modal = new bootstrap.Modal(document.getElementById('clearAllModal'));
            modal.show();
        }

        async function confirmClearAll() {
            const confirmation = document.getElementById('clearAllConfirmation').value;
            
            if (confirmation !== 'DELETE_ALL') {
                document.getElementById('clearAllError').textContent = 'Ketik DELETE_ALL untuk melanjutkan';
                return;
            }
            
            if (!confirm('PERINGATAN: Ini akan menghapus SEMUA data knowledge base. Lanjutkan?')) return;
            
            const formData = new FormData();
            formData.append('confirmation', confirmation);
            const btn = document.getElementById('clearAllBtn');
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menghapus...';
            
            try {
                const response = await fetch('{{ route("manajemen-chatbot.clear.all") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    showToast('success', 'Berhasil!', 
                        `Berhasil menghapus ${data.data.total_deleted} data.`);
                    const modal = bootstrap.Modal.getInstance(document.getElementById('clearAllModal'));
                    modal.hide();
                    loadStats();
                    loadKnowledge();
                } else {
                    showToast('error', 'Gagal', data.message || 'Terjadi kesalahan');
                }
            } catch (error) {
                showToast('error', 'Terjadi kesalahan', error.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-trash me-2"></i>Hapus Semua';
            }
        }

        // Toast notification
        function showToast(type, title, message) {
            // Create toast element
            const toastId = 'toast-' + Date.now();
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-bg-${type} border-0`;
            toast.id = toastId;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        <strong>${title}</strong><br>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            
            // Add to toast container
            let container = document.getElementById('toastContainer');
            if (!container) {
                container = document.createElement('div');
                container.id = 'toastContainer';
                container.className = 'toast-container position-fixed top-0 end-0 p-3';
                document.body.appendChild(container);
            }
            container.appendChild(toast);
            
            // Initialize and show toast
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            
            // Remove after hidden
            toast.addEventListener('hidden.bs.toast', () => {
                toast.remove();
            });
        }

        // Reset forms when modals are closed
        document.getElementById('tambahCSVModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('tambahCSVForm').reset();
            document.getElementById('uploadProgress').classList.add('d-none');
        });

        document.getElementById('tambahManualModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('tambahManualForm').reset();
        });

        document.getElementById('restoreModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('restoreForm').reset();
        });
    </script>
@endpush