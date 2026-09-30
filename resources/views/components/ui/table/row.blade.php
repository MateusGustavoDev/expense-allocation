{{-- Linha da tabela. Dentro de loops do Livewire, passe wire:key para o morph manter cada linha. --}}
<tr {{ $attributes->class(['transition-colors hover:bg-ds-gray-50']) }}>
    {{ $slot }}
</tr>
