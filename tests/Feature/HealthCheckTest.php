<?php

declare(strict_types=1);

it('responds on the health check route', function () {
    $this->get('/up')->assertOk();
});
