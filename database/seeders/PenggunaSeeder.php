<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pengguna;
use Illuminate\Support\Facades\Hash;

class PenggunaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $namaLaki = [
            'Ahmad', 'Budi', 'Cahyo', 'Dedi', 'Eko', 'Fajar', 'Gunawan', 'Hadi', 'Irfan', 'Joko',
            'Kurniawan', 'Lukman', 'Mulyadi', 'Nugroho', 'Oktavian', 'Prasetyo', 'Qomar', 'Rahmat', 'Surya', 'Teguh',
            'Umar', 'Viktor', 'Wahyu', 'Yoga', 'Zainal', 'Adi', 'Bayu', 'Candra', 'Dwi', 'Eka',
            'Fadli', 'Gilang', 'Hendra', 'Iwan', 'Jaya', 'Kusuma', 'Lintang', 'Maulana', 'Nanda', 'Oki',
            'Prabowo', 'Rizki', 'Satrio', 'Tri', 'Udin', 'Wawan', 'Yuda', 'Zaki', 'Arif', 'Bagus',
            'Cepi', 'Darma', 'Eri', 'Farhan', 'Guntur', 'Heri', 'Ibrahim', 'Juli', 'Kiki', 'Laksana',
            'Maman', 'Nopal', 'Opik', 'Pandu', 'Rendi', 'Sandi', 'Taufik', 'Ucok', 'Wira', 'Yanto',
            'Zulfikar', 'Ade', 'Bima', 'Catur', 'Dani', 'Evan', 'Fikri', 'Gideon', 'Hilman', 'Indra',
            'Jefri', 'Koko', 'Luthfi', 'Miko', 'Naufal', 'Omar', 'Pandji', 'Rafi', 'Samsul', 'Toni',
            'Usman', 'Vino', 'Wendy', 'Yosef', 'Zidane'
        ];

        $namaPerempuan = [
            'Ayu', 'Bunga', 'Citra', 'Dewi', 'Eka', 'Fitri', 'Gita', 'Hani', 'Intan', 'Juli',
            'Kartika', 'Lestari', 'Maya', 'Nina', 'Oktavia', 'Putri', 'Queenie', 'Rani', 'Sari', 'Tari',
            'Umi', 'Vina', 'Wati', 'Yuni', 'Zahra', 'Ani', 'Bella', 'Cinta', 'Diana', 'Elisa',
            'Fani', 'Gina', 'Hesti', 'Indah', 'Jessy', 'Kiki', 'Laila', 'Mega', 'Nadia', 'Olga',
            'Puspita', 'Rina', 'Siska', 'Tiara', 'Ulya', 'Vera', 'Winda', 'Yulia', 'Zaskia', 'Amelia',
            'Bulan', 'Cici', 'Dinda', 'Eva', 'Fira', 'Gisela', 'Hilda', 'Irma', 'Jihan', 'Kartini',
            'Linda', 'Mila', 'Nova', 'Ophelia', 'Pratiwi', 'Rika', 'Susi', 'Tika', 'Umiyati', 'Viona',
            'Wulan', 'Yani', 'Zelda', 'Aisyah', 'Betari', 'Cantika', 'Dara', 'Elli', 'Fiona', 'Grace',
            'Hana', 'Ira', 'Jeni', 'Kirana', 'Lina', 'Mira', 'Nurul', 'Olivia', 'Pia', 'Rara',
            'Sephia', 'Tasya', 'Utari', 'Vania', 'Widya', 'Yuliana', 'Zahira'
        ];

        $marga = [
            'Santoso', 'Wijaya', 'Pratama', 'Kusuma', 'Setiawan', 'Hidayat', 'Saputra', 'Nugroho', 'Putra', 'Wibowo',
            'Darmawan', 'Susanto', 'Siregar', 'Gunawan', 'Halim', 'Irawan', 'Jaya', 'Kurniawan', 'Lestari', 'Maulana',
            'Nasution', 'Oktaviani', 'Purnama', 'Rahman', 'Sihombing', 'Tanuwijaya', 'Utomo', 'Wahyudi', 'Yulianto', 'Zulkarnain',
            'Abdullah', 'Bakar', 'Chandra', 'Damanik', 'Efendi', 'Firmansyah', 'Ginting', 'Harahap', 'Ibrahim', 'Jamal',
            'Kadir', 'Lubis', 'Muhammad', 'Nababan', 'Oktaviana', 'Pangestu', 'Qodir', 'Rohman', 'Sembiring', 'Tampubolon',
            'Umar', 'Wijayanto', 'Yusuf', 'Zainuddin', 'Amin', 'Baskara', 'Cahaya', 'Darmaji', 'Eko', 'Fernando',
            'Ginanjar', 'Hakim', 'Ismail', 'Jumadi', 'Kusnadi', 'Laksana', 'Mulyono', 'Natalia', 'Oktavian', 'Prasetya',
            'Rachman', 'Sasongko', 'Tanjung', 'Ubaidillah', 'Wicaksono', 'Yunus', 'Zakaria', 'Agustina', 'Budiman', 'Cahyadi',
            'Darsono', 'Elvira', 'Fauzi', 'Gunardi', 'Himawan', 'Iskandar', 'Junaedi', 'Kartono', 'Lesmana', 'Mandala'
        ];

        $golonganDarah = ['A', 'B', 'AB', 'O'];
        $alergiOptions = [
            null,
            'Kacang',
            'Udang',
            'Susu sapi',
            'Telur',
            'Ikan laut',
            'Debu',
            'Serbuk sari',
            'Lateks',
            'Obat tertentu'
        ];

        $penggunaData = [];

        for ($i = 1; $i <= 500; $i++) {
            $jenisKelamin = rand(0, 1) ? 'L' : 'P';
            
            if ($jenisKelamin === 'L') {
                $namaDepan = $namaLaki[array_rand($namaLaki)];
            } else {
                $namaDepan = $namaPerempuan[array_rand($namaPerempuan)];
            }
            
            $namaBelakang = $marga[array_rand($marga)];
            $namaLengkap = $namaDepan . ' ' . $namaBelakang;
            
            // Email dibuat unik
            $email = strtolower(str_replace(' ', '.', $namaDepan)) . '.' . strtolower(str_replace(' ', '.', $namaBelakang)) . $i . '@gmail.com';

            if ($jenisKelamin === 'L') {
                $tinggiBadan = rand(160, 190); // cm untuk laki-laki
                $beratBadan = rand(55, 90); // kg untuk laki-laki
            } else {
                $tinggiBadan = rand(150, 175); // cm untuk perempuan
                $beratBadan = rand(45, 75); // kg untuk perempuan
            }
            
            if (rand(0, 10) === 0) {
                $tinggiBadan = null;
                $beratBadan = null;
            }
            
            $penggunaData[] = [
                'nama_lengkap' => $namaLengkap,
                'email' => $email,
                'password' => Hash::make('password'),
                'jenis_kelamin' => $jenisKelamin,
                'tinggi_badan' => $tinggiBadan,
                'berat_badan' => $beratBadan,
                'alergi' => $alergiOptions[array_rand($alergiOptions)],
                'golongan_darah' => $golonganDarah[array_rand($golonganDarah)],
                'foto_profile' => '',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($penggunaData, 100) as $chunk) {
            Pengguna::insert($chunk);
        }
    }
}