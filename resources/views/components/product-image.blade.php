@props([
    'product',
    'wrapperClass' => 'aspect-square',
    'imageClass' => 'h-full w-full object-contain p-4',
])

@php($imageUrl = $product->imageUrl())

<div {{ $attributes->class([$wrapperClass, 'overflow-hidden bg-zinc-100']) }}
    @if ($imageUrl) x-data="{ imageFailed: false }" @endif>
    @if ($imageUrl)
        <img src="{{ $imageUrl }}" alt="{{ $product->name }}" loading="lazy" decoding="async"
            x-show="!imageFailed" x-on:error="imageFailed = true" class="{{ $imageClass }}">

        <div x-cloak x-show="imageFailed"
            class="flex h-full w-full flex-col items-center justify-center gap-2 px-3 text-center text-zinc-400">
            <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"
                aria-hidden="true">
                <rect x="3" y="4" width="18" height="16" rx="2"></rect>
                <circle cx="9" cy="10" r="2"></circle>
                <path d="m4 17 4-4 3 3 3-3 6 6"></path>
            </svg>
            <span class="text-xs font-medium">Imagem indisponível</span>
        </div>
    @else
        <div class="flex h-full w-full flex-col items-center justify-center gap-2 px-3 text-center text-zinc-400">
            <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"
                aria-hidden="true">
                <rect x="3" y="4" width="18" height="16" rx="2"></rect>
                <circle cx="9" cy="10" r="2"></circle>
                <path d="m4 17 4-4 3 3 3-3 6 6"></path>
            </svg>
            <span class="text-xs font-medium">Imagem indisponível</span>
        </div>
    @endif
</div>
