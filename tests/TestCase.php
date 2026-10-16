<?php

namespace Tests;

use App\Models\Business;
use App\Services\CatalogContext;
use App\Services\CatalogCredentialCipher;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (
            config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:'
        ) {
            throw new RuntimeException('Os testes devem utilizar exclusivamente SQLite em memória.');
        }

        if (! Schema::hasTable('businesses')) {
            Schema::create('businesses', function (Blueprint $table): void {
                $table->id();
                $table->string('trade_name');
                $table->string('catalog_slug')->unique();
                $table->text('catalog_api_token')->nullable();
                $table->timestamp('catalog_api_token_expires_at')->nullable();
            });
        }

        DB::table('businesses')->insert([
            'id' => 1,
            'trade_name' => 'Catálogo Teste',
            'catalog_slug' => 'catalogo-teste',
            'catalog_api_token' => app(CatalogCredentialCipher::class)->encryptToken('catalog-token'),
            'catalog_api_token_expires_at' => now()->addDay(),
        ]);

        $business = Business::query()->findOrFail(1);
        app(CatalogContext::class)->set($business);
        URL::defaults(['catalog' => $business->catalog_slug]);
    }

    protected function tearDown(): void
    {
        if ($this->app?->bound(CatalogContext::class)) {
            app(CatalogContext::class)->clear();
        }

        parent::tearDown();
    }
}
