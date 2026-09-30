{{--
    Seletor de período com atalhos (Hoje, Últimos 7 dias, Este mês...) e calendário.
    O valor é ['from' => 'AAAA-MM-DD', 'to' => 'AAAA-MM-DD'].

    <x-ui.date-range-picker wire:model.live="period" label="Período" />      propriedade Livewire array $period
    <x-ui.date-range-picker name="period" :value="['from' => '2026-09-01', 'to' => '2026-09-30']" />

    Atalhos aplicam com um clique. No calendário, o 1º clique marca o início, o 2º o fim, e "Aplicar" confirma.
--}}
@props([
    'name' => null,
    'label' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
    'placeholder' => 'Selecione um período',
    'value' => null,
    'min' => null,
    'max' => null,
    'size' => 'md',
    'full' => false,
])

@php
    $wireModel = $attributes->wire('model')->value() ?: null;
    $name ??= $wireModel;
    $id = $attributes->get('id') ?? 'field-'.($name ? Str::slug(str_replace(['.', '[', ']'], '-', $name)) : Str::random(8));
    $error ??= ($name && isset($errors)) ? ($errors->first($name) ?: $errors->first("{$name}.from") ?: $errors->first("{$name}.to")) : null;

    $sizes = [
        'sm' => ['box' => 'h-8 gap-2 px-3 text-sm', 'icon' => 'size-3.5'],
        'md' => ['box' => 'h-10 gap-2 px-3 text-sm', 'icon' => 'size-4'],
        'lg' => ['box' => 'h-11 gap-2.5 px-4 text-base', 'icon' => 'size-5'],
    ];
    $sizeConfig = $sizes[$size] ?? throw new InvalidArgumentException("Tamanho de date-range-picker desconhecido: {$size}");

    $describedBy = $error ? "{$id}-error" : ($hint ? "{$id}-hint" : null);
@endphp

<x-ui.field :label="$label" :for="$id" :required="$required" :hint="$hint" :error="$error" :size="$size" {{ $attributes->only('class')->class(['w-full' => $full]) }}>
    <div
        x-data="dateRangePicker({ value: @js($value), min: @js($min), max: @js($max) })"
        x-modelable="value"
        {{ $attributes->whereStartsWith('wire:model') }}
        x-on:keydown.escape.prevent.stop="close(); $refs.trigger.focus()"
        @class(['relative', 'w-full' => $full, 'inline-flex' => ! $full])
    >
        <button
            type="button"
            id="{{ $id }}"
            x-ref="trigger"
            x-on:click="toggle()"
            aria-haspopup="dialog"
            x-bind:aria-expanded="open"
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if ($error) aria-invalid="true" @endif
            {{ $attributes->except(['class', 'id'])->whereDoesntStartWith('wire:model')->class([
                'flex w-full min-w-60 cursor-pointer items-center rounded-lg border bg-ds-white text-left transition-[border-color,box-shadow]',
                'focus:outline-none focus:ring-2 disabled:cursor-not-allowed disabled:opacity-60',
                $sizeConfig['box'],
                'border-ds-gray-300 focus:border-ds-primary-600 focus:ring-ds-primary-500/25' => ! $error,
                'border-ds-red-500 focus:border-ds-red-600 focus:ring-ds-red-500/25' => (bool) $error,
            ]) }}
        >
            <x-ui.icon name="calendar-range" @class(['shrink-0 text-ds-gray-500', $sizeConfig['icon']]) />
            <span class="flex-1 truncate tabular-nums" x-bind:class="value.from ? 'text-ds-gray-900' : 'text-ds-gray-500'" x-text="label() || @js($placeholder)">{{ $placeholder }}</span>
            <x-ui.icon name="chevron-down" @class(['shrink-0 text-ds-gray-500', $sizeConfig['icon']]) />
        </button>

        @if ($name && ! $wireModel)
            <input type="hidden" name="{{ $name }}[from]" x-bind:value="value.from">
            <input type="hidden" name="{{ $name }}[to]" x-bind:value="value.to">
        @endif

        {{-- Teleportado para o body: um card com overflow-hidden não recorta o calendário (≈ portal do React) --}}
        <template x-teleport="body">
            <div
                x-show="open"
                x-cloak
                x-anchor.bottom-start.offset.4="$refs.trigger"
                x-trap="open"
                x-on:click.outside="if (! $refs.trigger.contains($event.target)) close()"
                x-on:keydown.escape.prevent.stop="close(); $refs.trigger.focus()"
                x-transition.opacity.duration.100ms
                role="dialog"
                aria-label="Escolher período"
                class="z-50 flex overflow-hidden rounded-xl border border-ds-gray-200 bg-ds-white shadow-lg"
            >
                <div class="flex w-40 shrink-0 flex-col gap-0.5 border-r border-ds-gray-200 bg-ds-gray-50 p-2">
                    <p class="px-2 pt-1 pb-2 text-[11px] font-semibold tracking-wider text-ds-gray-500 uppercase">Período</p>
                    <template x-for="preset in presets" :key="preset.key">
                        <button
                            type="button"
                            x-on:click="applyPreset(preset)"
                            x-text="preset.label"
                            x-bind:aria-pressed="activePreset() === preset.key"
                            class="cursor-pointer rounded-md px-2 py-1.5 text-left text-sm transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ds-primary-600"
                            x-bind:class="activePreset() === preset.key ? 'bg-ds-primary-100 font-medium text-ds-primary-800' : 'text-ds-gray-700 hover:bg-ds-gray-100'"
                        ></button>
                    </template>
                </div>

                <div class="w-72 p-3">
                    <x-ui.calendar />

                    <div class="-mx-3 mt-3 flex items-center justify-between gap-2 border-t border-ds-gray-200 px-3 pt-3">
                        <x-ui.button variant="ghost" size="sm" x-on:click="clear()">Limpar</x-ui.button>
                        <x-ui.button size="sm" x-on:click="apply()" x-bind:disabled="! canApply()">Aplicar</x-ui.button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-ui.field>
