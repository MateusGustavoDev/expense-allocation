{{-- Item do menu suspenso. danger destaca ações destrutivas. Com href vira link. --}}
@props([
    'icon' => null,
    'danger' => false,
    'href' => null,
])

@php
    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }}
    role="menuitem"
    {{ $attributes->merge($href ? ['href' => $href] : ['type' => 'button'])->class([
        'flex w-full cursor-pointer items-center gap-2 rounded-md px-3 py-2 text-left text-sm transition-colors',
        'focus-visible:outline-none',
        'text-ds-gray-700 hover:bg-ds-gray-100 hover:text-ds-gray-900 focus-visible:bg-ds-gray-100' => ! $danger,
        'text-ds-red-700 hover:bg-ds-red-50 focus-visible:bg-ds-red-50' => $danger,
    ]) }}
>
    @if ($icon)
        <x-ui.icon :name="$icon" @class(['size-4 shrink-0', 'text-ds-gray-500' => ! $danger]) />
    @endif
    {{ $slot }}
</{{ $tag }}>
