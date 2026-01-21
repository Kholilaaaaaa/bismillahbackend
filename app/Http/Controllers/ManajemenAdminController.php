<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;

class ManajemenAdminController extends Controller
{
    /**
     * Display a listing of the admins.
     */
    public function index()
    {
        $admins = Admin::orderBy('created_at', 'desc')->paginate(10); // Ubah ini untuk pagination
        return view('admins.manajemen-admin.index', compact('admins'));
    }

    /**
     * Store a newly created admin in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_lengkap' => 'required|string|max:255',
            'email' => 'required|email|unique:admins,email',
            'nomor_telfon' => 'required|string|max:15',
            'password' => 'required|min:6|confirmed',
            'foto_profile' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = [
                'nama_lengkap' => $request->nama_lengkap,
                'email' => $request->email,
                'nomor_telfon' => $request->nomor_telfon,
                'password' => Hash::make($request->password),
            ];

            // Handle upload foto profile
            if ($request->hasFile('foto_profile')) {
                $file = $request->file('foto_profile');
                $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                
                // Simpan di public/admins
                $file->move(public_path('admins'), $fileName);
                $data['foto_profile'] = $fileName;
            }

            $admin = Admin::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Admin berhasil ditambahkan!',
                'data' => $admin
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified admin.
     */
    public function show($id)
    {
        $admin = Admin::findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $admin
        ]);
    }

    /**
     * Update the specified admin in storage.
     */
    public function update(Request $request, $id)
    {
        $admin = Admin::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'nama_lengkap' => 'required|string|max:255',
            'email' => 'required|email|unique:admins,email,' . $id,
            'nomor_telfon' => 'required|string|max:15',
            'password' => 'nullable|min:6|confirmed',
            'foto_profile' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = [
                'nama_lengkap' => $request->nama_lengkap,
                'email' => $request->email,
                'nomor_telfon' => $request->nomor_telfon,
            ];

            // Update password jika diisi
            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }

            // Handle upload foto profile baru
            if ($request->hasFile('foto_profile')) {
                // Hapus foto lama jika ada
                if ($admin->foto_profile && File::exists(public_path('admins/' . $admin->foto_profile))) {
                    File::delete(public_path('admins/' . $admin->foto_profile));
                }

                $file = $request->file('foto_profile');
                $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                
                // Simpan di public/admins
                $file->move(public_path('admins'), $fileName);
                $data['foto_profile'] = $fileName;
            }

            $admin->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Data admin berhasil diperbarui!',
                'data' => $admin
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified admin from storage.
     */
    public function destroy($id)
    {
        try {
            $admin = Admin::findOrFail($id);
            
            // Hapus foto profile jika ada
            if ($admin->foto_profile && File::exists(public_path('admins/' . $admin->foto_profile))) {
                File::delete(public_path('admins/' . $admin->foto_profile));
            }
            
            // Jangan hapus admin jika hanya tersisa satu
            $totalAdmins = Admin::count();
            if ($totalAdmins <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat menghapus admin terakhir!'
                ], 422);
            }
            
            $admin->delete();

            return response()->json([
                'success' => true,
                'message' => 'Admin berhasil dihapus!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove photo from admin.
     */
    public function removePhoto($id)
    {
        try {
            $admin = Admin::findOrFail($id);

            if ($admin->foto_profile && File::exists(public_path('admins/' . $admin->foto_profile))) {
                File::delete(public_path('admins/' . $admin->foto_profile));
            }

            $admin->update(['foto_profile' => null]);

            return response()->json([
                'success' => true,
                'message' => 'Foto profile berhasil dihapus!'
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
    private function getAvatarUrl($admin)
    {
        if ($admin->foto_profile) {
            return asset('admins/' . $admin->foto_profile);
        }
        
        $name = urlencode($admin->nama_lengkap);
        return "https://ui-avatars.com/api/?name={$name}&background=AF69EE&color=fff&size=200";
    }
}