{{-- PROTOTYPE — dispatches a face descriptor to the chosen variant (issue #8). --}}
@props([
    'face' => [],
    'variant' => 'A',
    'size' => 'md',
])

<x-dynamic-component
    :component="'prototype.tile-'.strtolower($variant)"
    :kind="$face['kind'] ?? 'number'"
    :suit="$face['suit'] ?? null"
    :number="$face['number'] ?? null"
    :wind="$face['wind'] ?? null"
    :dragon="$face['dragon'] ?? null"
    :variable="$face['variable'] ?? null"
    :size="$size"
    {{ $attributes }}
/>
