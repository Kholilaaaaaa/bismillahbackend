<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailWorkout extends Model
{
    use HasFactory;

    protected $table = 'detail_workouts';
    
    // ✅ PRIMARY KEY SESUAI MIGRATION
    protected $primaryKey = 'id_detail_workout';
    
    // ✅ AUTO-INCREMENT INTEGER
    public $incrementing = true;
    protected $keyType = 'int';
    
    protected $fillable = [
        'id_workout',
        'label_ml',
        'repetisi',
        'set',
        'durasi_detik',
        'catatan',
        'urutan'
    ];

    public $timestamps = true;

    /**
     * Relasi ke Workout
     */
    public function workout()
    {
        return $this->belongsTo(Workout::class, 'id_workout', 'id');
    }
}