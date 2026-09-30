{{--
    Pilha de notificações (toasts), incluída uma vez no layout. Dispare de qualquer lugar:
    - Livewire:  $this->dispatch('toast', type: 'success', message: 'Despesa criada.');
    - Alpine:    $dispatch('toast', { type: 'error', message: 'Falha ao salvar.' })
    - Redirect:  session()->flash('toast', ['type' => 'success', 'message' => '...'])

    Tipos: success, error, warning, info. Some sozinho após 5 s; aria-live anuncia a mensagem.
--}}
<div
    x-data="{
        toasts: [],
        add(toast) {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, type: toast.type ?? 'success', message: toast.message });
            setTimeout(() => this.remove(id), 5000);
        },
        remove(id) {
            this.toasts = this.toasts.filter((toast) => toast.id !== id);
        },
    }"
    x-on:toast.window="add($event.detail)"
    @if (session()->has('toast')) x-init="add(@js(session('toast')))" @endif
    aria-live="polite"
    class="pointer-events-none fixed right-4 bottom-4 z-60 flex w-full max-w-sm flex-col gap-2"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="translate-y-2 opacity-0"
            x-transition:enter-end="translate-y-0 opacity-100"
            class="pointer-events-auto flex items-start gap-3 rounded-lg border px-4 py-3 text-sm shadow-lg"
            x-bind:class="{
                'border-ds-green-200 bg-ds-green-50 text-ds-green-900': toast.type === 'success',
                'border-ds-red-200 bg-ds-red-50 text-ds-red-900': toast.type === 'error',
                'border-ds-yellow-200 bg-ds-yellow-50 text-ds-yellow-900': toast.type === 'warning',
                'border-ds-blue-200 bg-ds-blue-50 text-ds-blue-900': toast.type === 'info',
            }"
            role="status"
        >
            <x-ui.icon name="circle-check" class="mt-0.5 size-4 shrink-0 text-ds-green-700" x-show="toast.type === 'success'" />
            <x-ui.icon name="circle-x" class="mt-0.5 size-4 shrink-0 text-ds-red-700" x-show="toast.type === 'error'" />
            <x-ui.icon name="triangle-alert" class="mt-0.5 size-4 shrink-0 text-ds-yellow-800" x-show="toast.type === 'warning'" />
            <x-ui.icon name="info" class="mt-0.5 size-4 shrink-0 text-ds-blue-700" x-show="toast.type === 'info'" />

            <p class="flex-1" x-text="toast.message"></p>

            <button type="button" x-on:click="remove(toast.id)" aria-label="Fechar notificação" class="cursor-pointer rounded opacity-60 hover:opacity-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ds-primary-600">
                <x-ui.icon name="x" class="size-4" />
            </button>
        </div>
    </template>
</div>
