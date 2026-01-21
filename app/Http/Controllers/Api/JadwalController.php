<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JadwalWorkout;
use Illuminate\Http\Request;
use Carbon\Carbon;

class JadwalController extends Controller
{
    /**
     * Get today's workout schedules sorted by time
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTodaySchedules(Request $request)
    {
        try {
            // Get today's date
            $today = Carbon::today()->toDateString();
            
            // Query schedules for today, sorted by jam (time) ascending
            $schedules = JadwalWorkout::whereDate('tanggal', $today)
                ->orderBy('jam', 'asc')
                ->get();
            
            // Format the response
            $formattedSchedules = $schedules->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'nama_jadwal' => $schedule->nama_jadwal,
                    'kategori_jadwal' => $schedule->kategori_jadwal,
                    'tanggal' => $schedule->tanggal,
                    'jam' => $schedule->jam,
                    'durasi_workout' => $schedule->durasi_workout,
                    'created_at' => $schedule->created_at,
                    'updated_at' => $schedule->updated_at,
                ];
            });
            
            return response()->json([
                'status' => 'success',
                'message' => 'Today\'s workout schedules retrieved successfully',
                'date' => $today,
                'total' => $schedules->count(),
                'data' => $formattedSchedules
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve schedules: ' . $e->getMessage()
            ], 500);
        }
    }
}