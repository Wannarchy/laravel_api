<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class AuditLogger
{
    public static function log(
        string $action,
        ?string $targetType = null,
        ?int $targetId = null,
        ?string $details = null,
        ?Request $request = null,
        ?int $adminId = null,
        ?int $userId = null,
        bool $storeIp = true,
    ): void {
        $request ??= request();
        $actor = $request->user();

        if ($adminId === null && $userId === null && $actor) {
            if ((int) $actor->is_admin) {
                $adminId = $actor->id;
            } else {
                $userId = $actor->id;
            }
        }

        $actorId = $adminId ?? $userId;

        if ($actorId === null) {
            return;
        }

        $actorType = $adminId !== null
            ? ActivityLog::ACTOR_ADMIN
            : ActivityLog::ACTOR_USER;

        ActivityLog::create([
            'actor_type' => $actorType,
            'user_id' => $actorId,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'ip' => $storeIp ? self::maskIp($request?->ip()) : null,
            'details' => $details !== null ? strtoupper($details) : null,
            'created_at' => now(),
        ]);
    }

    public static function logFromRequest(Request $request): void
    {
        if (! self::shouldLogRequest($request)) {
            return;
        }

        [$action, $targetType, $targetId] = self::resolveContext($request);

        self::log($action, $targetType, $targetId, strtoupper($request->method()), $request);
    }

    public static function logPageView(Request $request, string $page): void
    {
        $page = basename(trim($page));

        if ($page === '' || ! self::isAllowedPageView($page)) {
            return;
        }

        self::log('page.view', $page, null, 'VIEW', $request, null, null, false);
    }

    public static function isAllowedPageView(string $page): bool
    {
        return in_array(basename($page), config('audit.allowed_page_views', []), true);
    }

    public static function shouldLogRequest(Request $request): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        $path = '/'.trim($request->path(), '/');

        if (preg_match('#/activity/page-view$#', $path)) {
            return false;
        }

        if ($request->isMethod('DELETE') && preg_match('#(^|/)profile$#', $path)) {
            return false;
        }

        if ((int) $user->is_admin) {
            return true;
        }

        return in_array(strtoupper($request->method()), ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }

    public static function maskIp(?string $ip): ?string
    {
        if ($ip === null || $ip === '') {
            return null;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            if (count($parts) === 4) {
                $parts[3] = '0';

                return implode('.', $parts);
            }
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            $parts = array_pad($parts, 8, '0');
            for ($i = 4; $i < 8; $i++) {
                $parts[$i] = '0';
            }

            return implode(':', array_slice($parts, 0, 8));
        }

        return null;
    }

    public static function anonymizeForDeletedUser(int $userId): void
    {
        ActivityLog::query()
            ->where('user_id', $userId)
            ->where('action', 'page.view')
            ->delete();

        $retained = config('audit.retain_actions_after_erasure', []);

        ActivityLog::query()
            ->where('user_id', $userId)
            ->whereNotIn('action', $retained)
            ->delete();

        ActivityLog::query()
            ->where('user_id', $userId)
            ->update([
                'user_id' => null,
                'ip' => null,
            ]);
    }

    public static function purgeExpired(): int
    {
        $days = max(30, (int) config('audit.retention_days', 365));
        $cutoff = now()->subDays($days);

        return ActivityLog::query()
            ->where('created_at', '<', $cutoff)
            ->delete();
    }

    /**
     * @return array{0: string, 1: ?string, 2: ?int}
     */
    private static function resolveContext(Request $request): array
    {
        $method = strtoupper($request->method());
        $path = '/'.trim($request->path(), '/');
        $params = $request->route()?->parameters() ?? [];
        $targetId = self::extractTargetId($params);
        $uri = (string) ($request->route()?->uri() ?? '');

        if (str_contains($path, '/admin/')) {
            return self::resolveAdminContext($uri, $method, $targetId);
        }

        return self::resolveApiContext($path, $method, $targetId, $request);
    }

    /**
     * @return array{0: string, 1: ?string, 2: ?int}
     */
    private static function resolveAdminContext(string $uri, string $method, ?int $targetId): array
    {
        if (str_contains($uri, 'users/{id}/bloquer')) {
            return ['user.block_toggle', 'User', $targetId];
        }

        if (str_contains($uri, 'users/{id}')) {
            return [self::verbAction('user', $method), 'User', $targetId];
        }

        if (str_contains($uri, 'users')) {
            return [self::verbAction('user', $method), 'User', null];
        }

        if (str_contains($uri, 'products/{id}')) {
            return [self::verbAction('product', $method), 'Product', $targetId];
        }

        if (str_contains($uri, 'products')) {
            return [self::verbAction('product', $method), 'Product', null];
        }

        if (str_contains($uri, 'categories/{id}')) {
            return [self::verbAction('category', $method), 'Category', $targetId];
        }

        if (str_contains($uri, 'categories')) {
            return [self::verbAction('category', $method), 'Category', null];
        }

        if (str_contains($uri, 'orders/{id}/status')) {
            return ['order.status_update', 'Order', $targetId];
        }

        if (str_contains($uri, 'orders/{id}')) {
            return [self::verbAction('order', $method), 'Order', $targetId];
        }

        if (str_contains($uri, 'orders')) {
            return [self::verbAction('order', $method), 'Order', null];
        }

        if (str_contains($uri, 'promo-codes/{id}')) {
            return [self::verbAction('promo_code', $method), 'PromoCode', $targetId];
        }

        if (str_contains($uri, 'promo-codes')) {
            return [self::verbAction('promo_code', $method), 'PromoCode', null];
        }

        if (str_contains($uri, 'homepage/slides/{id}')) {
            return ['homepage_slide.delete', 'HomepageSlide', $targetId];
        }

        if (str_contains($uri, 'homepage/slides')) {
            return ['homepage_slide.update', 'HomepageSlide', null];
        }

        if (str_contains($uri, 'homepage/content')) {
            return ['homepage_content.update', 'HomepageContent', null];
        }

        if (str_contains($uri, 'contact-messages/{id}/reply')) {
            return ['contact_message.reply', 'ContactMessage', $targetId];
        }

        if (str_contains($uri, 'contact-messages/{id}/status')) {
            return ['contact_message.status_update', 'ContactMessage', $targetId];
        }

        if (str_contains($uri, 'contact-messages')) {
            return [self::verbAction('contact_message', $method), 'ContactMessage', null];
        }

        if (str_contains($uri, 'uploads/image')) {
            return ['upload.image', 'Upload', null];
        }

        if (str_contains($uri, 'chat-logs')) {
            return [self::verbAction('chat_log', $method), 'ChatLog', null];
        }

        return ['admin.'.strtolower($method), null, $targetId];
    }

    /**
     * @return array{0: string, 1: ?string, 2: ?int}
     */
    private static function resolveApiContext(string $path, string $method, ?int $targetId, Request $request): array
    {
        if (preg_match('#/auth/register$#', $path)) {
            return ['auth.register', null, null];
        }

        if (preg_match('#/auth/login$#', $path)) {
            return ['auth.login', null, null];
        }

        if (preg_match('#/auth/logout$#', $path)) {
            return ['auth.logout', 'User', $request->user()?->id];
        }

        if (preg_match('#/auth/forgot-password$#', $path)) {
            return ['auth.forgot_password', null, null];
        }

        if (preg_match('#/auth/reset-password$#', $path)) {
            return ['auth.reset_password', null, null];
        }

        if (preg_match('#/auth/verify-email$#', $path)) {
            return ['auth.verify_email', null, null];
        }

        if (preg_match('#/auth/resend-verification#', $path)) {
            return ['auth.resend_verification', 'User', $request->user()?->id];
        }

        if (preg_match('#/profile$#', $path)) {
            return [self::verbAction('profile', $method), 'User', $request->user()?->id];
        }

        if (preg_match('#/billing/checkout/success$#', $path)) {
            return ['billing.checkout_success', 'Order', null];
        }

        if (preg_match('#/billing/checkout$#', $path)) {
            return ['billing.checkout', 'Order', null];
        }

        if (preg_match('#/billing/setup-intent$#', $path)) {
            return ['billing.setup_intent', null, null];
        }

        if (preg_match('#/billing/config$#', $path)) {
            return ['billing.config', null, null];
        }

        if (preg_match('#/orders/\d+$#', $path)) {
            return [self::verbAction('order', $method), 'Order', $targetId];
        }

        if (preg_match('#/orders$#', $path)) {
            return [self::verbAction('order', $method), 'Order', null];
        }

        if (preg_match('#/subscriptions/\d+/cancel$#', $path)) {
            return ['subscription.cancel', 'ProductSubscription', $targetId];
        }

        if (preg_match('#/subscriptions$#', $path)) {
            return [self::verbAction('subscription', $method), 'ProductSubscription', null];
        }

        if (preg_match('#/addresses/\d+$#', $path)) {
            return [self::verbAction('address', $method), 'UserAddress', $targetId];
        }

        if (preg_match('#/addresses$#', $path)) {
            return [self::verbAction('address', $method), 'UserAddress', null];
        }

        if (preg_match('#/payment-methods/\d+/default$#', $path)) {
            return ['payment_method.set_default', 'UserPaymentMethod', $targetId];
        }

        if (preg_match('#/payment-methods/\d+$#', $path)) {
            return [self::verbAction('payment_method', $method), 'UserPaymentMethod', $targetId];
        }

        if (preg_match('#/payment-methods$#', $path)) {
            return [self::verbAction('payment_method', $method), 'UserPaymentMethod', null];
        }

        if (preg_match('#/promo-codes/validate$#', $path)) {
            return ['promo_code.validate', 'PromoCode', null];
        }

        if (preg_match('#/chat/history$#', $path)) {
            return ['chat.history', 'ChatLog', $request->user()?->id];
        }

        if (preg_match('#/chat$#', $path)) {
            return ['chat.message', 'ChatLog', $request->user()?->id];
        }

        if (preg_match('#/contact$#', $path)) {
            return ['contact.send', 'ContactMessage', null];
        }

        if (preg_match('#/products/\d+$#', $path)) {
            return [self::verbAction('product', $method), 'Product', $targetId];
        }

        if (preg_match('#/products$#', $path)) {
            return [self::verbAction('product', $method), 'Product', null];
        }

        if (preg_match('#/categories$#', $path)) {
            return [self::verbAction('category', $method), 'Category', null];
        }

        if (preg_match('#/homepage$#', $path)) {
            return ['homepage.view', 'HomepageContent', null];
        }

        return ['api.'.strtolower($method), null, $targetId];
    }

    private static function verbAction(string $resource, string $method): string
    {
        return match ($method) {
            'POST' => $resource.'.create',
            'PUT', 'PATCH' => $resource.'.update',
            'DELETE' => $resource.'.delete',
            default => $resource.'.'.strtolower($method),
        };
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private static function extractTargetId(array $params): ?int
    {
        foreach (['id', 'productId', 'imageId'] as $key) {
            if (isset($params[$key]) && is_numeric($params[$key])) {
                return (int) $params[$key];
            }
        }

        return null;
    }
}
