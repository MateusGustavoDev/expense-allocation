{{--
    Tabela em card, com barra de filtros (toolbar) acima e paginação (footer) abaixo.

    <x-ui.table>
        <x-slot:toolbar><x-ui.table.toolbar>...filtros...</x-ui.table.toolbar></x-slot:toolbar>
        <x-slot:head>
            <x-ui.table.head sortable="date" :sorted-by="$sortBy" :direction="$sortDirection">Data</x-ui.table.head>
            <x-ui.table.head align="right">Valor</x-ui.table.head>
        </x-slot:head>

        @forelse ($rows as $row)
            <x-ui.table.row wire:key="row-{{ $row->id }}">...</x-ui.table.row>
        @empty
            <x-ui.table.empty colspan="2" title="Nada por aqui" />
        @endforelse

        <x-slot:footer><x-ui.pagination :paginator="$rows" livewire /></x-slot:footer>
    </x-ui.table>

    caption: descrição da tabela para leitores de tela (não aparece na tela).
    mobile: versão da lista para telas pequenas (<li> por item); com ele, a tabela só aparece a partir de md.
--}}
@props(['caption' => null])

<div {{ $attributes->class(['overflow-hidden rounded-xl border border-ds-gray-200 bg-ds-white']) }}>
    @isset($toolbar)
        <div class="border-b border-ds-gray-200 px-4 py-3">{{ $toolbar }}</div>
    @endisset

    {{-- Com o slot mobile, a tabela aparece a partir de md e o celular recebe a lista em cards --}}
    @isset($mobile)
        <ul class="divide-y divide-ds-gray-200 md:hidden" role="list">{{ $mobile }}</ul>
    @endisset
    <div @class(['overflow-x-auto', 'hidden md:block' => isset($mobile)])>
        <table class="w-full border-collapse text-left text-sm">
            @if ($caption)
                <caption class="sr-only">{{ $caption }}</caption>
            @endif

            @isset($head)
                <thead class="bg-ds-gray-50">
                    <tr class="border-b border-ds-gray-200">{{ $head }}</tr>
                </thead>
            @endisset

            <tbody class="divide-y divide-ds-gray-200">
                {{ $slot }}
            </tbody>
        </table>
    </div>

    @isset($footer)
        <div class="border-t border-ds-gray-200 px-4 py-3">{{ $footer }}</div>
    @endisset
</div>
