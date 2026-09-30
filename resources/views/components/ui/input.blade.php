{{--
    Campo de texto com rótulo, ícone, senha, ajuda e erro.

    <x-ui.input wire:model="form.description" label="Descrição" required full />
    <x-ui.input wire:model.live.debounce.300ms="search" icon="search" size="sm" placeholder="Buscar..." />
    <x-ui.input name="password" label="Senha" password full />
    <x-ui.input wire:model="form.slug" label="Slug" mono full />

    - name vem do atributo name ou do wire:model; o erro é lido de $errors por esse nome.
    - class vai para o invólucro (layout: w-64, col-span-2); os demais atributos vão para o <input>.
    - mono: fonte monoespaçada no valor (identificadores como slug).
--}}
@props([
    'name' => null,
    'label' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
    'icon' => null,
    'iconDirection' => 'left',
    'password' => false,
    'size' => 'md',
    'variant' => 'default',
    'full' => false,
    'mono' => false,
])

@php
    $name ??= $attributes->wire('model')->value() ?: null;
    $id = $attributes->get('id') ?? 'field-'.($name ? Str::slug(str_replace(['.', '[', ']'], '-', $name)) : Str::random(8));
    $error ??= ($name && isset($errors)) ? $errors->first($name) : null;

    $sizes = [
        'sm' => ['box' => 'h-8 px-3 text-sm', 'icon' => 'size-3.5', 'left' => 'left-2.5', 'right' => 'right-2.5', 'padLeft' => 'pl-8', 'padRight' => 'pr-8'],
        'md' => ['box' => 'h-10 px-3 text-sm', 'icon' => 'size-4', 'left' => 'left-3', 'right' => 'right-3', 'padLeft' => 'pl-9', 'padRight' => 'pr-10'],
        'lg' => ['box' => 'h-11 px-4 text-base', 'icon' => 'size-5', 'left' => 'left-3.5', 'right' => 'right-3.5', 'padLeft' => 'pl-11', 'padRight' => 'pr-11'],
    ];
    $sizeConfig = $sizes[$size] ?? throw new InvalidArgumentException("Tamanho de input desconhecido: {$size}");

    // Em campo de senha o ícone customizado vai à esquerda: a direita é do botão de mostrar/ocultar
    $iconSide = $password ? 'left' : $iconDirection;

    $describedBy = $error ? "{$id}-error" : ($hint ? "{$id}-hint" : null);
@endphp

<x-ui.field :label="$label" :for="$id" :required="$required" :hint="$hint" :error="$error" :size="$size" {{ $attributes->only('class')->class(['w-full' => $full]) }}>
    <div @class(['relative', 'w-full' => $full, 'inline-flex' => ! $full]) @if ($password) x-data="{ visible: false }" @endif>
        @if ($icon)
            <x-ui.icon :name="$icon" @class([
                'pointer-events-none absolute top-1/2 -translate-y-1/2 text-ds-gray-500',
                $sizeConfig['icon'],
                $sizeConfig[$iconSide],
            ]) />
        @endif

        <input
            id="{{ $id }}"
            @if ($name && ! $attributes->has('name')) name="{{ $name }}" @endif
            @if ($password) x-bind:type="visible ? 'text' : 'password'" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if ($error) aria-invalid="true" @endif
            @required($required)
            {{ $attributes->except(['class', 'id'])->merge(['type' => $password ? 'password' : 'text'])->class([
                'block w-full min-w-0 rounded-lg border text-ds-gray-900 placeholder:text-ds-gray-500 transition-[border-color,box-shadow]',
                'focus:outline-none focus:ring-2',
                'disabled:cursor-not-allowed disabled:opacity-60 read-only:bg-ds-gray-50',
                $sizeConfig['box'],
                'font-mono' => $mono,
                'bg-ds-white' => $variant === 'default',
                'bg-ds-gray-50' => $variant === 'soft',
                'border-ds-gray-300 focus:border-ds-primary-600 focus:ring-ds-primary-500/25' => ! $error,
                'border-ds-red-500 focus:border-ds-red-600 focus:ring-ds-red-500/25' => (bool) $error,
                $sizeConfig['padLeft'] => $icon && $iconSide === 'left',
                $sizeConfig['padRight'] => ($icon && $iconSide === 'right') || $password,
            ]) }}
        />

        @if ($password)
            <button
                type="button"
                x-on:click="visible = ! visible"
                x-bind:aria-label="visible ? 'Ocultar senha' : 'Mostrar senha'"
                x-bind:aria-pressed="visible"
                aria-controls="{{ $id }}"
                class="absolute top-1/2 right-1.5 -translate-y-1/2 cursor-pointer rounded-md p-1 text-ds-gray-500 hover:bg-ds-gray-100 hover:text-ds-gray-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ds-primary-600"
            >
                <x-ui.icon name="eye" @class([$sizeConfig['icon']]) x-show="! visible" />
                <x-ui.icon name="eye-off" @class([$sizeConfig['icon']]) x-show="visible" x-cloak />
            </button>
        @endif
    </div>
</x-ui.field>
