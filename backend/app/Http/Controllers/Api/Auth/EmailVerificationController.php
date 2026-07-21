<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function __invoke(Request $request, string $id, string $hash)
    {
        /** @var User|null $user */
        $user = User::query()->findOrFail($id);

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            abort(403, 'Niepoprawny skrót weryfikacyjny.');
        }

        if ($user->hasVerifiedEmail()) {
            return $request->wantsJson()
                ? response()->json(['message' => 'Email jest już zweryfikowany.'])
                : redirect()->away(env('FRONTEND_URL', 'http://localhost:3000') . '/account?verified=already');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return $request->wantsJson()
            ? response()->json(['message' => 'Email został zweryfikowany.'])
            : redirect()->away(env('FRONTEND_URL', 'http://localhost:3000') . '/account?verified=true');
    }
}
