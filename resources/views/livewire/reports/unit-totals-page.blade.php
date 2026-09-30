@use('App\Support\Format')

@php
    $report = $this->report;
@endphp

<div class="flex flex-col gap-6">
    <x-ui.page-header
        title="Relatório por unidade"
        description="Total em reais rateado para cada unidade no período, a partir das despesas já convertidas."
        :breadcrumbs="['Operação' => null, 'Relatório' => null]"
    >
        <x-slot:actions>
            <x-ui.date-range-picker wire:model.live="period" aria-label="Período do relatório" />
        </x-slot:actions>
    </x-ui.page-header>

    @if ($report === null)
        <x-ui.alert variant="danger" title="Período inválido">
            Escolha uma data inicial e uma data final, com a final igual ou posterior à inicial.
        </x-ui.alert>
    @else
        <div class="flex flex-col gap-6 transition-opacity" wire:loading.class="opacity-60" wire:target="period">
            @unless ($report->isComplete())
                @php
                    $unconvertedCount = $report->pendingCount + $report->failedCount;
                    $unconvertedAmounts = collect($report->unconvertedAmounts)
                        ->map(fn ($amount) => Format::money($amount->amountCents, $amount->currency))
                        ->join(' + ');
                @endphp

                <x-ui.alert variant="warning" title="Total do período incompleto">
                    {{ $unconvertedCount }} {{ Str::plural('despesa', $unconvertedCount) }} deste período ainda sem valor em reais ({{ $unconvertedAmounts }}).
                    @if ($report->failedCount > 0)
                        {{ $report->failedCount }} {{ $report->failedCount === 1 ? 'falhou' : 'falharam' }} na conversão e {{ $report->failedCount === 1 ? 'precisa' : 'precisam' }} ser {{ $report->failedCount === 1 ? 'reprocessada' : 'reprocessadas' }}.
                    @else
                        Elas entram no total assim que a cotação for processada.
                    @endif

                    @if (Route::has('expenses.index'))
                        <x-slot:actions>
                            <a href="{{ route('expenses.index', ['status' => 'unconverted', 'period' => $period]) }}" class="underline underline-offset-2 hover:no-underline">Ver despesas</a>
                        </x-slot:actions>
                    @endif
                </x-ui.alert>
            @endunless

            @php
                $unitsWithExpenses = collect($report->units)->filter(fn ($unit) => $unit->totalCents > 0)->count();
                $companiesCount = collect($report->units)->pluck('companyId')->unique()->count();
            @endphp

            <div class="grid gap-4 md:grid-cols-3">
                <x-ui.stat
                    label="Total rateado no período"
                    :value="Format::money($report->totalCents)"
                    hint="Soma das despesas convertidas"
                    icon="wallet"
                    accent
                />
                <x-ui.stat
                    label="Despesas no período"
                    :value="$report->convertedCount + $report->pendingCount + $report->failedCount"
                    :hint="$report->convertedCount.' '.Str::plural('convertida', $report->convertedCount).($report->isComplete() ? '' : ' · '.($report->pendingCount + $report->failedCount).' sem cotação')"
                    icon="receipt"
                />
                <x-ui.stat
                    label="Unidades com rateio"
                    :value="$unitsWithExpenses"
                    :hint="'de '.count($report->units).' '.Str::plural('unidade', count($report->units)).' em '.$companiesCount.' '.Str::plural('empresa', $companiesCount)"
                    icon="building-2"
                />
            </div>

            <x-ui.table caption="Total em reais por unidade no período">
                <x-slot:toolbar>
                    <div class="flex flex-col gap-0.5">
                        <h2 class="text-base font-semibold text-ds-gray-900">Total por unidade</h2>
                        <p class="text-sm text-ds-gray-500">
                            {{ Format::date($report->from) }} a {{ Format::date($report->to) }} · somente despesas convertidas
                        </p>
                    </div>
                </x-slot:toolbar>

                <x-slot:head>
                    <x-ui.table.head>Unidade</x-ui.table.head>
                    <x-ui.table.head>Empresa</x-ui.table.head>
                    <x-ui.table.head>Despesas</x-ui.table.head>
                    <x-ui.table.head>Total (BRL)</x-ui.table.head>
                    <x-ui.table.head>Participação</x-ui.table.head>
                </x-slot:head>

                @forelse ($report->units as $unit)
                    <x-ui.table.row wire:key="unit-{{ $unit->unitId }}">
                        <x-ui.table.cell>
                            <p class="font-medium">{{ $unit->unitName }}</p>
                            <p class="font-mono text-xs text-ds-gray-500">{{ $unit->unitSlug }}</p>
                        </x-ui.table.cell>
                        <x-ui.table.cell muted>{{ $unit->companyName }}</x-ui.table.cell>
                        <x-ui.table.cell numeric>{{ $unit->expensesCount }}</x-ui.table.cell>
                        <x-ui.table.cell numeric @class(['font-semibold' => $unit->totalCents > 0, 'text-ds-gray-400' => $unit->totalCents === 0])>
                            {{ Format::money($unit->totalCents) }}
                        </x-ui.table.cell>
                        <x-ui.table.cell>
                            <div class="flex items-center gap-3">
                                <div class="h-1.5 w-32 overflow-hidden rounded-full bg-ds-gray-100" aria-hidden="true">
                                    <div class="h-full rounded-full bg-ds-primary-500" style="width: {{ $unit->shareBasisPoints / 100 }}%"></div>
                                </div>
                                <span class="text-sm text-ds-gray-700 tabular-nums">{{ Format::percent($unit->shareBasisPoints) }}</span>
                            </div>
                        </x-ui.table.cell>
                    </x-ui.table.row>
                @empty
                    <x-ui.table.empty colspan="5" icon="building-2" title="Nenhuma unidade cadastrada" description="Cadastre as unidades que recebem o rateio das despesas.">
                        @if (Route::has('units.index'))
                            <x-slot:action>
                                <x-ui.button size="sm" icon="plus" :href="route('units.index')">Cadastrar unidade</x-ui.button>
                            </x-slot:action>
                        @endif
                    </x-ui.table.empty>
                @endforelse

                @if ($report->units !== [])
                    <tr class="bg-ds-gray-50 font-semibold">
                        <td class="px-4 py-3 text-ds-gray-900" colspan="2">Total do período</td>
                        <td class="px-4 py-3 text-ds-gray-900 tabular-nums">{{ $report->convertedCount }}</td>
                        <td class="px-4 py-3 text-ds-gray-900 tabular-nums">{{ Format::money($report->totalCents) }}</td>
                        <td class="px-4 py-3 text-sm text-ds-gray-700">{{ $report->totalCents > 0 ? '100%' : '—' }}</td>
                    </tr>
                @endif
            </x-ui.table>
        </div>
    @endif
</div>
