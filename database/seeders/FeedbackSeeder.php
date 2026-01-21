<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Feedback;
use App\Models\Pengguna;
use Illuminate\Support\Facades\DB;

class FeedbackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Review positif (AI Personal Trainer App)
        $positiveReviews = [
            "Aplikasi AI Personal Trainer ini sangat membantu! Deteksi gerakan realtime-nya akurat dan langsung mengoreksi postur saat latihan. Saya jadi lebih percaya diri workout sendiri di rumah tanpa takut salah gerakan.",

            "Fitur rekomendasi makanan sangat berguna! Menu yang disarankan sesuai dengan target saya dan mudah diikuti. Ditambah jadwal latihan yang otomatis tersusun rapi, benar-benar seperti punya personal trainer pribadi di HP.",

            "Sebagai pemula gym, aplikasi ini sangat ramah pengguna. AI menjelaskan setiap latihan dengan jelas dan deteksi gerakannya membantu menghindari cedera. Progress latihan saya terasa jauh lebih cepat.",

            "Aplikasi ini keren banget! Deteksi realtime-nya responsif, tidak delay, dan cukup akurat meskipun hanya pakai kamera HP. Program latihannya juga variatif dan tidak membosankan.",

            "Jadwal latihan otomatis sangat membantu manajemen waktu saya. Aplikasi ini mengingatkan jadwal workout dan memberikan alternatif latihan kalau saya sedang sibuk. Sangat fleksibel dan pintar!",

            "Rekomendasi makanannya realistis dan mudah diterapkan. Tidak ribet dan tetap sesuai dengan kebutuhan kalori harian. Cocok untuk yang ingin hidup sehat tanpa harus hitung manual.",

            "Saya suka fitur analisis progresnya. Grafik perkembangan latihan dan berat badan sangat jelas. AI-nya terasa benar-benar memahami performa saya dari waktu ke waktu.",

            "Aplikasi ini cocok untuk workout di rumah maupun di gym. Deteksi gerakannya tetap berjalan dengan baik. Rasanya seperti dilatih langsung oleh personal trainer profesional.",

            "Interface aplikasinya modern dan mudah dipahami. Semua fitur dari latihan, jadwal, sampai makanan terintegrasi dengan baik. Sangat worth it digunakan jangka panjang.",

            "Customer support responsif dan update aplikasi rutin. Setiap update terasa meningkatkan akurasi AI dan menambah variasi latihan. Sangat recommended untuk fitness enthusiast!"
        ];

        // Review negatif
        $negativeReviews = [
            "Deteksi gerakan realtime masih kurang akurat di beberapa latihan tertentu. Kadang repetisi tidak terhitung meskipun gerakan sudah benar, cukup mengganggu sesi workout.",

            "Aplikasi cukup berat di HP spek menengah. Saat menggunakan kamera untuk deteksi gerakan, sering terjadi lag dan frame drop sehingga pengalaman latihan kurang maksimal.",

            "Rekomendasi makanan kurang variatif. Menu yang ditampilkan sering berulang dan belum sepenuhnya menyesuaikan preferensi makanan lokal.",

            "Jadwal latihan kadang tidak sinkron dengan notifikasi. Beberapa kali pengingat workout tidak muncul sesuai waktu yang sudah diatur.",

            "Fitur AI terasa kurang responsif di kondisi pencahayaan rendah. Deteksi gerakan sering gagal jika ruangan kurang terang.",

            "Beberapa latihan lanjutan kurang penjelasan detail. Untuk user advanced, instruksi dari AI masih terasa terlalu basic.",

            "Aplikasi sering force close saat berpindah dari menu latihan ke analisis progres. Cukup mengganggu, terutama saat sesi latihan sedang berjalan.",

            "Harga premium terasa agak mahal jika dibandingkan dengan fitur yang didapat saat ini. Masih perlu peningkatan agar lebih sepadan.",

            "Konsumsi baterai cukup boros karena kamera menyala terus saat latihan. Perlu optimasi agar lebih hemat daya.",

            "Integrasi dengan perangkat wearable masih terbatas. Padahal akan sangat membantu jika bisa sinkron dengan smartwatch atau fitness band."
        ];

        // Review netral / campuran
        $neutralReviews = [
            "Aplikasi ini cukup membantu untuk latihan mandiri. Deteksi gerakan sudah lumayan akurat, tapi masih bisa ditingkatkan di beberapa jenis latihan.",

            "Fitur jadwal dan rekomendasi makanan berguna, meskipun belum sepenuhnya fleksibel. Cocok untuk user yang ingin panduan basic sampai menengah.",

            "AI Personal Trainer ini menarik dan inovatif. Namun performa aplikasi sangat tergantung pada spesifikasi HP dan kondisi pencahayaan.",

            "Latihan cukup variatif dan terstruktur. Untuk pemula sangat membantu, tapi untuk level lanjut masih perlu pengembangan lebih lanjut.",

            "Secara keseluruhan aplikasi ini fungsional. Tidak sempurna, tapi sudah cukup membantu menjaga konsistensi latihan dan pola makan."
        ];

        // Ambil semua pengguna
        $penggunas = Pengguna::all();
        
        if ($penggunas->isEmpty()) {
            $this->command->info('Pengguna masih kosong. Jalankan PenggunaSeeder terlebih dahulu.');
            $this->call(PenggunaSeeder::class);
            $penggunas = Pengguna::all();
        }

        $feedbackData = [];
        $totalPengguna = count($penggunas);
        $jumlahMemberiFeedback = 350;

        $penggunaIds = $penggunas->pluck('id')->toArray();
        shuffle($penggunaIds);
        $selectedPenggunaIds = array_slice($penggunaIds, 0, $jumlahMemberiFeedback);

        $ratingDistribution = [
            5 => 0.40,
            4 => 0.30,
            3 => 0.15,
            2 => 0.10,
            1 => 0.05,
        ];
        
        $i = 0;
        foreach ($selectedPenggunaIds as $penggunaId) {
            $rand = mt_rand() / mt_getrandmax();
            $cumulative = 0;
            $rating = 3;
            
            foreach ($ratingDistribution as $rt => $prob) {
                $cumulative += $prob;
                if ($rand <= $cumulative) {
                    $rating = $rt;
                    break;
                }
            }

            if ($rating >= 4) {
                $review = $positiveReviews[array_rand($positiveReviews)];
                if ($rating == 4) {
                    $kritikKecil = [
                        " Hanya saja, performa AI masih bisa ditingkatkan.",
                        " Akan lebih baik jika variasi latihan ditambah.",
                        " Notifikasi jadwal kadang sedikit terlambat.",
                        " Konsumsi baterai masih agak tinggi.",
                        " UI sudah bagus, tapi masih bisa dibuat lebih ringan."
                    ];
                    $review .= $kritikKecil[array_rand($kritikKecil)];
                }
            } elseif ($rating == 3) {
                $review = $neutralReviews[array_rand($neutralReviews)];
            } else {
                $review = $negativeReviews[array_rand($negativeReviews)];
                if ($rating == 1) {
                    $tambahanNegatif = [
                        " Sangat mengecewakan untuk penggunaan jangka panjang.",
                        " Harapan saya terlalu tinggi dibandingkan hasilnya.",
                        " Perlu banyak perbaikan sebelum layak dipakai serius.",
                        " Pengalaman latihan jadi kurang nyaman.",
                        " Saya mempertimbangkan untuk berhenti menggunakan aplikasi ini."
                    ];
                    $review .= $tambahanNegatif[array_rand($tambahanNegatif)];
                }
            }

            $personalTouch = [
                " Sebagai pengguna selama " . rand(1, 12) . " bulan, saya merasa...",
                " Dibanding aplikasi fitness lain, menurut saya...",
                " Dengan target " . (rand(0,1) ? "weight loss" : "muscle building") . "...",
                " Untuk latihan di rumah, pengalaman saya...",
                " Sebagai pengguna mobile, menurut saya..."
            ];

            if (rand(0, 1)) {
                $pos = strpos($review, '.');
                if ($pos !== false) {
                    $review = substr_replace($review, $personalTouch[array_rand($personalTouch)], $pos + 1, 0);
                }
            }

            $feedbackData[] = [
                'id_pengguna' => $penggunaId,
                'rating' => $rating,
                'review' => $review,
                'created_at' => now()->subDays(rand(0, 180))->addHours(rand(0, 23))->addMinutes(rand(0, 59)),
                'updated_at' => now(),
            ];

            $i++;

            if ($i % 100 == 0 || $i == $jumlahMemberiFeedback) {
                Feedback::insert($feedbackData);
                $feedbackData = [];
                $this->command->info("Memasukkan {$i} feedback...");
            }
        }

        $this->command->info("Berhasil memasukkan {$jumlahMemberiFeedback} feedback dari {$totalPengguna} pengguna.");
    }
}
