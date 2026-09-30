{{--
    Menu suspenso (ex.: ações da linha da tabela).

    <x-ui.dropdown>
        <x-slot:trigger>
            <x-ui.button variant="ghost" size="sm" icon="ellipsis" icon-only aria-label="Ações da despesa" />
        </x-slot:trigger>
        <x-ui.dropdown.item icon="eye" href="...">Ver detalhes</x-ui.dropdown.item>
        <x-ui.dropdown.separator />
        <x-ui.dropdown.item icon="trash-2" danger wire:click="...">Excluir</x-ui.dropdown.item>
    </x-ui.dropdown>

    O menu é teleportado para o body e ancorado ao gatilho: não é recortado pelo card da tabela.
    Fecha ao escolher um item, ao clicar fora e com Esc. aria-expanded acompanha o estado.
--}}
@props([
    'align' => 'right',
    'width' => 'w-52',
])

<div
    x-data="{ open: false }"
    x-on:keydown.escape.window="open = false"
    {{ $attributes->class(['relative inline-block text-left']) }}
>
    <div x-ref="trigger" x-on:click="open = ! open" x-effect="$el.querySelector('button, a')?.setAttribute('aria-expanded', open)">
        {{ $trigger }}
    </div>

    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            x-anchor.{{ $align === 'left' ? 'bottom-start' : 'bottom-end' }}.offset.4="$refs.trigger"
            x-on:click.outside="if (! $refs.trigger.contains($event.target)) open = false"
            x-on:click="open = false"
            x-transition:enter="transition duration-100 ease-out"
            x-transition:enter-start="scale-95 opacity-0"
            x-transition:enter-end="scale-100 opacity-100"
            x-transition:leave="transition duration-75 ease-in"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            role="menu"
            @class(['z-40 flex flex-col gap-0.5 rounded-lg border border-ds-gray-200 bg-ds-white p-1 shadow-lg', $width])
        >
            {{ $slot }}
        </div>
    </template>
</div>
