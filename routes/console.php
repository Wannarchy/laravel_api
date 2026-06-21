<?php

use App\Models\PromoCode;
use App\Models\ProductSubscription;
use App\Models\User;
use App\Notifications\RenewalReminderNotification;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    ProductSubscription::query()
        ->with(['user', 'product'])
        ->where('status', 'active')
        ->whereNull('cancelled_at')
        ->whereDate('next_billing', now()->addDay()->toDateString())
        ->where('renewal_notified', false)
        ->each(function (ProductSubscription $subscription) {
            if ($subscription->user) {
                $subscription->user->notify(new RenewalReminderNotification($subscription));
            }

            $subscription->update(['renewal_notified' => true]);
        });
})->daily()->name('subscriptions:renewal-reminders');

Schedule::call(function () {
    PromoCode::query()
        ->where('is_active', true)
        ->whereNotNull('expires_at')
        ->whereDate('expires_at', '<', today())
        ->update(['is_active' => false]);
})->daily()->name('promo-codes:deactivate-expired');

Schedule::call(function () {
    User::query()
        ->whereNotNull('token_reinitialisation')
        ->where('expiration_token', '<', now())
        ->update([
            'token_reinitialisation' => null,
            'expiration_token' => null,
        ]);
})->daily()->name('users:purge-expired-reset-tokens');

Schedule::call(function () {
    User::query()
        ->where('est_confirme', false)
        ->whereNotNull('token_confirmation')
        ->whereNotNull('token_confirmation_expires_at')
        ->where('token_confirmation_expires_at', '<', now())
        ->update([
            'token_confirmation' => null,
            'token_confirmation_expires_at' => null,
        ]);
})->daily()->name('users:purge-expired-email-verification-tokens');

Schedule::call(function () {
    \App\Services\AuditLogger::purgeExpired();
})->daily()->name('logs:purge-expired');
