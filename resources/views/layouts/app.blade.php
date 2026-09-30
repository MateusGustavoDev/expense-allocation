{{--
    Layout da aplicação: sidebar + conteúdo. É o layout padrão dos componentes Livewire de página
    (layouts::app) e pode ser usado em views Blade como <x-layouts::app title="...">.
--}}
@props(['title' => null])

@php
    $navigation = [
        'Operação' => [
            ['label' => 'Relatório', 'icon' => 'chart-column', 'route' => 'reports.index'],
            ['label' => 'Despesas', 'icon' => 'receipt', 'route' => 'expenses.index'],
            ['label' => 'Importar CSV', 'icon' => 'file-up', 'route' => 'expenses.import'],
        ],
        'Cadastros' => [
            ['label' => 'Unidades', 'icon' => 'building-2', 'route' => 'units.index'],
            ['label' => 'Empresas', 'icon' => 'briefcase', 'route' => 'companies.index'],
        ],
    ];
@endphp

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? "{$title} · " : '' }}{{ config('app.name') }}</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen">
    <a href="#conteudo" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-60 focus:rounded-lg focus:bg-ds-white focus:px-4 focus:py-2 focus:shadow-lg">
        Pular para o conteúdo
    </a>

    <div class="flex min-h-screen">
        <aside class="sticky top-0 hidden h-screen w-64 shrink-0 flex-col gap-7 border-r border-ds-gray-200 bg-ds-white px-4 py-5 lg:flex">
            <a href="{{ Route::has('reports.index') ? route('reports.index') : url('/') }}" class="flex items-center gap-2.5 px-2">
                <span class="flex size-8 items-center justify-center rounded-lg bg-ds-primary-500 text-ds-black">
                    <x-ui.icon name="split" class="size-4.5" />
                </span>
                <span class="flex flex-col leading-tight">
                    <span class="text-base font-bold text-ds-gray-900">Rateio</span>
                    <span class="text-xs text-ds-gray-500">Despesas compartilhadas</span>
                </span>
            </a>

            <nav aria-label="Navegação principal" class="flex flex-col gap-6">
                @foreach ($navigation as $group => $items)
                    <div class="flex flex-col gap-0.5">
                        <p class="px-3 pb-2 text-[11px] font-semibold tracking-wider text-ds-gray-500 uppercase">{{ $group }}</p>
                        @foreach ($items as $item)
                            <x-ui.nav-item
                                :href="Route::has($item['route']) ? route($item['route']) : '#'"
                                :icon="$item['icon']"
                                :active="request()->routeIs($item['route'])"
                            >{{ $item['label'] }}</x-ui.nav-item>
                        @endforeach
                    </div>
                @endforeach
            </nav>

            <div class="mt-auto border-t border-ds-gray-200 pt-4">
                <x-ui.nav-item href="{{ url('/docs/api') }}" icon="book-open">Documentação da API</x-ui.nav-item>
            </div>
        </aside>

        <main id="conteudo" class="min-w-0 flex-1">
            <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-6 py-8 lg:px-10">
                {{ $slot }}
            </div>
        </main>
    </div>

    <x-ui.toaster />
    @livewireScripts
</body>
</html>
