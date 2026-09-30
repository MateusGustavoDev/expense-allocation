@use('App\Support\Format')
@use('App\Enums\ConversionStatus')

@php
    $expenses = $this->expenses;
    $statusOptions = ['converted' => 'Convertida', 'pending' => 'Pendente', 'failed' => 'Falhou', 'unconverted' => 'Sem valor em BRL'];
@endphp

<div class="flex flex-col gap-6">
    <x-ui.page-header title="Despesas" :breadcrumbs="['Operação' => null, 'Despesas' => null]">
        <x-slot:actions>
            <x-ui.button variant="outline" icon="file-up" :href="route('expenses.import')">Importar CSV</x-ui.button>
            <x-ui.button icon="plus" :href="route('expenses.create')">Nova despesa</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.table caption="Despesas cadastradas">
        <x-slot:toolbar>
            <x-ui.table.toolbar>
                <x-ui.input wire:model.live.debounce.300ms="search" icon="search" size="sm" placeholder="Buscar por descrição ou fornecedor" aria-label="Buscar despesas" class="w-full md:w-72" full />
                <x-ui.date-range-picker wire:model.live="period" size="sm" placeholder="Qualquer data" aria-label="Filtrar por período" />
                <x-ui.select wire:model.live="currency" size="sm" placeholder="Todas as moedas" :options="['BRL' => 'Real (BRL)', 'USD' => 'Dólar (USD)']" aria-label="Filtrar por moeda" />
                <x-ui.select wire:model.live="status" size="sm" placeholder="Todos os status" :options="$statusOptions" aria-label="Filtrar por status" />

                @if ($this->hasFilters())
                    <x-slot:end>
                        <x-ui.button variant="ghost" size="sm" icon="rotate-ccw" wire:click="resetFilters">Limpar filtros</x-ui.button>
                    </x-slot:end>
                @endif
            </x-ui.table.toolbar>
        </x-slot:toolbar>

        <x-slot:head>
            <x-ui.table.head sortable="date" :sorted-by="$sortColumn" :direction="$sortDirection">Data</x-ui.table.head>
            <x-ui.table.head>Despesa</x-ui.table.head>
            <x-ui.table.head>Rateio</x-ui.table.head>
            <x-ui.table.head>Valor original</x-ui.table.head>
            <x-ui.table.head sortable="amount_brl" :sorted-by="$sortColumn" :direction="$sortDirection">Valor em BRL</x-ui.table.head>
            <x-ui.table.head align="center">Status</x-ui.table.head>
            <x-ui.table.head align="center">Ações</x-ui.table.head>
        </x-slot:head>

        @forelse ($expenses as $expense)
            <x-ui.table.row wire:key="expense-{{ $expense->id }}" wire:loading.class="opacity-60">
                <x-ui.table.cell muted class="whitespace-nowrap tabular-nums">{{ Format::date($expense->date) }}</x-ui.table.cell>
                <x-ui.table.cell>
                    <a href="{{ route('expenses.show', $expense) }}" class="font-medium text-ds-gray-900 hover:text-ds-primary-700 hover:underline">{{ $expense->description }}</a>
                    <p class="text-xs text-ds-gray-500">{{ $expense->supplier }}</p>
                </x-ui.table.cell>
                <x-ui.table.cell muted class="whitespace-nowrap">
                    <span class="inline-flex items-center gap-1.5">
                        <x-ui.icon name="split" class="size-3.5" />
                        {{ $expense->allocations_count }} {{ Str::plural('unidade', $expense->allocations_count) }}
                    </span>
                </x-ui.table.cell>
                <x-ui.table.cell numeric>{{ Format::money($expense->amount_cents, $expense->currency) }}</x-ui.table.cell>
                <x-ui.table.cell numeric @class(['font-semibold' => $expense->amount_brl_cents !== null, 'text-ds-gray-400' => $expense->amount_brl_cents === null])>
                    {{ $expense->amount_brl_cents === null ? '—' : Format::money($expense->amount_brl_cents) }}
                </x-ui.table.cell>
                <x-ui.table.cell align="center"><x-ui.status-badge :status="$expense->conversion_status" /></x-ui.table.cell>
                <x-ui.table.cell align="center">
                    <x-ui.dropdown>
                        <x-slot:trigger>
                            <x-ui.button variant="ghost" size="sm" icon="ellipsis" icon-only aria-label="Ações de {{ $expense->description }}" />
                        </x-slot:trigger>
                        <x-ui.dropdown.item icon="eye" :href="route('expenses.show', $expense)">Ver detalhes</x-ui.dropdown.item>
                        @if ($expense->conversion_status !== ConversionStatus::Converted)
                            <x-ui.dropdown.item icon="refresh-cw" wire:click="retryConversion({{ $expense->id }})">Reenviar conversão</x-ui.dropdown.item>
                        @endif
                    </x-ui.dropdown>
                </x-ui.table.cell>
            </x-ui.table.row>
        @empty
            @if ($this->hasFilters())
                <x-ui.table.empty colspan="7" icon="search" title="Nenhuma despesa encontrada" description="Nenhuma despesa corresponde aos filtros aplicados.">
                    <x-slot:action>
                        <x-ui.button variant="outline" size="sm" icon="rotate-ccw" wire:click="resetFilters">Limpar filtros</x-ui.button>
                    </x-slot:action>
                </x-ui.table.empty>
            @else
                <x-ui.table.empty colspan="7" icon="receipt" title="Nenhuma despesa ainda" description="Cadastre uma despesa ou importe um arquivo CSV para começar.">
                    <x-slot:action>
                        <x-ui.button size="sm" icon="plus" :href="route('expenses.create')">Nova despesa</x-ui.button>
                    </x-slot:action>
                </x-ui.table.empty>
            @endif
        @endforelse

        <x-slot:footer>
            <x-ui.pagination :paginator="$expenses" label="despesas" livewire />
        </x-slot:footer>
    </x-ui.table>
</div>
