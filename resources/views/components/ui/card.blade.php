{{--
    Superfície branca com borda.

    <x-ui.card title="Dados da despesa" description="Informações da nota ou fatura.">
        ...
        <x-slot:actions><x-ui.button size="sm">...</x-ui.button></x-slot:actions>
        <x-slot:footer><x-ui.button>Salvar</x-ui.button></x-slot:footer>
    </x-ui.card>

    :padded="false" remove o espaçamento interno (ex.: tabela ocupando o card inteiro).
--}}
@props([
    'title' => null,
    'description' => null,
    'padded' => true,
])

@php
    $hasHeader = $title || isset($actions);
@endphp

<section {{ $attributes->class(['overflow-hidden rounded-xl border border-ds-gray-200 bg-ds-white']) }}>
    @if ($hasHeader)
        {{-- No celular as ações descem para baixo do título, em vez de espremê-lo --}}
        <header class="flex flex-col gap-3 px-4 pt-5 sm:flex-row sm:items-start sm:justify-between sm:gap-4 sm:px-6 sm:pt-6">
            <div class="flex flex-col gap-1">
                @if ($title)
                    <h2 class="text-base font-semibold text-ds-gray-900">{{ $title }}</h2>
                @endif
                @if ($description)
                    <p class="text-sm text-ds-gray-500">{{ $description }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex flex-wrap items-center gap-2 sm:shrink-0">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    <div @class(['px-4 pb-5 sm:px-6 sm:pb-6' => $padded, 'pt-4 sm:pt-5' => $padded && $hasHeader, 'pt-5 sm:pt-6' => $padded && ! $hasHeader])>
        {{ $slot }}
    </div>

    @isset($footer)
        <footer class="flex flex-col-reverse gap-3 border-t border-ds-gray-200 bg-ds-gray-50 px-4 py-4 sm:flex-row sm:justify-end sm:px-6">
            {{ $footer }}
        </footer>
    @endisset
</section>
