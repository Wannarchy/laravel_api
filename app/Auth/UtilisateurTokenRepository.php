<?php

namespace App\Auth;

use Illuminate\Auth\Passwords\TokenRepositoryInterface;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class UtilisateurTokenRepository implements TokenRepositoryInterface
{
    public function __construct(
        protected ConnectionInterface $connection,
        protected int $expires = 3600,
        protected int $throttle = 60,
    ) {}

    public function create(CanResetPasswordContract $user): string
    {
        $token = Str::random(64);

        $this->connection->table('utilisateurs')
            ->where('email', $user->getEmailForPasswordReset())
            ->update([
                'token_reinitialisation' => $token,
                'expiration_token' => now()->addSeconds($this->expires),
            ]);

        return $token;
    }

    public function exists(CanResetPasswordContract $user, #[\SensitiveParameter] $token): bool
    {
        $record = $this->connection->table('utilisateurs')
            ->where('email', $user->getEmailForPasswordReset())
            ->first();

        if (! $record || ! $record->token_reinitialisation) {
            return false;
        }

        if ($record->expiration_token && Carbon::parse($record->expiration_token)->isPast()) {
            return false;
        }

        return hash_equals($record->token_reinitialisation, $token);
    }

    public function recentlyCreatedToken(CanResetPasswordContract $user): bool
    {
        $record = $this->connection->table('utilisateurs')
            ->where('email', $user->getEmailForPasswordReset())
            ->first();

        if (! $record || ! $record->token_reinitialisation || ! $record->expiration_token) {
            return false;
        }

        $createdAt = Carbon::parse($record->expiration_token)->subSeconds($this->expires);

        return $createdAt->addSeconds($this->throttle)->isFuture();
    }

    public function delete(CanResetPasswordContract $user): void
    {
        $this->connection->table('utilisateurs')
            ->where('email', $user->getEmailForPasswordReset())
            ->update([
                'token_reinitialisation' => null,
                'expiration_token' => null,
            ]);
    }

    public function deleteExpired(): void
    {
        $this->connection->table('utilisateurs')
            ->where('expiration_token', '<', now())
            ->whereNotNull('token_reinitialisation')
            ->update([
                'token_reinitialisation' => null,
                'expiration_token' => null,
            ]);
    }
}
