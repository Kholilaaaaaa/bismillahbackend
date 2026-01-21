<?php

namespace App\Http\Controllers;

use App\Models\Food;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ManajemenFoodController extends Controller
{
    /**
     * Display a listing of the foods.
     */
    public function index()
    {
        $foods = Food::orderBy('created_at', 'desc')->paginate(10);
        $categories = ['pagi', 'siang', 'sore', 'malam'];
        
        return view('admins.manajemen-foodplan.index', compact('foods', 'categories'));
    }

    /**
     * Store a newly created food in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_makanan' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'kategori_makanan' => 'required|in:pagi,siang,sore,malam',
            'kalori' => 'required|integer|min:0',
            'protein' => 'required|numeric|min:0',
            'karbohidrat' => 'required|numeric|min:0',
            'lemak' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $food = Food::create([
                'nama_makanan' => $request->nama_makanan,
                'deskripsi' => $request->deskripsi,
                'kategori_makanan' => $request->kategori_makanan,
                'kalori' => $request->kalori,
                'protein' => $request->protein,
                'karbohidrat' => $request->karbohidrat,
                'lemak' => $request->lemak,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Makanan berhasil ditambahkan!',
                'data' => $food
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified food.
     */
    public function show($id)
    {
        $food = Food::findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $food
        ]);
    }

    /**
     * Update the specified food in storage.
     */
    public function update(Request $request, $id)
    {
        $food = Food::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'nama_makanan' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'kategori_makanan' => 'required|in:pagi,siang,sore,malam',
            'kalori' => 'required|integer|min:0',
            'protein' => 'required|numeric|min:0',
            'karbohidrat' => 'required|numeric|min:0',
            'lemak' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $food->update([
                'nama_makanan' => $request->nama_makanan,
                'deskripsi' => $request->deskripsi,
                'kategori_makanan' => $request->kategori_makanan,
                'kalori' => $request->kalori,
                'protein' => $request->protein,
                'karbohidrat' => $request->karbohidrat,
                'lemak' => $request->lemak,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Data makanan berhasil diperbarui!',
                'data' => $food
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified food from storage.
     */
    public function destroy($id)
    {
        try {
            $food = Food::findOrFail($id);
            $food->delete();

            return response()->json([
                'success' => true,
                'message' => 'Makanan berhasil dihapus!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}