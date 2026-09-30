<div class="flex flex-col gap-6">
    <x-ui.page-header title="Unidades" description="Unidades que recebem o rateio das despesas. O slug identifica a unidade na importação por CSV." :breadcrumbs="['Cadastros' => null, 'Unidades' => null]">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="create" :disabled="$this->companyOptions === []">Nova unidade</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($this->companyOptions === [])
        <x-ui.alert variant="info" title="Cadastre uma empresa primeiro">
            Toda unidade pertence a uma empresa do grupo.
            <x-slot:actions>
                <a href="{{ route('companies.index') }}" class="underline underline-offset-2 hover:no-underline">Ir para empresas</a>
            </x-slot:actions>
        </x-ui.alert>
    @endif

    <x-ui.table caption="Unidades cadastradas">
        <x-slot:mobile>
            @forelse ($this->units as $unit)
                <li wire:key="unit-card-{{ $unit->id }}" class="flex items-center gap-2 py-3 pr-2 pl-4">
                    <div class="flex min-w-0 flex-1 flex-col gap-1">
                        <p class="truncate text-sm font-medium text-ds-gray-900">{{ $unit->name }}</p>
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-ds-gray-500">
                            <x-ui.badge variant="mono" size="sm">{{ $unit->slug }}</x-ui.badge>
                            <span class="truncate">{{ $unit->company->name }} · {{ $unit->allocations_count }} {{ Str::plural('despesa', $unit->allocations_count) }}</span>
                        </div>
                    </div>
                    <x-ui.dropdown>
                        <x-slot:trigger>
                            <x-ui.button variant="ghost" size="sm" icon="ellipsis" icon-only aria-label="Ações de {{ $unit->name }}" />
                        </x-slot:trigger>
                        <x-ui.dropdown.item icon="pencil" wire:click="edit({{ $unit->id }})">Editar</x-ui.dropdown.item>
                        <x-ui.dropdown.separator />
                        <x-ui.dropdown.item icon="trash-2" danger wire:click="confirmDelete({{ $unit->id }})">Excluir</x-ui.dropdown.item>
                    </x-ui.dropdown>
                </li>
            @empty
                <li><x-ui.empty icon="building-2" title="Nenhuma unidade cadastrada" description="Cadastre as unidades que recebem o rateio das despesas." /></li>
            @endforelse
        </x-slot:mobile>

        <x-slot:head>
            <x-ui.table.head>Unidade</x-ui.table.head>
            <x-ui.table.head align="center">Slug (usado no CSV)</x-ui.table.head>
            <x-ui.table.head>Empresa</x-ui.table.head>
            <x-ui.table.head>Despesas</x-ui.table.head>
            <x-ui.table.head align="center">Ações</x-ui.table.head>
        </x-slot:head>

        @forelse ($this->units as $unit)
            <x-ui.table.row wire:key="unit-{{ $unit->id }}">
                <x-ui.table.cell class="font-medium">{{ $unit->name }}</x-ui.table.cell>
                <x-ui.table.cell align="center"><x-ui.badge variant="mono">{{ $unit->slug }}</x-ui.badge></x-ui.table.cell>
                <x-ui.table.cell muted>{{ $unit->company->name }}</x-ui.table.cell>
                <x-ui.table.cell numeric>{{ $unit->allocations_count }}</x-ui.table.cell>
                <x-ui.table.cell align="center">
                    <x-ui.dropdown>
                        <x-slot:trigger>
                            <x-ui.button variant="ghost" size="sm" icon="ellipsis" icon-only aria-label="Ações de {{ $unit->name }}" />
                        </x-slot:trigger>
                        <x-ui.dropdown.item icon="pencil" wire:click="edit({{ $unit->id }})">Editar</x-ui.dropdown.item>
                        <x-ui.dropdown.separator />
                        <x-ui.dropdown.item icon="trash-2" danger wire:click="confirmDelete({{ $unit->id }})">Excluir</x-ui.dropdown.item>
                    </x-ui.dropdown>
                </x-ui.table.cell>
            </x-ui.table.row>
        @empty
            <x-ui.table.empty colspan="5" icon="building-2" title="Nenhuma unidade cadastrada" description="Cadastre as unidades que recebem o rateio das despesas." />
        @endforelse
    </x-ui.table>

    <x-ui.modal name="unit-form" :title="$editingId ? 'Editar unidade' : 'Nova unidade'" description="A unidade recebe parte das despesas rateadas.">
        <form novalidate id="unit-form" wire:submit="save" class="flex flex-col gap-4">
            <x-ui.select wire:model="form.company_id" label="Empresa" placeholder="Selecione a empresa" :options="$this->companyOptions" required full />
            <x-ui.input wire:model="form.name" label="Nome da unidade" placeholder="Ex.: Unidade São Paulo" required full />
            <x-ui.input wire:model="form.slug" label="Slug" placeholder="gerado a partir do nome" hint="Identificador da unidade na importação por CSV. Se ficar vazio, é gerado a partir do nome." mono full />
        </form>

        <x-slot:footer>
            <x-ui.button variant="outline" x-on:click="$dispatch('close-modal', 'unit-form')">Cancelar</x-ui.button>
            <x-ui.button type="submit" form="unit-form" wire:target="save">{{ $editingId ? 'Salvar alterações' : 'Cadastrar unidade' }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.confirm
        name="delete-unit"
        title="Excluir unidade"
        description="A unidade será removida. Unidades com despesas rateadas não podem ser excluídas."
        confirm-label="Excluir"
        action="delete"
        danger
    />
</div>
