<!DOCTYPE html>
<html lang="fr" data-theme="{{ session('theme', 'day') }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ?? config('app.name', 'Pixelnime') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-sky-day font-sans text-ink antialiased night:bg-sky-night">
        <x-ui.sky />

        <div class="mx-auto flex min-h-screen max-w-[1280px] flex-col gap-7 px-4 py-7 sm:px-8 lg:px-20">
            <header class="flex items-center justify-between gap-4">
                <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-3.5 rounded-button focus-visible:outline-3 focus-visible:outline-offset-4 focus-visible:outline-violet">
                    <x-ui.logo class="h-12 w-auto sm:h-14" />
                    <span class="font-title text-2xl font-semibold text-ink sm:text-[28px] night:text-rose-pale">PIXELNIME</span>
                </a>

                <livewire:theme-toggle />
            </header>

            <main wire:transition.navigate class="flex flex-1 flex-col justify-center pb-7">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
