<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;


class AuthController extends Controller
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
            $accessToken = $user->createToken('authToken')->accessToken;
            $refreshToken = $user->createToken('authToken')->refreshToken;

            return response()->json([
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
            ]);
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }


    public function register(Request $request) {
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Create the user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        $accessToken = $user->createToken('authToken')->accessToken;

        return response()->json([
            'message' => 'Registration successful!',
            'user' => $user,
            'access_token' => $accessToken,
        ], 201);
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
            $accessToken = $user->createToken('authToken')->accessToken;
            $refreshToken = $user->createToken('authToken')->refreshToken;

            return response()->json([
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Google authentication failed'], 500);
        }
    }



}
