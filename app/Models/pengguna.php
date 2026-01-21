<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;

class Pengguna extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'penggunas';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nama_lengkap',
        'email',
        'password',
        'jenis_kelamin',
        'tinggi_badan',
        'berat_badan',
        'alergi',
        'golongan_darah',
        'foto_profile',
        'token_pengguna',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'tinggi_badan' => 'decimal:2',
        'berat_badan' => 'decimal:2',
    ];

    /**
     * Generate custom token for authentication
     */
    public function generateAuthToken()
    {
        $token = 'token_auth_' . bin2hex(random_bytes(32)) . '_' . time();
        $this->token_pengguna = hash('sha256', $token);
        $this->save();
        
        return $token;
    }

    /**
     * Validate custom token
     */
    public static function validateAuthToken($token)
    {
        if (empty($token)) {
            return null;
        }

        return self::where('token_pengguna', hash('sha256', $token))->first();
    }

    /**
     * Clear custom token on logout
     */
    public function clearAuthToken()
    {
        $this->token_pengguna = null;
        $this->save();
    }

    /**
     * Get the URL for the profile photo
     */
    public function getFotoProfileUrlAttribute()
    {
        if (!$this->foto_profile) {
            return null;
        }
        
        return url('/profile/' . $this->foto_profile);
    }

    /**
     * Calculate BMI
     */
    public function getBmiAttribute()
    {
        if (!$this->tinggi_badan || !$this->berat_badan) {
            return null;
        }
        
        // Convert cm to m
        $heightInMeters = $this->tinggi_badan / 100;
        $bmi = $this->berat_badan / ($heightInMeters * $heightInMeters);
        
        return round($bmi, 2);
    }

    /**
     * Get BMI category
     */
    public function getBmiCategoryAttribute()
    {
        $bmi = $this->bmi;
        
        if (!$bmi) {
            return null;
        }
        
        if ($bmi < 18.5) return 'Underweight';
        if ($bmi < 25) return 'Normal';
        if ($bmi < 30) return 'Overweight';
        return 'Obesity';
    }

    /**
     * Append calculated attributes to array
     */
    protected $appends = ['foto_profile_url', 'bmi', 'bmi_category'];

    /**
     * Relasi ke model Feedback
     */
    public function feedbacks()
    {
        return $this->hasMany(Feedback::class, 'id_pengguna', 'id');
    }
}