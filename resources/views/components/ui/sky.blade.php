{{-- Animated pixel art backgrounds from the Figma file (« 03 — Peach horizon » and « 04 — Pixel night »). --}}
@php
    $randomizer = new Random\Randomizer(new Random\Engine\Mt19937(2026));

    $stars = collect(range(1, 70))
        ->map(fn (): array => [
            'x' => $randomizer->getInt(0, 1190),
            'y' => $randomizer->getInt(0, 330),
            'size' => [3, 4, 4, 5, 6, 8][$randomizer->getInt(0, 5)],
            'delay' => $randomizer->getInt(0, 60) / 10,
            'duration' => $randomizer->getInt(25, 60) / 10,
        ])
        ->reject(fn (array $star): bool => $star['x'] > 790 && $star['x'] < 1030 && $star['y'] > 60 && $star['y'] < 260)
        ->values();

    $sparkles = collect([[170, 125], [310, 220], [570, 105], [1050, 175], [990, 300], [90, 260], [430, 60], [680, 230], [1130, 90], [240, 40]])
        ->map(fn (array $position, int $index): array => [
            'x' => $position[0],
            'y' => $position[1],
            'delay' => $index * 0.7,
            'duration' => 3 + ($index % 3),
        ]);

    $cloud = 'M0 16h72v12H0z M12 8h44v8H12z M24 0h20v8H24z';
@endphp

