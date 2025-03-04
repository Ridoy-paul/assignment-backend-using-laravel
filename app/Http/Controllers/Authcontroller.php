<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;


class Authcontroller extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $credentials = $request->only('email', 'password');
        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $accessToken = $user->createToken('YourApp')->accessToken;
            $refreshToken = $user->createToken('YourApp')->refreshToken;

            return response()->json([
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
            ]);
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }

    public function googleCallback() {
        try {
            $googleUser = Socialite::driver('google')->user();

            // Check if the user already exists, or create one
            $user = User::firstOrCreate([
                'google_id' => $googleUser->getId(),
            ], [
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'avatar' => $googleUser->getAvatar(),
            ]);

            // Generate token for the user
            $accessToken = $user->createToken('YourApp')->accessToken;
            $refreshToken = $user->createToken('YourApp')->refreshToken;

            return response()->json([
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Google authentication failed'], 500);
        }
    }



}
