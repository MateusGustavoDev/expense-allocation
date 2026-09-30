{{-- Indicador numérico (KPI): <x-ui.stat label="Total rateado" value="R$ 48.392,17" hint="Despesas convertidas" icon="wallet" accent /> --}}
@props([
    'label',
    'value',
    'hint' => null,
    'icon' => null,
    'accent' => false,
])

<div {{ $attributes->class(['flex flex-col gap-3 rounded-xl border border-ds-gray-200 bg-ds-white p-5']) }}>
    <div class="flex items-center gap-2">
        @if ($icon)
            <span @class([
                'flex size-7 shrink-0 items-center justify-center rounded-md',
                'bg-ds-primary-100 text-ds-primary-700' => $accent,
                'bg-ds-gray-100 text-ds-gray-600' => ! $accent,
            ])>
                <x-ui.icon :name="$icon" class="size-4" />
            </span>
        @endif
        <p class="text-sm font-medium text-ds-gray-600">{{ $label }}</p>
    </div>

    <p class="text-3xl font-bold tracking-tight text-ds-gray-900 tabular-nums">{{ $value }}</p>

    @if ($hint)
        <p class="text-xs text-ds-gray-500">{{ $hint }}</p>
    @endif
</div>
