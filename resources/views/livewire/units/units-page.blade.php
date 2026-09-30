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
                <x-ui.table.cell align="center" class="w-24">
                    <div class="flex justify-center gap-1">
                        <x-ui.button variant="ghost" size="sm" icon="pencil" icon-only aria-label="Editar {{ $unit->name }}" wire:click="edit({{ $unit->id }})" />
                        <x-ui.button variant="ghost" size="sm" icon="trash-2" icon-only aria-label="Excluir {{ $unit->name }}" wire:click="confirmDelete({{ $unit->id }})" />
                    </div>
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
