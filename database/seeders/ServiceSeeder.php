<?php

namespace Database\Seeders;

use App\Enums\ServiceStatus;
use App\Models\Lookup\ServiceStatus as ServiceStatusLookup;
use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Seed the service catalogue customers can browse.
     */
    public function run(): void
    {
        foreach ($this->services() as $service) {
            Service::query()->updateOrCreate(
                ['slug' => $service['slug']],
                [
                    ...$service,
                    'service_status_id' => ServiceStatusLookup::ensureIdFor(
                        ServiceStatus::Published->value,
                        ServiceStatus::Published->label(),
                    ),
                ],
            );
        }
    }

    /**
     * The starter catalogue shipped with Jovexa.
     *
     * @return list<array<string, mixed>>
     */
    private function services(): array
    {
        return [
            [
                'name' => 'Pembuatan Skripsi Word',
                'slug' => 'pembuatan-skripsi-word',
                'price' => 750000,
                'estimated_duration' => 7,
                'description' => "Menyusun skripsi dari nol hingga siap sidang, mulai dari pemilihan topik, pencarian landasan teori, sampai penulisan bab I sampai V.\n\nTermasuk proposal, kerangka teori, metode, hasil, dan daftar pustaka.",
                'requirements' => "Topik yang sudah disetujui.\nReferensi minimal 15 jurnal ilmiah.\nData mentah bila bab hasil diperlukan.\nFormat institusi yang berlaku.",
            ],
            [
                'name' => 'Bantuan Tugas Kuliah',
                'slug' => 'bantuan-tugas-kuliah',
                'price' => 150000,
                'estimated_duration' => 2,
                'description' => "Mengerjakan tugas mata kuliah seperti makalah, UTS, soal latihan, dan presentasi.\n\nCocok untuk tugas esai, analisis data sederhana, dan rangkuman materi.",
                'requirements' => "Soal atau instruksi tugas.\nPanduan dari dosen bila ada.\nBatas waktu pengumpulan.",
            ],
            [
                'name' => 'Jasa Coding Web',
                'slug' => 'jasa-coding-web',
                'price' => 1200000,
                'estimated_duration' => 10,
                'description' => "Membangun website dari nol: landing page, sistem login, dan dashboard sederhana.\n\nMenggunakan Laravel atau framework pilihanmu. Source code dan dokumentasi setup disertakan.",
                'requirements' => "Kebutuhan fitur dan referensi desain.\nAkses repository bila memakai kode yang sudah ada.\nKonten dan aset yang akan dipasang.",
            ],
            [
                'name' => 'Desain Landing Page',
                'slug' => 'desain-landing-page',
                'price' => 450000,
                'estimated_duration' => 3,
                'description' => "Desain landing page yang conversion tinggi untuk produk, jasa, atau event.\n\nDiberikan dalam bentuk file Figma sekaligus hasil siap develop, lengkap dengan responsive layout.",
                'requirements' => "Nama dan deskripsi produk.\nLogo atau brand guideline.\nReferensi desain yang disukai.",
            ],
            [
                'name' => 'Presentasi PowerPoint',
                'slug' => 'presentasi-powerpoint',
                'price' => 200000,
                'estimated_duration' => 2,
                'description' => "Membuat slide presentasi yang rapi dan mudah dipahami, lengkap dengan visual dan speaker notes.\n\nCocok untuk tugas kelompok, sidang proposal, dan seminar.",
                'requirements' => "Materi dan garis besar presentasi.\nDurasi presentasi.\nTemplate kampus bila ada.",
            ],
            [
                'name' => 'Analisis Data SPSS',
                'slug' => 'analisis-data-spss',
                'price' => 500000,
                'estimated_duration' => 4,
                'description' => "Pengolahan dan analisis data menggunakan SPSS, mulai dari input data, uji validitas, hingga interpretasi hasil.\n\nDiserahkan dengan file output dan ringkasan dari setiap uji.",
                'requirements' => "Data mentah beserta kategori variabel.\nHipotesis yang akan diuji.\nPanduan dosen bila tersedia.",
            ],
        ];
    }
}
