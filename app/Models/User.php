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
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'utilisateurs';

    protected $fillable = [
        'prenom',
        'nom',
        'email',
        'mot_de_passe',
        'est_confirme',
        'token_confirmation',
        'token_reinitialisation',
        'expiration_token',
        'is_admin',
        'est_actif',
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
        ])->save();
    }

    public function markEmailAsUnverified(): bool
    {
        return $this->forceFill([
            'est_confirme' => false,
        ])->save();
    }

    public function sendEmailVerificationNotification(): void
    {
        if (! $this->token_confirmation) {
            $this->forceFill(['token_confirmation' => Str::random(64)])->save();
        }

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

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'user_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class, 'user_id');
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(UserPaymentMethod::class, 'user_id');
    }

    public function chatLogs(): HasMany
    {
        return $this->hasMany(ChatLog::class, 'user_id');
    }
}
