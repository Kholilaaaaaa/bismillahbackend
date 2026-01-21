<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Food extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'foods';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nama_makanan',
        'deskripsi',
        'kategori_makanan',
        'kalori',
        'protein',
        'karbohidrat',
        'lemak'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'kalori' => 'integer',
        'protein' => 'decimal:2',
        'karbohidrat' => 'decimal:2',
        'lemak' => 'decimal:2',
    ];

    /**
     * Scope untuk filter berdasarkan kategori
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('kategori_makanan', $category);
    }

    /**
     * Scope untuk makanan rendah kalori
     */
    public function scopeLowCalorie($query, $maxCalories = 300)
    {
        return $query->where('kalori', '<=', $maxCalories);
    }

    /**
     * Scope untuk makanan tinggi protein
     */
    public function scopeHighProtein($query, $minProtein = 20)
    {
        return $query->where('protein', '>=', $minProtein);
    }
}