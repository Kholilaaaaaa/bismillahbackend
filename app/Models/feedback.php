<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;

    protected $table = 'feedbacks';

    protected $fillable = [
        'id_pengguna',
        'rating',
        'review',
    ];

    protected $casts = [
        'rating' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relasi ke model Pengguna
     */
    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id');
    }

    /**
     * Validasi rating
     */
    public static function validateRating($value)
    {
        return in_array($value, [1, 2, 3, 4, 5]);
    }

    /**
     * Scope untuk rating tertentu
     */
    public function scopeWithRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Scope untuk pengguna tertentu
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('id_pengguna', $userId);
    }
}