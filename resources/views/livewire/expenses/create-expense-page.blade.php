@use('App\Support\Format')
@use('App\Enums\Currency')
@use('App\Services\Money\AllocationSplitter')

@php
    $currency = Currency::tryFrom($form->currency) ?? Currency::BRL;
    $total = $preview['totalBasisPoints'];
    $difference = $total === null ? null : AllocationSplitter::TOTAL_BASIS_POINTS - $total;
    $hasUnits = $this->unitOptions !== [];
    $allocationsError = $errors->first('form.allocations');
@endphp

<form novalidate wire:submit="save" class="flex flex-col gap-6">
    <x-ui.page-header title="Nova despesa" :breadcrumbs="['Despesas' => route('expenses.index'), 'Nova despesa' => null]">
        <x-slot:actions>
            <x-ui.button variant="outline" :href="route('expenses.index')">Cancelar</x-ui.button>
            <x-ui.button type="submit" icon="check" wire:target="save" :disabled="! $hasUnits">Salvar despesa</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @unless ($hasUnits)
        <x-ui.alert variant="warning" title="Nenhuma unidade cadastrada">
            A despesa precisa ser rateada entre unidades.
            <x-slot:actions>
                <a href="{{ route('units.index') }}" class="underline underline-offset-2 hover:no-underline">Cadastrar unidades</a>
            </x-slot:actions>
        </x-ui.alert>
    @endunless

    <div class="grid items-start gap-6 lg:grid-cols-[1fr_20rem]">
        <div class="flex flex-col gap-6">
            <x-ui.card title="Dados da despesa" description="Informações da nota ou fatura.">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.input wire:model="form.description" label="Descrição" placeholder="Ex.: Licença CRM" required full class="sm:col-span-2" />
                    <x-ui.input wire:model="form.supplier" label="Fornecedor" placeholder="Ex.: Fornecedor X" required full />
                    <x-ui.date-picker wire:model="form.date" label="Data da despesa" hint="Define a cotação usada na conversão." required full />
                    <x-ui.input wire:model.live.debounce.400ms="form.amount" label="Valor total" placeholder="1.500,00" inputmode="decimal" required full />
                    <x-ui.segmented wire:model.live="form.currency" name="currency" label="Moeda" :options="['BRL' => 'BRL · Real', 'USD' => 'USD · Dólar']" :value="$form->currency" />
                </div>

                @if ($currency === Currency::USD)
                    <x-ui.alert variant="info" class="mt-5">
                        Despesa em dólar: depois de salvar, o valor é convertido para reais pela cotação PTAX da data da despesa. Se a API de câmbio estiver fora do ar, a despesa é salva como pendente e a conversão é refeita automaticamente.
                    </x-ui.alert>
                @endif
            </x-ui.card>

            <x-ui.card title="Rateio entre unidades" description="A soma dos percentuais precisa ser exatamente 100%.">
                <x-slot:actions>
                    <x-ui.button variant="ghost" size="sm" icon="equal" wire:click="splitEvenly">Dividir igualmente</x-ui.button>
                    <x-ui.button variant="outline" size="sm" icon="plus" wire:click="addAllocation">Adicionar unidade</x-ui.button>
                </x-slot:actions>

                <div class="flex flex-col gap-3">
                    <div class="hidden grid-cols-[1fr_8rem_8rem_2.5rem] gap-3 border-b border-ds-gray-200 pb-2 text-xs font-semibold tracking-wide text-ds-gray-500 uppercase sm:grid">
                        <span>Unidade</span>
                        <span>Percentual</span>
                        <span>Valor</span>
                        <span class="sr-only">Remover</span>
                    </div>

                    @foreach ($form->allocations as $index => $allocation)
                        <div class="grid items-start gap-3 sm:grid-cols-[1fr_8rem_8rem_2.5rem]" wire:key="allocation-{{ $index }}">
                            <x-ui.select wire:model.live="form.allocations.{{ $index }}.unit_id" placeholder="Selecione a unidade" :options="$this->unitOptions" :aria-label="'Unidade da linha '.($index + 1)" full />
                            <x-ui.input wire:model.live.debounce.400ms="form.allocations.{{ $index }}.percentage" icon="percent" icon-direction="right" placeholder="0,00" inputmode="decimal" :aria-label="'Percentual da linha '.($index + 1)" full />
                            <p class="flex h-10 items-center text-sm font-medium text-ds-gray-900 tabular-nums">
                                {{ isset($preview['shares'][$index]) ? Format::money($preview['shares'][$index], $currency) : '—' }}
                            </p>
                            <x-ui.button variant="ghost" icon="trash-2" icon-only :aria-label="'Remover linha '.($index + 1)" wire:click="removeAllocation({{ $index }})" :disabled="count($form->allocations) === 1" />
                        </div>
                    @endforeach

                    <div class="mt-2 flex flex-col gap-3 border-t border-ds-gray-200 pt-4">
                        <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                            @if ($total === AllocationSplitter::TOTAL_BASIS_POINTS)
                                <p class="flex items-center gap-2 font-semibold text-ds-green-800">
                                    <x-ui.icon name="circle-check" class="size-4.5 text-ds-green-600" /> 100% alocado
                                </p>
                            @elseif ($total === null)
                                <p class="flex items-center gap-2 font-medium text-ds-gray-600">
                                    <x-ui.icon name="info" class="size-4.5" /> Informe o percentual de cada unidade
                                </p>
                            @else
                                <p class="flex items-center gap-2 font-semibold text-ds-yellow-800">
                                    <x-ui.icon name="triangle-alert" class="size-4.5" />
                                    {{ Format::decimal($total) }}% alocado · {{ $difference > 0 ? 'faltam '.Format::decimal($difference).'%' : 'excede em '.Format::decimal(-$difference).'%' }}
                                </p>
                            @endif

                            @if ($preview['amountCents'] !== null)
                                <p class="font-semibold text-ds-gray-900 tabular-nums">Total: {{ Format::money($preview['amountCents'], $currency) }}</p>
                            @endif
                        </div>

                        <div class="h-2 overflow-hidden rounded-full bg-ds-gray-100" aria-hidden="true">
                            <div @class(['h-full rounded-full transition-all', 'bg-ds-green-500' => $total === AllocationSplitter::TOTAL_BASIS_POINTS, 'bg-ds-yellow-500' => $total !== AllocationSplitter::TOTAL_BASIS_POINTS]) style="width: {{ min(100, ($total ?? 0) / 100) }}%"></div>
                        </div>

                        @if ($allocationsError)
                            <p class="text-sm text-ds-red-700" role="alert">{{ $allocationsError }}</p>
                        @endif
                    </div>
                </div>
            </x-ui.card>
        </div>

        <aside class="flex flex-col gap-4 lg:sticky lg:top-8">
            <x-ui.card title="Resumo">
                <dl class="flex flex-col gap-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-ds-gray-500">Valor original</dt><dd class="font-semibold tabular-nums">{{ $preview['amountCents'] !== null ? Format::money($preview['amountCents'], $currency) : '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-ds-gray-500">Moeda</dt><dd>{{ $currency->label() }}</dd></div>
                    @if ($currency === Currency::USD)
                        <div class="flex justify-between gap-4"><dt class="text-ds-gray-500">Cotação</dt><dd class="text-right">PTAX de {{ $form->date ? Format::date(Carbon\CarbonImmutable::parse($form->date)) : '—' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-ds-gray-500">Valor em BRL</dt><dd class="text-right text-ds-gray-500">calculado após salvar</dd></div>
                    @endif
                </dl>

                @if ($preview['shares'] !== null)
                    <div class="mt-4 flex flex-col gap-3 border-t border-ds-gray-200 pt-4">
                        <p class="text-xs font-semibold tracking-wide text-ds-gray-500 uppercase">Distribuição</p>
                        @foreach ($form->allocations as $index => $allocation)
                            <div class="flex flex-col gap-1.5">
                                <div class="flex justify-between gap-2 text-sm">
                                    <span class="truncate font-medium">{{ Str::before($this->unitOptions[(int) $allocation['unit_id']] ?? 'Unidade não selecionada', ' · ') }}</span>
                                    <span class="text-ds-gray-700 tabular-nums">{{ Format::money($preview['shares'][$index], $currency) }}</span>
                                </div>
                                <div class="h-1.5 overflow-hidden rounded-full bg-ds-gray-100" aria-hidden="true">
                                    <div class="h-full rounded-full bg-ds-primary-500" style="width: {{ $preview['amountCents'] > 0 ? $preview['shares'][$index] / $preview['amountCents'] * 100 : 0 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>

            <div class="flex flex-col gap-2 rounded-xl border border-ds-primary-100 bg-ds-primary-50 p-5 text-sm text-ds-primary-900">
                <p class="flex items-center gap-2 font-semibold"><x-ui.icon name="calculator" class="size-4 text-ds-primary-700" /> Os centavos sempre fecham</p>
                <p class="leading-relaxed">R$ 100,00 em 33,34% / 33,33% / 33,33% vira R$ 33,34 + R$ 33,33 + R$ 33,33. Os centavos que sobram vão para as maiores frações.</p>
            </div>
        </aside>
    </div>
</form>
