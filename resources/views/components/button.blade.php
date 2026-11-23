@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'disabled' => false,
])

@php
    $variantClasses = match ($variant) {
        'primary' => 'bg-brand-500 text-gray-900 hover:bg-brand-600 focus-visible:outline-brand-800',
        'secondary' => 'border border-brand-600 bg-white text-brand-700 hover:bg-brand-50 focus-visible:outline-brand-700',
        'ghost' => 'text-gray-700 hover:bg-gray-100 focus-visible:outline-gray-500',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 focus-visible:outline-red-700',
        default => 'bg-brand-500 text-gray-900 hover:bg-brand-600 focus-visible:outline-brand-800',
    };

    $sizeClasses = match ($size) {
        'sm' => 'px-3 py-1.5 text-sm',
        'lg' => 'px-6 py-3 text-base',
        default => 'px-4 py-2 text-sm',
    };
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->class([
        'inline-flex items-center justify-center gap-2 rounded-md font-medium whitespace-nowrap transition-colors',
        'focus-visible:outline-2 focus-visible:outline-offset-2',
        'disabled:cursor-not-allowed disabled:opacity-50',
        $variantClasses,
        $sizeClasses,
    ]) }}
    @if ($disabled) disabled @endif
>{{ $slot }}</button>
