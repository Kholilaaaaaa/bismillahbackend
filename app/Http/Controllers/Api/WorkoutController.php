<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workout;
use App\Models\JadwalWorkout;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class WorkoutController extends Controller
{
    /**
     * Get today's workouts with their jadwal information
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTodayWorkouts(Request $request)
    {
        try {
            $today = Carbon::today()->toDateString();
            $now = Carbon::now();
            
            Log::info('Today\'s date for workout query: ' . $today);
            $workouts = Workout::with(['jadwalWorkout' => function($query) use ($today) {
                $query->whereDate('tanggal', $today)
                      ->orderBy('jam', 'asc');
            }])
            ->whereHas('jadwalWorkout', function($query) use ($today) {
                $query->whereDate('tanggal', $today);
            })
            ->get()
            ->map(function ($workout) use ($now) {
                $jadwal = $workout->jadwalWorkout;
                
                if (!$jadwal) {
                    return null;
                }
                
                $tanggal = Carbon::parse($jadwal->tanggal)->format('Y-m-d');
                $jam = $jadwal->jam;
                
                Log::info('Jadwal date: ' . $tanggal . ', Jam: ' . $jam);
                $jadwalDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $tanggal . ' ' . $jam);
                
                return [
                    'id' => $workout->id,
                    'nama_workout' => $workout->nama_workout,
                    'deskripsi' => $workout->deskripsi,
                    'equipment' => $workout->equipment,
                    'kategori' => $workout->kategori,
                    'exercises' => $workout->exercises,
                    'status' => $workout->status,
                    'jadwal_workout_id' => $workout->jadwal_workout_id,
                    'created_at' => $workout->created_at,
                    'updated_at' => $workout->updated_at,
                    'jadwal' => [
                        'id' => $jadwal->id,
                        'nama_jadwal' => $jadwal->nama_jadwal,
                        'kategori_jadwal' => $jadwal->kategori_jadwal,
                        'tanggal' => $jadwal->tanggal,
                        'jam' => $jadwal->jam,
                        'durasi_workout' => $jadwal->durasi_workout
                    ]
                ];
            })
            ->filter()
            ->values();

            Log::info('Workouts found: ' . $workouts->count());

            if ($workouts->isEmpty()) {
                $todayJadwalsCount = JadwalWorkout::whereDate('tanggal', $today)->count();
                
                if ($todayJadwalsCount > 0) {
                    return response()->json([
                        'status' => 'success',
                        'message' => 'No workouts found for today\'s schedules.',
                        'date' => $today,
                        'total_jadwals' => $todayJadwalsCount,
                        'total_workouts' => 0,
                        'data' => []
                    ], 200);
                }
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'No schedules or workouts found for today.',
                    'date' => $today,
                    'total_jadwals' => 0,
                    'total_workouts' => 0,
                    'data' => []
                ], 200);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Today\'s workouts retrieved successfully.',
                'date' => $today,
                'total_jadwals' => JadwalWorkout::whereDate('tanggal', $today)->count(),
                'total_workouts' => $workouts->count(),
                'data' => $workouts
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error in getTodayWorkouts: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve today\'s workouts: ' . $e->getMessage()
            ], 500);
        }
    }
}