{{--
    Seleção nativa (<select>) com a aparência dos demais campos: acessível e sem JavaScript.

    <x-ui.select wire:model="form.currency" label="Moeda" :options="['BRL' => 'Real', 'USD' => 'Dólar']" />
    <x-ui.select wire:model.live="status" size="sm" placeholder="Todos os status" :options="$statuses" />

    options: [valor => rótulo]. Opções extras podem vir pelo slot. value marca a opção selecionada fora do Livewire.
--}}
@props([
    'name' => null,
    'label' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
    'options' => [],
    'placeholder' => null,
    'value' => null,
    'size' => 'md',
    'full' => false,
])

@php
    $name ??= $attributes->wire('model')->value() ?: null;
    $id = $attributes->get('id') ?? 'field-'.($name ? Str::slug(str_replace(['.', '[', ']'], '-', $name)) : Str::random(8));
    $error ??= ($name && isset($errors)) ? $errors->first($name) : null;

    $sizes = [
        'sm' => ['box' => 'h-8 pr-8 pl-3 text-sm', 'icon' => 'size-3.5 right-2.5'],
        'md' => ['box' => 'h-10 pr-9 pl-3 text-sm', 'icon' => 'size-4 right-3'],
        'lg' => ['box' => 'h-11 pr-10 pl-4 text-base', 'icon' => 'size-5 right-3.5'],
    ];
    $sizeConfig = $sizes[$size] ?? throw new InvalidArgumentException("Tamanho de select desconhecido: {$size}");

    $describedBy = $error ? "{$id}-error" : ($hint ? "{$id}-hint" : null);
    $selected = $value ?? ($name ? old($name) : null);
@endphp

<x-ui.field :label="$label" :for="$id" :required="$required" :hint="$hint" :error="$error" :size="$size" {{ $attributes->only('class')->class(['w-full' => $full]) }}>
    <div @class(['relative', 'w-full' => $full, 'inline-flex' => ! $full])>
        <select
            id="{{ $id }}"
            @if ($name && ! $attributes->has('name')) name="{{ $name }}" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if ($error) aria-invalid="true" @endif
            @required($required)
            {{ $attributes->except(['class', 'id'])->class([
                'block w-full min-w-0 cursor-pointer appearance-none rounded-lg border bg-ds-white text-ds-gray-900 transition-[border-color,box-shadow]',
                'focus:outline-none focus:ring-2',
                'disabled:cursor-not-allowed disabled:opacity-60',
                $sizeConfig['box'],
                'border-ds-gray-300 focus:border-ds-primary-600 focus:ring-ds-primary-500/25' => ! $error,
                'border-ds-red-500 focus:border-ds-red-600 focus:ring-ds-red-500/25' => (bool) $error,
            ]) }}
        >
            @if ($placeholder !== null)
                <option value="">{{ $placeholder }}</option>
            @endif
            @foreach ($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected($selected !== null && (string) $selected === (string) $optionValue)>{{ $optionLabel }}</option>
            @endforeach
            {{ $slot }}
        </select>

        <x-ui.icon name="chevron-down" @class(['pointer-events-none absolute top-1/2 -translate-y-1/2 text-ds-gray-500', $sizeConfig['icon']]) />
    </div>
</x-ui.field>
