<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';

    protected $casts = [
        'active' => 'boolean',
        'sale_price' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('business', function (Builder $query): void {
            $query->where(
                $query->qualifyColumn('business_id'),
                max((int) config('services.mini_erp.business_id'), 0),
            );
        });
    }

    public function imageUrl(): ?string
    {
        $photoPath = (string) ($this->photo_path ?? '');

        if ($photoPath === '') {
            return null;
        }

        if (
            str_starts_with($photoPath, '/')
            || str_contains($photoPath, '..')
            || preg_match('/^[a-z][a-z0-9+.-]*:\/\//i', $photoPath) === 1
        ) {
            return null;
        }

        $photoDisk = (string) ($this->photo_disk ?? 'public');
        $publicPhotoUrls = config('services.mini_erp.product_photo_urls', []);
        $publicBaseUrl = is_array($publicPhotoUrls)
            ? rtrim((string) ($publicPhotoUrls[$photoDisk] ?? ''), '/')
            : '';

        if ($publicBaseUrl === '') {
            return null;
        }

        return $publicBaseUrl.'/'.ltrim($photoPath, '/');
    }
}
