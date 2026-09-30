{{--
    Cabeçalho de coluna. Com sortable="coluna", vira botão que chama sortBy('coluna') no componente Livewire
    e anuncia a ordenação atual com aria-sort.

    <x-ui.table.head sortable="date" :sorted-by="$sortBy" :direction="$sortDirection">Data</x-ui.table.head>
--}}
@props([
    'sortable' => null,
    'sortedBy' => null,
    'direction' => 'asc',
    'align' => 'left',
])

@php
    $active = $sortable !== null && $sortable === $sortedBy;
    $ariaSort = $active ? ($direction === 'desc' ? 'descending' : 'ascending') : ($sortable ? 'none' : null);
    $sortIcon = $active ? ($direction === 'desc' ? 'chevron-down' : 'chevron-up') : 'chevrons-up-down';
@endphp

<th
    scope="col"
    @if ($ariaSort) aria-sort="{{ $ariaSort }}" @endif
    {{ $attributes->class([
        'h-10 px-4 text-xs font-semibold tracking-wide whitespace-nowrap text-ds-gray-500 uppercase',
        'text-left' => $align === 'left',
        'text-center' => $align === 'center',
        'text-right' => $align === 'right',
    ]) }}
>
    @if ($sortable)
        <button
            type="button"
            wire:click="sortBy('{{ $sortable }}')"
            @class([
                'inline-flex cursor-pointer items-center gap-1.5 rounded uppercase hover:text-ds-gray-900',
                'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ds-primary-600',
                'flex-row-reverse' => $align === 'right',
                'text-ds-gray-900' => $active,
            ])
        >
            {{ $slot }}
            <x-ui.icon :name="$sortIcon" @class(['size-3.5', 'opacity-50' => ! $active]) />
        </button>
    @else
        {{ $slot }}
    @endif
</th>
