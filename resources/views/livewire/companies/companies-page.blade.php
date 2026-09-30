<div class="flex flex-col gap-6">
    <x-ui.page-header title="Empresas" description="Empresas do grupo. Cada unidade pertence a uma empresa." :breadcrumbs="['Cadastros' => null, 'Empresas' => null]">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="create">Nova empresa</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.table caption="Empresas cadastradas">
        <x-slot:mobile>
            @forelse ($this->companies as $company)
                <li wire:key="company-card-{{ $company->id }}" class="flex items-center gap-2 py-3 pr-2 pl-4">
                    <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                        <p class="truncate text-sm font-medium text-ds-gray-900">{{ $company->name }}</p>
                        <p class="text-xs text-ds-gray-500">{{ $company->units_count }} {{ Str::plural('unidade', $company->units_count) }}</p>
                    </div>
                    <x-ui.dropdown>
                        <x-slot:trigger>
                            <x-ui.button variant="ghost" size="sm" icon="ellipsis" icon-only aria-label="Ações de {{ $company->name }}" />
                        </x-slot:trigger>
                        <x-ui.dropdown.item icon="pencil" wire:click="edit({{ $company->id }})">Editar</x-ui.dropdown.item>
                        <x-ui.dropdown.separator />
                        <x-ui.dropdown.item icon="trash-2" danger wire:click="confirmDelete({{ $company->id }})">Excluir</x-ui.dropdown.item>
                    </x-ui.dropdown>
                </li>
            @empty
                <li>
                    <x-ui.empty icon="briefcase" title="Nenhuma empresa cadastrada" description="Cadastre as empresas do grupo antes das unidades.">
                        <x-slot:action>
                            <x-ui.button size="sm" icon="plus" wire:click="create">Nova empresa</x-ui.button>
                        </x-slot:action>
                    </x-ui.empty>
                </li>
            @endforelse
        </x-slot:mobile>

        <x-slot:head>
            <x-ui.table.head>Empresa</x-ui.table.head>
            <x-ui.table.head>Unidades</x-ui.table.head>
            <x-ui.table.head align="center">Ações</x-ui.table.head>
        </x-slot:head>

        @forelse ($this->companies as $company)
            <x-ui.table.row wire:key="company-{{ $company->id }}">
                <x-ui.table.cell class="font-medium">{{ $company->name }}</x-ui.table.cell>
                <x-ui.table.cell numeric>{{ $company->units_count }}</x-ui.table.cell>
                <x-ui.table.cell align="center">
                    <x-ui.dropdown>
                        <x-slot:trigger>
                            <x-ui.button variant="ghost" size="sm" icon="ellipsis" icon-only aria-label="Ações de {{ $company->name }}" />
                        </x-slot:trigger>
                        <x-ui.dropdown.item icon="pencil" wire:click="edit({{ $company->id }})">Editar</x-ui.dropdown.item>
                        <x-ui.dropdown.separator />
                        <x-ui.dropdown.item icon="trash-2" danger wire:click="confirmDelete({{ $company->id }})">Excluir</x-ui.dropdown.item>
                    </x-ui.dropdown>
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
