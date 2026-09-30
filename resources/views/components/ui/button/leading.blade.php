{{--
    Ícone do botão (uso interno de x-ui.button). Mostra o spinner no lugar do ícone quando:
    - loading = true (controle manual), ou
    - há um wire:target e o Livewire está processando essa ação.
--}}
@props([
    'icon' => null,
    'loading' => false,
    'target' => null,
    'sizeClass',
])

@if ($loading)
    <x-ui.icon name="loader-circle" class="{{ $sizeClass }} shrink-0 animate-spin" />
@elseif ($target)
    @if ($icon)
        <x-ui.icon :name="$icon" class="{{ $sizeClass }} shrink-0" wire:loading.remove wire:target="{{ $target }}" />
    @endif
    <x-ui.icon name="loader-circle" class="{{ $sizeClass }} shrink-0 animate-spin" wire:loading wire:target="{{ $target }}" />
@elseif ($icon)
    <x-ui.icon :name="$icon" class="{{ $sizeClass }} shrink-0" />
@endif
