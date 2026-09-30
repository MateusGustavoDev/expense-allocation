{{--
    Célula da tabela. Padrão: tudo à esquerda; colunas de ação e de badge usam align="center" (célula e cabeçalho).
    - muted: informação secundária (cinza)
    - numeric: valores e quantidades (algarismos de largura fixa, sem quebra de linha)
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
    'tabular-nums whitespace-nowrap' => $numeric,
    'text-right' => $align === 'right',
    'text-center' => $align === 'center',
    'font-mono text-xs' => $mono,
]) }}>
    {{ $slot }}
</td>
