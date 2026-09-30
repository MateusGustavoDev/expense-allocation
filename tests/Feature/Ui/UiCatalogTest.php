<?php

declare(strict_types=1);

it('is not available outside the local environment', function () {
    $this->get('/ui')->assertNotFound();
});

it('renders every component of the catalog without errors', function () {
    // Renderizar o catálogo compila todos os componentes com todas as variantes e tamanhos
    $this->view('dev.ui-catalog')
        ->assertSeeText('Catálogo de componentes')
        ->assertSeeText('Mostrando 1–4 de 27 despesas');
});
