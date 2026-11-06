@props([
    'href' => null,
    'size' => 'md',
])

@php
    $classes = [
        'inline-flex items-center justify-center gap-2.5 rounded-button border-2 border-rose bg-rose-pale font-title font-semibold text-ink shadow-button transition',
        'hover:bg-lavender hover:shadow-button-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-violet',
        'active:translate-y-1 active:shadow-none disabled:pointer-events-none disabled:opacity-50',
        'px-7 py-3 text-xl sm:text-[22px]' => $size === 'md',
        'px-7 py-4 text-xl sm:text-[22px]' => $size === 'lg',
        'px-4 py-2 text-base' => $size === 'sm',
    ];
@endphp

@if ($href)
    <a href="{{ $href }}" wire:navigate {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'button'])->class($classes) }}>{{ $slot }}</button>
@endif
