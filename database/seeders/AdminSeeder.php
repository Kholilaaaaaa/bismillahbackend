<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run()
    {
        $indonesianNames = [
            'Budi Santoso', 'Ani Lestari', 'Rudi Hartono', 'Siti Nurhaliza',
            'Agus Salim', 'Dian Puspita', 'Joko Widodo', 'Susi Susanti',
            'Ahmad Fauzi', 'Lina Marlina', 'Eko Prasetyo', 'Rina Kumala',
            'Taufik Hidayat', 'Maya Sari', 'Dedi Supriadi', 'Nina Kusuma',
            'Heri Susanto', 'Fitri Lestari', 'Bayu Aji', 'Rini Wulandari',
            'Adi Pratama', 'Yuni Kartika', 'Andi Kurniawan', 'Lulu Fitriana',
            'Iwan Setiawan', 'Tina Agustina', 'Rizki Ramadhan', 'Dewi Kartika',
            'Fajar Nugraha', 'Siska Amelia', 'Hendra Gunawan', 'Ratih Permata',
            'Yusuf Pratama', 'Ayu Lestari', 'Dian Nugroho', 'Bella Sari',
            'Rian Saputra', 'Sari Indah', 'Bima Sakti', 'Lia Anggraini',
            'Guntur Prakasa', 'Citra Dewi', 'Arief Prasetya', 'Wulan Sari',
            'Zainal Abidin', 'Ratna Sari', 'Maulana Yusuf', 'Intan Permata',
            'Rendi Prasetya', 'Putri Ayu'
        ];

        Admin::create([
            'nama_lengkap' => 'Admin Gym GenZ',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('password'),
            'nomor_telfon' => '+6281234567890',
            'foto_profile' => '',
        ]);

        for ($i = 1; $i < 50; $i++) {
            $name = $indonesianNames[$i] ?? "Admin " . ($i + 1);
            Admin::create([
                'nama_lengkap' => $name,
                'email' => 'admin' . ($i + 1) . '@gmail.com',
                'password' => Hash::make('password'),
                'nomor_telfon' => '+6281' . str_pad($i + 1, 8, '0', STR_PAD_LEFT),
                'foto_profile' => '',
            ]);
        }
    }
}