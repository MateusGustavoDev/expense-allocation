{{--
    Item da navegação lateral. active marca a página atual (destaque + aria-current).
    external abre em nova aba (páginas fora da interface, como a documentação da API), com aviso para leitor de tela.
--}}
@props([
    'href',
    'icon',
    'active' => false,
    'external' => false,
])

<a
    href="{{ $href }}"
    @if ($active) aria-current="page" @endif
    @if ($external) target="_blank" rel="noopener" @endif
    {{ $attributes->class([
        'flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ds-primary-600',
        'bg-ds-primary-50 text-ds-primary-800' => $active,
        'text-ds-gray-700 hover:bg-ds-gray-100 hover:text-ds-gray-900' => ! $active,
    ]) }}
>
    <x-ui.icon :name="$icon" @class(['size-4.5 shrink-0', 'text-ds-primary-600' => $active, 'text-ds-gray-500' => ! $active]) />
    {{ $slot }}
    @if ($external)
        <span class="sr-only">(abre em nova aba)</span>
    @endif
</a>
