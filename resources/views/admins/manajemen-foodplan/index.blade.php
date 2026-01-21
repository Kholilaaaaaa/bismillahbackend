@extends('layouts.app')

@section('title', 'Manajemen Food Plan - Gym GenZ Admin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/food-plan.css') }}">
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

        .nutrient-cell {
            text-align: center;
        }

        .nutrient-value {
            font-weight: 600;
            color: var(--text-dark);
        }

        .nutrient-unit {
            font-size: 0.8rem;
            color: #64748b;
            margin-left: 0.25rem;
        }

        .category-filter {
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .filter-btn {
            padding: 0.625rem 1.25rem;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .filter-btn i {
            transition: transform 0.3s ease;
        }

        .filter-btn:hover i {
            transform: scale(1.2);
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

        .error-message {
            font-size: 0.8rem;
            margin-top: 0.25rem;
            color: var(--danger-color);
        }

        /* Category badges */
        .category-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.75rem;
        }

        .badge-pagi {
            background-color: rgba(59, 130, 246, 0.1);
            color: #1d4ed8;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .badge-siang {
            background-color: rgba(16, 185, 129, 0.1);
            color: #047857;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .badge-sore {
            background-color: rgba(245, 158, 11, 0.1);
            color: #92400e;
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .badge-malam {
            background-color: rgba(175, 105, 238, 0.1);
            color: var(--primary-color);
            border: 1px solid rgba(175, 105, 238, 0.2);
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            .search-input-container {
                width: 100%;
                margin-bottom: 1rem;
            }

            .category-filter {
                justify-content: center;
            }

            .table-responsive {
                font-size: 0.85rem;
            }

            .nutrient-cell {
                text-align: center;
            }

            .card-header {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 1rem;
            }

            .card-header .text-muted {
                align-self: flex-start;
            }
        }

        @media (max-width: 576px) {
            .filter-btn {
                flex: 1 0 calc(50% - 0.75rem);
                min-width: 0;
                justify-content: center;
                text-align: center;
            }

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
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-2 text-dark">Manajemen Food Plan</h1>
                <p class="text-muted">Kelola data makanan dan nutrisi untuk food plan</p>
            </div>
            <button class="btn btn-purple" data-bs-toggle="modal" data-bs-target="#addFoodModal">
                <i class="fas fa-plus-circle me-2"></i>Tambah Makanan
            </button>
        </div>

        <!-- Search and Stats -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="search-input-container">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control" id="searchInput" placeholder="Cari makanan...">
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-end gap-3">
                    <div class="text-end">
                        <h5 class="mb-0">{{ $foods->total() }}</h5>
                        <small class="text-muted">Total Makanan</small>
                    </div>
                    <div class="vr"></div>
                </div>
            </div>
        </div>

        <!-- Food Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Daftar Makanan</h5>
                <div class="text-muted small">
                    Menampilkan {{ $foods->firstItem() ?: 0 }}-{{ $foods->lastItem() ?: 0 }} dari {{ $foods->total() }}
                    makanan
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Nama Makanan</th>
                                <th>Kategori</th>
                                <th>Deskripsi</th>
                                <th class="text-center">Kalori</th>
                                <th class="text-center">Protein</th>
                                <th class="text-center">Karbo</th>
                                <th class="text-center">Lemak</th>
                                <th style="width: 150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="foodTableBody">
                            @forelse ($foods as $index => $food)
                                <tr data-food-id="{{ $food->id }}">
                                    <td data-label="No">{{ $foods->firstItem() + $index }}</td>
                                    <td data-label="Nama Makanan">
                                        <div class="fw-medium">{{ $food->nama_makanan }}</div>
                                    </td>
                                    <td data-label="Kategori">
                                        <span class="category-badge badge-{{ $food->kategori_makanan }}">
                                            <i
                                                class="fas fa-{{ $food->kategori_makanan == 'pagi' ? 'sun' : ($food->kategori_makanan == 'siang' ? 'cloud-sun' : ($food->kategori_makanan == 'sore' ? 'cloud' : 'moon')) }}"></i>
                                            {{ ucfirst($food->kategori_makanan) }}
                                        </span>
                                    </td>
                                    <td data-label="Deskripsi">
                                        <div class="text-muted small">{{ $food->deskripsi ?: '-' }}</div>
                                    </td>
                                    <td data-label="Kalori" class="nutrient-cell">
                                        <span class="nutrient-value">{{ $food->kalori }}</span>
                                        <span class="nutrient-unit">kcal</span>
                                    </td>
                                    <td data-label="Protein" class="nutrient-cell">
                                        <span class="nutrient-value">{{ number_format($food->protein, 1) }}</span>
                                        <span class="nutrient-unit">g</span>
                                    </td>
                                    <td data-label="Karbohidrat" class="nutrient-cell">
                                        <span class="nutrient-value">{{ number_format($food->karbohidrat, 1) }}</span>
                                        <span class="nutrient-unit">g</span>
                                    </td>
                                    <td data-label="Lemak" class="nutrient-cell">
                                        <span class="nutrient-value">{{ number_format($food->lemak, 1) }}</span>
                                        <span class="nutrient-unit">g</span>
                                    </td>
                                    <td data-label="Aksi">
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-sm btn-purple" onclick="editFood({{ $food->id }})">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger"
                                                onclick="showDeleteConfirm({{ $food->id }})">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="no-data">
                                        <i class="fas fa-utensils-slash"></i>
                                        <div>Tidak ada data makanan</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if ($foods->hasPages())
                    <div class="pagination-container">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="pagination-info">
                                Halaman {{ $foods->currentPage() }} dari {{ $foods->lastPage() }}
                            </div>
                            <nav>
                                <ul class="pagination">
                                    {{-- Previous Page Link --}}
                                    @if ($foods->onFirstPage())
                                        <li class="page-item disabled">
                                            <span class="page-link">
                                                <i class="fas fa-chevron-left"></i>
                                            </span>
                                        </li>
                                    @else
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $foods->previousPageUrl() }}" rel="prev">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    @endif

                                    {{-- Pagination Elements --}}
                                    @php
                                        $start = max(1, $foods->currentPage() - 2);
                                        $end = min($foods->lastPage(), $foods->currentPage() + 2);
                                    @endphp

                                    @if ($start > 1)
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $foods->url(1) }}">1</a>
                                        </li>
                                        @if ($start > 2)
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        @endif
                                    @endif

                                    @for ($i = $start; $i <= $end; $i++)
                                        <li class="page-item {{ $foods->currentPage() == $i ? 'active' : '' }}">
                                            <a class="page-link" href="{{ $foods->url($i) }}">{{ $i }}</a>
                                        </li>
                                    @endfor

                                    @if ($end < $foods->lastPage())
                                        @if ($end < $foods->lastPage() - 1)
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        @endif
                                        <li class="page-item">
                                            <a class="page-link"
                                                href="{{ $foods->url($foods->lastPage()) }}">{{ $foods->lastPage() }}</a>
                                        </li>
                                    @endif

                                    {{-- Next Page Link --}}
                                    @if ($foods->hasMorePages())
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $foods->nextPageUrl() }}" rel="next">
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

    <!-- Add Food Modal -->
    <div class="modal fade" id="addFoodModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Makanan Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addFoodForm">
                        @csrf
                        <div class="row g-3">
                            <!-- Basic Info -->
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label">Nama Makanan <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-utensils"></i>
                                        </span>
                                        <input type="text" class="form-control" id="nama_makanan" name="nama_makanan"
                                            placeholder="Masukkan nama makanan" required>
                                    </div>
                                    <div id="namaError" class="error-message"></div>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label">Kategori Makanan <span class="text-danger">*</span></label>
                                    <select class="form-select" id="kategori_makanan" name="kategori_makanan" required>
                                        <option value="">Pilih kategori</option>
                                        <option value="pagi">Pagi</option>
                                        <option value="siang">Siang</option>
                                        <option value="sore">Sore</option>
                                        <option value="malam">Malam</option>
                                    </select>
                                    <div id="kategoriError" class="error-message"></div>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label">Deskripsi (Opsional)</label>
                                    <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"
                                        placeholder="Masukkan deskripsi makanan"></textarea>
                                    <div id="deskripsiError" class="error-message"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Nutrition Information -->
                        <h6 class="mt-4 mb-3 text-dark">Informasi Nutrisi</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-group nutrition-input-group">
                                    <label class="form-label">Kalori <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="kalori" name="kalori"
                                            placeholder="0" min="0" step="1" required>
                                        <span class="input-group-text">kcal</span>
                                    </div>
                                    <div id="kaloriError" class="error-message"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group nutrition-input-group">
                                    <label class="form-label">Protein <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="protein" name="protein"
                                            placeholder="0.0" min="0" step="0.1" required>
                                        <span class="input-group-text">gram</span>
                                    </div>
                                    <div id="proteinError" class="error-message"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group nutrition-input-group">
                                    <label class="form-label">Karbohidrat <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="karbohidrat" name="karbohidrat"
                                            placeholder="0.0" min="0" step="0.1" required>
                                        <span class="input-group-text">gram</span>
                                    </div>
                                    <div id="karbohidratError" class="error-message"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group nutrition-input-group">
                                    <label class="form-label">Lemak <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="lemak" name="lemak"
                                            placeholder="0.0" min="0" step="0.1" required>
                                        <span class="input-group-text">gram</span>
                                    </div>
                                    <div id="lemakError" class="error-message"></div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-success" onclick="addNewFood()" id="addFoodBtn">
                        <i class="fas fa-plus-circle me-2"></i>Tambah Makanan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Food Modal -->
    <div class="modal fade" id="editFoodModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Data Makanan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editFoodForm">
                        @csrf
                        @method('PUT')
                        <input type="hidden" id="editFoodId">
                        <div class="row g-3">
                            <!-- Basic Info -->
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label">Nama Makanan <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-utensils"></i>
                                        </span>
                                        <input type="text" class="form-control" id="editNamaMakanan"
                                            name="nama_makanan" required>
                                    </div>
                                    <div id="editNamaError" class="error-message"></div>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label">Kategori Makanan <span class="text-danger">*</span></label>
                                    <select class="form-select" id="editKategoriMakanan" name="kategori_makanan"
                                        required>
                                        <option value="">Pilih kategori</option>
                                        <option value="pagi">Pagi</option>
                                        <option value="siang">Siang</option>
                                        <option value="sore">Sore</option>
                                        <option value="malam">Malam</option>
                                    </select>
                                    <div id="editKategoriError" class="error-message"></div>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label">Deskripsi (Opsional)</label>
                                    <textarea class="form-control" id="editDeskripsi" name="deskripsi" rows="3"></textarea>
                                    <div id="editDeskripsiError" class="error-message"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Nutrition Information -->
                        <h6 class="mt-4 mb-3 text-dark">Informasi Nutrisi</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-group nutrition-input-group">
                                    <label class="form-label">Kalori <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="editKalori" name="kalori"
                                            min="0" step="1" required>
                                        <span class="input-group-text">kcal</span>
                                    </div>
                                    <div id="editKaloriError" class="error-message"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group nutrition-input-group">
                                    <label class="form-label">Protein <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="editProtein" name="protein"
                                            min="0" step="0.1" required>
                                        <span class="input-group-text">gram</span>
                                    </div>
                                    <div id="editProteinError" class="error-message"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group nutrition-input-group">
                                    <label class="form-label">Karbohidrat <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="editKarbohidrat"
                                            name="karbohidrat" min="0" step="0.1" required>
                                        <span class="input-group-text">gram</span>
                                    </div>
                                    <div id="editKarbohidratError" class="error-message"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group nutrition-input-group">
                                    <label class="form-label">Lemak <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="editLemak" name="lemak"
                                            min="0" step="0.1" required>
                                        <span class="input-group-text">gram</span>
                                    </div>
                                    <div id="editLemakError" class="error-message"></div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-purple" onclick="updateFood()" id="updateFoodBtn">
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
                    <h6>Yakin ingin menghapus makanan ini?</h6>
                    <p class="text-muted small">Data makanan akan dihapus permanen dan tidak dapat dikembalikan.</p>
                    <input type="hidden" id="deleteFoodId">
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

        // Add new food
        async function addNewFood() {
            const form = document.getElementById('addFoodForm');
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
            const btn = document.getElementById('addFoodBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
            btn.disabled = true;

            try {
                const response = await fetch('{{ route('manajemen-food.store') }}', {
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
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addFoodModal'));
                    modal.hide();

                    // Show success message
                    showToast(data.message, 'success');

                    // Reload page to show new food
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

        // Edit food
        async function editFood(id) {
            try {
                const response = await fetch(`/admin/manajemen-food/${id}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    const food = data.data;

                    // Fill form
                    document.getElementById('editFoodId').value = food.id;
                    document.getElementById('editNamaMakanan').value = food.nama_makanan;
                    document.getElementById('editKategoriMakanan').value = food.kategori_makanan;
                    document.getElementById('editDeskripsi').value = food.deskripsi || '';
                    document.getElementById('editKalori').value = food.kalori;
                    document.getElementById('editProtein').value = food.protein;
                    document.getElementById('editKarbohidrat').value = food.karbohidrat;
                    document.getElementById('editLemak').value = food.lemak;

                    // Clear errors
                    clearErrors();

                    // Show modal
                    const modal = new bootstrap.Modal(document.getElementById('editFoodModal'));
                    modal.show();
                } else {
                    showToast('Gagal mengambil data makanan', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Terjadi kesalahan', 'error');
            }
        }

        // Update food
        async function updateFood() {
            const id = document.getElementById('editFoodId').value;
            const form = document.getElementById('editFoodForm');
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
            const btn = document.getElementById('updateFoodBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
            btn.disabled = true;

            try {
                const response = await fetch(`/admin/manajemen-food/${id}`, {
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
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editFoodModal'));
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

        // Show delete confirmation
        function showDeleteConfirm(id) {
            document.getElementById('deleteFoodId').value = id;
            const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
            modal.show();
        }

        // Delete food
        async function confirmDelete() {
            const id = document.getElementById('deleteFoodId').value;

            try {
                const response = await fetch(`/admin/manajemen-food/${id}`, {
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
                    const row = document.querySelector(`tr[data-food-id="${id}"]`);
                    if (row) {
                        row.remove();
                    }

                    // Reload if no rows left
                    setTimeout(() => {
                        const rows = document.querySelectorAll('#foodTableBody tr');
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
            const rows = document.querySelectorAll('#foodTableBody tr');

            rows.forEach(row => {
                const foodName = row.querySelector('td:nth-child(2) .fw-medium').textContent.toLowerCase();
                const category = row.querySelector('td:nth-child(3) .category-badge').textContent
                    .toLowerCase();

                if (foodName.includes(searchTerm) || category.includes(searchTerm)) {
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
        document.getElementById('addFoodModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('addFoodForm').reset();
            clearErrors();
        });

        // Reset edit modal when closed
        document.getElementById('editFoodModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('editFoodForm').reset();
            clearErrors();
        });

        // Make table responsive on mobile
        function makeTableResponsive() {
            if (window.innerWidth <= 768) {
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