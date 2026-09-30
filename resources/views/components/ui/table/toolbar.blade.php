{{-- Barra de filtros da tabela: filtros no slot padrão (esquerda), ações no slot end (direita). --}}
<div {{ $attributes->class(['flex flex-col gap-3 md:flex-row md:items-center md:justify-between']) }}>
    <div class="flex flex-1 flex-wrap items-center gap-2">{{ $slot }}</div>

    @isset($end)
        <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $end }}</div>
    @endisset
</div>
