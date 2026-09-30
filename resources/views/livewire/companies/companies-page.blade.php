<div class="flex flex-col gap-6">
    <x-ui.page-header title="Empresas" description="Empresas do grupo. Cada unidade pertence a uma empresa." :breadcrumbs="['Cadastros' => null, 'Empresas' => null]">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="create">Nova empresa</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.table caption="Empresas cadastradas">
        <x-slot:head>
            <x-ui.table.head>Empresa</x-ui.table.head>
            <x-ui.table.head align="right">Unidades</x-ui.table.head>
            <x-ui.table.head><span class="sr-only">Ações</span></x-ui.table.head>
        </x-slot:head>

        @forelse ($this->companies as $company)
            <x-ui.table.row wire:key="company-{{ $company->id }}">
                <x-ui.table.cell class="font-medium">{{ $company->name }}</x-ui.table.cell>
                <x-ui.table.cell numeric>{{ $company->units_count }}</x-ui.table.cell>
                <x-ui.table.cell align="right" class="w-24">
                    <div class="flex justify-end gap-1">
                        <x-ui.button variant="ghost" size="sm" icon="pencil" icon-only aria-label="Editar {{ $company->name }}" wire:click="edit({{ $company->id }})" />
                        <x-ui.button variant="ghost" size="sm" icon="trash-2" icon-only aria-label="Excluir {{ $company->name }}" wire:click="confirmDelete({{ $company->id }})" />
                    </div>
                </x-ui.table.cell>
            </x-ui.table.row>
        @empty
            <x-ui.table.empty colspan="3" icon="briefcase" title="Nenhuma empresa cadastrada" description="Cadastre as empresas do grupo antes das unidades.">
                <x-slot:action>
                    <x-ui.button size="sm" icon="plus" wire:click="create">Nova empresa</x-ui.button>
                </x-slot:action>
            </x-ui.table.empty>
        @endforelse
    </x-ui.table>

    <x-ui.modal name="company-form" :title="$editingId ? 'Editar empresa' : 'Nova empresa'" description="Empresa do grupo que tem unidades participando do rateio.">
        <form novalidate id="company-form" wire:submit="save" class="flex flex-col gap-4">
            <x-ui.input wire:model="form.name" label="Nome" placeholder="Ex.: Acme Holding" required full />
        </form>

        <x-slot:footer>
            <x-ui.button variant="outline" x-on:click="$dispatch('close-modal', 'company-form')">Cancelar</x-ui.button>
            <x-ui.button type="submit" form="company-form" wire:target="save">{{ $editingId ? 'Salvar alterações' : 'Cadastrar empresa' }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.confirm
        name="delete-company"
        title="Excluir empresa"
        description="A empresa será removida. Empresas com unidades cadastradas não podem ser excluídas."
        confirm-label="Excluir"
        action="delete"
        danger
    />
</div>
