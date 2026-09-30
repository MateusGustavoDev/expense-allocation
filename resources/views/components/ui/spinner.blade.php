{{-- Indicador de carregamento. role="status" + texto sr-only para leitores de tela. --}}
@props([
    'size' => 'md',
    'label' => 'Carregando',
])

@php
    $sizes = [
        'sm' => 'size-4',
        'md' => 'size-6',
        'lg' => 'size-8',
    ];
@endphp

<span role="status" {{ $attributes->class(['inline-flex items-center justify-center text-ds-gray-500']) }}>
    <x-ui.icon name="loader-circle" @class(['animate-spin', $sizes[$size] ?? throw new InvalidArgumentException("Tamanho de spinner desconhecido: {$size}")]) />
    <span class="sr-only">{{ $label }}</span>
</span>
