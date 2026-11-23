@props([
    'align' => 'left',
])

<th
    scope="col"
    {{ $attributes->class([
        'bg-gray-50 px-4 py-3 text-xs font-semibold tracking-wide text-gray-600 uppercase',
        match ($align) {
            'center' => 'text-center',
            'right' => 'text-right',
            default => 'text-left',
        },
    ]) }}
>{{ $slot }}</th>
