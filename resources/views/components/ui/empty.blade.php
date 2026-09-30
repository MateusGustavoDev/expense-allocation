{{--
    Estado vazio: ausência de dados explicada, com próximo passo opcional.

    <x-ui.empty icon="receipt" title="Nenhuma despesa" description="Cadastre ou importe um CSV.">
        <x-slot:action><x-ui.button icon="plus">Nova despesa</x-ui.button></x-slot:action>
    </x-ui.empty>

    bare: sem borda nem fundo (usado dentro da tabela).
--}}
@props([
    'icon' => 'inbox',
    'title',
    'description' => null,
    'bare' => false,
])

<div {{ $attributes->class([
    'flex flex-col items-center justify-center gap-4 px-6 py-12 text-center',
    'rounded-xl border border-dashed border-ds-gray-300 bg-ds-white' => ! $bare,
]) }}>
    <span class="flex size-12 items-center justify-center rounded-full bg-ds-gray-100 text-ds-gray-500">
        <x-ui.icon :name="$icon" class="size-6" />
    </span>

    <div class="flex flex-col gap-1">
        <p class="text-sm font-semibold text-ds-gray-900">{{ $title }}</p>
        @if ($description)
            <p class="max-w-sm text-sm text-ds-gray-500">{{ $description }}</p>
        @endif
    </div>

    @isset($action)
        <div class="mt-1">{{ $action }}</div>
    @endisset
</div>
