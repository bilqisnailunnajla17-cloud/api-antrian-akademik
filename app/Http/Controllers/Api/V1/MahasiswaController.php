<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\MahasiswaApiService;
use Illuminate\Http\JsonResponse;

class MahasiswaController extends Controller
{
    protected MahasiswaApiService $mahasiswaService;

    public function __construct(MahasiswaApiService $mahasiswaService)
    {
        $this->mahasiswaService = $mahasiswaService;
    }

    public function show(string $nim): JsonResponse
    {
        $hasil = $this->mahasiswaService->cariMahasiswa($nim);

        if ($hasil['status'] === 'not_found') {
            return response()->json([
                'success' => false,
                'message' => 'Mahasiswa tidak ditemukan',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data mahasiswa ditemukan',
            'source' => $hasil['source'],
            'data' => $hasil['data'],
        ], 200);
    }
}
