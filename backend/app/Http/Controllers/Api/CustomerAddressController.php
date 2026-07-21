<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerAddressController extends Controller
{
    public function index(): JsonResponse
    {
        $addresses = Auth::user()->addresses()->latest()->get();

        return response()->json([
            'data' => $addresses,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'nip' => 'nullable|string|max:20',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'postal_code' => 'required|string|max:20',
            'city' => 'required|string|max:255',
            'country_code' => 'required|string|min:2|max:3',
            'phone' => 'nullable|string|max:50',
            'is_default_shipping' => 'boolean',
            'is_default_billing' => 'boolean',
        ]);

        $address = Auth::user()->addresses()->create($validated);

        return response()->json([
            'data' => $address,
            'message' => 'Adres został zapisany.',
        ], 201);
    }

    public function update(Request $request, CustomerAddress $address): JsonResponse
    {
        if ($address->user_id !== Auth::id()) {
            return response()->json(['message' => 'Brak dostępu.'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'nip' => 'nullable|string|max:20',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'postal_code' => 'required|string|max:20',
            'city' => 'required|string|max:255',
            'country_code' => 'required|string|min:2|max:3',
            'phone' => 'nullable|string|max:50',
            'is_default_shipping' => 'boolean',
            'is_default_billing' => 'boolean',
        ]);

        $address->update($validated);

        return response()->json([
            'data' => $address,
            'message' => 'Adres został zaktualizowany.',
        ]);
    }

    public function destroy(CustomerAddress $address): JsonResponse
    {
        if ($address->user_id !== Auth::id()) {
            return response()->json(['message' => 'Brak dostępu.'], 403);
        }

        $address->delete();

        return response()->json([
            'message' => 'Adres został usunięty.',
        ]);
    }
}
