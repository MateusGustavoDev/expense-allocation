{{--
    Seletor de data com calendário em português. O valor é uma string AAAA-MM-DD.

    <x-ui.date-picker wire:model="form.date" label="Data da despesa" required full />
    <x-ui.date-picker name="date" value="2026-09-01" min="2026-01-01" />     formulário comum (input hidden)

    wire:model funciona como num input nativo (x-modelable). Esc, clique fora e escolher uma data fecham o calendário.
--}}
@props([
    'name' => null,
    'label' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
    'placeholder' => 'Selecione uma data',
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
    $error ??= ($name && isset($errors)) ? $errors->first($name) : null;

    $sizes = [
        'sm' => ['box' => 'h-8 gap-2 px-3 text-sm', 'icon' => 'size-3.5'],
        'md' => ['box' => 'h-10 gap-2 px-3 text-sm', 'icon' => 'size-4'],
        'lg' => ['box' => 'h-11 gap-2.5 px-4 text-base', 'icon' => 'size-5'],
    ];
    $sizeConfig = $sizes[$size] ?? throw new InvalidArgumentException("Tamanho de date-picker desconhecido: {$size}");

    $describedBy = $error ? "{$id}-error" : ($hint ? "{$id}-hint" : null);
@endphp

<x-ui.field :label="$label" :for="$id" :required="$required" :hint="$hint" :error="$error" :size="$size" {{ $attributes->only('class')->class(['w-full' => $full]) }}>
    <div
        x-data="datePicker({ value: @js($value ?? ($name ? old($name) : null)), min: @js($min), max: @js($max) })"
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
                'flex w-full min-w-44 cursor-pointer items-center rounded-lg border bg-ds-white text-left transition-[border-color,box-shadow]',
                'focus:outline-none focus:ring-2 disabled:cursor-not-allowed disabled:opacity-60',
                $sizeConfig['box'],
                'border-ds-gray-300 focus:border-ds-primary-600 focus:ring-ds-primary-500/25' => ! $error,
                'border-ds-red-500 focus:border-ds-red-600 focus:ring-ds-red-500/25' => (bool) $error,
            ]) }}
        >
            <x-ui.icon name="calendar" @class(['shrink-0 text-ds-gray-500', $sizeConfig['icon']]) />
            <span class="flex-1 truncate" x-bind:class="value ? 'text-ds-gray-900' : 'text-ds-gray-500'" x-text="label() || @js($placeholder)">{{ $placeholder }}</span>
        </button>

        @if ($name && ! $wireModel)
            <input type="hidden" name="{{ $name }}" x-bind:value="value">
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
                aria-label="Escolher data"
                class="z-50 w-72 rounded-xl border border-ds-gray-200 bg-ds-white p-3 shadow-lg"
            >
                <x-ui.calendar />

                <div class="-mx-3 mt-3 flex items-center justify-between border-t border-ds-gray-200 px-3 pt-3">
                    <x-ui.button variant="ghost" size="sm" x-on:click="clear()">Limpar</x-ui.button>
                    <x-ui.button variant="outline" size="sm" x-on:click="pickToday()">Hoje</x-ui.button>
                </div>
            </div>
        </template>
    </div>
</x-ui.field>
