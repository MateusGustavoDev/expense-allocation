{{-- Linhas esqueleto enquanto os dados carregam (ex.: placeholder de componente Livewire lazy). --}}
@props([
    'colspan',
    'rows' => 5,
])

@for ($row = 0; $row < $rows; $row++)
    <tr aria-hidden="true">
        @for ($column = 0; $column < $colspan; $column++)
            <td class="px-4 py-3.5">
                <div @class(['h-4 animate-pulse rounded bg-ds-gray-100', 'w-3/4' => $column === 0, 'w-1/2' => $column > 0])></div>
            </td>
        @endfor
    </tr>
@endfor
