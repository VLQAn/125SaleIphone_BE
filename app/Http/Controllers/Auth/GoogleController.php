<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class GoogleController extends Controller
{
    // Redirect người dùng tới Google
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    // Callback từ Google
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Kiểm tra user đã tồn tại chưa
            $user = User::where('Email', $googleUser->getEmail())->first();

            if (!$user) {
                // Sinh IdUser
                $lastUser = User::orderBy('IdUser', 'desc')->first();
                $number = $lastUser ? intval($lastUser->IdUser) + 1 : 1;
                $newId = str_pad($number, 5, '0', STR_PAD_LEFT);

                // Tạo user mới
                $user = User::create([
                    'IdUser'       => $newId,
                    'Email'        => $googleUser->getEmail(),
                    'UserName'     => $googleUser->getName(),
                    'Password'     => bcrypt(Str::random(16)),
                    'Role'         => '02',           // roles.IdRole = '02' phải tồn tại
                    'IsVerified'   => 1,              // tinyint(1)
                    'Code'         => null,
                    'CodeExpiresAt'=> null,
                    'Provider'     => 'google',
                    'ProviderId'   => $googleUser->id,
                    'Avatar'       => $googleUser->avatar,
                ]);
            }

            Auth::login($user);

            // Tạo token cho frontend
            $token = $user->createToken('google-login')->plainTextToken;

            // Encode token để URL-safe
            return redirect(env('FRONTEND_URL') . '/auth/callback?token=' . urlencode($token));

        } catch (\Throwable $e) {
            dd($e->getMessage(), $e->getTraceAsString());
        }
    }
}