<div {{ $attributes->class('pointer-events-none fixed inset-0 -z-10 overflow-hidden') }} aria-hidden="true">
    {{-- Jour : coucher de soleil --}}
    <svg class="size-full night:hidden" viewBox="0 0 1200 675" preserveAspectRatio="xMidYMax slice" shape-rendering="crispEdges" xmlns="http://www.w3.org/2000/svg">
        <rect width="1200" height="120" fill="#EED3E2"/>
        <rect y="120" width="1200" height="100" fill="#F2D6DE"/>
        <rect y="220" width="1200" height="80" fill="#F5D9D9"/>
        <rect y="300" width="1200" height="55" fill="#F8DFD6"/>
        <rect y="355" width="1200" height="90" fill="#FBE8D8"/>

        <g class="sky-glow" fill="#FFF3C9">
            <rect x="772" y="87" width="186" height="186"/>
            <rect x="812" y="57" width="106" height="30"/>
            <rect x="792" y="273" width="146" height="30"/>
            <rect x="742" y="127" width="30" height="106"/>
            <rect x="958" y="127" width="30" height="106"/>
        </g>
        <g class="sky-sun" fill="#FFF0B8">
            <rect x="790" y="105" width="150" height="150"/>
            <rect x="830" y="75" width="70" height="30"/>
            <rect x="810" y="255" width="110" height="30"/>
            <rect x="760" y="145" width="30" height="70"/>
            <rect x="940" y="145" width="30" height="70"/>
        </g>

        @foreach ([[70, 90, 0], [160, 140, -50], [250, 110, -20], [40, 170, -110]] as [$y, $duration, $delay])
            <g class="sky-cloud" style="--duration: {{ $duration }}s; --delay: {{ $delay }}s">
                <path d="{{ $cloud }}" transform="translate(0 {{ $y }})" fill="#FFF7F2" fill-opacity="0.85"/>
            </g>
        @endforeach

        @foreach ($sparkles as $sparkle)
            <rect class="sky-twinkle" x="{{ $sparkle['x'] }}" y="{{ $sparkle['y'] }}" width="8" height="8" fill="#FFF9E8" style="--delay: {{ $sparkle['delay'] }}s; --duration: {{ $sparkle['duration'] }}s"/>
        @endforeach

        <g fill="#E7B7B5">
            @foreach ([[57.75, 365, 1084.5], [41.25, 395, 1117.5], [24.75, 425, 1150.5], [8.25, 455, 1183.5], [8.25, 485, 1183.5], [24.75, 515, 1150.5], [41.25, 545, 1117.5], [57.75, 575, 1084.5]] as [$x, $y, $width])
                <rect x="{{ $x }}" y="{{ $y }}" width="{{ $width }}" height="32"/>
            @endforeach
            <rect y="390" width="330" height="285"/>
            <rect x="210" y="345" width="270" height="330"/>
            <rect x="420" y="405" width="330" height="270"/>
            <rect x="720" y="385" width="480" height="290"/>
        </g>
        <g fill="#D994A8">
            <rect y="500" width="1200" height="175"/>
            <rect y="465" width="260" height="210"/>
            <rect x="260" y="430" width="300" height="245"/>
            <rect x="560" y="465" width="250" height="210"/>
            <rect x="810" y="405" width="260" height="270"/>
            <rect x="1070" y="455" width="130" height="220"/>
        </g>
        <g fill="#BFA9D6">
            <rect y="585" width="1200" height="90"/>
            <rect y="555" width="240" height="30"/>
            <rect x="240" y="525" width="210" height="60"/>
            <rect x="450" y="555" width="280" height="30"/>
            <rect x="730" y="510" width="250" height="75"/>
            <rect x="980" y="555" width="220" height="30"/>
        </g>
        <g fill="#F8D9C6">
            <rect x="170" y="605" width="96" height="12"/>
            <rect x="610" y="570" width="72" height="12"/>
            <rect x="930" y="625" width="120" height="12"/>
        </g>
    </svg>

    {{-- Nuit : ciel étoilé --}}
    <svg class="hidden size-full night:block" viewBox="0 0 1200 675" preserveAspectRatio="xMidYMax slice" shape-rendering="crispEdges" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <g id="sky-moon-disc">
                <rect x="860" y="120" width="90" height="90"/>
                <rect x="875" y="105" width="60" height="15"/>
                <rect x="875" y="210" width="60" height="15"/>
                <rect x="845" y="135" width="15" height="60"/>
                <rect x="950" y="135" width="15" height="60"/>
            </g>
            <mask id="sky-moon-crescent">
                <use href="#sky-moon-disc" fill="white"/>
                <use href="#sky-moon-disc" fill="black" transform="translate(40 -30)"/>
            </mask>
        </defs>

        <rect width="1200" height="120" fill="#5F5F96"/>
        <rect y="120" width="1200" height="110" fill="#6A69A0"/>
        <rect y="230" width="1200" height="125" fill="#7776A9"/>
        <rect y="355" width="1200" height="90" fill="#9691C1"/>

        @foreach ($stars as $star)
            <rect class="sky-twinkle" x="{{ $star['x'] }}" y="{{ $star['y'] }}" width="{{ $star['size'] }}" height="{{ $star['size'] }}" fill="#FFF5D8" style="--delay: {{ $star['delay'] }}s; --duration: {{ $star['duration'] }}s"/>
        @endforeach

        @foreach ([[140, 100, 0], [470, 90, 1.5], [1040, 220, 3], [255, 190, 4.5], [650, 160, 2.2]] as [$x, $y, $delay])
            <g class="sky-twinkle" fill="#FFF5D8" style="--delay: {{ $delay }}s; --duration: 4s">
                <rect x="{{ $x }}" y="{{ $y }}" width="12" height="12"/>
                <rect x="{{ $x + 4 }}" y="{{ $y - 8 }}" width="4" height="8"/>
                <rect x="{{ $x + 4 }}" y="{{ $y + 12 }}" width="4" height="8"/>
                <rect x="{{ $x - 8 }}" y="{{ $y + 4 }}" width="8" height="4"/>
                <rect x="{{ $x + 12 }}" y="{{ $y + 4 }}" width="8" height="4"/>
            </g>
        @endforeach

        @foreach ([[180, 50, 0, 13], [620, 30, 6, 17]] as [$x, $y, $delay, $duration])
            <g class="sky-shooting-star" fill="#FFF5D8" style="--delay: {{ $delay }}s; --duration: {{ $duration }}s">
                <rect x="{{ $x }}" y="{{ $y }}" width="8" height="8"/>
                <rect x="{{ $x - 12 }}" y="{{ $y - 6 }}" width="6" height="6" fill-opacity="0.75"/>
                <rect x="{{ $x - 22 }}" y="{{ $y - 11 }}" width="5" height="5" fill-opacity="0.5"/>
                <rect x="{{ $x - 31 }}" y="{{ $y - 15 }}" width="4" height="4" fill-opacity="0.3"/>
            </g>
        @endforeach

        <g class="sky-glow" fill="#F8E9C7" fill-opacity="0.16">
            <rect x="840" y="100" width="130" height="130"/>
            <rect x="860" y="80" width="90" height="170"/>
            <rect x="820" y="120" width="170" height="90"/>
        </g>
        <use href="#sky-moon-disc" fill="#F8E9C7" mask="url(#sky-moon-crescent)"/>

        <g fill="#68658F">
            @foreach ([[57.75, 365, 1084.5], [41.25, 395, 1117.5], [24.75, 425, 1150.5], [8.25, 455, 1183.5], [8.25, 485, 1183.5], [24.75, 515, 1150.5], [41.25, 545, 1117.5], [57.75, 575, 1084.5]] as [$x, $y, $width])
                <rect x="{{ $x }}" y="{{ $y }}" width="{{ $width }}" height="32"/>
            @endforeach
            <rect y="390" width="330" height="285"/>
            <rect x="210" y="345" width="270" height="330"/>
            <rect x="420" y="405" width="330" height="270"/>
            <rect x="720" y="385" width="480" height="290"/>
        </g>
        <g fill="#8D83B8">
            <rect y="500" width="1200" height="175"/>
            <rect y="465" width="260" height="210"/>
            <rect x="260" y="430" width="300" height="245"/>
            <rect x="560" y="465" width="250" height="210"/>
            <rect x="810" y="405" width="260" height="270"/>
            <rect x="1070" y="455" width="130" height="220"/>
        </g>
        <g fill="#B7A4D2">
            <rect y="585" width="1200" height="90"/>
            <rect y="555" width="240" height="30"/>
            <rect x="240" y="525" width="210" height="60"/>
            <rect x="450" y="555" width="280" height="30"/>
            <rect x="730" y="510" width="250" height="75"/>
            <rect x="980" y="555" width="220" height="30"/>
        </g>
        <g fill="#DCC8E8">
            <rect x="170" y="605" width="96" height="12"/>
            <rect x="610" y="570" width="72" height="12"/>
            <rect x="930" y="625" width="120" height="12"/>
        </g>
    </svg>
</div>
