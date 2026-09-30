{{--
    Moldura de um campo: rótulo, marcador de obrigatório, ajuda e erro.
    Usada pelos controles (input, select, textarea) e diretamente para controles customizados:

    <x-ui.field label="Rateio" for="allocations" :error="$errors->first('allocations')" required>...</x-ui.field>

    Erro substitui a ajuda. Os ids {for}-hint e {for}-error são os usados em aria-describedby.
--}}
@props([
    'label' => null,
    'for' => null,
    'required' => false,
    'hint' => null,
    'error' => null,
    'size' => 'md',
])

<div {{ $attributes->class(['flex flex-col gap-1.5']) }}>
    @if ($label)
        <label @if ($for) for="{{ $for }}" @endif @class([
            'font-medium text-ds-gray-700',
            'text-xs' => $size === 'sm',
            'text-sm' => $size !== 'sm',
        ])>
            {{ $label }}@if ($required)<span class="text-ds-red-600" aria-hidden="true"> *</span>@endif
        </label>
    @endif

    {{ $slot }}

    @if ($error)
        <p @if ($for) id="{{ $for }}-error" @endif class="text-xs text-ds-red-700">{{ $error }}</p>
    @elseif ($hint)
        <p @if ($for) id="{{ $for }}-hint" @endif class="text-xs text-ds-gray-500">{{ $hint }}</p>
    @endif
</div>
