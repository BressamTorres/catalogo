<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseConnectionsTest extends TestCase
{
    public function test_erp_records_and_catalog_users_use_separate_connections(): void
    {
        $user = User::factory()->create(['email' => 'catalogo@example.test']);
        $business = Business::query()->findOrFail(1);

        $this->assertSame('mysql', $user->getConnectionName());
        $this->assertSame('erp_read', $business->getConnectionName());
        $this->assertDatabaseHas('users', ['email' => 'catalogo@example.test'], 'mysql');
        $this->assertDatabaseHas('businesses', ['id' => 1], 'erp_read');
        $this->assertFalse(Schema::connection('mysql')->hasTable('businesses'));
        $this->assertFalse(Schema::connection('erp_read')->hasTable('users'));
    }
}
