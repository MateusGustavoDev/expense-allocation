{{--
    Catálogo dos componentes de interface (só em ambiente local: rota /ui).
    Referência visual de tokens, variantes, tamanhos e estados — o equivalente a um playground/Storybook.
--}}
@php
    $palette = ['primary' => 'Primary', 'gray' => 'Gray', 'blue' => 'Blue', 'green' => 'Green', 'yellow' => 'Yellow', 'red' => 'Red'];
    $shades = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900];
    $buttonVariants = ['primary', 'secondary', 'outline', 'ghost', 'danger', 'danger-outline', 'link'];
    $sizes = ['sm', 'md', 'lg'];
    $badgeVariants = ['neutral', 'primary', 'success', 'warning', 'danger', 'info', 'outline', 'mono'];
    $logoSizes = ['sm', 'md', 'lg', 'xl'];
    $logoRadii = ['none', 'sm', 'md', 'lg', 'full'];

    $rows = collect([
        ['2026-09-29', 'Assinatura AWS', 'Amazon Web Services', 'US$ 320,00', null, App\Enums\ConversionStatus::Pending],
        ['2026-09-25', 'Honorários contábeis', 'Escritório Contábil Silva', 'R$ 4.800,00', 'R$ 4.800,00', App\Enums\ConversionStatus::Converted],
        ['2026-09-15', 'Google Workspace', 'Google', 'US$ 540,00', null, App\Enums\ConversionStatus::Failed],
        ['2026-09-01', 'Licença CRM', 'Fornecedor X', 'US$ 1.500,00', 'R$ 7.735,50', App\Enums\ConversionStatus::Converted],
    ]);
    $paginator = new Illuminate\Pagination\LengthAwarePaginator($rows, 27, 4, max(1, request()->integer('page', 1)), ['path' => url('/ui')]);

    $section = 'flex flex-col gap-5';
    $sectionTitle = 'text-lg font-semibold text-ds-gray-900';
    $caption = 'text-xs font-semibold tracking-wider text-ds-gray-500 uppercase';
@endphp

