{{--
    Cabeçalho da página: trilha, título, descrição e ações.

    <x-ui.page-header title="Despesas" :breadcrumbs="['Operação' => null, 'Despesas' => null]">
        <x-slot:actions><x-ui.button icon="plus">Nova despesa</x-ui.button></x-slot:actions>
    </x-ui.page-header>

    breadcrumbs: [rótulo => url|null]; o último item é a página atual.
--}}
@props([
    'title',
    'description' => null,
    'breadcrumbs' => [],
])

<div {{ $attributes->class(['flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between']) }}>
    <div class="flex min-w-0 flex-col gap-1.5">
        @if ($breadcrumbs !== [])
            <nav aria-label="Trilha de navegação">
                <ol class="flex flex-wrap items-center gap-1.5 text-sm">
                    @foreach ($breadcrumbs as $label => $url)
                        @if (! $loop->first)
                            <li aria-hidden="true"><x-ui.icon name="chevron-right" class="size-3.5 text-ds-gray-400" /></li>
                        @endif
                        <li>
                            @if ($loop->last)
                                <span class="font-medium text-ds-gray-700" aria-current="page">{{ $label }}</span>
                            @elseif ($url)
                                <a href="{{ $url }}" class="text-ds-gray-500 hover:text-ds-gray-900">{{ $label }}</a>
                            @else
                                <span class="text-ds-gray-500">{{ $label }}</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </nav>
        @endif

        <h1 class="text-2xl font-bold tracking-tight text-ds-gray-900">{{ $title }}</h1>

        @if ($description)
            <p class="text-sm text-ds-gray-500">{{ $description }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
