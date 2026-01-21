<?php

namespace App\Http\Controllers;

use App\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;

class ManajemenPenggunaController extends Controller
{
    /**
     * Display a listing of the pengguna.
     */
    public function index()
    {
        $penggunas = Pengguna::orderBy('created_at', 'desc')->paginate(10);
        return view('admins.manajemen-pengguna.index', compact('penggunas'));
    }

    /**
     * Display the specified pengguna.
     */
    public function show($id)
    {
        try {
            $pengguna = Pengguna::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $pengguna
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak ditemukan: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Remove the specified pengguna from storage.
     */
    public function destroy($id)
    {
        try {
            $pengguna = Pengguna::findOrFail($id);
            
            // Hapus foto profile jika ada
            if ($pengguna->foto_profile && File::exists(public_path('profile/' . $pengguna->foto_profile))) {
                File::delete(public_path('profile/' . $pengguna->foto_profile));
            }
            
            $pengguna->delete();

            return response()->json([
                'success' => true,
                'message' => 'Pengguna berhasil dihapus!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get avatar URL
     */
    private function getAvatarUrl($pengguna)
    {
        // Cek jika foto profile ada di path C:\xampp\htdocs\gym-api\public\profile
        if ($pengguna->foto_profile && File::exists(public_path('profile/' . $pengguna->foto_profile))) {
            return asset('profile/' . $pengguna->foto_profile);
        }
        
        // Jika tidak ada foto, gunakan avatar dari UI Avatars
        $name = urlencode($pengguna->nama_lengkap);
        return "https://ui-avatars.com/api/?name={$name}&background=AF69EE&color=fff&size=200";
    }

    /**
     * Get initials from name
     */
    private function getInitials($name)
    {
        $nameParts = explode(' ', $name);
        if (count($nameParts) >= 2) {
            return strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1));
        }
        return strtoupper(substr($name, 0, 2));
    }
}