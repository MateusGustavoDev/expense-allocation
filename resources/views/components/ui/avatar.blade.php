{{--
    Avatar com as iniciais do nome: primeira letra do primeiro e do último nome ("Ana Souza" → AS);
    com um nome só, as duas primeiras letras ("Administrador" → AD).

    <x-ui.avatar :name="$user->name" />               decorativo: o nome já aparece ao lado
    <x-ui.avatar :name="$user->name" size="lg" labelled />   sozinho: vira imagem com o nome acessível
--}}
@props([
    'name',
    'size' => 'md',
    'labelled' => false,
])

@php
    $words = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $initials = match (true) {
        count($words) >= 2 => mb_substr($words[0], 0, 1).mb_substr($words[array_key_last($words)], 0, 1),
        count($words) === 1 => mb_substr($words[0], 0, 2),
        default => '?',
    };

    $sizes = [
        'sm' => 'size-7 text-xs',
        'md' => 'size-9 text-sm',
        'lg' => 'size-12 text-base',
    ];

    $sizeClass = $sizes[$size] ?? throw new InvalidArgumentException("Tamanho de avatar desconhecido: {$size}");
@endphp

<span
    @if ($labelled) role="img" aria-label="{{ $name }}" @else aria-hidden="true" @endif
    {{ $attributes->class(['inline-flex shrink-0 items-center justify-center rounded-full bg-ds-primary-100 font-semibold text-ds-primary-800 select-none', $sizeClass]) }}
>{{ mb_strtoupper($initials) }}</span>
