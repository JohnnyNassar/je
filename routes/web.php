<?php

use App\Http\Controllers\Admin\ImageCoverController;
use App\Http\Controllers\Admin\QuickAddController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('quick-add', [QuickAddController::class, 'show'])->name('quick-add');
    Route::post('quick-add', [QuickAddController::class, 'store'])->name('quick-add.store');

    Route::get('image-cover/{product}', [ImageCoverController::class, 'show'])->name('image-cover');
    Route::post('image-cover/{product}', [ImageCoverController::class, 'save'])->name('image-cover.save');
});

Route::get('/', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/products/{product}', [CatalogController::class, 'show'])->name('catalog.show');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/{product}', [CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/{line}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{line}', [CartController::class, 'destroy'])->name('cart.remove');
Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');

Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

Route::post('/coupon', [CheckoutController::class, 'applyCoupon'])->name('coupon.apply');
Route::delete('/coupon', [CheckoutController::class, 'removeCoupon'])->name('coupon.remove');

Route::get('/orders/{order}/confirmation', [OrderController::class, 'confirmation'])->name('orders.confirmation');

Route::get('/track', [\App\Http\Controllers\TrackOrderController::class, 'show'])->name('track.show');
Route::post('/track', [\App\Http\Controllers\TrackOrderController::class, 'lookup'])->name('track.lookup');

Route::view('/privacy', 'legal.privacy')->name('privacy');

// Customer auth (guest-accessible)
Route::middleware('guest:customer')->group(function () {
    Route::get('/login', [\App\Http\Controllers\CustomerAuth\LoginController::class, 'show'])->name('customer.login');
    Route::post('/login', [\App\Http\Controllers\CustomerAuth\LoginController::class, 'login']);
    Route::get('/register', [\App\Http\Controllers\CustomerAuth\RegisterController::class, 'show'])->name('customer.register');
    Route::post('/register', [\App\Http\Controllers\CustomerAuth\RegisterController::class, 'register']);
    Route::get('/forgot-password', [\App\Http\Controllers\CustomerAuth\PasswordResetController::class, 'showForgot'])->name('customer.password.request');
    Route::post('/forgot-password', [\App\Http\Controllers\CustomerAuth\PasswordResetController::class, 'sendResetLink'])->name('customer.password.email');
    Route::get('/reset-password/{token}', [\App\Http\Controllers\CustomerAuth\PasswordResetController::class, 'showReset'])->name('customer.password.reset');
    Route::post('/reset-password', [\App\Http\Controllers\CustomerAuth\PasswordResetController::class, 'reset'])->name('customer.password.update');
});

Route::middleware('auth:customer')->group(function () {
    Route::post('/logout', [\App\Http\Controllers\CustomerAuth\LoginController::class, 'logout'])->name('customer.logout');
    Route::get('/my-orders', [\App\Http\Controllers\MyOrdersController::class, 'index'])->name('my-orders.index');
    Route::get('/my-orders/{order}', [\App\Http\Controllers\MyOrdersController::class, 'show'])->name('my-orders.show');
});
