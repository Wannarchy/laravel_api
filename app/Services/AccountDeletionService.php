<?php

namespace App\Services;

use App\Models\ChatLog;
use App\Models\ContactMessage;
use App\Models\ProductSubscription;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\UserPaymentMethod;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Laravel\Cashier\Subscription as CashierSubscription;

class AccountDeletionService
{
    public function delete(User $user): void
    {
        if ((bool) $user->is_admin) {
            throw new \RuntimeException('Les comptes administrateur ne peuvent pas être supprimés depuis l\'espace client.');
        }

        $userId = (int) $user->id;

        DB::transaction(function () use ($user, $userId): void {
            AuditLogger::log(
                'account.self_deleted',
                'User',
                $userId,
                null,
                request(),
                null,
                $userId,
                false,
            );

            $this->cancelSubscriptions($user);
            $this->purgeStripeData($user);
            $this->deletePersonalData($userId);
            $this->anonymizeContactMessages($userId);
            $this->detachBillingRecords($userId);
            AuditLogger::anonymizeForDeletedUser($userId);
            $this->revokeSessions($user);

            $user->delete();
        });
    }

    private function cancelSubscriptions(User $user): void
    {
        ProductSubscription::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->get()
            ->each(function (ProductSubscription $subscription): void {
                if ($subscription->stripe_subscription_id) {
                    $cashierSubscription = CashierSubscription::query()
                        ->where('stripe_id', $subscription->stripe_subscription_id)
                        ->first();

                    $cashierSubscription?->cancelNow();
                }

                $subscription->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                ]);
            });

        CashierSubscription::query()
            ->where('user_id', $user->id)
            ->get()
            ->each(fn (CashierSubscription $subscription) => $subscription->cancelNow());
    }

    private function purgeStripeData(User $user): void
    {
        if (! $user->hasStripeId()) {
            return;
        }

        try {
            foreach ($user->paymentMethods() as $method) {
                $method->delete();
            }
        } catch (\Throwable) {
        }

        try {
            $user->asStripeCustomer()->delete();
        } catch (\Throwable) {
        }

        $user->forceFill([
            'stripe_id' => null,
            'pm_type' => null,
            'pm_last_four' => null,
            'trial_ends_at' => null,
        ])->save();
    }

    private function deletePersonalData(int $userId): void
    {
        UserAddress::query()->where('user_id', $userId)->delete();
        UserPaymentMethod::query()->where('user_id', $userId)->delete();
        ChatLog::query()->where('user_id', $userId)->delete();
    }

    private function anonymizeContactMessages(int $userId): void
    {
        ContactMessage::query()
            ->where('user_id', $userId)
            ->update([
                'user_id' => null,
                'email' => 'deleted+'.$userId.'@anonymized.local',
            ]);
    }

    private function detachBillingRecords(int $userId): void
    {
        ProductSubscription::query()
            ->where('user_id', $userId)
            ->update(['user_id' => null]);

        CashierSubscription::query()
            ->where('user_id', $userId)
            ->delete();
    }

    private function revokeSessions(User $user): void
    {
        $user->tokens()->delete();
    }
}
