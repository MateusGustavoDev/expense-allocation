{{--
    Ícone Lucide pelo nome (https://lucide.dev/icons): <x-ui.icon name="plus" class="size-4" />
    Decorativo por padrão (aria-hidden): o texto ao lado ou o aria-label do botão descreve a ação.
--}}
@props(['name'])

<x-dynamic-component :component="'lucide-'.$name" {{ $attributes->merge(['aria-hidden' => 'true']) }} />
