@props([
    'active' => false,
])

<button
    type="button"
    aria-pressed="{{ $active ? 'true' : 'false' }}"
    {{ $attributes->class([
        'rounded-bubble border-2 px-3.5 py-1.5 text-base text-ink transition focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-violet',
        'border-violet bg-lavender' => $active,
        'border-rose bg-white hover:bg-lavender/50' => ! $active,
    ]) }}
>{{ $slot }}</button>
