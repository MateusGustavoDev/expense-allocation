@php
    $fileError = $errors->first('file');
@endphp

<div class="flex flex-col gap-6">
    <x-ui.page-header title="Importar despesas" description="Linhas com erro não impedem a importação das demais: o relatório mostra o que corrigir." :breadcrumbs="['Operação' => null, 'Importar CSV' => null]">
        <x-slot:actions>
            <x-ui.button variant="outline" icon="download" :href="route('expenses.import.example')">Baixar exemplo</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid items-start gap-6 lg:grid-cols-[1fr_22rem]">
        <div class="flex flex-col gap-6">
            <x-ui.card>
                <form novalidate wire:submit="import" class="flex flex-col gap-4">
                    {{-- O input cobre a área inteira: clicar ou soltar o arquivo usa o seletor nativo do navegador --}}
                    <label @class([
                        'relative flex cursor-pointer flex-col items-center gap-3 rounded-xl border border-dashed px-6 py-10 text-center transition-colors hover:border-ds-primary-400 hover:bg-ds-primary-50 focus-within:ring-2 focus-within:ring-ds-primary-600',
                        'border-ds-gray-300 bg-ds-gray-50' => ! $fileError,
                        'border-ds-red-400 bg-ds-red-50' => (bool) $fileError,
                    ])>
                        <input type="file" wire:model="file" accept=".csv,text/csv,text/plain" class="absolute inset-0 cursor-pointer opacity-0" aria-describedby="import-file-help" @if ($fileError) aria-invalid="true" @endif>
                        <span class="flex size-12 items-center justify-center rounded-full bg-ds-primary-100 text-ds-primary-700">
                            <x-ui.icon name="upload" class="size-6" wire:loading.remove wire:target="file" />
                            <x-ui.spinner size="md" class="text-ds-primary-700" wire:loading wire:target="file" />
                        </span>
                        <span class="text-sm text-ds-gray-700"><span class="font-semibold text-ds-primary-700">Clique para selecionar</span> ou arraste o arquivo CSV aqui</span>
                        <span id="import-file-help" class="text-xs text-ds-gray-500">Separador ponto e vírgula · UTF-8 ou Windows-1252 · até 5 MB</span>
                    </label>

                    @if ($fileError)
                        <p class="text-sm text-ds-red-700" role="alert">{{ $fileError }}</p>
                    @endif

                    @if ($file)
                        <div class="flex items-center gap-3 rounded-lg border border-ds-gray-200 px-4 py-3">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-ds-green-100 text-ds-green-700">
                                <x-ui.icon name="file-spreadsheet" class="size-4.5" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-ds-gray-900">{{ $file->getClientOriginalName() }}</p>
                                <p class="text-xs text-ds-gray-500">{{ number_format($file->getSize() / 1024, 1, ',', '.') }} KB</p>
                            </div>
                            <x-ui.button type="submit" icon="upload" wire:target="import">Importar arquivo</x-ui.button>
                        </div>
                    @endif
                </form>
            </x-ui.card>

            @if ($report)
                <x-ui.card :padded="false">
                    <div class="flex flex-col gap-4 p-6">
                        <div class="flex flex-col gap-0.5">
                            <h2 class="text-base font-semibold text-ds-gray-900">Resultado da importação</h2>
                            <p class="text-sm text-ds-gray-500">{{ $report['file_name'] }}</p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            <div class="flex items-center gap-3 rounded-lg border border-ds-gray-200 bg-ds-gray-50 p-4">
                                <x-ui.icon name="file-text" class="size-5 text-ds-gray-600" />
                                <div><p class="text-2xl font-bold tabular-nums">{{ $report['total_rows'] }}</p><p class="text-xs font-medium text-ds-gray-600">Linhas lidas</p></div>
                            </div>
                            <div class="flex items-center gap-3 rounded-lg border border-ds-green-200 bg-ds-green-50 p-4 text-ds-green-900">
                                <x-ui.icon name="circle-check" class="size-5 text-ds-green-700" />
                                <div><p class="text-2xl font-bold tabular-nums">{{ $report['imported'] }}</p><p class="text-xs font-medium text-ds-green-800">Importadas</p></div>
                            </div>
                            <div @class(['flex items-center gap-3 rounded-lg border p-4', 'border-ds-red-200 bg-ds-red-50 text-ds-red-900' => $report['failed'] > 0, 'border-ds-gray-200 bg-ds-gray-50 text-ds-gray-900' => $report['failed'] === 0])>
                                <x-ui.icon name="circle-alert" @class(['size-5', 'text-ds-red-700' => $report['failed'] > 0, 'text-ds-gray-500' => $report['failed'] === 0]) />
                                <div><p class="text-2xl font-bold tabular-nums">{{ $report['failed'] }}</p><p class="text-xs font-medium">Com erro</p></div>
                            </div>
                        </div>

                        @if ($report['failed'] > 0)
                            <p class="text-sm text-ds-gray-600">As linhas válidas já foram importadas. Corrija as linhas abaixo e envie um novo arquivo só com elas.</p>
                        @elseif ($report['imported'] > 0)
                            <p class="text-sm text-ds-gray-600">
                                Todas as linhas foram importadas.
                                <a href="{{ route('expenses.index') }}" class="font-medium text-ds-primary-700 underline underline-offset-2 hover:no-underline">Ver despesas</a>
                            </p>
                        @endif
                    </div>

                    @if ($report['errors'] !== [])
                        <x-ui.table caption="Linhas com erro" class="rounded-none border-x-0 border-b-0">
                            <x-slot:mobile>
                                @foreach ($report['errors'] as $error)
                                    <li wire:key="import-error-card-{{ $error['line'] }}" class="flex flex-col gap-2 px-4 py-3.5">
                                        <x-ui.badge variant="mono" size="sm" class="self-start">Linha {{ $error['line'] }}</x-ui.badge>
                                        <ul class="flex flex-col gap-1">
                                            @foreach ($error['messages'] as $message)
                                                <li class="flex items-start gap-2 text-sm text-ds-red-800">
                                                    <x-ui.icon name="circle-x" class="mt-0.5 size-4 shrink-0 text-ds-red-600" /> {{ $message }}
                                                </li>
                                            @endforeach
                                        </ul>
                                        <p class="font-mono text-xs break-all text-ds-gray-500">{{ $error['content'] }}</p>
                                    </li>
                                @endforeach
                            </x-slot:mobile>

                            <x-slot:head>
                                <x-ui.table.head align="center">Linha</x-ui.table.head>
                                <x-ui.table.head>Conteúdo</x-ui.table.head>
                                <x-ui.table.head>Erro</x-ui.table.head>
                            </x-slot:head>

                            @foreach ($report['errors'] as $error)
                                <x-ui.table.row wire:key="import-error-{{ $error['line'] }}">
                                    <x-ui.table.cell align="center" class="w-16"><x-ui.badge variant="mono">{{ $error['line'] }}</x-ui.badge></x-ui.table.cell>
                                    <x-ui.table.cell mono class="max-w-md break-all text-ds-gray-700">{{ $error['content'] }}</x-ui.table.cell>
                                    <x-ui.table.cell>
                                        <ul class="flex flex-col gap-1">
                                            @foreach ($error['messages'] as $message)
                                                <li class="flex items-start gap-2 text-sm text-ds-red-800">
                                                    <x-ui.icon name="circle-x" class="mt-0.5 size-4 shrink-0 text-ds-red-600" /> {{ $message }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    </x-ui.table.cell>
                                </x-ui.table.row>
                            @endforeach
                        </x-ui.table>
                    @endif
                </x-ui.card>
            @endif
        </div>

        <x-ui.card title="Formato esperado" class="lg:sticky lg:top-8">
            <div class="flex flex-col gap-4">
                <pre class="overflow-x-auto rounded-lg bg-ds-gray-900 p-3.5 font-mono text-xs leading-relaxed text-ds-gray-100"><span class="text-ds-primary-300">data;descricao;fornecedor;valor;moeda;rateio</span>
2026-09-01;Licença CRM;Fornecedor X;1500.00;USD;unidade-a:50|unidade-b:30|unidade-c:20</pre>

                <dl class="flex flex-col gap-3 text-sm">
                    <div><dt class="font-mono text-xs font-semibold text-ds-gray-900">data</dt><dd class="text-ds-gray-600">AAAA-MM-DD</dd></div>
                    <div><dt class="font-mono text-xs font-semibold text-ds-gray-900">valor</dt><dd class="text-ds-gray-600">Ponto como separador decimal: 1500.00</dd></div>
                    <div><dt class="font-mono text-xs font-semibold text-ds-gray-900">moeda</dt><dd class="text-ds-gray-600">BRL ou USD</dd></div>
                    <div><dt class="font-mono text-xs font-semibold text-ds-gray-900">rateio</dt><dd class="text-ds-gray-600">slug:percentual separados por |, somando 100%</dd></div>
                </dl>

                <x-ui.alert variant="info">
                    O slug de cada unidade aparece em <a href="{{ route('units.index') }}" class="font-medium underline underline-offset-2">Cadastros › Unidades</a>.
                </x-ui.alert>
            </div>
        </x-ui.card>
    </div>
</div>
