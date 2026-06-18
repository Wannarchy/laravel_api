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
        ?array $details = null,
        ?Request $request = null,
        ?int $adminId = null,
        ?int $userId = null,
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

        if ($adminId === null && $userId === null) {
            return;
        }

        ActivityLog::create([
            'admin_id' => $adminId,
            'user_id' => $userId,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'ip' => $request?->ip(),
            'details' => $details,
            'created_at' => now(),
        ]);
    }

    public static function logFromRequest(Request $request): void
    {
        [$action, $targetType, $targetId] = self::resolveContext($request);

        self::log($action, $targetType, $targetId, [
            'method' => strtoupper($request->method()),
        ], $request);
    }

    /**
     * @return array{0: string, 1: ?string, 2: ?int}
     */
    private static function resolveContext(Request $request): array
    {
        $method = strtoupper($request->method());
        $params = $request->route()?->parameters() ?? [];
        $targetId = self::extractTargetId($params);
        $uri = (string) ($request->route()?->uri() ?? '');

        if (str_contains($uri, 'users/{id}')) {
            return [self::verbAction('user', $method), 'User', $targetId];
        }

        if (str_contains($uri, 'products/{productId}/images/sort')) {
            return ['product_image.sort', 'Product', isset($params['productId']) ? (int) $params['productId'] : null];
        }

        if (str_contains($uri, 'products/{productId}/images/{imageId}')) {
            return ['product_image.delete', 'ProductImage', isset($params['imageId']) ? (int) $params['imageId'] : null];
        }

        if (str_contains($uri, 'products/{productId}/images')) {
            return ['product_image.create', 'Product', isset($params['productId']) ? (int) $params['productId'] : null];
        }

        if (str_contains($uri, 'products/{id}')) {
            return [self::verbAction('product', $method), 'Product', $targetId];
        }

        if (str_contains($uri, 'products')) {
            return ['product.create', 'Product', null];
        }

        if (str_contains($uri, 'categories/{id}')) {
            return [self::verbAction('category', $method), 'Category', $targetId];
        }

        if (str_contains($uri, 'categories')) {
            return ['category.create', 'Category', null];
        }

        if (str_contains($uri, 'orders/{id}/status')) {
            return ['order.status_update', 'Order', $targetId];
        }

        if (str_contains($uri, 'promo-codes/{id}')) {
            return [self::verbAction('promo_code', $method), 'PromoCode', $targetId];
        }

        if (str_contains($uri, 'promo-codes')) {
            return ['promo_code.create', 'PromoCode', null];
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

        if (str_contains($uri, 'uploads/image')) {
            return ['upload.image', 'Upload', null];
        }

        return ['admin.'.strtolower($method), null, $targetId];
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
