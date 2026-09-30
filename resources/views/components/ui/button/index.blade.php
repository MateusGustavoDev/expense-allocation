{{--
    Botão (ou link com aparência de botão, quando recebe href).

    <x-ui.button icon="plus">Nova despesa</x-ui.button>
    <x-ui.button variant="outline" size="sm" icon="download" icon-direction="right">Exportar</x-ui.button>
    <x-ui.button variant="ghost" icon="ellipsis" icon-only aria-label="Ações" />
    <x-ui.button type="submit" wire:target="save">Salvar</x-ui.button>   loading automático do Livewire
    <x-ui.button :loading="$saving">Salvar</x-ui.button>                  loading manual

    Loading automático, com o botão bloqueado e o spinner no lugar do ícone:
    - wire:click: só enquanto a requisição disparada por ESTE botão roda (atributo data-loading que o
      Livewire põe no elemento de origem). Dois botões com a mesma chamada, como "Próxima" e "Página 2",
      não entram em loading juntos.
    - wire:target: sempre que o Livewire processa aquela ação, venha de onde vier (ex.: submit do formulário,
      cuja origem é o <form>, não o botão).
    Botão só com ícone exige aria-label.
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

    $target = $href ? null : $attributes->get('wire:target');
    $selfLoading = ! $href && $target === null && $attributes->wire('click')->value() !== '';
    // Sem ícone para trocar, o spinner cobre o texto (que fica invisível e preserva a largura do botão)
    $spinnerOverText = $selfLoading && $icon === null && ! $iconOnly;

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
            'group/button data-loading:pointer-events-none data-loading:opacity-50' => $selfLoading,
            'relative' => $spinnerOverText,
            $variantClasses,
            $boxClasses,
            'w-full' => $full,
        ]);
@endphp

<{{ $tag }} {{ $elementAttributes }}>
    @if ($spinnerOverText)
        <span class="group-data-loading/button:invisible">{{ $slot }}</span>
        <span class="absolute inset-0 hidden items-center justify-center group-data-loading/button:flex" aria-hidden="true">
            <x-ui.icon name="loader-circle" class="{{ $sizeConfig['icon'] }} animate-spin" />
        </span>
    @elseif ($iconOnly)
        <x-ui.button.leading :icon="$icon" :loading="$loading" :target="$target" :self-loading="$selfLoading" :size-class="$sizeConfig['icon']" />
    @else
        @if ($iconDirection === 'left')
            <x-ui.button.leading :icon="$icon" :loading="$loading" :target="$target" :self-loading="$selfLoading" :size-class="$sizeConfig['icon']" />
        @endif

        {{ $slot }}

        @if ($iconDirection === 'right')
            <x-ui.button.leading :icon="$icon" :loading="$loading" :target="$target" :self-loading="$selfLoading" :size-class="$sizeConfig['icon']" />
        @endif
    @endif
</{{ $tag }}>
