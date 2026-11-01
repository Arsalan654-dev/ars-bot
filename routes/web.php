<?php

use App\Http\Controllers\Admin\ClientController as AdminClientController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\MerchantAccountController;
use App\Http\Controllers\Admin\MyBotController;
use App\Http\Controllers\Admin\PageSectionController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\BotChatController;
use App\Http\Controllers\Api\WidgetController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BotPageController;
use App\Http\Controllers\Crm\BillingController;
use App\Http\Controllers\Crm\BotSettingsController;
use App\Http\Controllers\Crm\ClientController as CrmClientController;
use App\Http\Controllers\Crm\DashboardController as CrmDashboardController;
use App\Http\Controllers\Crm\KnowledgeController;
use App\Http\Controllers\OrderPaymentController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------
//  Public
// ---------------------------------------------------------
Route::get('/', [BotPageController::class, 'index'])->name('home');
Route::get('/bot/{key}', [BotPageController::class, 'page'])->name('bot.page');
Route::get('/embed/{key}', [BotPageController::class, 'embed'])->name('bot.embed');
Route::get('/widget.js', WidgetController::class)->name('widget');

// Public marketing pages (content managed by the super admin)
Route::get('/work', [PageController::class, 'work'])->name('pages.work');
Route::get('/agency', [PageController::class, 'agency'])->name('pages.agency');
Route::get('/careers', [PageController::class, 'careers'])->name('pages.careers');

// ---------------------------------------------------------
//  Bot API (X-Api-Key protected)
// ---------------------------------------------------------
Route::post('/api/bot/chat', BotChatController::class)
    ->middleware('bot.key')
    ->name('api.bot.chat');

// ---------------------------------------------------------
//  Payment (public link from email)
// ---------------------------------------------------------
Route::get('/pay/{reference}', [PaymentController::class, 'show'])->name('payment.show');
Route::get('/pay/{reference}/start', [PaymentController::class, 'pay'])->name('payment.pay');
Route::get('/pay/{reference}/bank', [PaymentController::class, 'bank'])->name('payment.bank');
Route::post('/pay/{reference}/bank-claim', [PaymentController::class, 'bankClaim'])->name('payment.bank-claim');
Route::get('/pay/{reference}/return', [PaymentController::class, 'return'])->name('payment.return');
Route::post('/pay/{reference}/confirm', [PaymentController::class, 'confirm'])->name('payment.confirm');

// ---------------------------------------------------------
//  Order payments (invoice links sent by the bot)
// ---------------------------------------------------------
Route::get('/invoice/{reference}', [OrderPaymentController::class, 'show'])->name('order.payment.show');
Route::get('/invoice/{reference}/start', [OrderPaymentController::class, 'pay'])->name('order.payment.pay');
Route::get('/invoice/{reference}/bank', [OrderPaymentController::class, 'bank'])->name('order.payment.bank');
Route::post('/invoice/{reference}/bank-claim', [OrderPaymentController::class, 'bankClaim'])->name('order.payment.bank-claim');
Route::get('/invoice/{reference}/return', [OrderPaymentController::class, 'return'])->name('order.payment.return');

