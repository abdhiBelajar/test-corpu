<?php

namespace App\Services;

class SimpegApiService
{
    /**
     * Dummy function to get employee data by NIP from SIMPEG.
     * In a real scenario, this would make an HTTP request to the SIMPEG API.
     */
    public function getPegawaiByNip($nip)
    {
        // Mock data
        $mockData = [
            '198001012005011001' => [
                'nama_lengkap' => 'Budi Santoso, S.Kom',
                'jabatan' => 'Pranata Komputer Ahli Muda',
                'rumpun_jabatan' => 'JF',
                'unit_kerja' => 'Dinas Komunikasi, Informatika, Persandian, dan Statistik',
            ],
            '199001012015011002' => [
                'nama_lengkap' => 'Andi Wijaya, S.E.',
                'jabatan' => 'Analis Kepegawaian Ahli Pertama',
                'rumpun_jabatan' => 'JA',
                'unit_kerja' => 'Badan Kepegawaian dan Pengembangan Sumber Daya Manusia',
            ],
            '197501011995011003' => [
                'nama_lengkap' => 'Dr. Ni Made Ayu',
                'jabatan' => 'Kepala Dinas',
                'rumpun_jabatan' => 'JPT',
                'unit_kerja' => 'Dinas Kesehatan',
            ],
            '199501012020011004' => [
                'nama_lengkap' => 'Putu Eka',
                'jabatan' => 'Pengadministrasi Umum',
                'rumpun_jabatan' => 'Pelaksana',
                'unit_kerja' => 'Sekretariat Daerah',
            ],
            '199501012020011005' => [
                'nama_lengkap' => 'Putu nanta',
                'jabatan' => 'Pengadministrasi Umum',
                'rumpun_jabatan' => 'JPT',
                'unit_kerja' => 'Sekretariat Daerah',
            ],
            '199501012020011006' => [
                'nama_lengkap' => 'Putu aldi',
                'jabatan' => 'Pengadministrasi Umum',
                'rumpun_jabatan' => 'JPT',
                'unit_kerja' => 'Sekretariat Daerah',
            ],
        ];

        return $mockData[$nip] ?? null;
    }
}
