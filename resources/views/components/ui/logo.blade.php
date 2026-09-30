{{--
    Marca da aplicação: o ícone de rateio sobre o quadrado laranja.

    <x-ui.logo />                            32px, cantos em 25% do lado (sidebar)
    <x-ui.logo size="xl" rounded="full" />   64px, circular
    <x-ui.logo label="Rateio" />             sozinha, sem texto ao lado: vira imagem com nome acessível

    Proporção fixa em qualquer tamanho: o ícone ocupa 9/16 do lado e o raio é uma porcentagem do lado
    (tokens --radius-mark-* em resources/css/app.css). Mudar o size escala o conjunto sem distorcer.
--}}
@props([
    'size' => 'md',
    'rounded' => 'md',
    'label' => null,
])

@php
    $sizes = [
        'sm' => 'size-6',
        'md' => 'size-8',
        'lg' => 'size-12',
        'xl' => 'size-16',
    ];

    $radii = [
        'none' => 'rounded-none',
        'sm' => 'rounded-mark-sm',
        'md' => 'rounded-mark-md',
        'lg' => 'rounded-mark-lg',
        'full' => 'rounded-full',
    ];

    $sizeClass = $sizes[$size] ?? throw new InvalidArgumentException("Tamanho de logo desconhecido: {$size}");
    $radiusClass = $radii[$rounded] ?? throw new InvalidArgumentException("Arredondamento de logo desconhecido: {$rounded}");
@endphp

<span
    @if ($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" @endif
    {{ $attributes->class(['inline-flex shrink-0 items-center justify-center bg-ds-primary-500 text-ds-black', $sizeClass, $radiusClass]) }}
>
    <x-ui.icon name="split" class="size-9/16" />
</span>
