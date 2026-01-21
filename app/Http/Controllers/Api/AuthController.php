<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengguna;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /**
     * Register new pengguna with Firebase Authentication
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_lengkap' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:penggunas',
            'password' => 'required|string|min:8|confirmed',
            'jenis_kelamin' => 'nullable|in:L,P',
            'tinggi_badan' => 'nullable|numeric|min:50|max:250',
            'berat_badan' => 'nullable|numeric|min:20|max:300',
            'alergi' => 'nullable|string|max:500',
            'golongan_darah' => 'nullable|in:A,B,AB,O',
            'foto_profile' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Create user in Firebase Authentication first
        $firebaseResult = $this->firebaseService->createUser(
            $request->email,
            $request->password,
            $request->nama_lengkap
        );

        if (!$firebaseResult['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat akun Firebase',
                'error' => $firebaseResult['error']
            ], 500);
        }

        // Handle profile photo upload
        $fotoProfileName = null;
        if ($request->hasFile('foto_profile')) {
            $file = $request->file('foto_profile');
            $fotoProfileName = time() . '_' . Str::slug($request->nama_lengkap) . '.' . $file->getClientOriginalExtension();

            // Save to public/profile directory
            $file->move(public_path('profile'), $fotoProfileName);
        }

        // Create user in local database
        try {
            $pengguna = Pengguna::create([
                'firebase_uid' => $firebaseResult['uid'],
                'nama_lengkap' => $request->nama_lengkap,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'jenis_kelamin' => $request->jenis_kelamin ?? 'L',
                'tinggi_badan' => $request->tinggi_badan,
                'berat_badan' => $request->berat_badan,
                'alergi' => $request->alergi,
                'golongan_darah' => $request->golongan_darah,
                'foto_profile' => $fotoProfileName,
            ]);

            // Generate custom token
            $authToken = $pengguna->generateAuthToken();

            // Create Firebase custom token
            $firebaseTokenResult = $this->firebaseService->createCustomToken($firebaseResult['uid']);

            return response()->json([
                'success' => true,
                'message' => 'Registrasi berhasil',
                'data' => [
                    'pengguna' => $pengguna,
                    'token_auth' => $authToken,
                    'firebase_token' => $firebaseTokenResult['success'] ? $firebaseTokenResult['token'] : null,
                    'firebase_uid' => $firebaseResult['uid'],
                    'bmi' => $pengguna->bmi,
                    'bmi_category' => $pengguna->bmi_category,
                ]
            ], 201);
        } catch (\Exception $e) {
            // If database creation fails, delete Firebase user
            $this->firebaseService->deleteUser($firebaseResult['uid']);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data ke database',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login pengguna with email/password
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $pengguna = Pengguna::where('email', $request->email)->first();

        if (!$pengguna || !Hash::check($request->password, $pengguna->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        // Generate custom token
        $authToken = $pengguna->generateAuthToken();

        // Create Firebase custom token if firebase_uid exists
        $firebaseToken = null;
        if ($pengguna->firebase_uid) {
            $firebaseTokenResult = $this->firebaseService->createCustomToken($pengguna->firebase_uid);
            $firebaseToken = $firebaseTokenResult['success'] ? $firebaseTokenResult['token'] : null;
        }

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data' => [
                'pengguna' => $pengguna,
                'token_auth' => $authToken,
                'firebase_token' => $firebaseToken,
                'bmi' => $pengguna->bmi,
                'bmi_category' => $pengguna->bmi_category,
            ]
        ]);
    }

    /**
     * Login with Google (Firebase)
     */
    public function loginWithGoogle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firebase_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify Firebase ID Token
        $verifyResult = $this->firebaseService->verifyIdToken($request->firebase_token);

        if (!$verifyResult['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Token Firebase tidak valid',
                'error' => $verifyResult['error']
            ], 401);
        }

        $firebaseUid = $verifyResult['uid'];
        $email = $verifyResult['email'];

        // Check if user exists in database
        $pengguna = Pengguna::where('firebase_uid', $firebaseUid)
            ->orWhere('email', $email)
            ->first();

        if (!$pengguna) {
            // Get user details from Firebase
            $firebaseUser = $this->firebaseService->getUser($firebaseUid);

            if (!$firebaseUser['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mendapatkan data pengguna dari Firebase'
                ], 500);
            }

            $userData = $firebaseUser['user'];

            // Create new user in database
            $pengguna = Pengguna::create([
                'firebase_uid' => $firebaseUid,
                'nama_lengkap' => $userData->displayName ?? 'User',
                'email' => $email,
                'password' => Hash::make(Str::random(32)), // Random password for Google users
                'jenis_kelamin' => 'L',
            ]);
        } else {
            // Update firebase_uid if not set
            if (!$pengguna->firebase_uid) {
                $pengguna->firebase_uid = $firebaseUid;
                $pengguna->save();
            }
        }

        // Generate custom token
        $authToken = $pengguna->generateAuthToken();

        return response()->json([
            'success' => true,
            'message' => 'Login dengan Google berhasil',
            'data' => [
                'pengguna' => $pengguna,
                'token_auth' => $authToken,
                'firebase_uid' => $firebaseUid,
                'bmi' => $pengguna->bmi,
                'bmi_category' => $pengguna->bmi_category,
            ]
        ]);
    }

    /**
     * Logout pengguna
     */
    public function logout(Request $request)
    {
        $pengguna = $this->getAuthenticatedPengguna($request);

        if (!$pengguna) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid'
            ], 401);
        }

        $pengguna->clearAuthToken();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil'
        ]);
    }

    /**
     * Get authenticated pengguna profile
     */
    public function profile(Request $request)
    {
        $pengguna = $this->getAuthenticatedPengguna($request);

        if (!$pengguna) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'pengguna' => $pengguna,
                'bmi' => $pengguna->bmi,
                'bmi_category' => $pengguna->bmi_category,
            ]
        ]);
    }

    /**
     * Update pengguna profile
     */
    public function updateProfile(Request $request)
    {
        $pengguna = $this->getAuthenticatedPengguna($request);

        if (!$pengguna) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'nama_lengkap' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:penggunas,email,' . $pengguna->id,
            'jenis_kelamin' => 'sometimes|in:L,P',
            'tinggi_badan' => 'nullable|numeric|min:50|max:250',
            'berat_badan' => 'nullable|numeric|min:20|max:300',
            'alergi' => 'nullable|string|max:500',
            'golongan_darah' => 'nullable|in:A,B,AB,O',
            'foto_profile' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Handle profile photo upload
        if ($request->hasFile('foto_profile')) {
            // Delete old photo if exists
            if ($pengguna->foto_profile && file_exists(public_path('profile/' . $pengguna->foto_profile))) {
                unlink(public_path('profile/' . $pengguna->foto_profile));
            }

            $file = $request->file('foto_profile');
            $fotoProfileName = time() . '_' . Str::slug($request->nama_lengkap ?? $pengguna->nama_lengkap) . '.' . $file->getClientOriginalExtension();

            // Save to public/profile directory
            $file->move(public_path('profile'), $fotoProfileName);
            $pengguna->foto_profile = $fotoProfileName;
        }

        // Update fields
        $pengguna->fill($request->only([
            'nama_lengkap',
            'email',
            'jenis_kelamin',
            'tinggi_badan',
            'berat_badan',
            'alergi',
            'golongan_darah',
        ]));

        $pengguna->save();

        // Update Firebase user if firebase_uid exists
        if ($pengguna->firebase_uid) {
            $updateData = [];
            
            if ($request->has('nama_lengkap')) {
                $updateData['displayName'] = $request->nama_lengkap;
            }
            
            if ($request->has('email')) {
                $updateData['email'] = $request->email;
            }

            if (!empty($updateData)) {
                $this->firebaseService->updateUser($pengguna->firebase_uid, $updateData);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile berhasil diperbarui',
            'data' => [
                'pengguna' => $pengguna,
                'bmi' => $pengguna->bmi,
                'bmi_category' => $pengguna->bmi_category,
            ]
        ]);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request)
    {
        $pengguna = $this->getAuthenticatedPengguna($request);

        if (!$pengguna) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Check current password
        if (!Hash::check($request->current_password, $pengguna->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password saat ini salah'
            ], 401);
        }

        // Update password in database
        $pengguna->password = Hash::make($request->new_password);
        $pengguna->save();

        // Update password in Firebase if firebase_uid exists
        if ($pengguna->firebase_uid) {
            $this->firebaseService->updateUser($pengguna->firebase_uid, [
                'password' => $request->new_password
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah'
        ]);
    }

    /**
     * Helper method to get authenticated pengguna from token
     */
    private function getAuthenticatedPengguna(Request $request)
    {
        $token = $request->header('Authorization');

        if (!$token) {
            return null;
        }

        // Remove "Bearer " prefix if present
        $token = str_replace('Bearer ', '', $token);

        return Pengguna::validateAuthToken($token);
    }
}