<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'question',
        'answer',
        'model_type',
        'confidence'
    ];

    protected $casts = [
        'confidence' => 'float'
    ];
}