<?php

declare(strict_types=1);

it('serves the api documentation publicly', function () {
    $this->get('/docs/api')->assertOk();
});

it('exposes the openapi specification with the api routes', function () {
    $this->getJson('/docs/api.json')
        ->assertOk()
        ->assertJsonPath('info.title', 'Expense Allocation API')
        ->assertJsonStructure(['paths' => ['/companies', '/units']]);
});