<x-layouts::app title="Catálogo de componentes">
    <x-ui.page-header
        title="Catálogo de componentes"
        description="Tokens e componentes base (resources/views/components/ui). Disponível apenas em ambiente local. Abra um modal pela URL com ?open=new-unit ou ?open=confirm-delete."
        :breadcrumbs="['Desenvolvimento' => null, 'Catálogo' => null]"
    >
        <x-slot:actions>
            <x-ui.badge variant="warning" dot>Somente local</x-ui.badge>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Paleta --}}
    <x-ui.card title="Paleta" description="Tokens ds-{cor}-{tom}. Primária extraída de gridmidia.com (#FE8400 = primary-500).">
        <div class="flex flex-col gap-4">
            @foreach ($palette as $color => $label)
                <div class="grid grid-cols-[6rem_1fr] items-center gap-3">
                    <p class="text-sm font-medium text-ds-gray-700">{{ $label }}</p>
                    <div class="grid grid-cols-10 gap-1.5">
                        @foreach ($shades as $shade)
                            <div class="flex flex-col gap-1">
                                <div class="bg-ds-{{ $color }}-{{ $shade }} h-9 rounded-md border border-ds-gray-200"></div>
                                <p @class(['text-center text-[11px] text-ds-gray-500', 'font-bold text-ds-gray-900' => $shade === 500])>{{ $shade }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </x-ui.card>

    {{-- Tipografia --}}
    <x-ui.card title="Tipografia" description="Inter para textos, JetBrains Mono para identificadores. Escala 12 · 13 · 14 · 16 · 18 · 24 · 30.">
        <div class="flex flex-col gap-3">
            <p class="text-3xl font-bold tracking-tight">R$ 48.392,17 <span class="text-sm font-normal text-ds-gray-500">text-3xl · indicadores</span></p>
            <p class="text-2xl font-bold tracking-tight">Relatório por unidade <span class="text-sm font-normal text-ds-gray-500">text-2xl · título da página</span></p>
            <p class="text-lg font-semibold">Rateio entre unidades <span class="text-sm font-normal text-ds-gray-500">text-lg · título de modal</span></p>
            <p class="text-base font-semibold">Dados da despesa <span class="text-sm font-normal text-ds-gray-500">text-base · título de card</span></p>
            <p class="text-sm">A soma dos percentuais precisa ser exatamente 100%. <span class="text-ds-gray-500">text-sm · corpo</span></p>
            <p class="text-xs text-ds-gray-500">Gerado a partir do nome. text-xs · ajuda e legendas</p>
            <p class="font-mono text-sm">unidade-a:50|unidade-b:30 <span class="font-sans text-ds-gray-500">font-mono · identificadores</span></p>
        </div>
    </x-ui.card>

    {{-- Logo --}}
    <x-ui.card title="Logo" description="x-ui.logo com size e rounded. Ícone em 9/16 do lado e raio em porcentagem do lado: a forma é a mesma em qualquer tamanho.">
        <div class="flex flex-col gap-6">
            <div class="overflow-x-auto">
                <div class="grid w-fit grid-cols-[5rem_repeat(4,5rem)] items-center gap-y-4">
                    <span></span>
                    @foreach ($logoSizes as $logoSize)
                        <p class="{{ $caption }} text-center">{{ $logoSize }}</p>
                    @endforeach

                    @foreach ($logoRadii as $logoRadius)
                        <p class="{{ $caption }}">{{ $logoRadius }}</p>
                        @foreach ($logoSizes as $logoSize)
                            <div class="flex justify-center">
                                <x-ui.logo :size="$logoSize" :rounded="$logoRadius" />
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>

            <div class="flex flex-col gap-2 border-t border-ds-gray-200 pt-5">
                <p class="{{ $caption }}">Na sidebar (padrão: size="md" rounded="md")</p>
                <div class="flex items-center gap-2.5">
                    <x-ui.logo />
                    <span class="flex flex-col leading-tight">
                        <span class="text-base font-bold text-ds-gray-900">Rateio</span>
                        <span class="text-xs text-ds-gray-500">Despesas compartilhadas</span>
                    </span>
                </div>
            </div>
        </div>
    </x-ui.card>

    {{-- Botões --}}
    <section class="{{ $section }}" aria-labelledby="botoes">
        <h2 id="botoes" class="{{ $sectionTitle }}">Botões</h2>
        <x-ui.card>
            <div class="flex flex-col gap-6">
                @foreach ($sizes as $size)
                    <div class="flex flex-col gap-2">
                        <p class="{{ $caption }}">Tamanho {{ $size }}</p>
                        <div class="flex flex-wrap items-center gap-3">
                            @foreach ($buttonVariants as $variant)
                                <x-ui.button :variant="$variant" :size="$size">{{ Str::headline($variant) }}</x-ui.button>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div class="flex flex-col gap-2">
                    <p class="{{ $caption }}">Ícone, direção, só ícone e largura total</p>
                    <div class="flex flex-wrap items-center gap-3">
                        <x-ui.button icon="plus">Nova despesa</x-ui.button>
                        <x-ui.button variant="outline" icon="download" icon-direction="right">Exportar CSV</x-ui.button>
                        <x-ui.button variant="outline" icon="refresh-cw" icon-only aria-label="Atualizar" />
                        <x-ui.button variant="ghost" size="sm" icon="ellipsis" icon-only aria-label="Ações" />
                        <x-ui.button variant="danger" icon="trash-2" icon-only aria-label="Excluir" />
                        <x-ui.button variant="link" icon="external-link" icon-direction="right" href="#">Abrir documentação</x-ui.button>
                    </div>
                    <div class="max-w-sm">
                        <x-ui.button full icon="log-in">Entrar (full)</x-ui.button>
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <p class="{{ $caption }}">Estados</p>
                    <div class="flex flex-wrap items-center gap-3">
                        <x-ui.button loading>Salvando</x-ui.button>
                        <x-ui.button variant="outline" icon="check" loading>Com ícone</x-ui.button>
                        <x-ui.button disabled>Desabilitado</x-ui.button>
                        <x-ui.button variant="outline" disabled>Desabilitado</x-ui.button>
                    </div>
                </div>
            </div>
        </x-ui.card>
    </section>

    {{-- Badges e status --}}
    <section class="{{ $section }}" aria-labelledby="badges">
        <h2 id="badges" class="{{ $sectionTitle }}">Badges e status</h2>
        <x-ui.card>
            <div class="flex flex-col gap-6">
                @foreach ($sizes as $size)
                    <div class="flex flex-col gap-2">
                        <p class="{{ $caption }}">Tamanho {{ $size }}</p>
                        <div class="flex flex-wrap items-center gap-2">
                            @foreach ($badgeVariants as $variant)
                                <x-ui.badge :variant="$variant" :size="$size">{{ $variant === 'mono' ? 'unidade-a' : Str::headline($variant) }}</x-ui.badge>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                <div class="flex flex-col gap-2">
                    <p class="{{ $caption }}">Com ponto, com ícone e status de conversão</p>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-ui.badge variant="info" icon="dollar-sign">USD</x-ui.badge>
                        <x-ui.badge variant="neutral" dot>BRL</x-ui.badge>
                        @foreach (App\Enums\ConversionStatus::cases() as $status)
                            <x-ui.status-badge :status="$status" />
                        @endforeach
                    </div>
                </div>
            </div>
        </x-ui.card>
    </section>

    {{-- Feedback --}}
    <section class="{{ $section }}" aria-labelledby="feedback">
        <h2 id="feedback" class="{{ $sectionTitle }}">Feedback</h2>
        <div class="grid gap-4 lg:grid-cols-2">
            <x-ui.alert variant="info" title="Despesa em dólar">Após salvar, o valor é convertido pela cotação PTAX da data da despesa.</x-ui.alert>
            <x-ui.alert variant="success">Importação concluída: 11 despesas criadas.</x-ui.alert>
            <x-ui.alert variant="warning" title="Total incompleto">
                3 despesas em USD aguardam conversão (US$ 2.140,00).
                <x-slot:actions><a href="#" class="underline">Ver pendentes</a></x-slot:actions>
            </x-ui.alert>
            <x-ui.alert variant="danger" title="Conversão falhou">A API de câmbio não respondeu depois de 5 tentativas.</x-ui.alert>
        </div>
        <x-ui.card title="Spinner e toasts">
            <div class="flex flex-wrap items-center gap-6">
                <x-ui.spinner size="sm" />
                <x-ui.spinner />
                <x-ui.spinner size="lg" />
                <div class="flex flex-wrap gap-2">
                    <x-ui.button variant="outline" size="sm" x-on:click="$dispatch('toast', { type: 'success', message: 'Despesa criada com sucesso.' })">Toast sucesso</x-ui.button>
                    <x-ui.button variant="outline" size="sm" x-on:click="$dispatch('toast', { type: 'error', message: 'Não foi possível salvar a despesa.' })">Toast erro</x-ui.button>
                    <x-ui.button variant="outline" size="sm" x-on:click="$dispatch('toast', { type: 'warning', message: '3 linhas do CSV não foram importadas.' })">Toast aviso</x-ui.button>
                    <x-ui.button variant="outline" size="sm" x-on:click="$dispatch('toast', { type: 'info', message: 'A conversão roda em segundo plano.' })">Toast info</x-ui.button>
                </div>
            </div>
        </x-ui.card>
        <div class="grid gap-4 md:grid-cols-3">
            <x-ui.stat label="Total rateado no período" value="R$ 48.392,17" hint="Soma das despesas convertidas" icon="wallet" accent />
            <x-ui.stat label="Despesas no período" value="27" hint="24 convertidas · 3 pendentes" icon="receipt" />
            <x-ui.stat label="Unidades com rateio" value="6" hint="de 3 empresas" icon="building-2" />
        </div>
        <x-ui.empty icon="receipt" title="Nenhuma despesa ainda" description="Cadastre uma despesa ou importe um arquivo CSV para começar.">
            <x-slot:action><x-ui.button size="sm" icon="plus">Nova despesa</x-ui.button></x-slot:action>
        </x-ui.empty>
    </section>

    {{-- Formulário --}}
    <section class="{{ $section }}" aria-labelledby="formulario">
        <h2 id="formulario" class="{{ $sectionTitle }}">Formulário</h2>
        <x-ui.card title="Campos" description="Rótulo, obrigatório, ajuda, erro, ícone, senha, tamanhos e estados.">
            <div class="grid gap-5 md:grid-cols-2">
                <x-ui.input name="description" label="Descrição" placeholder="Ex.: Licença CRM" required full />
                <x-ui.input name="supplier" label="Fornecedor" hint="Como aparece na nota fiscal." full />
                <x-ui.input name="amount" label="Valor total" value="10,50" error="O valor deve usar ponto como separador decimal e ter até 2 casas, ex.: 1500.00." full />
                <x-ui.input name="date" type="date" label="Data da despesa" value="2026-09-01" full />
                <x-ui.input name="search" icon="search" placeholder="Buscar por descrição ou fornecedor" full />
                <x-ui.input name="password" label="Senha" password value="secret123" full />
                <x-ui.input name="readonly" label="Somente leitura" value="unidade-sao-paulo" readonly mono full />
                <x-ui.input name="disabled" label="Desabilitado" value="Não editável" disabled full />
                <x-ui.select name="currency" label="Moeda" :options="['BRL' => 'Real (BRL)', 'USD' => 'Dólar (USD)']" value="USD" full />
                <x-ui.select name="unit" label="Unidade" placeholder="Selecione" :options="['1' => 'Unidade A', '2' => 'Unidade B']" error="A unidade informada não existe." required full />
                <x-ui.textarea name="notes" label="Observações" hint="Opcional." class="md:col-span-2" full />
                <x-ui.segmented name="currency_choice" label="Moeda (segmented)" :options="['BRL' => 'BRL · Real', 'USD' => 'USD · Dólar']" value="USD" />
                <div class="flex items-end">
                    <x-ui.checkbox name="remember" label="Manter conectado" hint="Não use em computadores compartilhados." checked />
                </div>
            </div>
        </x-ui.card>
        <x-ui.card title="Datas" description="Calendário em português; o valor é AAAA-MM-DD (período: from/to). Funcionam com wire:model.">
            <div class="grid gap-5 md:grid-cols-2">
                <x-ui.date-picker name="expense_date" label="Data da despesa" value="2026-09-01" required full />
                <x-ui.date-range-picker name="period" label="Período" :value="['from' => '2026-09-01', 'to' => '2026-09-30']" full />
                <x-ui.date-picker name="empty_date" label="Sem valor" hint="Não aceita datas futuras." :max="now()->toDateString()" full />
                <x-ui.date-range-picker name="empty_period" label="Período sem valor" error="Informe o período do relatório." full />
            </div>
            <div class="mt-5 flex flex-wrap items-end gap-3">
                @foreach ($sizes as $size)
                    <x-ui.date-range-picker :name="'size_period_'.$size" :size="$size" :label="'Período '.$size" :value="['from' => '2026-09-01', 'to' => '2026-09-30']" />
                @endforeach
            </div>
        </x-ui.card>
        <x-ui.card title="Tamanhos">
            <div class="flex flex-col gap-4">
                @foreach ($sizes as $size)
                    <div class="flex flex-wrap items-end gap-3">
                        <x-ui.input :name="'size_input_'.$size" :size="$size" :label="'Input '.$size" icon="search" placeholder="Buscar" />
                        <x-ui.select :name="'size_select_'.$size" :size="$size" :label="'Select '.$size" placeholder="Todos os status" :options="['pending' => 'Pendente', 'converted' => 'Convertida']" />
                        <x-ui.segmented :name="'size_segmented_'.$size" :size="$size" :full="false" :options="['all' => 'Todos', 'brl' => 'BRL', 'usd' => 'USD']" value="all" />
                        <x-ui.button :size="$size" icon="check">Botão {{ $size }}</x-ui.button>
                    </div>
                @endforeach
            </div>
        </x-ui.card>
    </section>

    {{-- Tabela --}}
    <section class="{{ $section }}" aria-labelledby="tabela">
        <h2 id="tabela" class="{{ $sectionTitle }}">Tabela</h2>
        <x-ui.table caption="Despesas de exemplo">
            <x-slot:toolbar>
                <x-ui.table.toolbar>
                    <x-ui.input name="table_search" icon="search" size="sm" placeholder="Buscar por descrição ou fornecedor" class="w-full md:w-72" full />
                    <x-ui.select name="table_status" size="sm" placeholder="Todos os status" :options="['pending' => 'Pendente', 'converted' => 'Convertida', 'failed' => 'Falhou']" />
                    <x-slot:end>
                        <x-ui.button variant="ghost" size="sm" icon="rotate-ccw">Limpar</x-ui.button>
                        <x-ui.button size="sm" icon="plus">Nova despesa</x-ui.button>
                    </x-slot:end>
                </x-ui.table.toolbar>
            </x-slot:toolbar>

            <x-slot:head>
                <x-ui.table.head sortable="date" sorted-by="date" direction="desc">Data</x-ui.table.head>
                <x-ui.table.head sortable="description">Despesa</x-ui.table.head>
                <x-ui.table.head>Valor original</x-ui.table.head>
                <x-ui.table.head>Valor em BRL</x-ui.table.head>
                <x-ui.table.head align="center">Status</x-ui.table.head>
                <x-ui.table.head align="center">Ações</x-ui.table.head>
            </x-slot:head>

            @foreach ($paginator as [$date, $description, $supplier, $original, $brl, $status])
                <x-ui.table.row>
                    <x-ui.table.cell muted class="whitespace-nowrap tabular-nums">{{ Carbon\CarbonImmutable::parse($date)->format('d/m/Y') }}</x-ui.table.cell>
                    <x-ui.table.cell>
                        <p class="font-medium">{{ $description }}</p>
                        <p class="text-xs text-ds-gray-500">{{ $supplier }}</p>
                    </x-ui.table.cell>
                    <x-ui.table.cell numeric>{{ $original }}</x-ui.table.cell>
                    <x-ui.table.cell numeric @class(['font-semibold' => $brl])>{{ $brl ?? '—' }}</x-ui.table.cell>
                    <x-ui.table.cell align="center"><x-ui.status-badge :status="$status" /></x-ui.table.cell>
                    <x-ui.table.cell align="center">
                        <x-ui.dropdown>
                            <x-slot:trigger>
                                <x-ui.button variant="ghost" size="sm" icon="ellipsis" icon-only aria-label="Ações de {{ $description }}" />
                            </x-slot:trigger>
                            <x-ui.dropdown.item icon="eye">Ver detalhes</x-ui.dropdown.item>
                            @if ($status === App\Enums\ConversionStatus::Failed)
                                <x-ui.dropdown.item icon="refresh-cw">Tentar conversão de novo</x-ui.dropdown.item>
                            @endif
                            <x-ui.dropdown.separator />
                            <x-ui.dropdown.item icon="trash-2" danger x-on:click="$dispatch('open-modal', 'confirm-delete')">Excluir</x-ui.dropdown.item>
                        </x-ui.dropdown>
                    </x-ui.table.cell>
                </x-ui.table.row>
            @endforeach

            <x-slot:footer>
                <x-ui.pagination :paginator="$paginator" label="despesas" />
            </x-slot:footer>
        </x-ui.table>

        <div class="grid gap-4 lg:grid-cols-2">
            <x-ui.table caption="Tabela vazia">
                <x-slot:head>
                    <x-ui.table.head>Unidade</x-ui.table.head>
                    <x-ui.table.head>Total</x-ui.table.head>
                </x-slot:head>
                <x-ui.table.empty colspan="2" icon="search" title="Nenhum resultado" description="Nenhuma despesa corresponde aos filtros." />
            </x-ui.table>
            <x-ui.table caption="Tabela carregando">
                <x-slot:head>
                    <x-ui.table.head>Unidade</x-ui.table.head>
                    <x-ui.table.head>Total</x-ui.table.head>
                </x-slot:head>
                <x-ui.table.loading colspan="2" rows="4" />
            </x-ui.table>
        </div>
    </section>

    {{-- Sobreposições --}}
    <section class="{{ $section }}" aria-labelledby="sobreposicoes">
        <h2 id="sobreposicoes" class="{{ $sectionTitle }}">Modal e confirmação</h2>
        <x-ui.card>
            <div class="flex flex-wrap gap-3">
                <x-ui.button variant="outline" icon="plus" x-on:click="$dispatch('open-modal', 'new-unit')">Abrir modal</x-ui.button>
                <x-ui.button variant="danger-outline" icon="trash-2" x-on:click="$dispatch('open-modal', 'confirm-delete')">Abrir confirmação</x-ui.button>
            </div>
        </x-ui.card>
    </section>

    <x-ui.modal name="new-unit" :show="request('open') === 'new-unit'" title="Nova unidade" description="A unidade recebe parte das despesas rateadas.">
        <div class="flex flex-col gap-4">
            <x-ui.select name="modal_company" label="Empresa" :options="['1' => 'Acme Holding', '2' => 'Globex']" required full />
            <x-ui.input name="modal_name" label="Nome da unidade" value="Unidade São Paulo" required full />
            <x-ui.input name="modal_slug" label="Slug" value="unidade-sao-paulo" hint="Gerado a partir do nome. É o identificador da unidade na importação por CSV." mono full />
        </div>
        <x-slot:footer>
            <x-ui.button variant="outline" x-on:click="$dispatch('close-modal', 'new-unit')">Cancelar</x-ui.button>
            <x-ui.button x-on:click="$dispatch('close-modal', 'new-unit'); $dispatch('toast', { type: 'success', message: 'Unidade criada.' })">Salvar unidade</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.confirm
        name="confirm-delete"
        :show="request('open') === 'confirm-delete'"
        title="Excluir despesa"
        description="A despesa e o seu rateio serão removidos. Esta ação não pode ser desfeita."
        confirm-label="Excluir"
        danger
        x-on:confirmed-confirm-delete.window="$dispatch('toast', { type: 'success', message: 'Despesa excluída.' })"
    />
</x-layouts::app>
