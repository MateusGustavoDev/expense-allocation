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
{{-- x-data no body: a página inteira é um escopo Alpine; menu controla a gaveta de navegação no celular --}}
<body class="min-h-screen" x-data="{ menu: false }" x-on:keydown.escape.window="menu = false">
    <a href="#conteudo" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-60 focus:rounded-lg focus:bg-ds-white focus:px-4 focus:py-2 focus:shadow-lg">
        Pular para o conteúdo
    </a>

    {{-- Celular: barra superior fixa com a marca e o botão que abre a gaveta de navegação --}}
    <header class="sticky top-0 z-40 flex h-14 items-center justify-between border-b border-ds-gray-200 bg-ds-white/95 px-4 backdrop-blur lg:hidden">
        <a href="{{ route('reports.index') }}" class="flex items-center gap-2">
            <x-ui.logo size="sm" />
            <span class="text-base font-bold text-ds-gray-900">Rateio</span>
        </a>
        <x-ui.button variant="ghost" icon="menu" icon-only aria-label="Abrir menu" x-on:click="menu = true" x-bind:aria-expanded="menu" aria-controls="menu-celular" />
    </header>

    <div x-show="menu" x-cloak id="menu-celular" class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Menu">
        <div x-show="menu" x-transition.opacity class="absolute inset-0 bg-ds-black/40" x-on:click="menu = false"></div>
        <div
            x-show="menu"
            x-trap.inert.noscroll="menu"
            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition duration-150 ease-in"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="relative h-full w-72 max-w-[85vw] overflow-y-auto bg-ds-white px-4 py-5 shadow-xl"
        >
            <x-ui.button variant="ghost" size="sm" icon="x" icon-only aria-label="Fechar menu" class="absolute top-4 right-3" x-on:click="menu = false" />
            @include('layouts.partials.sidebar')
        </div>
    </div>

    <div class="flex min-h-screen">
        <aside class="sticky top-0 hidden h-screen w-64 shrink-0 border-r border-ds-gray-200 bg-ds-white px-4 py-5 lg:block">
            @include('layouts.partials.sidebar')
        </aside>

        <main id="conteudo" class="min-w-0 flex-1">
            <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 sm:py-8 lg:px-10">
                {{ $slot }}
            </div>
        </main>
    </div>

    <x-ui.toaster />
    {{-- Livewire e Alpine vêm do app.js (ver resources/js/app.js); aqui só a configuração --}}
    @livewireScriptConfig
</body>
</html>
