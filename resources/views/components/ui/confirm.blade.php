{{--
    Confirmação de ação (especialmente destrutiva) antes de executá-la.

    <x-ui.confirm name="delete-unit" title="Excluir unidade" description="Esta ação não pode ser desfeita."
        confirm-label="Excluir" action="delete" danger />

    action: método do componente Livewire chamado ao confirmar (o botão mostra o loading enquanto executa).
    Sem action, o botão só dispara o evento confirmed-{name}, para uso com Alpine.
--}}
@props([
    'name',
    'title',
    'description' => null,
    'confirmLabel' => 'Confirmar',
    'cancelLabel' => 'Cancelar',
    'danger' => false,
    'action' => null,
    'show' => false,
])

@php
    $confirmAttributes = new Illuminate\View\ComponentAttributeBag(
        $action
            ? ['wire:click' => $action]
            : ['x-on:click' => "\$dispatch('confirmed-{$name}'); \$dispatch('close-modal', '{$name}')"]
    );
@endphp

<x-ui.modal :name="$name" :title="$title" :description="$description" :show="$show" max-width="sm" {{ $attributes }}>
    {{ $slot }}

    <x-slot:footer>
        <x-ui.button variant="outline" x-on:click="$dispatch('close-modal', '{{ $name }}')">{{ $cancelLabel }}</x-ui.button>
        <x-ui.button :variant="$danger ? 'danger' : 'primary'" :icon="$danger ? 'trash-2' : 'check'" :attributes="$confirmAttributes">{{ $confirmLabel }}</x-ui.button>
    </x-slot:footer>
</x-ui.modal>
