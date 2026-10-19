<?php

namespace App\Services;

use App\Models\Business;
use LogicException;

class CatalogContext
{
    private ?Business $business = null;

    public function set(Business $business): void
    {
        $this->business = $business;
    }

    public function clear(): void
    {
        $this->business = null;
    }

    public function business(): Business
    {
        if ($this->business === null) {
            throw new LogicException('O contexto do catálogo não foi definido.');
        }

        return $this->business;
    }

    public function id(): int
    {
        return (int) $this->business()->getKey();
    }

    public function slug(): string
    {
        return (string) $this->business()->catalog_slug;
    }

    public function cartSessionKey(): string
    {
        return "catalog.{$this->id()}.cart";
    }

    public function processedCartAdditionsSessionKey(): string
    {
        return "catalog.{$this->id()}.processed_cart_additions";
    }

    public function externalOrderSessionKey(): string
    {
        return "catalog.{$this->id()}.checkout.external_order_id";
    }

    public function completedOrderSessionKey(): string
    {
        return "catalog.{$this->id()}.checkout.completed_order";
    }
}
