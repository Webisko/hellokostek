<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\GusClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class B2bGusController extends Controller
{
    public function __construct(
        private readonly GusClient $gusClient
    ) {
    }

    public function __invoke(Request $request, string $nip): JsonResponse
    {
        // Basic NIP validation (Polish tax identification numbers consist of 10 digits)
        $cleanNip = preg_replace('/\D/', '', $nip);
        
        $validator = Validator::make(['nip' => $cleanNip], [
            'nip' => ['required', 'string', 'size:10', 'regex:/^[0-9]{10}$/'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Podany numer NIP jest niepoprawny. Musi składać się z 10 cyfr.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Query the GUS/MF databases
        $companyData = $this->gusClient->searchByNip($cleanNip);

        if (!$companyData) {
            return response()->json([
                'success' => false,
                'message' => 'Nie znaleziono firmy o podanym numerze NIP w bazie GUS/MF.',
            ], 404);
        }

        if (isset($companyData['status']) && $companyData['status'] === 'timeout_fallback') {
            return response()->json($companyData, 200);
        }

        return response()->json([
            'success' => true,
            'data' => $companyData,
        ]);
    }
}
