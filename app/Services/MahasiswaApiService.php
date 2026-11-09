<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;

class MahasiswaApiService
{
    public function cariMahasiswa(string $nim): array
    {
        $baseUrl = rtrim(
            (string) config('services.mahasiswa.url'),
            '/'
        );

        $url = $baseUrl . '/api/mahasiswa/' . rawurlencode($nim);

        try {
            $response = Http::acceptJson()
                ->timeout(5)
                ->get($url);

            // Jika API menyatakan NIM tidak ditemukan.
            if ($response->status() === 404) {
                return [
                    'status' => 'not_found',
                    'source' => 'api',
                    'data' => null,
                ];
            }

            // Jika API berhasil memberikan data.
            if ($response->successful()) {
                $payload = $response->json();
                $data = $payload['data'] ?? null;

                if (($payload['success'] ?? false) === false) {
                    return [
                        'status' => 'not_found',
                        'source' => 'api',
                        'data' => null,
                    ];
                }

                if (
                    ($payload['success'] ?? false) === true &&
                    is_array($data) &&
                    ($data['nim'] ?? null) === $nim &&
                    !empty($data['nama']) &&
                    !empty($data['prodi']) &&
                    !empty($data['status'])
                ) {
                    $student = Student::updateOrCreate(
                        ['nim' => $nim],
                        [
                            'nama' => $data['nama'],
                            'prodi' => $data['prodi'],
                            'status' => $data['status'],
                        ]
                    );

                    return [
                        'status' => 'found',
                        'source' => 'api',
                        'data' => $student,
                    ];
                }
            }
        } catch (ConnectionException $e) {
            // API tidak dapat dihubungi.
            // Proses dilanjutkan ke database lokal.
        }

        // Fallback: cari data mahasiswa di database lokal.
        $student = Student::where('nim', $nim)->first();

        if ($student) {
            return [
                'status' => 'found',
                'source' => 'database',
                'data' => $student,
            ];
        }

        return [
            'status' => 'not_found',
            'source' => 'database',
            'data' => null,
        ];
    }
}
