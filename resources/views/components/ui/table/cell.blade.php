{{--
    Célula da tabela.
    - muted: informação secundária (cinza)
    - numeric: valores e quantidades (à direita, algarismos de largura fixa para alinhar as colunas)
    - mono: identificadores (slug, código)
--}}
@props([
    'align' => 'left',
    'muted' => false,
    'numeric' => false,
    'mono' => false,
])

<td {{ $attributes->class([
    'px-4 py-3 align-middle',
    'text-ds-gray-500' => $muted,
    'text-ds-gray-900' => ! $muted,
    'text-right tabular-nums whitespace-nowrap' => $numeric || $align === 'right',
    'text-center' => $align === 'center',
    'font-mono text-xs' => $mono,
]) }}>
    {{ $slot }}
</td>
