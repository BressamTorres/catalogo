<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;

#[Hidden(['catalog_api_token'])]
class Business extends Model
{
    protected $connection = 'erp_read';

    protected $table = 'businesses';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'catalog_api_token_expires_at' => 'datetime',
        ];
    }
}
