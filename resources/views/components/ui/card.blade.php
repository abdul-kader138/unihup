@props([
    'hover' => false,
    'pad' => true,
])

<div {{ $attributes->class([
    'ui-card',
    'ui-card--pad' => $pad,
    'ui-card--hover' => $hover,
]) }}>
    {{ $slot }}
</div>
