@props([
    'type' => 'player',
])

@php
    $isPlayer = $type === 'player';

    $color = match ($type) {
        'player' => 'bg-lavender text-ink',
        'wrong' => 'bg-danger-pale text-danger',
        'close' => 'bg-danger-pale text-danger',
        'right' => 'bg-mint text-success',
        default => 'bg-white text-ink',
    };
@endphp

<div {{ $attributes->class(['flex flex-col', 'items-end' => $isPlayer, 'items-start' => ! $isPlayer]) }}>
    <p class="max-w-[85%] rounded-bubble px-5 py-3 text-lg leading-snug {{ $color }}">{{ $slot }}</p>

    <span class="relative h-5 w-14" aria-hidden="true">
        <span @class(['absolute top-0 size-3.5 rounded-pixel', $color, 'left-5' => $isPlayer, 'left-[22px]' => ! $isPlayer])></span>
        <span @class(['absolute top-3 size-2 rounded-pixel', $color, 'left-[34px]' => $isPlayer, 'left-3.5' => ! $isPlayer])></span>
    </span>
</div>
