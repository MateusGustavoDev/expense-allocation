{{--
    Layout da aplicação: sidebar + conteúdo. É o layout padrão dos componentes Livewire de página
    (layouts::app) e pode ser usado em views Blade como <x-layouts::app title="...">.
--}}
@props(['title' => null])

@php
    $navigation = [
        'Operação' => [
            ['label' => 'Relatório', 'icon' => 'chart-column', 'route' => 'reports.index'],
            ['label' => 'Despesas', 'icon' => 'receipt', 'route' => 'expenses.index', 'active' => ['expenses.index', 'expenses.create', 'expenses.show']],
            ['label' => 'Importar CSV', 'icon' => 'file-up', 'route' => 'expenses.import'],
        ],
        'Cadastros' => [
            ['label' => 'Unidades', 'icon' => 'building-2', 'route' => 'units.index'],
            ['label' => 'Empresas', 'icon' => 'briefcase', 'route' => 'companies.index'],
        ],
        // Itens com url (em vez de route) ficam fora da interface e abrem em nova aba
        'Integrações' => [
            ['label' => 'Documentação da API', 'icon' => 'code-xml', 'url' => '/docs/api'],
        ],
    ];
@endphp

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    @include('layouts.partials.head', ['title' => $title])
</head>
{{-- x-data no body: a página inteira é um escopo Alpine, então qualquer elemento pode usar x-on e $dispatch --}}
<body class="min-h-screen" x-data>
    <a href="#conteudo" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-60 focus:rounded-lg focus:bg-ds-white focus:px-4 focus:py-2 focus:shadow-lg">
        Pular para o conteúdo
    </a>

    <div class="flex min-h-screen">
        <aside class="sticky top-0 hidden h-screen w-64 shrink-0 flex-col gap-7 border-r border-ds-gray-200 bg-ds-white px-4 py-5 lg:flex">
            <a href="{{ Route::has('reports.index') ? route('reports.index') : url('/') }}" class="flex items-center gap-2.5 px-2">
                <x-ui.logo />
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
                            @if (isset($item['url']))
                                <x-ui.nav-item :href="url($item['url'])" :icon="$item['icon']" external>{{ $item['label'] }}</x-ui.nav-item>
                            @else
                                <x-ui.nav-item
                                    :href="Route::has($item['route']) ? route($item['route']) : '#'"
                                    :icon="$item['icon']"
                                    :active="request()->routeIs(...($item['active'] ?? [$item['route']]))"
                                >{{ $item['label'] }}</x-ui.nav-item>
                            @endif
                        @endforeach
                    </div>
                @endforeach
            </nav>

            @auth
                <div class="mt-auto border-t border-ds-gray-200 pt-4">
                    <div class="flex items-center gap-2.5 px-2">
                        <x-ui.avatar :name="auth()->user()->name" />
                        <div class="flex min-w-0 flex-1 flex-col leading-tight">
                            <span class="truncate text-sm font-medium text-ds-gray-900" title="{{ auth()->user()->name }}">{{ auth()->user()->name }}</span>
                            <span class="truncate text-xs text-ds-gray-500" title="{{ auth()->user()->email }}">{{ auth()->user()->email }}</span>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-ui.button type="submit" variant="ghost" size="sm" icon="log-out" icon-only aria-label="Sair" title="Sair" />
                        </form>
                    </div>
                </div>
            @endauth
        </aside>

        <main id="conteudo" class="min-w-0 flex-1">
            <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-6 py-8 lg:px-10">
                {{ $slot }}
            </div>
        </main>
    </div>

    <x-ui.toaster />
    {{-- Livewire e Alpine vêm do app.js (ver resources/js/app.js); aqui só a configuração --}}
    @livewireScriptConfig
</body>
</html>
