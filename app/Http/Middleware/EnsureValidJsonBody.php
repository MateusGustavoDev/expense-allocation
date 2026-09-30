<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Recusa corpo JSON malformado com 400.
 *
 * Sem isso o Laravel decodifica o JSON inválido como vazio e responde 422 com "O campo nome é obrigatório.",
 * apontando o campo errado: o problema é a sintaxe do corpo, não o conteúdo.
 */
final class EnsureValidJsonBody
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $content = $request->getContent();

        if ($request->isJson() && trim($content) !== '' && ! json_validate($content)) {
            throw new BadRequestHttpException('O corpo da requisição não é um JSON válido.');
        }

        return $next($request);
    }
}
