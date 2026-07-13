<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthTokenController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'email' => [
                'required',
                'email',
            ],
            'password' => [
                'required',
            ],
            'token_name' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $user = User::where(
            'email',
            $request->email
        )->first();

        if (
            !$user
            || !Hash::check(
                $request->password,
                $user->password
            )
        ) {
            throw ValidationException::withMessages([
                'email' => [
                    'メールアドレスまたはパスワードが正しくありません。',
                ],
            ]);
        }

        $tokenName = $request->input(
            'token_name',
            'api-token'
        );

        $token = $user
            ->createToken($tokenName)
            ->plainTextToken;

        return response()->json([
            'token' => $token,
        ]);
    }
}
