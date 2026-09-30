<?php

declare(strict_types=1);

use App\Enums\ConversionStatus;
use App\Models\User;
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

it('shows loading only on the button that fired the request', function () {
    $this->blade('<x-ui.button wire:click="retry(1)" icon="refresh-cw">Tentar</x-ui.button>')
        ->assertSee('data-loading:pointer-events-none', false)
        ->assertSee('group-data-loading/button:block', false)
        ->assertDontSee('wire:target', false);
});

it('covers the text with the spinner when the button has no icon', function () {
    $this->blade('<x-ui.button wire:click="gotoPage(3)">3</x-ui.button>')
        ->assertSee('<span class="group-data-loading/button:invisible">3</span>', false)
        ->assertSee('absolute inset-0 hidden items-center justify-center group-data-loading/button:flex', false);
});

it('ties the loading state to an explicit wire:target', function () {
    $this->blade('<x-ui.button type="submit" wire:target="save" icon="check">Salvar</x-ui.button>')
        ->assertSee('wire:loading.attr="disabled"', false)
        ->assertSee('wire:target="save"', false);
});

it('does not share the loading state between pagination buttons with the same call', function () {
    $paginator = new LengthAwarePaginator(range(1, 15), 45, 15, 1);

    // "Próxima" e "Página 2" chamam o mesmo gotoPage(2): um alvo por chamada deixaria os dois em loading
    $this->blade('<x-ui.pagination :paginator="$paginator" livewire />', ['paginator' => $paginator])
        ->assertSee('data-loading:pointer-events-none', false)
        ->assertDontSee('wire:target', false);
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

it('binds the date picker to wire:model through x-modelable', function () {
    $this->blade('<x-ui.date-picker wire:model="form.date" label="Data da despesa" required />')
        ->assertSee('x-data="datePicker(', false)
        ->assertSee('x-modelable="value"', false)
        ->assertSee('wire:model="form.date"', false)
        ->assertSee('for="field-form-date"', false)
        ->assertDontSee('type="hidden"', false);
});

it('posts the picked date through a hidden input outside Livewire', function () {
    $this->blade('<x-ui.date-picker name="date" value="2026-09-01" />')
        ->assertSee('<input type="hidden" name="date"', false)
        ->assertSee('value: \'2026-09-01\'', false);
});

it('posts both ends of the period outside Livewire', function () {
    $this->blade('<x-ui.date-range-picker name="period" :value="[\'from\' => \'2026-09-01\', \'to\' => \'2026-09-30\']" />')
        ->assertSee('x-data="dateRangePicker(', false)
        ->assertSee('name="period[from]"', false)
        ->assertSee('name="period[to]"', false);
});

it('renders overlay panels in the body so cards do not clip them', function () {
    $this->blade('<x-ui.date-range-picker name="period" />')->assertSee('x-teleport="body"', false);
    // Quebra de linha depois de </x-slot>: o Blade compila para @endslot sem espaço, e texto colado quebra a diretiva
    $this->blade("<x-ui.dropdown>\n<x-slot:trigger><button>Ações</button></x-slot:trigger>\nItem\n</x-ui.dropdown>")->assertSee('x-teleport="body"', false);
});

it('renders the logo as a decorative mark by default', function () {
    $this->blade('<x-ui.logo />')
        ->assertSee('aria-hidden="true"', false)
        ->assertSee('size-8', false)
        ->assertSee('rounded-mark-md', false)
        ->assertSee('size-9/16', false);
});

it('keeps the logo proportions when the size changes', function () {
    // Só o lado muda: ícone e raio são frações do lado, iguais em qualquer tamanho
    $this->blade('<x-ui.logo size="xl" rounded="lg" />')
        ->assertSee('size-16', false)
        ->assertSee('rounded-mark-lg', false)
        ->assertSee('size-9/16', false);
});

it('exposes the logo as an image when it has a label', function () {
    $this->blade('<x-ui.logo label="Rateio" rounded="full" />')
        ->assertSee('role="img"', false)
        ->assertSee('aria-label="Rateio"', false)
        ->assertSee('rounded-full', false);
});

it('rejects an unknown logo size', function () {
    $this->blade('<x-ui.logo size="huge" />');
})->throws(ErrorException::class, 'Tamanho de logo desconhecido: huge');

it('links the favicon files in the application layout', function () {
    $this->get('/reports')
        ->assertOk()
        ->assertSee('rel="icon" href="'.asset('favicon.svg').'" type="image/svg+xml"', false)
        ->assertSee('rel="icon" href="'.asset('favicon.ico').'"', false)
        ->assertSee('rel="apple-touch-icon" href="'.asset('apple-touch-icon.png').'"', false);

    expect(public_path('favicon.svg'))->toBeFile()
        ->and(filesize(public_path('favicon.ico')))->toBeGreaterThan(0)
        ->and(public_path('apple-touch-icon.png'))->toBeFile();
});

it('shows the initials of the first and last names', function (string $name, string $initials) {
    $this->blade('<x-ui.avatar :name="$name" />', ['name' => $name])
        ->assertSee('>'.$initials.'</span>', false)
        ->assertSee('aria-hidden="true"', false);
})->with([
    'nome e sobrenome' => ['Ana Souza', 'AS'],
    'nome composto' => ['Maria da Silva Costa', 'MC'],
    'um nome só' => ['Administrador', 'AD'],
    'acento' => ['élida nogueira', 'ÉN'],
    'espaços sobrando' => ['  Ana   Souza ', 'AS'],
    'uma letra' => ['X', 'X'],
    'vazio' => ['', '?'],
]);

it('exposes the avatar as an image when it stands alone', function () {
    $this->blade('<x-ui.avatar name="Ana Souza" size="lg" labelled />')
        ->assertSee('role="img"', false)
        ->assertSee('aria-label="Ana Souza"', false)
        ->assertSee('size-12', false);
});

it('opens external navigation items in a new tab', function () {
    $this->blade('<x-ui.nav-item href="/docs/api" icon="code-xml" external>Documentação da API</x-ui.nav-item>')
        ->assertSee('target="_blank" rel="noopener"', false)
        ->assertSee('(abre em nova aba)')
        ->assertDontSee('arrow-up-right', false);
});

it('groups the api documentation under integrations and shows the user avatar', function () {
    $this->actingAs(User::factory()->create(['name' => 'Ana Souza']))
        ->get('/reports')
        ->assertSeeInOrder(['Operação', 'Cadastros', 'Integrações', 'Documentação da API'])
        ->assertSee('>AS</span>', false);
});
