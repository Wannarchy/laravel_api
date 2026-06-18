<?php

namespace App\Models;

use App\Notifications\EmailVerificationNotification;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use Billable, HasApiTokens, HasFactory, Notifiable;

    protected $table = 'utilisateurs';

    protected $fillable = [
        'prenom',
        'nom',
        'email',
        'mot_de_passe',
        'est_confirme',
        'token_confirmation',
        'token_confirmation_expires_at',
        'token_reinitialisation',
        'expiration_token',
        'is_admin',
        'est_actif',
        'bloquer',
    ];

    protected $hidden = [
        'mot_de_passe',
        'token_confirmation',
        'token_reinitialisation',
    ];

    protected function casts(): array
    {
        return [
            'est_confirme' => 'boolean',
            'is_admin' => 'boolean',
            'est_actif' => 'boolean',
            'bloquer' => 'boolean',
            'token_confirmation_expires_at' => 'datetime',
            'expiration_token' => 'datetime',
            'date_inscription' => 'datetime',
            'derniere_connexion' => 'datetime',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->mot_de_passe;
    }

    public function hasVerifiedEmail(): bool
    {
        return (bool) $this->est_confirme;
    }

    public function markEmailAsVerified(): bool
    {
        return $this->forceFill([
            'est_confirme' => true,
            'token_confirmation' => null,
            'token_confirmation_expires_at' => null,
        ])->save();
    }

    public function issueEmailVerificationToken(): string
    {
        $token = Str::random(64);

        $this->forceFill([
            'token_confirmation' => $token,
            'token_confirmation_expires_at' => now()->addHours(
                (int) config('cyna.email_verification_expire_hours', 24)
            ),
        ])->save();

        return $token;
    }

    public function isEmailVerificationTokenExpired(): bool
    {
        return $this->token_confirmation_expires_at !== null
            && $this->token_confirmation_expires_at->isPast();
    }

    public function isEmailVerificationTokenValid(string $token): bool
    {
        if ($this->token_confirmation === null || $token === '') {
            return false;
        }

        if (! hash_equals((string) $this->token_confirmation, $token)) {
            return false;
        }

        return ! $this->isEmailVerificationTokenExpired();
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->issueEmailVerificationToken();

        $this->notify(new EmailVerificationNotification);
    }

    public function getEmailForVerification(): string
    {
        return (string) $this->email;
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    public function productSubscriptions(): HasMany
    {
        return $this->hasMany(ProductSubscription::class, 'user_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class, 'user_id');
    }

    public function chatLogs(): HasMany
    {
        return $this->hasMany(ChatLog::class, 'user_id');
    }
}
