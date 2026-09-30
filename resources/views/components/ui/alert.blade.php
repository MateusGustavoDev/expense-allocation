{{--
    Mensagem em destaque dentro da página (callout).

    <x-ui.alert variant="warning" title="Total incompleto">3 despesas aguardam conversão.</x-ui.alert>
    <x-ui.alert variant="info">Texto <x-slot:actions><a href="#">Ver</a></x-slot:actions></x-ui.alert>

    Avisos e erros usam role="alert" (anunciados na hora); informação e sucesso, role="status".
--}}
@props([
    'variant' => 'info',
    'title' => null,
    'icon' => null,
])

@php
    $variants = [
        'info' => ['box' => 'border-ds-blue-200 bg-ds-blue-50 text-ds-blue-900', 'icon' => 'text-ds-blue-700', 'default' => 'info', 'role' => 'status'],
        'success' => ['box' => 'border-ds-green-200 bg-ds-green-50 text-ds-green-900', 'icon' => 'text-ds-green-700', 'default' => 'circle-check', 'role' => 'status'],
        'warning' => ['box' => 'border-ds-yellow-200 bg-ds-yellow-50 text-ds-yellow-900', 'icon' => 'text-ds-yellow-800', 'default' => 'triangle-alert', 'role' => 'alert'],
        'danger' => ['box' => 'border-ds-red-200 bg-ds-red-50 text-ds-red-900', 'icon' => 'text-ds-red-700', 'default' => 'circle-alert', 'role' => 'alert'],
    ];

    $config = $variants[$variant] ?? throw new InvalidArgumentException("Variante de alerta desconhecida: {$variant}");
@endphp

<div role="{{ $config['role'] }}" {{ $attributes->class(['flex items-start gap-3 rounded-lg border px-4 py-3 text-sm', $config['box']]) }}>
    <x-ui.icon :name="$icon ?? $config['default']" @class(['mt-0.5 size-4.5 shrink-0', $config['icon']]) />

    <div class="flex min-w-0 flex-1 flex-col gap-0.5 leading-relaxed">
        @if ($title)
            <p class="font-semibold">{{ $title }}</p>
        @endif
        <div>{{ $slot }}</div>
    </div>

    @isset($actions)
        <div class="flex shrink-0 items-center gap-2 font-semibold">{{ $actions }}</div>
    @endisset
</div>
