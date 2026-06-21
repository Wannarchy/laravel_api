<?php

namespace App\Services;

use App\Models\AdminLoginOtp;
use App\Models\User;
use App\Notifications\AdminLoginOtpNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class AdminLoginOtpService
{
    public function issue(User $user): AdminLoginOtp
    {
        AdminLoginOtp::query()
            ->where('user_id', $user->id)
            ->delete();

        $code = str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        $expireMinutes = (int) config('cyna.admin_otp_expire_minutes', 15);

        $challenge = AdminLoginOtp::create([
            'user_id' => $user->id,
            'challenge_token' => Str::random(64),
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes($expireMinutes),
        ]);

        $user->notify(new AdminLoginOtpNotification($code, $expireMinutes));

        return $challenge;
    }

    public function verify(string $challengeToken, string $code): User
    {
        $challenge = AdminLoginOtp::query()
            ->where('challenge_token', $challengeToken)
            ->first();

        if (! $challenge) {
            throw new RuntimeException('Code de vérification invalide.');
        }

        $maxAttempts = (int) config('cyna.admin_otp_max_attempts', 5);

        if ($challenge->hasExceededAttempts($maxAttempts)) {
            $challenge->delete();

            throw new RuntimeException('Nombre de tentatives dépassé. Reconnectez-vous.');
        }

        if ($challenge->isExpired()) {
            $challenge->delete();

            throw new RuntimeException('Ce code a expiré. Reconnectez-vous pour en recevoir un nouveau.');
        }

        $normalizedCode = preg_replace('/\D+/', '', $code) ?? '';

        if (strlen($normalizedCode) !== 8 || ! Hash::check($normalizedCode, $challenge->code_hash)) {
            $challenge->increment('attempts');

            throw new RuntimeException('Code de vérification incorrect.');
        }

        $user = $challenge->user;

        if (! $user || (int) $user->is_admin !== 1) {
            $challenge->delete();

            throw new RuntimeException('Code de vérification invalide.');
        }

        $challenge->delete();

        return $user;
    }
}
