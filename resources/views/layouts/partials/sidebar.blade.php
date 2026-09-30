{{-- Conteúdo da navegação lateral: marca, grupos de páginas e usuário. Usado na sidebar (desktop) e na gaveta (celular). --}}
<div class="flex h-full flex-col gap-7">
    <a href="{{ Route::has('reports.index') ? route('reports.index') : url('/') }}" class="flex items-center gap-2.5 px-2">
        <x-ui.logo />
        <span class="flex flex-col leading-tight">
            <span class="text-base font-bold text-ds-gray-900">Rateio</span>
            <span class="text-xs text-ds-gray-500">Despesas compartilhadas</span>
        </span>
    </a>

    <nav aria-label="Navegação principal" class="flex flex-col gap-6">
        @foreach ($navigation as $group => $items)
            <div class="flex flex-col gap-0.5">
                <p class="px-3 pb-2 text-[11px] font-semibold tracking-wider text-ds-gray-500 uppercase">{{ $group }}</p>
                @foreach ($items as $item)
                    @if (isset($item['url']))
                        <x-ui.nav-item :href="url($item['url'])" :icon="$item['icon']" external>{{ $item['label'] }}</x-ui.nav-item>
                    @else
                        <x-ui.nav-item
                            :href="Route::has($item['route']) ? route($item['route']) : '#'"
                            :icon="$item['icon']"
                            :active="request()->routeIs(...($item['active'] ?? [$item['route']]))"
                        >{{ $item['label'] }}</x-ui.nav-item>
                    @endif
                @endforeach
            </div>
        @endforeach
    </nav>

    @auth
        {{-- -mx-4 + px-4: a linha ocupa a largura toda da sidebar (que tem px-4) sem mexer no conteúdo --}}
        <div class="-mx-4 mt-auto border-t border-ds-gray-200 px-4 pt-4">
            <div class="flex items-center gap-2.5 px-2">
                <x-ui.avatar :name="auth()->user()->name" />
                <div class="flex min-w-0 flex-1 flex-col leading-tight">
                    <span class="truncate text-sm font-medium text-ds-gray-900" title="{{ auth()->user()->name }}">{{ auth()->user()->name }}</span>
                    <span class="truncate text-xs text-ds-gray-500" title="{{ auth()->user()->email }}">{{ auth()->user()->email }}</span>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-ui.button type="submit" variant="ghost" size="sm" icon="log-out" icon-only aria-label="Sair" title="Sair" />
                </form>
            </div>
        </div>
    @endauth
</div>
