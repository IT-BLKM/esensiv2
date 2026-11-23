@props([
    'variant' => 'neutral',
])

@php
    $variantClasses = match ($variant) {
        'brand' => 'bg-brand-100 text-brand-800',
        'info' => 'bg-info-100 text-info-800',
        'accent' => 'bg-accent-100 text-accent-800',
        'success' => 'bg-green-100 text-green-800',
        'warning' => 'bg-amber-100 text-amber-800',
        'danger' => 'bg-red-100 text-red-800',
        'neutral' => 'bg-gray-100 text-gray-700',
        default => 'bg-gray-100 text-gray-700',
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', $variantClasses]) }}>{{ $slot }}</span>
