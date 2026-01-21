<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;

class ProfileController extends Controller
{
    // Menampilkan halaman profile
    public function index()
    {
        $admin = Auth::guard('admin')->user();
        return view('admins.profile.index', compact('admin'));
    }

    // Update profile
    public function update(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        
        $validator = Validator::make($request->all(), [
            'nama_lengkap' => 'required|string|max:255',
            'email' => 'required|email|unique:admins,email,' . $admin->id,
            'nomor_telfon' => 'nullable|string|max:15',
            'foto_profile' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Update menggunakan query builder untuk menghindari error save()
            Admin::where('id', $admin->id)->update([
                'nama_lengkap' => $request->nama_lengkap,
                'email' => $request->email,
                'nomor_telfon' => $request->nomor_telfon,
            ]);

            // Handle upload foto profile
            if ($request->hasFile('foto_profile')) {
                // Hapus foto lama jika ada
                if ($admin->foto_profile && File::exists(public_path('admins/' . $admin->foto_profile))) {
                    File::delete(public_path('admins/' . $admin->foto_profile));
                }

                $file = $request->file('foto_profile');
                $fileName = time() . '_' . $file->getClientOriginalName();
                
                // Simpan di public/admins
                $file->move(public_path('admins'), $fileName);
                
                // Update foto profile
                Admin::where('id', $admin->id)->update([
                    'foto_profile' => $fileName
                ]);
            }

            // Ambil data terbaru
            $updatedAdmin = Admin::find($admin->id);

            return response()->json([
                'success' => true,
                'message' => 'Profile berhasil diperbarui!',
                'data' => $updatedAdmin
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    // Update password
    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $admin = Auth::guard('admin')->user();

        // Cek password saat ini
        if (!Hash::check($request->current_password, $admin->password)) {
            return response()->json([
                'success' => false,
                'errors' => ['current_password' => ['Password saat ini salah']]
            ], 422);
        }

        // Update password menggunakan query builder
        Admin::where('id', $admin->id)->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah!'
        ]);
    }

    // Hapus foto profile
    public function removePhoto(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        try {
            if ($admin->foto_profile && File::exists(public_path('admins/' . $admin->foto_profile))) {
                File::delete(public_path('admins/' . $admin->foto_profile));
            }

            // Update menggunakan query builder
            Admin::where('id', $admin->id)->update([
                'foto_profile' => null
            ]);

            // Ambil data terbaru
            $updatedAdmin = Admin::find($admin->id);

            return response()->json([
                'success' => true,
                'message' => 'Foto profile berhasil dihapus!',
                'avatar_url' => $this->getAvatarUrl($updatedAdmin)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    // Helper untuk mendapatkan URL avatar
    private function getAvatarUrl($admin)
    {
        if ($admin->foto_profile) {
            return asset('admins/' . $admin->foto_profile);
        }
        
        // Generate avatar dari nama jika tidak ada foto
        $name = urlencode($admin->nama_lengkap);
        return "https://ui-avatars.com/api/?name={$name}&background=AF69EE&color=fff&size=200";
    }
}