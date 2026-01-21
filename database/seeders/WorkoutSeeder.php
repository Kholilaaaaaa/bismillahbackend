<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JadwalWorkout;
use App\Models\Workout;
use Carbon\Carbon;

class WorkoutSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $workoutData = [
            [
                'jadwal' => [
                    'nama_jadwal' => 'Full Body Strength',
                    'kategori_jadwal' => 'Strength Training',
                    'tanggal' => Carbon::today()->format('Y-m-d'),
                    'jam' => '07:00',
                    'durasi_workout' => '60 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Push Up',
                    'deskripsi' => 'Latihan dasar untuk menguatkan dada, bahu, dan trisep menggunakan berat badan sendiri.',
                    'equipment' => 'Tidak ada (bodyweight)',
                    'kategori' => 'Without Equipment',
                    'exercises' => 6,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'HIIT Cardio',
                    'kategori_jadwal' => 'Cardio',
                    'tanggal' => Carbon::today()->format('Y-m-d'),
                    'jam' => '17:30',
                    'durasi_workout' => '45 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Shoulder Press',
                    'deskripsi' => 'Latihan untuk menguatkan otot bahu dengan menekan beban ke atas kepala.',
                    'equipment' => 'Dumbbells, Barbell, Shoulder Press Machine',
                    'kategori' => 'With Equipment',
                    'exercises' => 8,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Full Body Strength',
                    'kategori_jadwal' => 'Strength Training',
                    'tanggal' => Carbon::today()->addDays(1)->format('Y-m-d'),
                    'jam' => '07:00',
                    'durasi_workout' => '60 menit',
                ],
                'workout' => [
                    'nama_workout' => 'T Bar Row',
                    'deskripsi' => 'Latihan menarik untuk menguatkan otot punggung tengah dan lats.',
                    'equipment' => 'T-bar Row Machine, Barbell, Weight Plates',
                    'kategori' => 'With Equipment',
                    'exercises' => 6,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'HIIT Cardio',
                    'kategori_jadwal' => 'Cardio',
                    'tanggal' => Carbon::today()->addDays(1)->format('Y-m-d'),
                    'jam' => '17:30',
                    'durasi_workout' => '45 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Push Up',
                    'deskripsi' => 'Variasi push up untuk membangun kekuatan dada dan trisep tanpa alat.',
                    'equipment' => 'Tidak ada',
                    'kategori' => 'Without Equipment',
                    'exercises' => 8,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Upper Body Focus',
                    'kategori_jadwal' => 'Muscle Building',
                    'tanggal' => Carbon::today()->addDays(2)->format('Y-m-d'),
                    'jam' => '08:00',
                    'durasi_workout' => '75 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Shoulder Press',
                    'deskripsi' => 'Latihan shoulder press dengan barbell untuk mengembangkan kekuatan bahu.',
                    'equipment' => 'Barbell, Weight Plates, Bench',
                    'kategori' => 'With Equipment',
                    'exercises' => 7,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Yoga Morning',
                    'kategori_jadwal' => 'Flexibility',
                    'tanggal' => Carbon::today()->addDays(2)->format('Y-m-d'),
                    'jam' => '06:30',
                    'durasi_workout' => '60 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Push Up',
                    'deskripsi' => 'Push up dengan fokus pada kontrol gerakan dan stabilitas tubuh.',
                    'equipment' => 'Yoga Mat',
                    'kategori' => 'Without Equipment',
                    'exercises' => 12,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Leg Day Intensive',
                    'kategori_jadwal' => 'Lower Body',
                    'tanggal' => Carbon::today()->addDays(3)->format('Y-m-d'),
                    'jam' => '09:00',
                    'durasi_workout' => '80 menit',
                ],
                'workout' => [
                    'nama_workout' => 'T Bar Row',
                    'deskripsi' => 'Latihan t-bar row untuk punggung yang kuat dan postur yang baik.',
                    'equipment' => 'T-bar Machine, Grip Handle',
                    'kategori' => 'With Equipment',
                    'exercises' => 8,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Bodyweight Challenge',
                    'kategori_jadwal' => 'Calisthenics',
                    'tanggal' => Carbon::today()->addDays(3)->format('Y-m-d'),
                    'jam' => '18:00',
                    'durasi_workout' => '50 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Push Up',
                    'deskripsi' => 'Berbagai variasi push up untuk melatih seluruh tubuh tanpa alat.',
                    'equipment' => 'Tidak ada',
                    'kategori' => 'Without Equipment',
                    'exercises' => 10,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Back & Biceps',
                    'kategori_jadwal' => 'Muscle Building',
                    'tanggal' => Carbon::today()->addDays(4)->format('Y-m-d'),
                    'jam' => '10:00',
                    'durasi_workout' => '70 menit',
                ],
                'workout' => [
                    'nama_workout' => 'T Bar Row',
                    'deskripsi' => 'Latihan t-bar row fokus pada kontraksi otot punggung tengah.',
                    'equipment' => 'T-bar Row Station, Chest Support',
                    'kategori' => 'With Equipment',
                    'exercises' => 7,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Morning Run',
                    'kategori_jadwal' => 'Cardio',
                    'tanggal' => Carbon::today()->addDays(4)->format('Y-m-d'),
                    'jam' => '06:00',
                    'durasi_workout' => '40 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Shoulder Press',
                    'deskripsi' => 'Shoulder press dengan dumbbell untuk kekuatan dan stabilitas bahu.',
                    'equipment' => 'Dumbbells, Adjustable Bench',
                    'kategori' => 'With Equipment',
                    'exercises' => 5,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Core & Abs',
                    'kategori_jadwal' => 'Core Training',
                    'tanggal' => Carbon::today()->addDays(5)->format('Y-m-d'),
                    'jam' => '07:30',
                    'durasi_workout' => '45 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Push Up',
                    'deskripsi' => 'Push up dengan variasi untuk melatih kekuatan inti tubuh.',
                    'equipment' => 'Exercise Mat',
                    'kategori' => 'Without Equipment',
                    'exercises' => 15,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Circuit Training',
                    'kategori_jadwal' => 'Mixed',
                    'tanggal' => Carbon::today()->addDays(5)->format('Y-m-d'),
                    'jam' => '17:00',
                    'durasi_workout' => '55 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Shoulder Press',
                    'deskripsi' => 'Shoulder press sebagai bagian dari circuit training untuk tubuh atas.',
                    'equipment' => 'Kettlebells, Dumbbells',
                    'kategori' => 'With Equipment',
                    'exercises' => 9,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Push Day',
                    'kategori_jadwal' => 'Strength Training',
                    'tanggal' => Carbon::today()->addDays(6)->format('Y-m-d'),
                    'jam' => '08:30',
                    'durasi_workout' => '65 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Push Up',
                    'deskripsi' => 'Push up intensif untuk membangun kekuatan dada dan lengan.',
                    'equipment' => 'Tidak ada',
                    'kategori' => 'Without Equipment',
                    'exercises' => 6,
                    'status' => 'sedang dilakukan',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Pilates Session',
                    'kategori_jadwal' => 'Flexibility',
                    'tanggal' => Carbon::today()->addDays(6)->format('Y-m-d'),
                    'jam' => '09:30',
                    'durasi_workout' => '50 menit',
                ],
                'workout' => [
                    'nama_workout' => 'T Bar Row',
                    'deskripsi' => 'Latihan t-bar row untuk postur tubuh yang lebih baik.',
                    'equipment' => 'T-bar Machine, Light Weights',
                    'kategori' => 'With Equipment',
                    'exercises' => 14,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Glutes & Hamstrings',
                    'kategori_jadwal' => 'Lower Body',
                    'tanggal' => Carbon::today()->addDays(7)->format('Y-m-d'),
                    'jam' => '10:30',
                    'durasi_workout' => '70 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Shoulder Press',
                    'deskripsi' => 'Latihan shoulder press dengan fokus pada stabilitas tubuh.',
                    'equipment' => 'Barbell, Squat Rack',
                    'kategori' => 'With Equipment',
                    'exercises' => 8,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Swimming Cardio',
                    'kategori_jadwal' => 'Cardio',
                    'tanggal' => Carbon::today()->addDays(7)->format('Y-m-d'),
                    'jam' => '16:00',
                    'durasi_workout' => '60 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Push Up',
                    'deskripsi' => 'Push up sebagai latihan kekuatan pendamping berenang.',
                    'equipment' => 'Tidak ada',
                    'kategori' => 'Without Equipment',
                    'exercises' => 7,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Mobility & Stretching',
                    'kategori_jadwal' => 'Recovery',
                    'tanggal' => Carbon::today()->addDays(8)->format('Y-m-d'),
                    'jam' => '19:00',
                    'durasi_workout' => '40 menit',
                ],
                'workout' => [
                    'nama_workout' => 'T Bar Row',
                    'deskripsi' => 'T-bar row ringan untuk menjaga kekuatan punggung selama pemulihan.',
                    'equipment' => 'Light T-bar, Supportive Bench',
                    'kategori' => 'With Equipment',
                    'exercises' => 12,
                    'status' => 'selesai',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'CrossFit WOD',
                    'kategori_jadwal' => 'High Intensity',
                    'tanggal' => Carbon::today()->addDays(8)->format('Y-m-d'),
                    'jam' => '06:30',
                    'durasi_workout' => '60 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Push Up',
                    'deskripsi' => 'Push up sebagai bagian dari WOD CrossFit untuk daya tahan tubuh.',
                    'equipment' => 'Tidak ada',
                    'kategori' => 'Without Equipment',
                    'exercises' => 10,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Arms Specialization',
                    'kategori_jadwal' => 'Muscle Building',
                    'tanggal' => Carbon::today()->addDays(9)->format('Y-m-d'),
                    'jam' => '11:00',
                    'durasi_workout' => '60 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Shoulder Press',
                    'deskripsi' => 'Shoulder press khusus untuk pengembangan kekuatan bahu.',
                    'equipment' => 'EZ Bar, Dumbbells, Shoulder Press Bench',
                    'kategori' => 'With Equipment',
                    'exercises' => 9,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Boxing Training',
                    'kategori_jadwal' => 'Martial Arts',
                    'tanggal' => Carbon::today()->addDays(9)->format('Y-m-d'),
                    'jam' => '17:30',
                    'durasi_workout' => '75 menit',
                ],
                'workout' => [
                    'nama_workout' => 'T Bar Row',
                    'deskripsi' => 'T-bar row untuk kekuatan punggung yang mendukung teknik tinju.',
                    'equipment' => 'T-bar Machine, Olympic Plates',
                    'kategori' => 'With Equipment',
                    'exercises' => 11,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Home Workout',
                    'kategori_jadwal' => 'Home Fitness',
                    'tanggal' => Carbon::today()->addDays(10)->format('Y-m-d'),
                    'jam' => '07:00',
                    'durasi_workout' => '45 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Push Up',
                    'deskripsi' => 'Push up sebagai latihan utama dalam rutinitas latihan di rumah.',
                    'equipment' => 'Tidak ada',
                    'kategori' => 'Without Equipment',
                    'exercises' => 8,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Spin Class',
                    'kategori_jadwal' => 'Cardio',
                    'tanggal' => Carbon::today()->addDays(10)->format('Y-m-d'),
                    'jam' => '18:30',
                    'durasi_workout' => '50 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Shoulder Press',
                    'deskripsi' => 'Shoulder press dengan beban ringan setelah kelas spinning.',
                    'equipment' => 'Light Dumbbells',
                    'kategori' => 'With Equipment',
                    'exercises' => 6,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Deadlift Day',
                    'kategori_jadwal' => 'Powerlifting',
                    'tanggal' => Carbon::today()->addDays(11)->format('Y-m-d'),
                    'jam' => '09:00',
                    'durasi_workout' => '80 menit',
                ],
                'workout' => [
                    'nama_workout' => 'T Bar Row',
                    'deskripsi' => 'T-bar row sebagai latihan pendamping untuk kekuatan punggung.',
                    'equipment' => 'T-bar Machine, Weight Plates',
                    'kategori' => 'With Equipment',
                    'exercises' => 7,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Functional Training',
                    'kategori_jadwal' => 'Functional',
                    'tanggal' => Carbon::today()->addDays(11)->format('Y-m-d'),
                    'jam' => '16:00',
                    'durasi_workout' => '55 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Push Up',
                    'deskripsi' => 'Push up sebagai latihan functional untuk kekuatan tubuh atas.',
                    'equipment' => 'Tidak ada',
                    'kategori' => 'Without Equipment',
                    'exercises' => 9,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Shoulder Sculpting',
                    'kategori_jadwal' => 'Muscle Building',
                    'tanggal' => Carbon::today()->addDays(12)->format('Y-m-d'),
                    'jam' => '08:30',
                    'durasi_workout' => '60 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Shoulder Press',
                    'deskripsi' => 'Latihan shoulder press untuk membentuk dan menguatkan otot bahu.',
                    'equipment' => 'Dumbbells, Shoulder Press Machine',
                    'kategori' => 'With Equipment',
                    'exercises' => 8,
                    'status' => 'selesai',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Trail Running',
                    'kategori_jadwal' => 'Outdoor',
                    'tanggal' => Carbon::today()->addDays(12)->format('Y-m-d'),
                    'jam' => '06:30',
                    'durasi_workout' => '90 menit',
                ],
                'workout' => [
                    'nama_workout' => 'T Bar Row',
                    'deskripsi' => 'T-bar row untuk menjaga kekuatan punggung setelah lari trail.',
                    'equipment' => 'T-bar Machine, Moderate Weights',
                    'kategori' => 'With Equipment',
                    'exercises' => 5,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Tabata Training',
                    'kategori_jadwal' => 'HIIT',
                    'tanggal' => Carbon::today()->addDays(13)->format('Y-m-d'),
                    'jam' => '07:30',
                    'durasi_workout' => '30 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Push Up',
                    'deskripsi' => 'Push up dalam format Tabata untuk intensitas tinggi.',
                    'equipment' => 'Timer, Mat',
                    'kategori' => 'Without Equipment',
                    'exercises' => 8,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Chest Building',
                    'kategori_jadwal' => 'Strength Training',
                    'tanggal' => Carbon::today()->addDays(13)->format('Y-m-d'),
                    'jam' => '17:00',
                    'durasi_workout' => '70 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Shoulder Press',
                    'deskripsi' => 'Shoulder press sebagai latihan pendukung untuk pengembangan dada.',
                    'equipment' => 'Barbell, Bench',
                    'kategori' => 'With Equipment',
                    'exercises' => 7,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Barre Class',
                    'kategori_jadwal' => 'Dance Fitness',
                    'tanggal' => Carbon::today()->addDays(14)->format('Y-m-d'),
                    'jam' => '09:00',
                    'durasi_workout' => '55 menit',
                ],
                'workout' => [
                    'nama_workout' => 'T Bar Row',
                    'deskripsi' => 'T-bar row ringan untuk postur dalam latihan barre.',
                    'equipment' => 'Light T-bar, Support',
                    'kategori' => 'With Equipment',
                    'exercises' => 12,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Squat Day',
                    'kategori_jadwal' => 'Powerlifting',
                    'tanggal' => Carbon::today()->addDays(14)->format('Y-m-d'),
                    'jam' => '10:00',
                    'durasi_workout' => '75 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Push Up',
                    'deskripsi' => 'Push up sebagai latihan tambahan pada hari squat.',
                    'equipment' => 'Tidak ada',
                    'kategori' => 'Without Equipment',
                    'exercises' => 6,
                    'status' => 'sedang dilakukan',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Recovery Yoga',
                    'kategori_jadwal' => 'Recovery',
                    'tanggal' => Carbon::today()->addDays(15)->format('Y-m-d'),
                    'jam' => '19:30',
                    'durasi_workout' => '60 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Shoulder Press',
                    'deskripsi' => 'Shoulder press ringan untuk menjaga mobilitas bahu selama pemulihan.',
                    'equipment' => 'Light Dumbbells, Yoga Mat',
                    'kategori' => 'With Equipment',
                    'exercises' => 10,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Kettlebell Flow',
                    'kategori_jadwal' => 'Functional',
                    'tanggal' => Carbon::today()->addDays(15)->format('Y-m-d'),
                    'jam' => '08:00',
                    'durasi_workout' => '50 menit',
                ],
                'workout' => [
                    'nama_workout' => 'T Bar Row',
                    'deskripsi' => 'T-bar row sebagai bagian dari latihan kettlebell flow.',
                    'equipment' => 'T-bar Machine, Kettlebells',
                    'kategori' => 'With Equipment',
                    'exercises' => 8,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Jump Rope Cardio',
                    'kategori_jadwal' => 'Cardio',
                    'tanggal' => Carbon::today()->addDays(16)->format('Y-m-d'),
                    'jam' => '07:00',
                    'durasi_workout' => '40 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Push Up',
                    'deskripsi' => 'Push up setelah latihan skipping untuk kekuatan tubuh atas.',
                    'equipment' => 'Tidak ada',
                    'kategori' => 'Without Equipment',
                    'exercises' => 7,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Lats & Traps',
                    'kategori_jadwal' => 'Back Training',
                    'tanggal' => Carbon::today()->addDays(16)->format('Y-m-d'),
                    'jam' => '16:30',
                    'durasi_workout' => '65 menit',
                ],
                'workout' => [
                    'nama_workout' => 'T Bar Row',
                    'deskripsi' => 'Latihan t-bar row fokus pada pengembangan lats dan punggung tengah.',
                    'equipment' => 'T-bar Row Machine, Various Handles',
                    'kategori' => 'With Equipment',
                    'exercises' => 7,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Calisthenics Park',
                    'kategori_jadwal' => 'Bodyweight',
                    'tanggal' => Carbon::today()->addDays(17)->format('Y-m-d'),
                    'jam' => '07:30',
                    'durasi_workout' => '70 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Push Up',
                    'deskripsi' => 'Berbagai variasi push up di taman calisthenics.',
                    'equipment' => 'Tidak ada',
                    'kategori' => 'Without Equipment',
                    'exercises' => 9,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Rowing Machine',
                    'kategori_jadwal' => 'Cardio',
                    'tanggal' => Carbon::today()->addDays(17)->format('Y-m-d'),
                    'jam' => '18:00',
                    'durasi_workout' => '45 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Shoulder Press',
                    'deskripsi' => 'Shoulder press setelah latihan rowing untuk keseimbangan tubuh.',
                    'equipment' => 'Dumbbells, Adjustable Bench',
                    'kategori' => 'With Equipment',
                    'exercises' => 6,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Forearm & Grip',
                    'kategori_jadwal' => 'Specialized',
                    'tanggal' => Carbon::today()->addDays(18)->format('Y-m-d'),
                    'jam' => '10:00',
                    'durasi_workout' => '40 menit',
                ],
                'workout' => [
                    'nama_workout' => 'T Bar Row',
                    'deskripsi' => 'T-bar row untuk melatih kekuatan genggaman dan lengan bawah.',
                    'equipment' => 'T-bar Machine, Fat Grip Attachments',
                    'kategori' => 'With Equipment',
                    'exercises' => 8,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Meditation & Breathing',
                    'kategori_jadwal' => 'Mindfulness',
                    'tanggal' => Carbon::today()->addDays(18)->format('Y-m-d'),
                    'jam' => '20:00',
                    'durasi_workout' => '30 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Push Up',
                    'deskripsi' => 'Push up sebagai latihan fisik sebelum sesi meditasi.',
                    'equipment' => 'Tidak ada',
                    'kategori' => 'Without Equipment',
                    'exercises' => 4,
                    'status' => 'selesai',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Bench Press Focus',
                    'kategori_jadwal' => 'Powerlifting',
                    'tanggal' => Carbon::today()->addDays(19)->format('Y-m-d'),
                    'jam' => '09:30',
                    'durasi_workout' => '75 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Shoulder Press',
                    'deskripsi' => 'Shoulder press sebagai latihan pendamping bench press.',
                    'equipment' => 'Barbell, Bench, Spotter',
                    'kategori' => 'With Equipment',
                    'exercises' => 6,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Zumba Party',
                    'kategori_jadwal' => 'Dance Fitness',
                    'tanggal' => Carbon::today()->addDays(19)->format('Y-m-d'),
                    'jam' => '18:30',
                    'durasi_workout' => '60 menit',
                ],
                'workout' => [
                    'nama_workout' => 'T Bar Row',
                    'deskripsi' => 'T-bar row ringan setelah kelas Zumba untuk kekuatan punggung.',
                    'equipment' => 'Light T-bar Machine',
                    'kategori' => 'With Equipment',
                    'exercises' => 10,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Suspension Training',
                    'kategori_jadwal' => 'Functional',
                    'tanggal' => Carbon::today()->addDays(20)->format('Y-m-d'),
                    'jam' => '08:00',
                    'durasi_workout' => '50 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Push Up',
                    'deskripsi' => 'Push up dengan variasi menggunakan suspension trainer.',
                    'equipment' => 'TRX System',
                    'kategori' => 'With Equipment',
                    'exercises' => 9,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Hill Sprints',
                    'kategori_jadwal' => 'Speed Training',
                    'tanggal' => Carbon::today()->addDays(20)->format('Y-m-d'),
                    'jam' => '06:45',
                    'durasi_workout' => '35 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Shoulder Press',
                    'deskripsi' => 'Shoulder press ringan setelah sprint untuk kekuatan tubuh atas.',
                    'equipment' => 'Light Dumbbells',
                    'kategori' => 'With Equipment',
                    'exercises' => 5,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Obliques & Side Abs',
                    'kategori_jadwal' => 'Core Training',
                    'tanggal' => Carbon::today()->addDays(21)->format('Y-m-d'),
                    'jam' => '11:00',
                    'durasi_workout' => '40 menit',
                ],
                'workout' => [
                    'nama_workout' => 'T Bar Row',
                    'deskripsi' => 'T-bar row untuk melatih stabilitas core samping.',
                    'equipment' => 'T-bar Machine, Core Support',
                    'kategori' => 'With Equipment',
                    'exercises' => 10,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Stair Climber',
                    'kategori_jadwal' => 'Cardio',
                    'tanggal' => Carbon::today()->addDays(21)->format('Y-m-d'),
                    'jam' => '17:15',
                    'durasi_workout' => '30 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Push Up',
                    'deskripsi' => 'Push up setelah latihan stair climber untuk kekuatan tubuh atas.',
                    'equipment' => 'Tidak ada',
                    'kategori' => 'Without Equipment',
                    'exercises' => 4,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Pull-up Progression',
                    'kategori_jadwal' => 'Calisthenics',
                    'tanggal' => Carbon::today()->addDays(22)->format('Y-m-d'),
                    'jam' => '09:00',
                    'durasi_workout' => '55 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Shoulder Press',
                    'deskripsi' => 'Shoulder press untuk mendukung kekuatan dalam pull-up.',
                    'equipment' => 'Dumbbells, Pull-up Bar',
                    'kategori' => 'With Equipment',
                    'exercises' => 7,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Aqua Aerobics',
                    'kategori_jadwal' => 'Low Impact',
                    'tanggal' => Carbon::today()->addDays(22)->format('Y-m-d'),
                    'jam' => '10:00',
                    'durasi_workout' => '45 menit',
                ],
                'workout' => [
                    'nama_workout' => 'T Bar Row',
                    'deskripsi' => 'T-bar row ringan setelah aerobik air untuk kekuatan punggung.',
                    'equipment' => 'Light T-bar, Water-resistant Weights',
                    'kategori' => 'With Equipment',
                    'exercises' => 9,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Rotator Cuff',
                    'kategori_jadwal' => 'Prehab',
                    'tanggal' => Carbon::today()->addDays(23)->format('Y-m-d'),
                    'jam' => '08:30',
                    'durasi_workout' => '25 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Push Up',
                    'deskripsi' => 'Push up dengan teknik yang benar untuk kesehatan rotator cuff.',
                    'equipment' => 'Mat, Light Resistance Bands',
                    'kategori' => 'With Equipment',
                    'exercises' => 6,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Battle Ropes',
                    'kategori_jadwal' => 'HIIT',
                    'tanggal' => Carbon::today()->addDays(23)->format('Y-m-d'),
                    'jam' => '17:45',
                    'durasi_workout' => '20 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Shoulder Press',
                    'deskripsi' => 'Shoulder press setelah battle ropes untuk kekuatan bahu.',
                    'equipment' => 'Dumbbells, Battle Ropes',
                    'kategori' => 'With Equipment',
                    'exercises' => 8,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Neck & Traps',
                    'kategori_jadwal' => 'Specialized',
                    'tanggal' => Carbon::today()->addDays(24)->format('Y-m-d'),
                    'jam' => '11:30',
                    'durasi_workout' => '35 menit',
                ],
                'workout' => [
                    'nama_workout' => 'T Bar Row',
                    'deskripsi' => 'T-bar row untuk melatih upper traps dan punggung atas.',
                    'equipment' => 'T-bar Machine, Neck Support',
                    'kategori' => 'With Equipment',
                    'exercises' => 5,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Partner Workout',
                    'kategori_jadwal' => 'Group Training',
                    'tanggal' => Carbon::today()->addDays(24)->format('Y-m-d'),
                    'jam' => '16:00',
                    'durasi_workout' => '60 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Push Up',
                    'deskripsi' => 'Push up dengan variasi partner untuk motivasi dan tantangan.',
                    'equipment' => 'Tidak ada',
                    'kategori' => 'Without Equipment',
                    'exercises' => 8,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Plyometric Training',
                    'kategori_jadwal' => 'Explosive',
                    'tanggal' => Carbon::today()->addDays(25)->format('Y-m-d'),
                    'jam' => '08:00',
                    'durasi_workout' => '40 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Shoulder Press',
                    'deskripsi' => 'Shoulder press eksplosif untuk power dan kecepatan.',
                    'equipment' => 'Barbell, Plyo Box',
                    'kategori' => 'With Equipment',
                    'exercises' => 7,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Incline Walking',
                    'kategori_jadwal' => 'Low Impact Cardio',
                    'tanggal' => Carbon::today()->addDays(25)->format('Y-m-d'),
                    'jam' => '18:30',
                    'durasi_workout' => '45 menit',
                ],
                'workout' => [
                    'nama_workout' => 'T Bar Row',
                    'deskripsi' => 'T-bar row setelah walking untuk kekuatan punggung.',
                    'equipment' => 'T-bar Machine, Moderate Weights',
                    'kategori' => 'With Equipment',
                    'exercises' => 4,
                    'status' => 'selesai',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Cable Machine',
                    'kategori_jadwal' => 'Isolation',
                    'tanggal' => Carbon::today()->addDays(26)->format('Y-m-d'),
                    'jam' => '10:00',
                    'durasi_workout' => '65 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Push Up',
                    'deskripsi' => 'Push up dengan variasi menggunakan cable machine untuk resistensi.',
                    'equipment' => 'Cable Machine, Push-up Handles',
                    'kategori' => 'With Equipment',
                    'exercises' => 10,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Fartlek Running',
                    'kategori_jadwal' => 'Running',
                    'tanggal' => Carbon::today()->addDays(26)->format('Y-m-d'),
                    'jam' => '06:30',
                    'durasi_workout' => '50 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Shoulder Press',
                    'deskripsi' => 'Shoulder press setelah lari fartlek untuk keseimbangan tubuh.',
                    'equipment' => 'Dumbbells, Adjustable Bench',
                    'kategori' => 'With Equipment',
                    'exercises' => 5,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Pre-Workout Mobility',
                    'kategori_jadwal' => 'Warm-up',
                    'tanggal' => Carbon::today()->addDays(27)->format('Y-m-d'),
                    'jam' => '08:15',
                    'durasi_workout' => '15 menit',
                ],
                'workout' => [
                    'nama_workout' => 'T Bar Row',
                    'deskripsi' => 'T-bar row ringan sebagai bagian dari pemanasan untuk punggung.',
                    'equipment' => 'Light T-bar, Warm-up Weights',
                    'kategori' => 'With Equipment',
                    'exercises' => 8,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Cool Down Routine',
                    'kategori_jadwal' => 'Recovery',
                    'tanggal' => Carbon::today()->addDays(27)->format('Y-m-d'),
                    'jam' => '19:45',
                    'durasi_workout' => '20 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Push Up',
                    'deskripsi' => 'Push up ringan sebagai bagian dari pendinginan aktif.',
                    'equipment' => 'Mat',
                    'kategori' => 'Without Equipment',
                    'exercises' => 6,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Isometric Training',
                    'kategori_jadwal' => 'Strength Endurance',
                    'tanggal' => Carbon::today()->addDays(28)->format('Y-m-d'),
                    'jam' => '09:30',
                    'durasi_workout' => '35 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Shoulder Press',
                    'deskripsi' => 'Shoulder press isometric untuk kekuatan dan stabilitas bahu.',
                    'equipment' => 'Dumbbells, Wall Support',
                    'kategori' => 'With Equipment',
                    'exercises' => 6,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Posture Correction',
                    'kategori_jadwal' => 'Rehab',
                    'tanggal' => Carbon::today()->addDays(28)->format('Y-m-d'),
                    'jam' => '17:00',
                    'durasi_workout' => '40 menit',
                ],
                'workout' => [
                    'nama_workout' => 'T Bar Row',
                    'deskripsi' => 'T-bar row untuk memperbaiki postur tubuh dan menguatkan punggung.',
                    'equipment' => 'T-bar Machine, Posture Corrector',
                    'kategori' => 'With Equipment',
                    'exercises' => 7,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Vacation Workout',
                    'kategori_jadwal' => 'Minimalist',
                    'tanggal' => Carbon::today()->addDays(29)->format('Y-m-d'),
                    'jam' => '07:30',
                    'durasi_workout' => '30 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Push Up',
                    'deskripsi' => 'Push up sebagai latihan utama saat liburan tanpa alat.',
                    'equipment' => 'Tidak ada',
                    'kategori' => 'Without Equipment',
                    'exercises' => 9,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Endurance Run',
                    'kategori_jadwal' => 'Long Distance',
                    'tanggal' => Carbon::today()->addDays(29)->format('Y-m-d'),
                    'jam' => '16:30',
                    'durasi_workout' => '120 menit',
                ],
                'workout' => [
                    'nama_workout' => 'Shoulder Press',
                    'deskripsi' => 'Shoulder press ringan setelah lari jarak jauh untuk pemulihan.',
                    'equipment' => 'Light Dumbbells',
                    'kategori' => 'With Equipment',
                    'exercises' => 3,
                    'status' => 'belum',
                ]
            ],
            [
                'jadwal' => [
                    'nama_jadwal' => 'Active Rest Day',
                    'kategori_jadwal' => 'Recovery',
                    'tanggal' => Carbon::today()->addDays(30)->format('Y-m-d'),
                    'jam' => '10:00',
                    'durasi_workout' => '45 menit',
                ],
                'workout' => [
                    'nama_workout' => 'T Bar Row',
                    'deskripsi' => 'T-bar row ringan pada hari istirahat aktif untuk menjaga kekuatan.',
                    'equipment' => 'Light T-bar, Foam Roller',
                    'kategori' => 'With Equipment',
                    'exercises' => 5,
                    'status' => 'belum',
                ]
            ]
        ];

        $workoutData = array_slice($workoutData, 0, 50);

        foreach ($workoutData as $data) {
            $jadwal = JadwalWorkout::create($data['jadwal']);
            
            $workoutData = $data['workout'];
            $workoutData['jadwal_workout_id'] = $jadwal->id;
            
            Workout::create($workoutData);
        }
    }
}