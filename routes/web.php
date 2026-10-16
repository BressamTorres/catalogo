<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ProductController;
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
