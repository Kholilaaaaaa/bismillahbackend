<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workout extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_workout',
        'deskripsi',
        'equipment',
        'kategori',
        'exercises',
        'status',
        'jadwal_workout_id',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    /**
     * Get the jadwalWorkout that owns the workout.
     */
    public function jadwalWorkout()
    {
        return $this->belongsTo(JadwalWorkout::class);
    }
}