<?php

use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\Admin\AdminCategoryController;
use App\Http\Controllers\Api\Admin\LogController;
use App\Http\Controllers\Api\Admin\AdminUploadController;
use App\Http\Controllers\Api\Admin\AdminContactController;
use App\Http\Controllers\Api\Admin\AdminChatController;
use App\Http\Controllers\Api\Admin\AdminHomepageController;
use App\Http\Controllers\Api\Admin\AdminOrderController;
use App\Http\Controllers\Api\Admin\AdminProductController;
use App\Http\Controllers\Api\Admin\AdminPromoCodeController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\HomepageController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\PromoCodeController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Middleware\LogAudit;
use Illuminate\Support\Facades\Route;

Route::middleware([LogAudit::class])->group(function () {
    Route::prefix('auth')->middleware('throttle:60,1')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/verify-admin-otp', [AuthController::class, 'verifyAdminOtp'])->middleware('throttle:10,1');
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
        Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
        Route::post('/resend-verification-email', [AuthController::class, 'resendVerificationByEmail']);
    });

    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/homepage', [HomepageController::class, 'index']);
    Route::get('/billing/config', [BillingController::class, 'config']);
    Route::post('/contact', [ContactController::class, 'store'])->middleware(['throttle:10,1', 'optional.sanctum']);

    Route::post('/activity/page-view', [ActivityController::class, 'pageView'])
        ->middleware(['throttle:120,1', 'auth:sanctum', 'active']);

    Route::middleware(['auth:sanctum', 'active'])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/resend-verification', [AuthController::class, 'resendVerification']);

        Route::get('/profile', [ProfileController::class, 'show']);
        Route::put('/profile', [ProfileController::class, 'update']);
        Route::delete('/profile', [ProfileController::class, 'destroy']);

        Route::get('/billing/setup-intent', [BillingController::class, 'setupIntent']);
        Route::post('/billing/checkout', [BillingController::class, 'checkout']);
        Route::post('/billing/checkout/success', [BillingController::class, 'checkoutSuccess']);

        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{id}', [OrderController::class, 'show']);
        Route::post('/orders', [OrderController::class, 'store']);

        Route::get('/subscriptions', [SubscriptionController::class, 'index']);
        Route::post('/subscriptions/{id}/cancel', [SubscriptionController::class, 'cancel']);
        Route::post('/subscriptions/{id}/change-cycle', [SubscriptionController::class, 'changeCycle']);

        Route::get('/addresses', [AddressController::class, 'index']);
        Route::post('/addresses', [AddressController::class, 'store']);
        Route::put('/addresses/{id}', [AddressController::class, 'update']);
        Route::delete('/addresses/{id}', [AddressController::class, 'destroy']);

        Route::get('/payment-methods', [PaymentMethodController::class, 'index']);
        Route::post('/payment-methods', [PaymentMethodController::class, 'store']);
        Route::post('/payment-methods/{id}/default', [PaymentMethodController::class, 'setDefault']);
        Route::delete('/payment-methods/{id}', [PaymentMethodController::class, 'destroy']);

        Route::post('/promo-codes/validate', [PromoCodeController::class, 'validate']);

        Route::post('/chat', [ChatController::class, 'store']);
        Route::get('/chat/history', [ChatController::class, 'history']);
    });

    Route::prefix('admin')->middleware(['auth:sanctum', 'active', 'admin'])->group(function () {
        Route::get('/logs', [LogController::class, 'index']);

        Route::get('/users', [AdminUserController::class, 'index']);
        Route::get('/users/{id}', [AdminUserController::class, 'show']);
        Route::put('/users/{id}', [AdminUserController::class, 'update']);
        Route::patch('/users/{id}/bloquer', [AdminUserController::class, 'setBlocked']);
        Route::delete('/users/{id}', [AdminUserController::class, 'destroy']);

        Route::get('/products', [AdminProductController::class, 'index']);
        Route::post('/products', [AdminProductController::class, 'store']);
        Route::put('/products/{id}', [AdminProductController::class, 'update']);
        Route::delete('/products/{id}', [AdminProductController::class, 'destroy']);

        Route::get('/categories', [AdminCategoryController::class, 'index']);
        Route::post('/categories', [AdminCategoryController::class, 'store']);
        Route::put('/categories/{id}', [AdminCategoryController::class, 'update']);
        Route::delete('/categories/{id}', [AdminCategoryController::class, 'destroy']);

        Route::get('/orders', [AdminOrderController::class, 'index']);
        Route::get('/orders/{id}', [AdminOrderController::class, 'show']);
        Route::patch('/orders/{id}/status', [AdminOrderController::class, 'updateStatus']);

        Route::get('/promo-codes', [AdminPromoCodeController::class, 'index']);
        Route::post('/promo-codes', [AdminPromoCodeController::class, 'store']);
        Route::put('/promo-codes/{id}', [AdminPromoCodeController::class, 'update']);
        Route::delete('/promo-codes/{id}', [AdminPromoCodeController::class, 'destroy']);

        Route::put('/homepage/slides', [AdminHomepageController::class, 'updateSlides']);
        Route::delete('/homepage/slides/{id}', [AdminHomepageController::class, 'destroySlide']);
        Route::put('/homepage/content', [AdminHomepageController::class, 'updateContent']);

        Route::get('/chat-logs', [AdminChatController::class, 'index']);
        Route::get('/contact-messages', [AdminContactController::class, 'index']);
        Route::patch('/contact-messages/{id}/status', [AdminContactController::class, 'updateStatus']);
        Route::post('/contact-messages/{id}/reply', [AdminContactController::class, 'reply']);
        Route::post('/uploads/image', [AdminUploadController::class, 'store']);
    });
});
