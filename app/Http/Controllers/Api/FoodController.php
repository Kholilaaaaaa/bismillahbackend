<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Food;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FoodController extends Controller
{
    /**
     * Get 5 daily food recommendations based on meal categories
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFoods(Request $request)
    {
        try {
            $categories = ['pagi', 'siang', 'sore', 'malam'];
            $today = Carbon::now()->toDateString();
            $foods = [];
            $daySeed = $this->getDaySeed($today);
            foreach ($categories as $category) {
                $food = $this->getRandomFoodByCategory($category, $daySeed);
                if ($food) {
                    $foods[] = $food;
                }
            }
            
            if (count($foods) < 4) {
                $additionalFood = $this->getRandomFoodFromAnyCategory($daySeed, $categories, array_column($foods, 'id'));
                if ($additionalFood) {
                    $foods[] = $additionalFood;
                }
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'Daily food recommendations retrieved successfully',
                'data' => [
                    'foods' => $foods,
                    'date' => $today,
                    'total' => count($foods)
                ]
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve food recommendations',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Generate a consistent seed based on date
     */
    private function getDaySeed($date)
    {
        return crc32($date);
    }
    
    /**
     * Get one random food from specific category using day seed
     */
    private function getRandomFoodByCategory($category, $seed)
    {
        $total = Food::where('kategori_makanan', $category)->count();
        
        if ($total === 0) {
            return null;
        }
        
        $index = abs($seed % $total);
        
        return Food::where('kategori_makanan', $category)
            ->orderBy('id') // Ensure consistent ordering
            ->skip($index)
            ->take(1)
            ->first();
    }
    
    /**
     * Get random food from any category (excluding already selected)
     */
    private function getRandomFoodFromAnyCategory($seed, $categories, $excludeIds = [])
    {
        $query = Food::whereIn('kategori_makanan', $categories);
        
        if (!empty($excludeIds)) {
            $query->whereNotIn('id', $excludeIds);
        }
        
        $total = $query->count();
        
        if ($total === 0) {
            return null;
        }
        
        $variationSeed = $seed + 1234;
        $index = abs($variationSeed % $total);
        
        return $query->orderBy('id')
            ->skip($index)
            ->take(1)
            ->first();
    }
}