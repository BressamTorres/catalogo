<?php

namespace Tests\Feature\Http\Controllers;

use App\Services\CatalogContext;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HealthCheckControllerTest extends TestCase
{
    public function test_public_healthcheck_returns_200_without_external_dependencies(): void
    {
        $queryCount = 0;
        DB::listen(function (QueryExecuted $query) use (&$queryCount): void {
            $queryCount++;
        });
        Http::preventStrayRequests();
        app(CatalogContext::class)->clear();

        $response = $this->getJson('/health');

        $response->assertOk()
            ->assertExactJson(['status' => 'ok']);
        $this->assertSame(0, $queryCount);
        $this->assertFalse($response->headers->has('Set-Cookie'));
        Http::assertNothingSent();
    }
}
