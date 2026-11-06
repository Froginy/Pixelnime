@props([
    'title',
    'controls' => 'all',
    'bodyClass' => '',
    'back' => null,
    'backClick' => null,
])

@php
    $hasBack = filled($back) || filled($backClick);
    $backClasses = 'window-back grid size-7 place-items-center rounded-control border-2 border-violet bg-lavender shadow-[0_2px_0_var(--color-violet)] transition hover:bg-violet focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ink active:translate-y-0.5 active:shadow-none';
@endphp

<section {{ $attributes->class('flex flex-col rounded-window bg-rose px-1.5 pb-1.5 shadow-window') }} aria-label="{{ $title }}">
    <div class="flex items-center gap-2 py-2 pr-0.5 pl-2.5">
        <svg class="size-4 shrink-0" viewBox="0 0 20 20" aria-hidden="true">
            <g class="fill-rose-pale">
                <rect x="6" y="6" width="8" height="8" rx="2"/>
                <rect x="8" y="1" width="4" height="4" rx="1"/>
                <rect x="8" y="15" width="4" height="4" rx="1"/>
                <rect x="1" y="8" width="4" height="4" rx="1"/>
                <rect x="15" y="8" width="4" height="4" rx="1"/>
            </g>
        </svg>

        <p class="min-w-0 flex-1 truncate text-lg text-ink">{{ $title }}</p>

        @if ($controls !== 'none')
            <div class="flex shrink-0 items-center gap-1.5">
                @if ($controls === 'all')
                    <span class="grid size-7 place-items-center rounded-control bg-rose-pale" aria-hidden="true">
                        <svg class="size-3 fill-ink" viewBox="0 0 15 15"><rect x="1.5" y="6" width="12" height="3"/></svg>
                    </span>
                    <span class="grid size-7 place-items-center rounded-control bg-rose-pale" aria-hidden="true">
                        <svg class="size-3 fill-ink" viewBox="0 0 15 15"><path fill-rule="evenodd" d="M1.5 1.5h12v12h-12z M4.5 4.5v6h6v-6z"/></svg>
                    </span>
                @endif

                @if ($hasBack)
                    @if (filled($back))
                        <a href="{{ $back }}" wire:navigate class="{{ $backClasses }}" aria-label="Retour" title="Retour">
                    @else
                        <button type="button" wire:click="{{ $backClick }}" class="{{ $backClasses }}" aria-label="Retour" title="Retour">
                    @endif
                        <svg class="size-3 fill-ink" viewBox="0 0 15 15" aria-hidden="true">
                            @foreach ([[0, 0], [1, 1], [2, 2], [3, 3], [4, 4], [4, 0], [3, 1], [1, 3], [0, 4]] as [$column, $row])
                                <rect x="{{ $column * 3 }}" y="{{ $row * 3 }}" width="3" height="3"/>
                            @endforeach
                        </svg>
                    @if (filled($back))
                        </a>
                    @else
                        </button>
                    @endif
                @endif
            </div>
        @endif
    </div>

    <div class="flex min-h-0 flex-1 flex-col gap-4 rounded-inner bg-rose-pale p-5 {{ $bodyClass }}">
        {{ $slot }}
    </div>
</section>
