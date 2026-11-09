<?php

namespace App\Services\External;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class HolidayClient
{
    protected string $baseUrl = 'https://date.nager.at/api/v3/publicholidays';

    /**
     * Memeriksa apakah tanggal hari ini adalah hari libur nasional Indonesia.
     * Menggunakan cache 24 jam, timeout 5 detik, retry 2 kali, dan fail-open fallback.
     */
    public function isPublicHoliday(?Carbon $date = null): array
    {
        $targetDate = $date ?? Carbon::today();
        $year = $targetDate->year;
        $dateString = $targetDate->format('Y-m-d');
        $cacheKey = "public_holidays_id_{$year}";

        try {
            $holidays = Cache::remember($cacheKey, 86400, function () use ($year) {
                $response = Http::timeout(5)
                    ->retry(2, 200)
                    ->get("{$this->baseUrl}/{$year}/ID");

                if ($response->successful()) {
                    return $response->json();
                }

                return [];
            });

            foreach ($holidays as $holiday) {
                if (isset($holiday['date']) && $holiday['date'] === $dateString) {
                    return [
                        'is_holiday' => true,
                        'name' => $holiday['localName'] ?? $holiday['name'] ?? 'Hari Libur Nasional'
                    ];
                }
            }

            return ['is_holiday' => false, 'name' => null];
        } catch (\Throwable $e) {
            // Fallback fail-open: jika internet gangguan atau API eksternal mati,
            // sistem tetap mengizinkan antrean agar operasional kampus tidak terhenti.
            Log::warning("Gagal menghubungi Nager.Date API: " . $e->getMessage());
            return ['is_holiday' => false, 'name' => null];
        }
    }
}