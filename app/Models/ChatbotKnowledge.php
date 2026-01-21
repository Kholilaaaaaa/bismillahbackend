<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotKnowledge extends Model
{
    use HasFactory;

    /**
     * Nama tabel (karena bukan bentuk jamak default Laravel)
     */
    protected $table = 'chatbot_knowledge';

    /**
     * Kolom yang boleh diisi mass assignment
     */
    protected $fillable = [
        'question',
        'answer',
        'source',
    ];
}
