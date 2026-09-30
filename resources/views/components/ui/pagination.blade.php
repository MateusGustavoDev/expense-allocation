{{--
    Paginação a partir do LengthAwarePaginator do Laravel (o retorno de ->paginate()).

    <x-ui.pagination :paginator="$expenses" livewire />     botões chamam gotoPage() do trait WithPagination
    <x-ui.pagination :paginator="$expenses" />              links comuns (?page=2)

    Páginas: todas até 5; acima disso, primeira, vizinhas da atual e última, com reticências.
--}}
@props([
    'paginator',
    'livewire' => false,
    'label' => 'resultados',
])

@php
    $current = $paginator->currentPage();
    $last = $paginator->lastPage();

    $pages = $last <= 5
        ? range(1, $last)
        : collect([1, $current - 1, $current, $current + 1, $last])
            ->filter(fn (int $page): bool => $page >= 1 && $page <= $last)
            ->unique()
            ->sort()
            ->values()
            ->reduce(function (array $carry, int $page): array {
                if ($carry !== [] && $page - end($carry) > 1) {
                    $carry[] = '...';
                }
                $carry[] = $page;

                return $carry;
            }, []);

    $pageName = $paginator->getPageName();

    // Atributos de navegação para uma página: wire:click no Livewire, href fora dele
    $goTo = fn (int $page): array => $livewire
        ? ['wire:click' => "gotoPage({$page}, '{$pageName}')"]
        : ['href' => $paginator->url($page)];
@endphp

<div {{ $attributes->class(['flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between']) }}>
    <p class="text-sm text-ds-gray-500">
        @if ($paginator->total() > 0)
            Mostrando <span class="font-medium text-ds-gray-700">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</span>
            de <span class="font-medium text-ds-gray-700">{{ $paginator->total() }}</span> {{ $label }}
        @else
            Nenhum {{ Str::singular($label) }}
        @endif
    </p>

    @if ($paginator->hasPages())
        <nav aria-label="Paginação" class="flex items-center justify-between gap-1 sm:justify-start">
            {{-- Celular: só anterior, "página X de Y" e próxima; números e extremos a partir de sm --}}
            <span class="hidden sm:contents">
                <x-ui.button variant="outline" size="sm" icon="chevrons-left" icon-only aria-label="Primeira página"
                    :disabled="$paginator->onFirstPage()" :attributes="new Illuminate\View\ComponentAttributeBag($paginator->onFirstPage() ? [] : $goTo(1))" />
            </span>
            <x-ui.button variant="outline" size="sm" icon="chevron-left" icon-only aria-label="Página anterior"
                :disabled="$paginator->onFirstPage()" :attributes="new Illuminate\View\ComponentAttributeBag($paginator->onFirstPage() ? [] : $goTo($current - 1))" />
            <span class="px-3 text-sm text-ds-gray-600 tabular-nums sm:hidden">{{ $current }} de {{ $last }}</span>
            <span class="hidden items-center gap-1 sm:flex">
                @foreach ($pages as $page)
                    @if ($page === '...')
                        <span class="flex size-8 items-center justify-center text-ds-gray-400" aria-hidden="true">
                            <x-ui.icon name="ellipsis" class="size-4" />
                        </span>
                    @elseif ($page === $current)
                        <x-ui.button variant="secondary" size="sm" class="min-w-8 px-2" aria-current="page" aria-label="Página {{ $page }}, atual">{{ $page }}</x-ui.button>
                    @else
                        <x-ui.button variant="ghost" size="sm" class="min-w-8 px-2" aria-label="Página {{ $page }}"
                            :attributes="new Illuminate\View\ComponentAttributeBag($goTo($page))">{{ $page }}</x-ui.button>
                    @endif
                @endforeach
            </span>
            <x-ui.button variant="outline" size="sm" icon="chevron-right" icon-only aria-label="Próxima página"
                :disabled="! $paginator->hasMorePages()" :attributes="new Illuminate\View\ComponentAttributeBag($paginator->hasMorePages() ? $goTo($current + 1) : [])" />
            <span class="hidden sm:contents">
                <x-ui.button variant="outline" size="sm" icon="chevrons-right" icon-only aria-label="Última página"
                    :disabled="! $paginator->hasMorePages()" :attributes="new Illuminate\View\ComponentAttributeBag($paginator->hasMorePages() ? $goTo($last) : [])" />
            </span>
        </nav>
    @endif
</div>
