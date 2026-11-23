@props([
    'variant' => 'info',
    'title' => null,
])

@php
    $variantClasses = match ($variant) {
        'success' => 'border-green-200 bg-green-50 text-green-800',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-800',
        'danger' => 'border-red-200 bg-red-50 text-red-800',
        'info' => 'border-info-200 bg-info-50 text-info-800',
        default => 'border-info-200 bg-info-50 text-info-800',
    };
@endphp

<div {{ $attributes->class(['rounded-md border px-4 py-3 text-sm', $variantClasses]) }}>
    @if ($title)
        <p class="font-medium">{{ $title }}</p>
        <div class="mt-1">{{ $slot }}</div>
    @else
        {{ $slot }}
    @endif
</div>
