{{--
    Escolha única entre poucas opções, lado a lado (ex.: moeda BRL / USD).
    São radios nativos: navegação por setas, foco e leitores de tela funcionam sem JavaScript.

    <x-ui.segmented wire:model.live="form.currency" name="currency" label="Moeda" :options="['BRL' => 'BRL · Real', 'USD' => 'USD · Dólar']" />
--}}
@props([
    'name',
    'label' => null,
    'options' => [],
    'value' => null,
    'error' => null,
    'size' => 'md',
    'full' => true,
])

@php
    $error ??= isset($errors) ? $errors->first($attributes->wire('model')->value() ?: $name) : null;

    $sizes = [
        'sm' => ['track' => 'h-8 p-0.5', 'item' => 'px-2.5 text-xs'],
        'md' => ['track' => 'h-10 p-1', 'item' => 'px-3 text-sm'],
        'lg' => ['track' => 'h-11 p-1', 'item' => 'px-4 text-base'],
    ];
    $sizeConfig = $sizes[$size] ?? throw new InvalidArgumentException("Tamanho de segmented desconhecido: {$size}");
@endphp

<fieldset {{ $attributes->only('class')->class(['flex min-w-0 flex-col gap-1.5', 'w-full' => $full]) }}>
    @if ($label)
        <legend class="mb-1.5 text-sm font-medium text-ds-gray-700">{{ $label }}</legend>
    @endif

    <div @class(['flex gap-1 rounded-lg bg-ds-gray-100', $sizeConfig['track'], 'w-full' => $full, 'w-fit' => ! $full])>
        @foreach ($options as $optionValue => $optionLabel)
            <label @class(['min-w-0', 'flex-1' => $full])>
                <input
                    type="radio"
                    name="{{ $name }}"
                    value="{{ $optionValue }}"
                    class="peer sr-only"
                    @checked($value !== null && (string) $value === (string) $optionValue)
                    {{ $attributes->except('class') }}
                />
                <span @class([
                    'flex h-full cursor-pointer items-center justify-center rounded-md font-medium whitespace-nowrap text-ds-gray-600 transition',
                    'hover:text-ds-gray-900 peer-checked:bg-ds-white peer-checked:text-ds-gray-900 peer-checked:shadow-sm',
                    'peer-focus-visible:ring-2 peer-focus-visible:ring-ds-primary-600 peer-disabled:cursor-not-allowed peer-disabled:opacity-50',
                    $sizeConfig['item'],
                ])>{{ $optionLabel }}</span>
            </label>
        @endforeach
    </div>

    @if ($error)
        <p class="text-xs text-ds-red-700">{{ $error }}</p>
    @endif
</fieldset>
