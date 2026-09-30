{{--
    Janela modal controlada por nome, aberta por evento:
    - Alpine:   x-on:click="$dispatch('open-modal', 'new-unit')"
    - Livewire: $this->dispatch('open-modal', name: 'new-unit')   (e 'close-modal' para fechar)

    <x-ui.modal name="new-unit" title="Nova unidade" description="...">
        ...conteúdo...
        <x-slot:footer>...botões...</x-slot:footer>
    </x-ui.modal>

    Acessibilidade: role="dialog", aria-modal, título/descrição ligados por aria-labelledby/describedby,
    foco preso dentro da janela (x-trap), Esc e clique fora fecham, rolagem da página bloqueada.
--}}
@props([
    'name',
    'title',
    'description' => null,
    'maxWidth' => 'md',
    'show' => false,
])

@php
    $widths = [
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
    ];
    $id = 'modal-'.Str::slug($name);
@endphp

<div
    x-data="{ open: @js($show) }"
    x-on:open-modal.window="if (($event.detail?.name ?? $event.detail) === @js($name)) open = true"
    x-on:close-modal.window="if (($event.detail?.name ?? $event.detail) === @js($name)) open = false"
    x-on:keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ $id }}-title"
    @if ($description) aria-describedby="{{ $id }}-description" @endif
>
    <div x-show="open" x-transition.opacity class="fixed inset-0 bg-ds-black/50" x-on:click="open = false"></div>

    <div
        x-show="open"
        x-trap.inert.noscroll="open"
        x-transition:enter="transition duration-150 ease-out"
        x-transition:enter-start="translate-y-2 scale-95 opacity-0"
        x-transition:enter-end="translate-y-0 scale-100 opacity-100"
        x-transition:leave="transition duration-100 ease-in"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        {{ $attributes->class(['relative flex max-h-[90vh] w-full flex-col overflow-hidden rounded-2xl bg-ds-white shadow-xl', $widths[$maxWidth] ?? throw new InvalidArgumentException("Largura de modal desconhecida: {$maxWidth}")]) }}
    >
        <header @class(['flex flex-col gap-1 px-6 pt-6 pr-14', 'pb-4' => $slot->isNotEmpty(), 'pb-6' => $slot->isEmpty()])>
            <h2 id="{{ $id }}-title" class="text-lg font-semibold text-ds-gray-900">{{ $title }}</h2>
            @if ($description)
                <p id="{{ $id }}-description" class="text-sm text-ds-gray-500">{{ $description }}</p>
            @endif
        </header>

        @if ($slot->isNotEmpty())
            <div class="overflow-y-auto px-6 pb-6">{{ $slot }}</div>
        @endif

        @isset($footer)
            <footer class="flex flex-col-reverse gap-3 border-t border-ds-gray-200 bg-ds-gray-50 px-6 py-4 sm:flex-row sm:justify-end">
                {{ $footer }}
            </footer>
        @endisset

        {{-- Último no HTML: o foco inicial vai para o primeiro campo (ou para "Cancelar" numa confirmação), não para o fechar --}}
        <x-ui.button variant="ghost" size="sm" icon="x" icon-only aria-label="Fechar" x-on:click="open = false" class="absolute top-4 right-4" />
    </div>
</div>
