@props(['name' => 'User', 'background' => '0058be', 'color' => 'fff'])

@php
    $words = array_values(array_filter(preg_split('/\s+/', trim((string) $name)), fn ($w) => $w !== ''));
    $initials = '';
    if (count($words) > 0) {
        $initials = mb_strtoupper(mb_substr($words[0], 0, 1));
        if (count($words) > 1) {
            $initials .= mb_strtoupper(mb_substr($words[count($words) - 1], 0, 1));
        }
    }
@endphp

<svg {{ $attributes }} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" role="img" aria-label="{{ $name }}">
    <rect width="100" height="100" fill="#{{ $background }}"/>
    <text x="50" y="50" fill="#{{ $color }}" font-family="Inter, ui-sans-serif, system-ui, sans-serif" font-size="40" font-weight="600" text-anchor="middle" dominant-baseline="central">{{ $initials }}</text>
</svg>
