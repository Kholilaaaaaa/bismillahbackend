@extends('layouts.app')

@section('title', 'Feedback Pengguna - Gym GenZ Admin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/feedback-pengguna.css') }}">
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

        .initials-avatar.small {
            width: 32px;
            height: 32px;
            font-size: 0.8rem;
        }

        /* Rating stars in table */
        .table-rating {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Review text ellipsis */
        .review-text {
            max-width: 330px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
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

            .review-text {
                max-width: 150px;
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

        /* User Header */
        .detail-modal-header {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            padding: 1.5rem;
            background: linear-gradient(90deg, #f8fafc, #ffffff);
            border-bottom: 1px solid #e2e8f0;
            margin: -1.5rem -1.5rem 1.5rem;
        }

        #detailUserAvatar .user-avatar-large {
            width: 70px;
            height: 70px;
            font-size: 1.25rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #AF69EE, #7C3AED);
            color: white;
            font-weight: 600;
        }

        /* Rating Display */
        .rating-display {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .rating-display i {
            font-size: 1.5rem;
        }

        /* Review Content */
        .review-content {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 1rem;
        }

        /* Timestamp */
        .timestamp {
            font-size: 0.85rem;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 0.5rem;
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

        /* Responsive Modal */
        @media (max-width: 768px) {
            .modal-dialog.modal-lg {
                margin: 0.5rem;
                max-width: calc(100% - 1rem);
            }

            .modal-body {
                padding: 1rem;
            }

            .detail-modal-header {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
                padding: 1rem;
            }

            #detailUserAvatar .user-avatar-large {
                width: 60px;
                height: 60px;
                font-size: 1rem;
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

        /* Sentiment Analysis Styles */
        .sentiment-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .sentiment-positive {
            background-color: rgba(34, 197, 94, 0.1);
            color: #16a34a;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }
        
        .sentiment-negative {
            background-color: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .sentiment-neutral {
            background-color: rgba(234, 179, 8, 0.1);
            color: #ca8a04;
            border: 1px solid rgba(234, 179, 8, 0.3);
        }
        
        .sentiment-unknown {
            background-color: rgba(100, 116, 139, 0.1);
            color: #64748b;
            border: 1px solid rgba(100, 116, 139, 0.3);
        }
        
        .sentiment-no_review {
            background-color: rgba(148, 163, 184, 0.1);
            color: #94a3b8;
            border: 1px solid rgba(148, 163, 184, 0.3);
        }
        
        .analysis-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            padding: 1.5rem;
            color: white;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .analysis-card h5 {
            color: white;
        }
        
        .analysis-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .stat-item {
            text-align: center;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            backdrop-filter: blur(10px);
            transition: transform 0.2s ease;
        }
        
        .stat-item:hover {
            transform: translateY(-2px);
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            color: black;
            font-weight: 600;
            font-size: 0.95rem;
            opacity: 0.9;
        }
        
        .probability-bar {
            height: 8px;
            border-radius: 4px;
            background: #e2e8f0;
            margin-top: 0.5rem;
            overflow: hidden;
        }
        
        .probability-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        .probability-fill.positive { background: #10b981; }
        .probability-fill.negative { background: #ef4444; }
        .probability-fill.neutral { background: #f59e0b; }
        
        .modal-sentiment .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .sentiment-result-item {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            transition: background-color 0.2s ease;
        }
        
        .sentiment-result-item:hover {
            background-color: #f8fafc;
        }
        
        .sentiment-result-item:last-child {
            border-bottom: none;
        }
        
        .progress-container {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .progress-label {
            min-width: 60px;
            font-size: 0.75rem;
            color: #64748b;
        }
        
        .progress-bar-custom {
            flex: 1;
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 4px;
        }
        
        .probability-value {
            min-width: 40px;
            font-size: 0.75rem;
            font-weight: 600;
            text-align: right;
        }
        
        .btn-purple {
            background: linear-gradient(135deg, #AF69EE, #7C3AED);
            border: none;
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-purple:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(175, 105, 238, 0.3);
            color: white;
        }
        
        .bg-gradient-purple {
            background: linear-gradient(135deg, #AF69EE, #7C3AED);
        }
        
        .toast-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border-radius: 8px;
            padding: 1rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-width: 300px;
            z-index: 9999;
            transform: translateX(400px);
            transition: transform 0.3s ease;
        }
        
        .toast-notification.show {
            transform: translateX(0);
        }
        
        .toast-content {
            display: flex;
            align-items: center;
        }
        
        .toast-success {
            border-left: 4px solid #10b981;
        }
        
        .toast-error {
            border-left: 4px solid #ef4444;
        }
        
        .toast-warning {
            border-left: 4px solid #f59e0b;
        }
        
        .toast-info {
            border-left: 4px solid #3b82f6;
        }
        
        .toast-close {
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 0.25rem;
            margin-left: 1rem;
        }
        
        .rating-stars-large {
            display: flex;
            gap: 0.25rem;
            font-size: 1.5rem;
        }
        
        .user-avatar-large {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #AF69EE, #7C3AED);
            color: white;
            font-weight: 600;
            font-size: 1.25rem;
        }
        
        .probability-bars {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .review-text-full {
            line-height: 1.6;
            color: #334155;
        }
        
        .badge.bg-purple {
            background: linear-gradient(135deg, #AF69EE, #7C3AED);
        }
        
        .text-purple {
            color: #7C3AED !important;
        }
        
        .btn-outline-light {
            border-color: rgba(255, 255, 255, 0.5);
            color: white;
        }
        
        .btn-outline-light:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: white;
            color: white;
        }
        
        .bg-opacity-10 {
            background-color: rgba(var(--color-rgb), 0.1) !important;
        }
        
        .border-opacity-25 {
            border-color: rgba(var(--color-rgb), 0.25) !important;
        }
        
        .spinner-border.text-purple {
            color: #7C3AED !important;
        }
        
        .probability-bars {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-2 text-dark">Feedback Pengguna</h1>
                <p class="text-muted">Kelola ulasan dan rating dari pengguna</p>
            </div>
            <div>
                <button class="btn btn-purple" onclick="runSentimentAnalysis()" id="analyzeBtn">
                    <i class="fas fa-chart-line me-2"></i>Analisis Sentimen
                </button>
            </div>
        </div>

        <!-- Sentiment Analysis Results Card -->
        @if($hasAnalysis && $analysisSummary)
        <div class="analysis-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">
                    <i class="fas fa-chart-pie me-2"></i>Hasil Analisis Sentimen
                </h5>
                <small class="opacity-75">
                    Terakhir diupdate: {{ \Carbon\Carbon::parse($analysisSummary['analysis_date'])->format('d/m/Y H:i') }}
                </small>
            </div>
            
            <div class="analysis-stats">
                <div class="stat-item">
                    <div class="stat-value text-black">{{ $analysisSummary['total_feedback'] }}</div>
                    <div class="stat-label">Total Feedback</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value text-success">{{ $analysisSummary['positive'] ?? 0 }}</div>
                    <div class="stat-label">Positif</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value text-warning">{{ $analysisSummary['neutral'] ?? 0 }}</div>
                    <div class="stat-label">Netral</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value text-danger">{{ $analysisSummary['negative'] ?? 0 }}</div>
                    <div class="stat-label">Negatif</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value text-black">{{ number_format($analysisSummary['mrr'], 3) }}</div>
                    <div class="stat-label">MRR Score</div>
                </div>
            </div>
            
            <div class="d-flex justify-content-end mt-3 gap-2">
                <button class="btn btn-sm btn-light" onclick="showSentimentResults()">
                    <i class="fas fa-list me-1"></i>Lihat Detail
                </button>
                <button class="btn btn-sm btn-outline-light" onclick="runSentimentAnalysis()">
                    <i class="fas fa-redo me-1"></i>Analisis Ulang
                </button>
            </div>
        </div>
        @endif

        <!-- Search -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="search-input-container">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control" id="searchInput"
                        placeholder="Cari feedback berdasarkan nama atau review...">
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-end gap-3">
                    <div class="text-end">
                        <h5 class="mb-0">{{ $feedbacks->total() }}</h5>
                        <small class="text-muted">Total Feedback</small>
                    </div>
                    <div class="vr"></div>
                    <div class="text-end">
                        <h5 class="mb-0">
                            @if($hasAnalysis && $analysisSummary)
                                {{ $analysisSummary['positive'] ?? 0 }}
                            @else
                                0
                            @endif
                        </h5>
                        <small class="text-muted">Feedback Positif</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Feedback Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Daftar Feedback</h5>
                <div class="text-muted small">
                    Menampilkan {{ $feedbacks->firstItem() ?: 0 }}-{{ $feedbacks->lastItem() ?: 0 }} dari
                    {{ $feedbacks->total() }} feedback
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th style="width: 60px;">Pengguna</th>
                                <th>Nama Pengguna</th>
                                <th>Review</th>
                                <th>Rating</th>
                                <th>Tanggal</th>
                                <th style="width: 100px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="feedbackTableBody">
                            @forelse ($feedbacks as $index => $feedback)
                                <tr data-feedback-id="{{ $feedback->id }}">
                                    <td data-label="No">{{ $feedbacks->firstItem() + $index }}</td>
                                    <td data-label="Pengguna">
                                        @if ($feedback->pengguna)
                                            @php
                                                $initials = '';
                                                $nameParts = explode(' ', $feedback->pengguna->nama_lengkap);
                                                if (count($nameParts) >= 2) {
                                                    $initials = strtoupper(
                                                        substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1),
                                                    );
                                                } else {
                                                    $initials = strtoupper(substr($feedback->pengguna->nama_lengkap, 0, 2));
                                                }
                                            @endphp
                                            <div class="initials-avatar">
                                                {{ $initials }}
                                            </div>
                                        @else
                                            <div class="initials-avatar">??</div>
                                        @endif
                                    </td>
                                    <td data-label="Nama Pengguna">
                                        @if ($feedback->pengguna)
                                            <div class="fw-medium">{{ $feedback->pengguna->nama_lengkap }}</div>
                                            <div class="text-muted small">ID: USR{{ str_pad($feedback->pengguna->id, 4, '0', STR_PAD_LEFT) }}</div>
                                        @else
                                            <span class="text-muted">Pengguna tidak ditemukan</span>
                                        @endif
                                    </td>
                                    <td data-label="Review">
                                        <div class="review-text" title="{{ $feedback->review }}">
                                            {{ $feedback->review ?: 'Tidak ada review' }}
                                        </div>
                                    </td>
                                    <td data-label="Rating">
                                        <div class="table-rating">
                                            <span class="rating-value">{{ $feedback->rating }}</span>
                                            <div class="rating-stars">
                                                @for ($i = 1; $i <= 5; $i++)
                                                    @if ($i <= $feedback->rating)
                                                        <i class="fas fa-star text-warning"></i>
                                                    @else
                                                        <i class="far fa-star text-muted"></i>
                                                    @endif
                                                @endfor
                                            </div>
                                        </div>
                                    </td>
                                    <td data-label="Tanggal">
                                        <div class="timestamp">
                                            <i class="far fa-clock"></i>
                                            {{ $feedback->created_at->format('d/m/Y') }}
                                        </div>
                                    </td>
                                    <td data-label="Aksi">
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-sm btn-purple"
                                                onclick="showDetail({{ $feedback->id }})">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="no-data">
                                        <i class="fas fa-comment-slash mt-3"></i>
                                        <p class="text-muted">Belum ada feedback dari pengguna</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if ($feedbacks->hasPages())
                    <div class="pagination-container">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="pagination-info">
                                Halaman {{ $feedbacks->currentPage() }} dari {{ $feedbacks->lastPage() }}
                            </div>
                            <nav>
                                <ul class="pagination">
                                    {{-- Previous Page Link --}}
                                    @if ($feedbacks->onFirstPage())
                                        <li class="page-item disabled">
                                            <span class="page-link">
                                                <i class="fas fa-chevron-left"></i>
                                            </span>
                                        </li>
                                    @else
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $feedbacks->previousPageUrl() }}" rel="prev">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    @endif

                                    {{-- Pagination Elements --}}
                                    @php
                                        $start = max(1, $feedbacks->currentPage() - 2);
                                        $end = min($feedbacks->lastPage(), $feedbacks->currentPage() + 2);
                                    @endphp

                                    @if ($start > 1)
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $feedbacks->url(1) }}">1</a>
                                        </li>
                                        @if ($start > 2)
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        @endif
                                    @endif

                                    @for ($i = $start; $i <= $end; $i++)
                                        <li class="page-item {{ $feedbacks->currentPage() == $i ? 'active' : '' }}">
                                            <a class="page-link" href="{{ $feedbacks->url($i) }}">{{ $i }}</a>
                                        </li>
                                    @endfor

                                    @if ($end < $feedbacks->lastPage())
                                        @if ($end < $feedbacks->lastPage() - 1)
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        @endif
                                        <li class="page-item">
                                            <a class="page-link"
                                                href="{{ $feedbacks->url($feedbacks->lastPage()) }}">{{ $feedbacks->lastPage() }}</a>
                                        </li>
                                    @endif

                                    {{-- Next Page Link --}}
                                    @if ($feedbacks->hasMorePages())
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $feedbacks->nextPageUrl() }}" rel="next">
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

    <!-- Detail Feedback Modal -->
    <div class="modal fade" id="detailFeedbackModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Feedback</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- User Header -->
                    <div class="detail-modal-header">
                        <div id="detailUserAvatar" class="user-avatar-large">
                            <!-- Avatar will be dynamically inserted here -->
                        </div>
                        <div class="d-flex justify-content-start align-items-start flex-column">
                            <h4 id="detailNamaLengkap" class="mb-1"></h4>
                            <p class="text-muted mb-2" id="detailEmail"></p>
                            <div class="d-flex align-items-center gap-3">
                                <span class="badge bg-purple bg-opacity-10 text-white border border-purple border-opacity-25 px-3 py-1 rounded-pill"
                                    id="detailUserId"></span>
                                <span class="timestamp" id="detailTimestamp"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Rating Display -->
                    <div class="mb-4">
                        <h6 class="text-muted mb-3">Rating</h6>
                        <div class="rating-display">
                            <span id="detailRatingValue" class="me-2"></span>
                            <div id="detailRatingStars" class="rating-stars-large">
                                <!-- Stars will be dynamically inserted here -->
                            </div>
                        </div>
                    </div>

                    <!-- Review Content -->
                    <div>
                        <h6 class="text-muted mb-3">Review</h6>
                        <div class="review-content">
                            <p id="detailReview" class="mb-0 review-text-full"></p>
                        </div>
                    </div>

                    <!-- Feedback Info -->
                    <div class="row g-3 mt-4">
                        <!-- Feedback ID -->
                        <div class="col-md-6">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <h6 class="card-title mb-3 text-dark">Informasi Feedback</h6>
                                    <div class="mb-2">
                                        <small class="text-muted d-block mb-1">ID Feedback</small>
                                        <div id="detailFeedbackId" class="fw-medium"></div>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted d-block mb-1">Tanggal Submit</small>
                                        <div id="detailCreatedAt" class="fw-medium"></div>
                                    </div>
                                    <div class="mb-0">
                                        <small class="text-muted d-block mb-1">Terakhir Diupdate</small>
                                        <div id="detailUpdatedAt" class="fw-medium"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- User Info -->
                        <div class="col-md-6">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <h6 class="card-title mb-3 text-dark">Informasi Pengguna</h6>
                                    <div class="mb-2">
                                        <small class="text-muted d-block mb-1">Email</small>
                                        <div id="detailUserEmail" class="fw-medium"></div>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted d-block mb-1">Terdaftar Sejak</small>
                                        <div id="detailUserCreatedAt" class="fw-medium"></div>
                                    </div>
                                    <div class="mb-0">
                                        <small class="text-muted d-block mb-1">Status Akun</small>
                                        <div>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-1 rounded-pill">
                                                Aktif
                                            </span>
                                        </div>
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

    <!-- Sentiment Results Modal -->
    <div class="modal fade" id="sentimentResultsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content modal-sentiment">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-chart-bar me-2"></i>Hasil Analisis Sentimen
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center" id="sentimentLoading">
                        <div class="spinner-border text-purple" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Memuat hasil analisis...</p>
                    </div>
                    
                    <div id="sentimentResultsContainer" style="display: none;">
                        <!-- Results will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-purple" onclick="runSentimentAnalysis()">
                        <i class="fas fa-redo me-1"></i>Analisis Ulang
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Analysis Progress Modal -->
    <div class="modal fade" id="analysisProgressModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-chart-line me-2"></i>Analisis Sentimen
                    </h5>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <div class="spinner-border text-purple mb-3" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <h6>Sedang menganalisis feedback...</h6>
                        <p class="text-muted small" id="progressMessage">Memulai proses analisis sentimen</p>
                        <div class="progress mt-3" style="height: 6px;">
                            <div id="analysisProgressBar" class="progress-bar bg-purple" role="progressbar" style="width: 0%"></div>
                        </div>
                        <div class="mt-3">
                            <span id="progressPercentage">0%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // CSRF Token untuk AJAX requests
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Get initials from name
        function getInitials(name) {
            if (!name) return '??';
            const nameParts = name.split(' ');
            if (nameParts.length >= 2) {
                return (nameParts[0].charAt(0) + nameParts[1].charAt(0)).toUpperCase();
            }
            return name.substring(0, 2).toUpperCase();
        }

        // Format date
        function formatDate(dateString) {
            if (!dateString) return '-';
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

        // Format date for timestamp
        function formatTimestamp(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now - date;
            const diffSec = Math.floor(diffMs / 1000);
            const diffMin = Math.floor(diffSec / 60);
            const diffHour = Math.floor(diffMin / 60);
            const diffDay = Math.floor(diffHour / 24);

            if (diffDay > 0) {
                return `${diffDay} hari yang lalu`;
            } else if (diffHour > 0) {
                return `${diffHour} jam yang lalu`;
            } else if (diffMin > 0) {
                return `${diffMin} menit yang lalu`;
            } else {
                return 'Baru saja';
            }
        }

        // Generate stars HTML
        function generateStars(rating) {
            let stars = '';
            for (let i = 1; i <= 5; i++) {
                if (i <= rating) {
                    stars += `<i class="fas fa-star text-warning"></i>`;
                } else {
                    stars += `<i class="far fa-star text-muted"></i>`;
                }
            }
            return stars;
        }

        // Show feedback detail
        async function showDetail(id) {
            try {
                const response = await fetch(`/admin/feedback-pengguna/${id}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    const feedback = data.data;
                    const pengguna = feedback.pengguna;

                    // Set user avatar
                    const avatar = document.getElementById('detailUserAvatar');
                    if (pengguna) {
                        const initials = getInitials(pengguna.nama_lengkap);
                        avatar.innerHTML = `<div class="user-avatar-large">${initials}</div>`;
                    } else {
                        avatar.innerHTML = '<div class="user-avatar-large">??</div>';
                    }

                    // Set user info
                    document.getElementById('detailNamaLengkap').textContent = pengguna ? pengguna.nama_lengkap : 'Pengguna tidak ditemukan';
                    document.getElementById('detailEmail').textContent = pengguna ? pengguna.email : '-';
                    document.getElementById('detailUserId').textContent = pengguna ? 
                        `ID: USR${String(pengguna.id).padStart(4, '0')}` : '-';
                    document.getElementById('detailUserEmail').textContent = pengguna ? pengguna.email : '-';

                    // Set rating
                    document.getElementById('detailRatingValue').textContent = `${feedback.rating}/5`;
                    document.getElementById('detailRatingStars').innerHTML = generateStars(feedback.rating);

                    // Set review
                    document.getElementById('detailReview').textContent = feedback.review || 'Tidak ada review';

                    // Set timestamps
                    document.getElementById('detailTimestamp').innerHTML = `
                        <i class="far fa-clock"></i>
                        ${formatTimestamp(feedback.created_at)}
                    `;
                    document.getElementById('detailCreatedAt').textContent = formatDate(feedback.created_at);
                    document.getElementById('detailUpdatedAt').textContent = formatDate(feedback.updated_at);
                    document.getElementById('detailUserCreatedAt').textContent = pengguna ? formatDate(pengguna.created_at) : '-';
                    
                    // Set feedback ID
                    document.getElementById('detailFeedbackId').textContent = `FDB${String(feedback.id).padStart(4, '0')}`;

                    // Show modal
                    const modal = new bootstrap.Modal(document.getElementById('detailFeedbackModal'));
                    modal.show();
                } else {
                    showToast('Gagal mengambil data feedback', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Terjadi kesalahan', 'error');
            }
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#feedbackTableBody tr');

            rows.forEach(row => {
                if (row.querySelector('.no-data')) return;

                const name = row.querySelector('td:nth-child(3) .fw-medium')?.textContent.toLowerCase() || '';
                const review = row.querySelector('td:nth-child(4) .review-text')?.textContent.toLowerCase() || '';
                const rating = row.querySelector('td:nth-child(5) .rating-value')?.textContent.toLowerCase() || '';
                const sentiment = row.querySelector('td:nth-child(6) .sentiment-badge')?.textContent.toLowerCase() || '';

                if (name.includes(searchTerm) || review.includes(searchTerm) || rating.includes(searchTerm) || sentiment.includes(searchTerm)) {
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
                                    type === 'error' ? 'exclamation-circle' :
                                    type === 'warning' ? 'exclamation-triangle' : 
                                    'info-circle'} me-2 text-${type}"></i>
                    <span>${message}</span>
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
            }, 5000);
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
                        if (!row.querySelector('.no-data')) {
                            const cell = row.querySelector(`td:nth-child(${index + 1})`);
                            if (cell) {
                                cell.setAttribute('data-label', label);
                            }
                        }
                    });
                });
            }
        }

        // Run sentiment analysis
        async function runSentimentAnalysis() {
            if (!confirm('Jalankan analisis sentimen? Proses ini mungkin memerlukan waktu beberapa menit.')) {
                return;
            }
            
            try {
                // Tampilkan modal progress
                const progressModal = new bootstrap.Modal(document.getElementById('analysisProgressModal'));
                progressModal.show();
                
                // Update progress secara bertahap
                const progressBar = document.getElementById('analysisProgressBar');
                const progressPercentage = document.getElementById('progressPercentage');
                const progressMessage = document.getElementById('progressMessage');
                
                // Simulasi progress
                const progressSteps = [
                    { percentage: 10, message: 'Menyiapkan data feedback...' },
                    { percentage: 30, message: 'Menghubungkan ke database...' },
                    { percentage: 50, message: 'Memproses analisis sentimen...' },
                    { percentage: 75, message: 'Menghitung statistik MRR...' },
                    { percentage: 90, message: 'Menyimpan hasil analisis...' },
                ];
                
                let currentStep = 0;
                const progressInterval = setInterval(() => {
                    if (currentStep < progressSteps.length) {
                        const step = progressSteps[currentStep];
                        progressBar.style.width = step.percentage + '%';
                        progressPercentage.textContent = step.percentage + '%';
                        progressMessage.textContent = step.message;
                        currentStep++;
                    }
                }, 1000);
                
                // Kirim request ke server
                const response = await fetch('/admin/feedback/analyze-sentiment', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                
                clearInterval(progressInterval);
                
                const data = await response.json();
                
                // Tampilkan progress 100%
                progressBar.style.width = '100%';
                progressPercentage.textContent = '100%';
                progressMessage.textContent = 'Analisis selesai!';
                
                setTimeout(() => {
                    progressModal.hide();
                    
                    if (data.success) {
                        showToast(data.message, 'success');
                        
                        // Reload page untuk menampilkan hasil
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        showToast(data.message, 'error');
                    }
                }, 1000);
                
            } catch (error) {
                console.error('Error:', error);
                showToast('Terjadi kesalahan saat analisis sentimen', 'error');
                
                // Tutup modal progress
                const progressModal = bootstrap.Modal.getInstance(document.getElementById('analysisProgressModal'));
                if (progressModal) {
                    progressModal.hide();
                }
            }
        }

        // Show sentiment results
        async function showSentimentResults(page = 1) {
            const modal = new bootstrap.Modal(document.getElementById('sentimentResultsModal'));
            modal.show();
            
            try {
                const response = await fetch(`/admin/feedback/sentiment-results?page=${page}`, {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                const data = await response.json();
                
                if (data.success) {
                    renderSentimentResults(data);
                } else {
                    document.getElementById('sentimentLoading').innerHTML = `
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-exclamation-circle fa-2x mb-3 opacity-50"></i>
                            <p>${data.message || 'Gagal memuat hasil analisis'}</p>
                            <button class="btn btn-purple mt-2" onclick="runSentimentAnalysis()">
                                <i class="fas fa-play me-1"></i>Jalankan Analisis
                            </button>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('sentimentLoading').innerHTML = `
                    <div class="text-center text-danger py-4">
                        <i class="fas fa-times-circle fa-2x mb-3"></i>
                        <p>Terjadi kesalahan saat memuat data</p>
                    </div>
                `;
            }
        }

        // Render sentiment results
        function renderSentimentResults(data) {
            const container = document.getElementById('sentimentResultsContainer');
            const loading = document.getElementById('sentimentLoading');
            
            loading.style.display = 'none';
            container.style.display = 'block';
            
            let html = `
                <div class="mb-4">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card border-0 bg-light">
                                <div class="card-body text-center">
                                    <h2 class="text-dark mb-1">${data.summary.total_feedback || 0}</h2>
                                    <p class="text-muted mb-0">Total Feedback</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 bg-light">
                                <div class="card-body text-center">
                                    <h2 class="text-success mb-1">${data.summary.sentiment_distribution?.positive || 0}</h2>
                                    <p class="text-muted mb-0">Positif</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 bg-light">
                                <div class="card-body text-center">
                                    <h2 class="text-warning mb-1">${data.summary.sentiment_distribution?.neutral || 0}</h2>
                                    <p class="text-muted mb-0">Netral</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 bg-light">
                                <div class="card-body text-center">
                                    <h2 class="text-danger mb-1">${data.summary.sentiment_distribution?.negative || 0}</h2>
                                    <p class="text-muted mb-0">Negatif</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card border-0 bg-gradient-purple text-white mt-3">
                        <div class="card-body text-center">
                            <h5 class="mb-2">Mean Reciprocal Rank (MRR)</h5>
                            <h1 class="mb-0">${data.mrr.toFixed(4)}</h1>
                            <p class="mb-0 opacity-75">Skor evaluasi ranking sentimen</p>
                        </div>
                    </div>
                </div>
                
                <h6 class="mb-3">Detail Analisis Sentimen</h6>
            `;
            
            if (data.data && data.data.length > 0) {
                data.data.forEach((item, index) => {
                    const sentimentClass = `sentiment-${item.sentiment}`;
                    let sentimentText = '';
                    
                    switch(item.sentiment) {
                        case 'positive':
                            sentimentText = 'Positif';
                            break;
                        case 'negative':
                            sentimentText = 'Negatif';
                            break;
                        case 'neutral':
                            sentimentText = 'Netral';
                            break;
                        case 'no_review':
                            sentimentText = 'Tidak Ada Review';
                            break;
                        default:
                            sentimentText = item.sentiment;
                    }
                    
                    html += `
                        <div class="sentiment-result-item">
                            <div class="row align-items-center">
                                <div class="col-md-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="initials-avatar small">
                                            ${getInitials(item.user_name)}
                                        </div>
                                        <div>
                                            <div class="fw-medium small">${item.user_name || 'N/A'}</div>
                                            <small class="text-muted">FDB${item.feedback_id.toString().padStart(4, '0')}</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="review-text small" title="${item.original_review || ''}">
                                        ${item.original_review ? (item.original_review.length > 100 ? item.original_review.substring(0, 100) + '...' : item.original_review) : 'Tidak ada review'}
                                    </div>
                                </div>
                                <div class="col-md-1 text-center">
                                    <span class="badge bg-warning bg-opacity-10 text-dark">${item.rating}/5</span>
                                </div>
                                <div class="col-md-2">
                                    <span class="sentiment-badge ${sentimentClass}">
                                        ${sentimentText}
                                    </span>
                                </div>
                                <div class="col-md-3">
                                    ${renderProbabilityBars(item.probability)}
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                // Pagination
                if (data.pagination && data.pagination.total_pages > 1) {
                    html += `
                        <div class="d-flex justify-content-center mt-4">
                            <nav>
                                <ul class="pagination">
                                    ${data.pagination.current_page > 1 ? 
                                        `<li class="page-item">
                                            <button class="page-link" onclick="showSentimentResults(${data.pagination.current_page - 1})">
                                                <i class="fas fa-chevron-left"></i>
                                            </button>
                                        </li>` : ''
                                    }
                                    
                                    ${Array.from({length: Math.min(5, data.pagination.total_pages)}, (_, i) => {
                                        const pageNum = i + 1;
                                        return `
                                            <li class="page-item ${pageNum === data.pagination.current_page ? 'active' : ''}">
                                                <button class="page-link" onclick="showSentimentResults(${pageNum})">
                                                    ${pageNum}
                                                </button>
                                            </li>
                                        `;
                                    }).join('')}
                                    
                                    ${data.pagination.current_page < data.pagination.total_pages ? 
                                        `<li class="page-item">
                                            <button class="page-link" onclick="showSentimentResults(${data.pagination.current_page + 1})">
                                                <i class="fas fa-chevron-right"></i>
                                            </button>
                                        </li>` : ''
                                    }
                                </ul>
                            </nav>
                        </div>
                    `;
                }
            } else {
                html += `
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-chart-line fa-3x mb-3 opacity-50"></i>
                        <p>Tidak ada data hasil analisis tersedia.</p>
                        <button class="btn btn-purple mt-2" onclick="runSentimentAnalysis()">
                            <i class="fas fa-play me-1"></i>Jalankan Analisis
                        </button>
                    </div>
                `;
            }
            
            container.innerHTML = html;
        }

        // Render probability bars
        function renderProbabilityBars(probability) {
            let bars = '';
            const colors = {
                positive: 'success',
                negative: 'danger',
                neutral: 'warning'
            };
            
            for (const [sentiment, value] of Object.entries(probability)) {
                if (sentiment !== 'unknown' && sentiment !== 'no_review') {
                    const percentage = (value * 100).toFixed(1);
                    const sentimentText = sentiment.charAt(0).toUpperCase() + sentiment.slice(1);
                    
                    bars += `
                        <div class="progress-container">
                            <span class="progress-label">${sentimentText}</span>
                            <div class="progress-bar-custom">
                                <div class="progress-fill bg-${colors[sentiment]}" 
                                     style="width: ${percentage}%"></div>
                            </div>
                            <span class="probability-value">${percentage}%</span>
                        </div>
                    `;
                }
            }
            
            return `<div class="probability-bars">${bars}</div>`;
        }

        // Load sentiment data for each feedback
        async function loadSentimentData() {
            try {
                const response = await fetch('/admin/feedback/sentiment-results?page=1', {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                const data = await response.json();
                
                if (data.success && data.data) {
                    // Update sentiment badges in table
                    data.data.forEach(item => {
                        const badge = document.getElementById(`sentiment-${item.feedback_id}`);
                        if (badge) {
                            badge.className = `sentiment-badge sentiment-${item.sentiment}`;
                            
                            let sentimentText = '';
                            switch(item.sentiment) {
                                case 'positive':
                                    sentimentText = 'Positif';
                                    break;
                                case 'negative':
                                    sentimentText = 'Negatif';
                                    break;
                                case 'neutral':
                                    sentimentText = 'Netral';
                                    break;
                                case 'no_review':
                                    sentimentText = 'Tidak Ada Review';
                                    break;
                                default:
                                    sentimentText = item.sentiment;
                            }
                            badge.textContent = sentimentText;
                        }
                    });
                }
            } catch (error) {
                console.error('Error loading sentiment data:', error);
            }
        }

        // Load sentiment data on page load
        document.addEventListener('DOMContentLoaded', function() {
            makeTableResponsive();
            window.addEventListener('resize', makeTableResponsive);
            
            @if($hasAnalysis)
                loadSentimentData();
            @endif
        });
    </script>
@endpush