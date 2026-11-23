@props([
    'type' => 'text',
    'invalid' => false,
])

@php
    $stateClasses = $invalid
        ? 'border-red-500 focus:border-red-600 focus:ring-red-500/30'
        : 'border-gray-300 focus:border-brand-600 focus:ring-brand-600/30';
@endphp

<input
    type="{{ $type }}"
    {{ $attributes->class([
        'block w-full rounded-md border px-3 py-2 text-sm text-gray-900 shadow-xs',
        'placeholder:text-gray-400 focus:ring-2 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500',
        $stateClasses,
    ]) }}
/>
