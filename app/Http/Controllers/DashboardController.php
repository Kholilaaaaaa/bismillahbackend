<?php

namespace App\Http\Controllers;

use App\Models\Pengguna;
use App\Models\Admin;
use App\Models\Food;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display dashboard overview
     */
    public function index()
    {
        // Get total counts
        $totalPengguna = Pengguna::count();
        $totalAdmin = Admin::count();
        $totalFood = Food::count();
        
        // Get latest update dates
        $latestPengguna = Pengguna::latest()->first();
        $latestAdmin = Admin::latest()->first();
        $latestFood = Food::latest()->first();
        
        // Get gender statistics
        $genderStats = Pengguna::selectRaw('jenis_kelamin, COUNT(*) as count')
            ->groupBy('jenis_kelamin')
            ->get()
            ->pluck('count', 'jenis_kelamin')
            ->toArray();
        
        // Get blood type statistics
        $bloodTypeStats = Pengguna::selectRaw('golongan_darah, COUNT(*) as count')
            ->whereNotNull('golongan_darah')
            ->groupBy('golongan_darah')
            ->get()
            ->pluck('count', 'golongan_darah')
            ->toArray();
        
        // Get BMI statistics
        $bmiStats = $this->getBMIStats();
        
        // Get food category statistics
        $foodCategoryStats = Food::selectRaw('kategori_makanan, COUNT(*) as count')
            ->groupBy('kategori_makanan')
            ->get()
            ->pluck('count', 'kategori_makanan')
            ->toArray();
        
        // Get recent pengguna
        $recentPengguna = Pengguna::latest()->take(5)->get();
        
        // Get recent foods
        $recentFoods = Food::latest()->take(5)->get();
        
        // Get recent admins
        $recentAdmins = Admin::latest()->take(5)->get();
        
        // Get new registrations in last 7 days
        $newRegistrations = Pengguna::whereDate('created_at', '>=', Carbon::now()->subDays(7))
            ->get()
            ->groupBy(function($date) {
                return Carbon::parse($date->created_at)->format('Y-m-d');
            })
            ->map->count()
            ->toArray();
        
        // Prepare chart data
        $chartData = [
            'labels' => array_keys($newRegistrations),
            'data' => array_values($newRegistrations),
        ];
        
        return view('admins.dashboard.index', compact(
            'totalPengguna',
            'totalAdmin',
            'totalFood',
            'latestPengguna',
            'latestAdmin',
            'latestFood',
            'genderStats',
            'bloodTypeStats',
            'bmiStats',
            'foodCategoryStats',
            'recentPengguna',
            'recentFoods',
            'recentAdmins',
            'chartData'
        ));
    }
    
    /**
     * Calculate BMI statistics
     */
    private function getBMIStats()
    {
        $penggunaWithBMI = Pengguna::whereNotNull('tinggi_badan')
            ->whereNotNull('berat_badan')
            ->get();
        
        $stats = [
            'underweight' => 0,
            'normal' => 0,
            'overweight' => 0,
            'obesity' => 0
        ];
        
        foreach ($penggunaWithBMI as $pengguna) {
            $bmi = $pengguna->bmi;
            
            if ($bmi < 18.5) {
                $stats['underweight']++;
            } elseif ($bmi < 25) {
                $stats['normal']++;
            } elseif ($bmi < 30) {
                $stats['overweight']++;
            } else {
                $stats['obesity']++;
            }
        }
        
        return $stats;
    }
    
    /**
     * Get dashboard statistics for API
     */
    public function getStats()
    {
        $totalPengguna = Pengguna::count();
        $totalAdmin = Admin::count();
        $totalFood = Food::count();
        
        // Get new pengguna today
        $newPenggunaToday = Pengguna::whereDate('created_at', Carbon::today())->count();
        
        // Get new food today
        $newFoodToday = Food::whereDate('created_at', Carbon::today())->count();
        
        // Get latest update
        $latestUpdate = max(
            Pengguna::max('updated_at'),
            Admin::max('updated_at'),
            Food::max('updated_at')
        );
        
        return response()->json([
            'success' => true,
            'data' => [
                'total_pengguna' => $totalPengguna,
                'total_admin' => $totalAdmin,
                'total_food' => $totalFood,
                'new_pengguna_today' => $newPenggunaToday,
                'new_food_today' => $newFoodToday,
                'latest_update' => $latestUpdate ? Carbon::parse($latestUpdate)->format('Y-m-d H:i:s') : null,
            ]
        ]);
    }
}