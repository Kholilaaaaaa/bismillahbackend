<?php

namespace App\Http\Controllers;

use App\Models\Workout;
use App\Models\JadwalWorkout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ManajemenWorkoutController extends Controller
{
    /**
     * Display a listing of the workouts.
     */
    public function index()
    {
        $workouts = Workout::with('jadwalWorkout')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        $jadwals = JadwalWorkout::whereDoesntHave('workout')
            ->orderBy('tanggal', 'desc')
            ->orderBy('jam', 'desc')
            ->get();
        
        return view('admins.manajemen-workout.index', compact('workouts', 'jadwals'));
    }

    /**
     * Store a newly created workout in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'jadwal_workout_id' => 'required|exists:jadwal_workouts,id|unique:workouts,jadwal_workout_id',
            'nama_workout' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'equipment' => 'required|string|max:255',
            'kategori' => 'required|in:Without Equipment,With Equipment',
            'exercises' => 'required|integer|min:1',
            'status' => 'required|in:belum,sedang dilakukan,selesai',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $workout = Workout::create([
                'jadwal_workout_id' => $request->jadwal_workout_id,
                'nama_workout' => $request->nama_workout,
                'deskripsi' => $request->deskripsi,
                'equipment' => $request->equipment,
                'kategori' => $request->kategori,
                'exercises' => $request->exercises,
                'status' => $request->status,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Workout berhasil ditambahkan!',
                'data' => $workout
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified workout.
     */
    public function show($id)
    {
        $workout = Workout::with('jadwalWorkout')->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $workout
        ]);
    }

    /**
     * Update the specified workout in storage.
     */
    public function update(Request $request, $id)
    {
        $workout = Workout::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'jadwal_workout_id' => 'required|exists:jadwal_workouts,id|unique:workouts,jadwal_workout_id,' . $id,
            'nama_workout' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'equipment' => 'required|string|max:255',
            'kategori' => 'required|in:Without Equipment,With Equipment',
            'exercises' => 'required|integer|min:1',
            'status' => 'required|in:belum,sedang dilakukan,selesai',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $workout->update([
                'jadwal_workout_id' => $request->jadwal_workout_id,
                'nama_workout' => $request->nama_workout,
                'deskripsi' => $request->deskripsi,
                'equipment' => $request->equipment,
                'kategori' => $request->kategori,
                'exercises' => $request->exercises,
                'status' => $request->status,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Workout berhasil diperbarui!',
                'data' => $workout
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified workout from storage.
     */
    public function destroy($id)
    {
        try {
            $workout = Workout::findOrFail($id);
            $workout->delete();

            return response()->json([
                'success' => true,
                'message' => 'Workout berhasil dihapus!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available jadwal workouts for dropdown.
     */
    public function getAvailableJadwals()
    {
        $jadwals = JadwalWorkout::whereDoesntHave('workout')
            ->orderBy('tanggal', 'desc')
            ->orderBy('jam', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $jadwals
        ]);
    }
}