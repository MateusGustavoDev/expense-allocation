{{-- Caixa de seleção nativa com rótulo clicável: <x-ui.checkbox name="remember" label="Manter conectado" /> --}}
@props([
    'name' => null,
    'label',
    'hint' => null,
])

@php
    $name ??= $attributes->wire('model')->value() ?: null;
    $id = $attributes->get('id') ?? 'field-'.($name ? Str::slug(str_replace(['.', '[', ']'], '-', $name)) : Str::random(8));
@endphp

<div {{ $attributes->only('class')->class(['flex items-start gap-2.5']) }}>
    <input
        type="checkbox"
        id="{{ $id }}"
        @if ($name && ! $attributes->has('name')) name="{{ $name }}" @endif
        @if ($hint) aria-describedby="{{ $id }}-hint" @endif
        {{ $attributes->except(['class', 'id'])->class([
            'mt-0.5 size-4 shrink-0 cursor-pointer rounded border-ds-gray-400 accent-ds-primary-600',
            'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ds-primary-600 focus-visible:ring-offset-2',
            'disabled:cursor-not-allowed disabled:opacity-50',
        ]) }}
    />
    <div class="flex flex-col gap-0.5">
        <label for="{{ $id }}" class="cursor-pointer text-sm text-ds-gray-700">{{ $label }}</label>
        @if ($hint)
            <p id="{{ $id }}-hint" class="text-xs text-ds-gray-500">{{ $hint }}</p>
        @endif
    </div>
</div>