// ---------------------------------------------------------
//  Auth
// ---------------------------------------------------------
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// ---------------------------------------------------------
//  Super Admin CRM
// ---------------------------------------------------------
Route::middleware(['auth', 'super.admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', AdminDashboardController::class)->name('dashboard');

        Route::resource('users', AdminUserController::class);
        Route::post('users/{user}/toggle-bot', [AdminUserController::class, 'toggleBot'])->name('users.toggle-bot');
        Route::post('users/{user}/mark-paid', [AdminUserController::class, 'markPaid'])->name('users.mark-paid');
        Route::post('users/{user}/resend-credentials', [AdminUserController::class, 'resendCredentials'])->name('users.resend-credentials');
        Route::post('users/{user}/regenerate-link', [AdminUserController::class, 'regeneratePaymentLink'])->name('users.regenerate-link');

        Route::get('clients', [AdminClientController::class, 'index'])->name('clients.index');
        Route::get('clients/{client}', [AdminClientController::class, 'show'])->name('clients.show');
        Route::delete('clients/{client}', [AdminClientController::class, 'destroy'])->name('clients.destroy');
        Route::post('clients/mark-all-read', [AdminClientController::class, 'markAllRead'])->name('clients.mark-all-read');

        Route::get('payments', [AdminPaymentController::class, 'index'])->name('payments.index');
        Route::delete('payments/{payment}', [AdminPaymentController::class, 'destroy'])->name('payments.destroy');

        // Merchant accounts (dynamic payment gateways)
        Route::get('merchants', [MerchantAccountController::class, 'index'])->name('merchants.index');
        Route::post('merchants', [MerchantAccountController::class, 'store'])->name('merchants.store');
        Route::put('merchants/{merchant}', [MerchantAccountController::class, 'update'])->name('merchants.update');
        Route::delete('merchants/{merchant}', [MerchantAccountController::class, 'destroy'])->name('merchants.destroy');
        Route::post('merchants/{merchant}/toggle', [MerchantAccountController::class, 'toggleActive'])->name('merchants.toggle');

        // Public page content (Work / Agency / Careers)
        Route::get('pages', [PageSectionController::class, 'index'])->name('pages.index');
        Route::post('pages', [PageSectionController::class, 'store'])->name('pages.store');
        Route::put('pages/{section}', [PageSectionController::class, 'update'])->name('pages.update');
        Route::delete('pages/{section}', [PageSectionController::class, 'destroy'])->name('pages.destroy');

        // My Bot — the platform owner's own bot
        Route::get('my-bot', [MyBotController::class, 'index'])->name('my-bot.index');
        Route::post('my-bot/knowledge', [MyBotController::class, 'storeKnowledge'])->name('my-bot.knowledge.store');
        Route::put('my-bot/knowledge/{entry}', [MyBotController::class, 'updateKnowledge'])->name('my-bot.knowledge.update');
        Route::delete('my-bot/knowledge/{entry}', [MyBotController::class, 'destroyKnowledge'])->name('my-bot.knowledge.destroy');

        Route::get('settings', [AdminSettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [AdminSettingsController::class, 'update'])->name('settings.update');
        Route::put('settings/bot', [AdminSettingsController::class, 'updateOwnBot'])->name('settings.bot');
        Route::post('settings/api-key', [AdminSettingsController::class, 'regenerateApiKey'])->name('settings.api-key');
    });

// ---------------------------------------------------------
//  Customer CRM
// ---------------------------------------------------------
Route::middleware('auth')
    ->prefix('crm')
    ->name('crm.')
    ->group(function (): void {
        // Reachable even when unpaid (shows "pay first" + payment link)
        Route::get('payment-required', [BillingController::class, 'paymentRequired'])->name('payment-required');
        Route::get('billing', [BillingController::class, 'index'])->name('billing.index');
        Route::post('billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
        Route::get('orders/{order}/invoice', [BotSettingsController::class, 'invoice'])->name('orders.invoice');
        Route::get('orders/{order}/artwork', [BotSettingsController::class, 'invoiceArtwork'])->name('orders.artwork');
    });

Route::middleware(['auth', 'subscribed'])
    ->prefix('crm')
    ->name('crm.')
    ->group(function (): void {
        Route::get('/', CrmDashboardController::class)->name('dashboard');

        Route::get('clients', [CrmClientController::class, 'index'])->name('clients.index');
        Route::get('clients/{client}', [CrmClientController::class, 'show'])->name('clients.show');
        Route::put('clients/{client}', [CrmClientController::class, 'update'])->name('clients.update');
        Route::delete('clients/{client}', [CrmClientController::class, 'destroy'])->name('clients.destroy');
        Route::get('conversations/{conversation}', [CrmClientController::class, 'conversation'])->name('conversations.show');

        Route::get('knowledge', [KnowledgeController::class, 'index'])->name('knowledge.index');
        Route::post('knowledge', [KnowledgeController::class, 'store'])->name('knowledge.store');
        Route::put('knowledge/{entry}', [KnowledgeController::class, 'update'])->name('knowledge.update');
        Route::delete('knowledge/{entry}', [KnowledgeController::class, 'destroy'])->name('knowledge.destroy');

        Route::get('bot', [BotSettingsController::class, 'edit'])->name('bot.edit');
        Route::put('bot', [BotSettingsController::class, 'update'])->name('bot.update');
        Route::post('bot/api-key', [BotSettingsController::class, 'regenerateApiKey'])->name('bot.api-key');
        Route::get('bot/embed', [BotSettingsController::class, 'embed'])->name('bot.embed');
    });
