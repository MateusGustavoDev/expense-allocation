{{-- Texto longo: <x-ui.textarea wire:model="notes" label="Observações" rows="4" full /> --}}
@props([
    'name' => null,
    'label' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
    'size' => 'md',
    'full' => false,
])

@php
    $name ??= $attributes->wire('model')->value() ?: null;
    $id = $attributes->get('id') ?? 'field-'.($name ? Str::slug(str_replace(['.', '[', ']'], '-', $name)) : Str::random(8));
    $error ??= ($name && isset($errors)) ? $errors->first($name) : null;

    $sizes = [
        'sm' => 'min-h-16 px-3 py-1.5 text-sm',
        'md' => 'min-h-20 px-3 py-2 text-sm',
        'lg' => 'min-h-28 px-4 py-3 text-base',
    ];

    $describedBy = $error ? "{$id}-error" : ($hint ? "{$id}-hint" : null);
@endphp

<x-ui.field :label="$label" :for="$id" :required="$required" :hint="$hint" :error="$error" :size="$size" {{ $attributes->only('class')->class(['w-full' => $full]) }}>
    <textarea
        id="{{ $id }}"
        @if ($name && ! $attributes->has('name')) name="{{ $name }}" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        @if ($error) aria-invalid="true" @endif
        @required($required)
        {{ $attributes->except(['class', 'id'])->merge(['rows' => 3])->class([
            'block w-full rounded-lg border bg-ds-white text-ds-gray-900 placeholder:text-ds-gray-500 transition-[border-color,box-shadow]',
            'focus:outline-none focus:ring-2 disabled:cursor-not-allowed disabled:opacity-60',
            $sizes[$size] ?? throw new InvalidArgumentException("Tamanho de textarea desconhecido: {$size}"),
            'border-ds-gray-300 focus:border-ds-primary-600 focus:ring-ds-primary-500/25' => ! $error,
            'border-ds-red-500 focus:border-ds-red-600 focus:ring-ds-red-500/25' => (bool) $error,
        ]) }}
    >{{ $slot }}</textarea>
</x-ui.field>
