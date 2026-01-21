@extends('layouts.app')

@section('title', 'Dashboard - Gym GenZ Admin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@section('content')
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h2 mb-2 text-dark">Dashboard Overview</h1>
                <h6 class="text-muted">Halo {{ Auth::guard('admin')->user()->nama_lengkap }}, selamat datang kembali. Yuk,
                    kelola data dan aktivitas gym hari ini.</h6>
            </div>
            <div class="text-end">
                <div class="text-muted small">
                    <i class="fas fa-sync-alt me-1"></i>
                    Data terakhir diperbarui:
                    @php
                        use Carbon\Carbon;

                        $latestUpdate = max(
                            $latestPengguna->created_at ?? null,
                            $latestAdmin->created_at ?? null,
                            $latestFood->created_at ?? null,
                        );
                    @endphp

                    @if ($latestUpdate)
                        {{ Carbon::parse($latestUpdate)->setTimezone('Asia/Jakarta')->locale('id_ID')->translatedFormat('d F Y H:i') }}
                        WIB
                    @else
                        Tidak ada data
                    @endif
                </div>
            </div>
        </div>

        <!-- Stat Cards -->
        <div class="row g-4 mb-4">
            <!-- Stat Card: Total Admin -->
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card stat-card-large">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="text-uppercase text-muted mb-2">Total Admin</h5>
                                <h2 class="stat-number mb-0">{{ number_format($totalAdmin) }}</h2>
                            </div>
                            <div class="icon-container bg-purple-light">
                                <i class="fas fa-user-shield text-purple"></i>
                            </div>
                        </div>
                        <div class="card-footer-stat mt-2 pt-3 border-top">
                            <div class="text-purple small">
                                <i class="fas fa-calendar-plus me-1"></i>
                                @if ($latestAdmin)
                                    Terakhir ditambahkan: {{ $latestAdmin->created_at->format('d M') }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stat Card: Total Pengguna -->
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card stat-card-large">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="text-uppercase text-muted mb-2">Total Pengguna</h5>
                                <h2 class="stat-number mb-0">{{ number_format($totalPengguna) }}</h2>
                            </div>
                            <div class="icon-container bg-purple-light">
                                <i class="fas fa-users text-purple"></i>
                            </div>
                        </div>
                        <div class="card-footer-stat mt-2 pt-3 border-top">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="small text-purple">
                                    <i class="fas fa-venus-mars me-1"></i>
                                    {{ ($genderStats['P'] ?? 0) + ($genderStats['L'] ?? 0) }} pengguna aktif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stat Card: Total Food Plan -->
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card stat-card-large">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="text-uppercase text-muted mb-2">Total Food Plan</h5>
                                <h2 class="stat-number mb-0">{{ number_format($totalFood) }}</h2>
                            </div>
                            <div class="icon-container bg-purple-light">
                                <i class="fas fa-utensils text-purple"></i>
                            </div>
                        </div>
                        <div class="card-footer-stat mt-2 pt-3 border-top">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="small text-purple">
                                    <i class="fas fa-utensils me-1"></i>
                                    {{ count($foodCategoryStats) }} kategori waktu makan
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stat Card: BMI Stats -->
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card stat-card-large">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="text-uppercase text-muted mb-2">Status BMI</h5>
                                @php
                                    $totalWithBMI = array_sum($bmiStats);
                                    $normalBMI = $bmiStats['normal'] ?? 0;
                                    $percentage = $totalWithBMI > 0 ? round(($normalBMI / $totalWithBMI) * 100) : 0;
                                @endphp
                                <h2 class="stat-number mb-0">{{ $percentage }}%</h2>
                            </div>
                            <div class="icon-container bg-purple-light">
                                <i class="fas fa-chart-line text-purple"></i>
                            </div>
                        </div>
                        <div class="card-footer-stat mt-2 pt-3 border-top">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-purple small">
                                    <i class="fas fa-heartbeat me-1"></i>
                                    {{ $normalBMI }} pengguna normal
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row g-4 mb-4">
            <!-- Chart 1: New Registrations -->
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-tint me-2 text-purple"></i>
                            Golongan Darah Pengguna
                        </h5>
                    </div>
                    <div class="card-body">
                        @if (count($bloodTypeStats) > 0)
                            <div style="height: 300px;">
                                <canvas id="bloodTypeChart"></canvas>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-tint fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Belum ada data golongan darah</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Chart 2: Gender Distribution -->
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-venus-mars me-2 text-purple"></i>
                            Distribusi Jenis Kelamin Pengguna
                        </h5>
                    </div>
                    <div class="card-body">
                        <div style="height: 300px;">
                            <canvas id="genderChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-layer-group me-2 text-purple"></i>
                            Kategori Makanan
                        </h5>
                    </div>
                    <div class="card-body">
                        @if (count($foodCategoryStats) > 0)
                            <div class="row g-3">
                                @foreach ($foodCategoryStats as $category => $count)
                                    <div class="col-6">
                                        <div class="d-flex align-items-center p-3 border rounded">
                                            <div class="bg-primary-light rounded p-2 me-3">
                                                <i class="fas fa-utensils text-purple"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-1">{{ $category }}</h6>
                                                <h4 class="mb-0">{{ $count }}</h4>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-utensils fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Belum ada data makanan</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-weight-scale me-2 text-purple"></i>
                            Kategori BMI Pengguna
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="d-flex align-items-center p-3 border rounded bg-success-light">
                                    <div class="bg-success rounded py-2 px-2 me-3">
                                        <i class="fas fa-heart text-white"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">Normal</h6>
                                        <h4 class="mb-0">{{ $bmiStats['normal'] ?? 0 }}</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="d-flex align-items-center p-3 border rounded bg-warning-light">
                                    <div class="bg-warning rounded px-2 py-2 me-3">
                                        <i class="fas fa-exclamation-circle text-white"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">Overweight</h6>
                                        <h4 class="mb-0">{{ $bmiStats['overweight'] ?? 0 }}</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="d-flex align-items-center p-3 border rounded bg-danger-light">
                                    <div class="bg-danger rounded py-2 px-2 me-3">
                                        <i class="fas fa-exclamation-triangle text-white"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">Obesitas</h6>
                                        <h4 class="mb-0">{{ $bmiStats['obesity'] ?? 0 }}</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="d-flex align-items-center p-3 border rounded bg-info-light">
                                    <div class="bg-info rounded px-2 py-2 me-3">
                                        <i class="fas fa-info-circle text-white"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">Underweight</h6>
                                        <h4 class="mb-0">{{ $bmiStats['underweight'] ?? 0 }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Primary color variables
            const primaryColor = '#AF69EE';
            const primaryDark = '#8a2be2';
            const primaryLight = '#c59cf4';

            // Gender Chart
            const genderCtx = document.getElementById('genderChart').getContext('2d');
            const genderChart = new Chart(genderCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Laki-laki', 'Perempuan'],
                    datasets: [{
                        data: [
                            {{ $genderStats['L'] ?? 0 }},
                            {{ $genderStats['P'] ?? 0 }}
                        ],
                        backgroundColor: [
                            '#8a2be2',
                            '#c59cf4'
                        ],
                        borderWidth: 1,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // Blood Type Chart
            @if (count($bloodTypeStats) > 0)
                const bloodTypeCtx = document.getElementById('bloodTypeChart').getContext('2d');
                const bloodTypeChart = new Chart(bloodTypeCtx, {
                    type: 'bar',
                    data: {
                        labels: @json(array_keys($bloodTypeStats)),
                        datasets: [{
                            label: 'Jumlah',
                            data: @json(array_values($bloodTypeStats)),
                            backgroundColor: primaryLight,
                            borderColor: primaryColor,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0,0,0,0.05)'
                                }
                            },
                            x: {
                                grid: {
                                    color: 'rgba(0,0,0,0.05)'
                                }
                            }
                        }
                    }
                });
            @endif

            // Auto refresh stats every 60 seconds
            function refreshStats() {
                fetch('{{ route('admin.dashboard.stats') }}')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update stat numbers if needed
                            console.log('Stats refreshed:', data.data);
                        }
                    });
            }

            // Start auto-refresh
            setInterval(refreshStats, 60000);
        });
    </script>

    <style>
        .bg-primary-light {
            background-color: rgba(175, 105, 238, 0.1) !important;
        }

        .bg-success-light {
            background-color: rgba(40, 167, 69, 0.1) !important;
        }

        .bg-warning-light {
            background-color: rgba(255, 193, 7, 0.1) !important;
        }

        .bg-danger-light {
            background-color: rgba(220, 53, 69, 0.1) !important;
        }

        .bg-info-light {
            background-color: rgba(23, 162, 184, 0.1) !important;
        }

        .text-purple {
            color: #AF69EE !important;
        }

        .border-primary-light {
            border-color: rgba(175, 105, 238, 0.2) !important;
        }

        .avatar {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #8a2be2, #AF69EE);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
@endsection
