<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JadwalWorkout extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_jadwal',
        'kategori_jadwal',
        'tanggal',
        'jam',
        'durasi_workout',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    /**
     * Get the workout associated with the jadwal.
     */
    public function workout()
    {
        return $this->hasOne(Workout::class, 'jadwal_workout_id', 'id');
    }
}