<?php

namespace App\Services;

use Illuminate\Http\Request;

class CatalogAuthenticationRedirect
{
    public function __construct(private CatalogContext $catalog) {}

    public function destination(Request $request): string
    {
        $destination = trim((string) $request->input('return_to'));

        if ($destination === '' || ! str_starts_with($destination, '/')) {
            return route('products.index');
        }

        $parts = parse_url($destination);

        if ($parts === false || isset($parts['scheme']) || isset($parts['host'])) {
            return route('products.index');
        }

        $path = $parts['path'] ?? '';
        $catalogPath = '/'.$this->catalog->slug();

        if ($path !== $catalogPath && ! str_starts_with($path, $catalogPath.'/')) {
            return route('products.index');
        }

        return $destination;
    }
}
