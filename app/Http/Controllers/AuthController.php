<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Mail\SendCodeToVerifyEmail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

use function Symfony\Component\Clock\now;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $userInput = $request->validated();

        $lastUser = User::orderBy('IdUser', 'desc')->first();

        if ($lastUser) {
            $number = intval(substr($lastUser->IdUser, 1)) + 1;
        } else {
            $number = 1;
        }

        $newId = str_pad($number, 5, '0', STR_PAD_LEFT);

        $userInput['IdUser'] = $newId;
        $userInput['Role'] = '02';
        $userInput ['Password'] = Hash::make($userInput['Password']);

        $userInput['Code'] = random_int(100000, 999999);
        $userInput['CodeExpiresAt'] = Carbon::now()->addMinutes(10);

        $user = User::create($userInput);

        Mail::to($user->Email)->send(new SendCodeToVerifyEmail($user->Code));

        return response()->json([
            'message' => 'User registered successfully',
            'user' => new UserResource($user)
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $userInput = $request->validated();
        $user = User::where('Email', $userInput['Email'])->first();

        if (!$user) {
            return response()->json([
                'message' => 'Email or password is incorrect',
            ],401);
        }

        $checkPassword = Hash::check($userInput['Password'], $user->Password);

        if (!$checkPassword) {
            return response()->json([
                'message' => 'Email or password is incorrect',
            ],401);
        }

        $accessToken = $user->createToken('authToken')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => new UserResource($user),
            'access_token' => $accessToken,
        ], 200);
    }

    public function getProfile(Request $request)
    {
        return new UserResource($request->user());
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout successful',
        ], 200);
    }
}
