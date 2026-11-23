@props([
    'title' => null,
    'subtitle' => null,
])

<div {{ $attributes->class(['overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm']) }}>
    @if ($title)
        <div class="border-b border-gray-200 px-4 py-3">
            <h2 class="text-sm font-semibold text-gray-900">{{ $title }}</h2>
            @if ($subtitle)
                <p class="mt-0.5 text-xs text-gray-500">{{ $subtitle }}</p>
            @endif
        </div>
    @endif

    <div class="p-4">{{ $slot }}</div>
</div>
