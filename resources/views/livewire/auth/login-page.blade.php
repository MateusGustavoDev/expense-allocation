<x-ui.card>
    <form novalidate wire:submit="login" class="flex flex-col gap-5">
        <div class="flex flex-col gap-1">
            <h1 class="text-lg font-semibold text-ds-gray-900">Entrar</h1>
            <p class="text-sm text-ds-gray-500">Use o e-mail e a senha fornecidos pelo administrador.</p>
        </div>

        <x-ui.input wire:model="form.email" type="email" label="E-mail" placeholder="voce@empresa.com.br" autocomplete="username" autofocus required full />
        <x-ui.input wire:model="form.password" label="Senha" autocomplete="current-password" password required full />
        <x-ui.checkbox wire:model="form.remember" label="Manter conectado" />

        <x-ui.button type="submit" wire:target="login" full>Entrar</x-ui.button>
    </form>
</x-ui.card>
