<?php

declare(strict_types=1);

use App\Enums\ConversionStatus;
use Illuminate\Pagination\LengthAwarePaginator;

// Componentes Blade renderizados sem navegador (≈ render() da Testing Library)

it('renders a button with its variant, size and icon', function () {
    $this->blade('<x-ui.button variant="outline" size="sm" icon="plus">Nova despesa</x-ui.button>')
        ->assertSee('type="button"', false)
        ->assertSee('border-ds-gray-300', false)
        ->assertSee('h-8', false)
        ->assertSee('<svg', false)
        ->assertSeeText('Nova despesa');
});

it('turns a button into a link when it receives href', function () {
    $this->blade('<x-ui.button href="/despesas">Ver despesas</x-ui.button>')
        ->assertSee('<a', false)
        ->assertSee('href="/despesas"', false)
        ->assertDontSee('type="button"', false);
});

it('disables a loading button and marks it busy', function () {
    $this->blade('<x-ui.button loading>Salvando</x-ui.button>')
        ->assertSee('disabled', false)
        ->assertSee('aria-busy="true"', false)
        ->assertSee('animate-spin', false);
});

it('wires the Livewire loading state to the clicked action', function () {
    $this->blade('<x-ui.button wire:click="save(1)" icon="check">Salvar</x-ui.button>')
        ->assertSee('wire:loading.attr="disabled"', false)
        ->assertSee('wire:target="save"', false);
});

it('requires an aria-label on icon-only buttons', function () {
    $this->blade('<x-ui.button icon="trash-2" icon-only />');
})->throws(ErrorException::class, 'aria-label');

it('rejects an unknown button variant', function () {
    $this->blade('<x-ui.button variant="fancy">X</x-ui.button>');
})->throws(ErrorException::class, 'Variante de botão desconhecida: fancy');

it('links the input label, hint and required marker', function () {
    $this->blade('<x-ui.input name="supplier" label="Fornecedor" hint="Como aparece na nota." required />')
        ->assertSee('for="field-supplier"', false)
        ->assertSee('id="field-supplier"', false)
        ->assertSee('aria-describedby="field-supplier-hint"', false)
        ->assertSee('required', false)
        ->assertSeeText('Como aparece na nota.');
});

it('reads the input error from the validation bag by name', function () {
    // $errors chega aos componentes por ser compartilhado com todas as views, como no request real
    $this->withViewErrors(['form.amount' => 'O valor é obrigatório.'])
        ->blade('<x-ui.input wire:model="form.amount" label="Valor" />')
        ->assertSee('aria-invalid="true"', false)
        ->assertSee('aria-describedby="field-form-amount-error"', false)
        ->assertSee('border-ds-red-500', false)
        ->assertSeeText('O valor é obrigatório.');
});

it('adds an accessible visibility toggle to password inputs', function () {
    $this->blade('<x-ui.input name="password" label="Senha" password />')
        ->assertSee('type="password"', false)
        ->assertSee("x-bind:aria-label=\"visible ? 'Ocultar senha' : 'Mostrar senha'\"", false);
});

it('sends layout classes to the wrapper and the rest to the control', function () {
    $this->blade('<x-ui.input name="search" class="w-72" placeholder="Buscar" />')
        ->assertSeeInOrder(['w-72', '<input'], false)
        ->assertSee('placeholder="Buscar"', false);
});

it('marks the selected option of a select', function () {
    $this->blade('<x-ui.select name="currency" :options="[\'BRL\' => \'Real\', \'USD\' => \'Dólar\']" value="USD" placeholder="Selecione" />')
        ->assertSee('<option value="">Selecione</option>', false)
        ->assertSee('<option value="USD" selected', false);
});

it('maps each conversion status to a labelled badge', function (ConversionStatus $status, string $label, string $color) {
    $this->blade('<x-ui.status-badge :status="$status" />', ['status' => $status])
        ->assertSeeText($label)
        ->assertSee($color, false);
})->with([
    [ConversionStatus::Converted, 'Convertida', 'bg-ds-green-100'],
    [ConversionStatus::Pending, 'Pendente', 'bg-ds-yellow-100'],
    [ConversionStatus::Failed, 'Falhou', 'bg-ds-red-100'],
]);

it('announces warnings as alerts and information as status', function () {
    $this->blade('<x-ui.alert variant="warning" title="Total incompleto">Há pendências.</x-ui.alert>')->assertSee('role="alert"', false);
    $this->blade('<x-ui.alert variant="info">Informação.</x-ui.alert>')->assertSee('role="status"', false);
});

it('exposes the sort direction of a sortable column', function () {
    $this->blade('<x-ui.table.head sortable="date" sorted-by="date" direction="desc">Data</x-ui.table.head>')
        ->assertSee('aria-sort="descending"', false)
        ->assertSee("wire:click=\"sortBy('date')\"", false);

    $this->blade('<x-ui.table.head sortable="amount" sorted-by="date">Valor</x-ui.table.head>')
        ->assertSee('aria-sort="none"', false);
});

it('paginates with page links, the current page marked and a summary', function () {
    $paginator = new LengthAwarePaginator(range(1, 10), 95, 10, 5, ['path' => '/despesas']);

    $this->blade('<x-ui.pagination :paginator="$paginator" label="despesas" />', ['paginator' => $paginator])
        ->assertSeeText('Mostrando 41–50 de 95 despesas')
        ->assertSee('aria-current="page"', false)
        ->assertSee('href="/despesas?page=4"', false)
        ->assertSee('href="/despesas?page=6"', false)
        ->assertSee('href="/despesas?page=10"', false)
        ->assertDontSee('href="/despesas?page=8"', false);
});

it('paginates through Livewire when asked', function () {
    $paginator = new LengthAwarePaginator(range(1, 10), 30, 10, 1, ['path' => '/despesas']);

    $this->blade('<x-ui.pagination :paginator="$paginator" livewire />', ['paginator' => $paginator])
        ->assertSee("wire:click=\"gotoPage(2, 'page')\"", false);
});

it('renders an accessible modal opened by name', function () {
    $this->blade('<x-ui.modal name="new-unit" title="Nova unidade" description="Descrição">Conteúdo</x-ui.modal>')
        ->assertSee('role="dialog"', false)
        ->assertSee('aria-modal="true"', false)
        ->assertSee('aria-labelledby="modal-new-unit-title"', false)
        ->assertSee('x-trap.inert.noscroll="open"', false)
        ->assertSee('aria-label="Fechar"', false);
});

it('calls the Livewire action when a confirmation is accepted', function () {
    $this->blade('<x-ui.confirm name="delete-unit" title="Excluir" action="delete" danger />')
        ->assertSee('wire:click="delete"', false)
        ->assertSee('bg-ds-red-600', false);
});
