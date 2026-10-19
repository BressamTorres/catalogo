<?php

use App\Http\Controllers\Auth\AuthenticatedConsumerController;
use App\Http\Controllers\Auth\RegisteredConsumerController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ProductController;
use App\Http\Middleware\EnsureCatalogConsumerBelongsToBusiness;
use App\Http\Middleware\ResolveCatalogBusiness;
use Illuminate\Support\Facades\Route;

Route::get('/', [ProductController::class, 'missing']);

Route::prefix('{catalog}')
    ->where(['catalog' => '[a-z0-9-]+'])
    ->middleware(ResolveCatalogBusiness::class)
    ->group(function (): void {
        Route::get('/', [ProductController::class, 'index'])
            ->name('home');

        Route::get('/produtos', [ProductController::class, 'index'])
            ->name('products.index');

        Route::get('/entrar', [AuthenticatedConsumerController::class, 'create'])
            ->name('login');
        Route::post('/entrar', [AuthenticatedConsumerController::class, 'store'])
            ->middleware('throttle:catalog-login')
            ->name('login.store');
        Route::post('/sair', [AuthenticatedConsumerController::class, 'destroy'])
            ->middleware('auth')
            ->name('logout');

        Route::get('/criar-conta', [RegisteredConsumerController::class, 'create'])
            ->name('register');
        Route::post('/criar-conta', [RegisteredConsumerController::class, 'store'])
            ->middleware('throttle:catalog-registration')
            ->name('register.store');

        Route::middleware(['auth', EnsureCatalogConsumerBelongsToBusiness::class])
            ->group(function (): void {
                Route::post('/carrinho/adicionar', [CartController::class, 'add'])
                    ->name('cart.add');

                Route::get('/carrinho', [CartController::class, 'index'])
                    ->name('cart.index');

                Route::post('/carrinho/atualizar', [CartController::class, 'update'])
                    ->name('cart.update');

                Route::get('/checkout', [CheckoutController::class, 'index'])
                    ->name('checkout.index');

                Route::post('/checkout', [CheckoutController::class, 'store'])
                    ->name('checkout.store');

                Route::get('/pedido/concluido', [CheckoutController::class, 'completed'])
                    ->name('checkout.completed');
            });
    });
