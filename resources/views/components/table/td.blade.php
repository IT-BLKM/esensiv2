@props([
    'align' => 'left',
])

<td
    {{ $attributes->class([
        'px-4 py-3 text-gray-900',
        match ($align) {
            'center' => 'text-center',
            'right' => 'text-right',
            default => 'text-left',
        },
    ]) }}
>{{ $slot }}</td>
