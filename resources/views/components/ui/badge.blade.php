{{--
    Rótulo curto de status ou categoria.

    <x-ui.badge variant="success" dot>Convertida</x-ui.badge>
    <x-ui.badge variant="mono">unidade-a</x-ui.badge>
    <x-ui.badge variant="info" icon="dollar-sign" size="sm">USD</x-ui.badge>
--}}
@props([
    'variant' => 'neutral',
    'size' => 'md',
    'icon' => null,
    'dot' => false,
])

@php
    // Fundo 100 + texto 800: contraste acima de 6:1 em todas as cores
    $variants = [
        'neutral' => ['box' => 'border-transparent bg-ds-gray-100 text-ds-gray-700', 'dot' => 'bg-ds-gray-500'],
        'primary' => ['box' => 'border-transparent bg-ds-primary-100 text-ds-primary-800', 'dot' => 'bg-ds-primary-600'],
        'success' => ['box' => 'border-transparent bg-ds-green-100 text-ds-green-800', 'dot' => 'bg-ds-green-600'],
        'warning' => ['box' => 'border-transparent bg-ds-yellow-100 text-ds-yellow-800', 'dot' => 'bg-ds-yellow-600'],
        'danger' => ['box' => 'border-transparent bg-ds-red-100 text-ds-red-800', 'dot' => 'bg-ds-red-600'],
        'info' => ['box' => 'border-transparent bg-ds-blue-100 text-ds-blue-800', 'dot' => 'bg-ds-blue-600'],
        'outline' => ['box' => 'border-ds-gray-300 bg-ds-white text-ds-gray-700', 'dot' => 'bg-ds-gray-500'],
        'mono' => ['box' => 'border-ds-gray-200 bg-ds-gray-100 font-mono font-medium text-ds-gray-700', 'dot' => 'bg-ds-gray-500'],
    ];

    $sizes = [
        'sm' => ['box' => 'gap-1 px-1.5 py-px text-[11px]', 'icon' => 'size-3', 'dot' => 'size-1.5'],
        'md' => ['box' => 'gap-1.5 px-2 py-0.5 text-xs', 'icon' => 'size-3.5', 'dot' => 'size-1.5'],
        'lg' => ['box' => 'gap-1.5 px-2.5 py-1 text-sm', 'icon' => 'size-4', 'dot' => 'size-2'],
    ];

    $variantConfig = $variants[$variant] ?? throw new InvalidArgumentException("Variante de badge desconhecida: {$variant}");
    $sizeConfig = $sizes[$size] ?? throw new InvalidArgumentException("Tamanho de badge desconhecido: {$size}");
@endphp

<span {{ $attributes->class([
    'inline-flex w-fit items-center rounded-full border font-semibold whitespace-nowrap',
    $variantConfig['box'],
    $sizeConfig['box'],
]) }}>
    @if ($dot)
        <span @class(['shrink-0 rounded-full', $variantConfig['dot'], $sizeConfig['dot']]) aria-hidden="true"></span>
    @elseif ($icon)
        <x-ui.icon :name="$icon" @class(['shrink-0', $sizeConfig['icon']]) />
    @endif
    {{ $slot }}
</span>
