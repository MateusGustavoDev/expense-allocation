@use('App\Support\Format')
@use('App\Enums\ConversionStatus')
@use('App\Enums\Currency')

@php
    $isForeign = $expense->currency !== Currency::BRL;
    $quotedOnAnotherDay = $expense->exchange_rate_date !== null && ! $expense->exchange_rate_date->isSameDay($expense->date);
    $dl = 'flex flex-col gap-1';
    $dt = 'text-xs font-medium tracking-wide text-ds-gray-500 uppercase';
    $dd = 'text-sm text-ds-gray-900';
@endphp

<div class="flex flex-col gap-6">
    <x-ui.page-header :title="$expense->description" :description="$expense->supplier" :breadcrumbs="['Despesas' => route('expenses.index'), $expense->description => null]">
        <x-slot:actions>
            <x-ui.button variant="outline" icon="arrow-left" :href="route('expenses.index')">Voltar</x-ui.button>
            @if ($expense->conversion_status !== ConversionStatus::Converted)
                <x-ui.button icon="refresh-cw" wire:click="retryConversion">Reenviar conversão</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    @if ($expense->conversion_status === ConversionStatus::Pending)
        <x-ui.alert variant="info" title="Aguardando a cotação">
            O valor em reais é calculado em segundo plano pela cotação PTAX de {{ Format::date($expense->date) }}. A cotação de um dia só é definitiva depois que ele termina; se a API de câmbio estiver fora do ar, a conversão é tentada de novo automaticamente.
        </x-ui.alert>
    @elseif ($expense->conversion_status === ConversionStatus::Failed)
        <x-ui.alert variant="danger" title="A conversão falhou">
            As tentativas automáticas se esgotaram sem obter a cotação. A despesa continua cadastrada; reenvie a conversão para tentar de novo.
        </x-ui.alert>
    @endif

    <x-ui.card title="Dados da despesa">
        <dl class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <div class="{{ $dl }}"><dt class="{{ $dt }}">Data</dt><dd class="{{ $dd }} tabular-nums">{{ Format::date($expense->date) }}</dd></div>
            <div class="{{ $dl }}"><dt class="{{ $dt }}">Valor original</dt><dd class="{{ $dd }} font-semibold tabular-nums">{{ Format::money($expense->amount_cents, $expense->currency) }}</dd></div>
            <div class="{{ $dl }}"><dt class="{{ $dt }}">Moeda</dt><dd class="{{ $dd }}">{{ $expense->currency->label() }}</dd></div>
            <div class="{{ $dl }}"><dt class="{{ $dt }}">Status</dt><dd><x-ui.status-badge :status="$expense->conversion_status" /></dd></div>

            <div class="{{ $dl }}">
                <dt class="{{ $dt }}">Valor em BRL</dt>
                <dd class="{{ $dd }} text-lg font-bold tabular-nums">{{ $expense->amount_brl_cents === null ? '—' : Format::money($expense->amount_brl_cents) }}</dd>
            </div>
            @if ($isForeign)
                <div class="{{ $dl }}">
                    <dt class="{{ $dt }}">Cotação (PTAX de venda)</dt>
                    <dd class="{{ $dd }} tabular-nums">{{ $expense->exchange_rate === null ? '—' : 'R$ '.Format::rate($expense->exchange_rate) }}</dd>
                </div>
                <div class="{{ $dl }}">
                    <dt class="{{ $dt }}">Data da cotação</dt>
                    <dd class="{{ $dd }} tabular-nums">
                        {{ $expense->exchange_rate_date ? Format::date($expense->exchange_rate_date) : '—' }}
                        @if ($quotedOnAnotherDay)
                            <p class="text-xs text-ds-gray-500">Último dia útil antes de {{ Format::date($expense->date) }}</p>
                        @endif
                    </dd>
                </div>
            @endif
            <div class="{{ $dl }}">
                <dt class="{{ $dt }}">Convertida em</dt>
                <dd class="{{ $dd }} tabular-nums">{{ $expense->converted_at?->timezone(config('app.business_timezone'))->format('d/m/Y H:i') ?? '—' }}</dd>
            </div>
        </dl>
    </x-ui.card>

    <x-ui.table caption="Rateio da despesa por unidade">
        <x-slot:toolbar>
            <div class="flex flex-col gap-0.5">
                <h2 class="text-base font-semibold text-ds-gray-900">Rateio entre unidades</h2>
                <p class="text-sm text-ds-gray-500">Os centavos que sobram na divisão vão para as maiores frações: a soma das partes sempre fecha com o total.</p>
            </div>
        </x-slot:toolbar>

        <x-slot:head>
            <x-ui.table.head>Unidade</x-ui.table.head>
            <x-ui.table.head>Empresa</x-ui.table.head>
            <x-ui.table.head align="right">Percentual</x-ui.table.head>
            @if ($isForeign)
                <x-ui.table.head align="right">Valor ({{ $expense->currency->value }})</x-ui.table.head>
            @endif
            <x-ui.table.head align="right">Valor em BRL</x-ui.table.head>
        </x-slot:head>

        @foreach ($allocations as $allocation)
            <x-ui.table.row wire:key="allocation-{{ $allocation->id }}">
                <x-ui.table.cell>
                    <p class="font-medium">{{ $allocation->unit->name }}</p>
                    <p class="font-mono text-xs text-ds-gray-500">{{ $allocation->unit->slug }}</p>
                </x-ui.table.cell>
                <x-ui.table.cell muted>{{ $allocation->unit->company->name }}</x-ui.table.cell>
                <x-ui.table.cell numeric>{{ Format::decimal($allocation->basis_points) }}%</x-ui.table.cell>
                @if ($isForeign)
                    <x-ui.table.cell numeric>{{ Format::money($allocation->amount_cents, $expense->currency) }}</x-ui.table.cell>
                @endif
                <x-ui.table.cell numeric class="font-semibold">{{ $allocation->amount_brl_cents === null ? '—' : Format::money($allocation->amount_brl_cents) }}</x-ui.table.cell>
            </x-ui.table.row>
        @endforeach

        <tr class="bg-ds-gray-50 font-semibold">
            <td class="px-4 py-3 text-ds-gray-900" colspan="2">Total</td>
            <td class="px-4 py-3 text-right text-ds-gray-900 tabular-nums">100,00%</td>
            @if ($isForeign)
                <td class="px-4 py-3 text-right text-ds-gray-900 tabular-nums">{{ Format::money($expense->amount_cents, $expense->currency) }}</td>
            @endif
            <td class="px-4 py-3 text-right text-ds-gray-900 tabular-nums">{{ $expense->amount_brl_cents === null ? '—' : Format::money($expense->amount_brl_cents) }}</td>
        </tr>
    </x-ui.table>
</div>
