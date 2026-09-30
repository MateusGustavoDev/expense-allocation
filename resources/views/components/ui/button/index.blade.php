{{--
    Botão (ou link com aparência de botão, quando recebe href).

    <x-ui.button icon="plus">Nova despesa</x-ui.button>
    <x-ui.button variant="outline" size="sm" icon="download" icon-direction="right">Exportar</x-ui.button>
    <x-ui.button variant="ghost" icon="ellipsis" icon-only aria-label="Ações" />
    <x-ui.button type="submit" wire:target="save">Salvar</x-ui.button>   loading automático do Livewire
    <x-ui.button :loading="$saving">Salvar</x-ui.button>                  loading manual

    Com wire:click ou wire:target, o botão fica desabilitado e troca o ícone pelo spinner enquanto o
    Livewire processa aquela ação. Botão só com ícone exige aria-label.
--}}
@props([
    'variant' => 'primary',
    'size' => 'md',
    'icon' => null,
    'iconDirection' => 'left',
    'iconOnly' => false,
    'full' => false,
    'loading' => false,
    'href' => null,
])

@php
    $variants = [
        // Texto escuro sobre o laranja: branco sobre primary-500 tem contraste 2.47:1 (reprova no WCAG AA)
        'primary' => 'bg-ds-primary-500 text-ds-black hover:bg-ds-primary-600',
        'secondary' => 'bg-ds-gray-100 text-ds-gray-900 hover:bg-ds-gray-200',
        'outline' => 'border border-ds-gray-300 bg-ds-white text-ds-gray-900 hover:bg-ds-gray-50',
        'ghost' => 'text-ds-gray-700 hover:bg-ds-gray-100 hover:text-ds-gray-900',
        'danger' => 'bg-ds-red-600 text-ds-white hover:bg-ds-red-700',
        'danger-outline' => 'border border-ds-red-200 bg-ds-red-50 text-ds-red-700 hover:bg-ds-red-100',
        'link' => 'text-ds-primary-700 underline-offset-4 hover:underline',
    ];

    $sizes = [
        'sm' => ['box' => 'h-8 gap-1.5 px-3 text-sm', 'square' => 'size-8', 'icon' => 'size-4'],
        'md' => ['box' => 'h-10 gap-2 px-4 text-sm', 'square' => 'size-10', 'icon' => 'size-4'],
        'lg' => ['box' => 'h-11 gap-2 px-5 text-base', 'square' => 'size-11', 'icon' => 'size-5'],
    ];

    $variantClasses = $variants[$variant] ?? throw new InvalidArgumentException("Variante de botão desconhecida: {$variant}");
    $sizeConfig = $sizes[$size] ?? throw new InvalidArgumentException("Tamanho de botão desconhecido: {$size}");

    if ($iconOnly && ! $attributes->has('aria-label') && ! app()->isProduction()) {
        throw new InvalidArgumentException('Botão só com ícone precisa de aria-label: o leitor de tela não tem outro texto para anunciar.');
    }

    // Ação do Livewire que controla o loading automático: wire:target explícito ou o método do wire:click
    $click = $attributes->wire('click')->value();
    $target = $href ? null : ($attributes->get('wire:target') ?? ($click ? Str::before($click, '(') : null));

    $tag = $href ? 'a' : 'button';

    $boxClasses = match (true) {
        $variant === 'link' => 'gap-1.5 text-sm',
        $iconOnly => $sizeConfig['square'],
        default => $sizeConfig['box'],
    };

    $elementAttributes = $attributes
        ->merge($href ? ['href' => $href] : ['type' => 'button'])
        ->merge($target ? ['wire:loading.attr' => 'disabled', 'wire:target' => $target] : [])
        ->merge($loading && ! $href ? ['disabled' => true, 'aria-busy' => 'true'] : [])
        ->class([
            'inline-flex shrink-0 cursor-pointer items-center justify-center rounded-lg font-medium whitespace-nowrap transition-colors select-none',
            'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ds-primary-600 focus-visible:ring-offset-2',
            'disabled:pointer-events-none disabled:opacity-50',
            $variantClasses,
            $boxClasses,
            'w-full' => $full,
        ]);
@endphp

<{{ $tag }} {{ $elementAttributes }}>
    @if ($iconOnly)
        <x-ui.button.leading :icon="$icon" :loading="$loading" :target="$target" :size-class="$sizeConfig['icon']" />
    @else
        @if ($iconDirection === 'left')
            <x-ui.button.leading :icon="$icon" :loading="$loading" :target="$target" :size-class="$sizeConfig['icon']" />
        @endif

        {{ $slot }}

        @if ($iconDirection === 'right')
            <x-ui.button.leading :icon="$icon" :loading="$loading" :target="$target" :size-class="$sizeConfig['icon']" />
        @endif
    @endif
</{{ $tag }}>
