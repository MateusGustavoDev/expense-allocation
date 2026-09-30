<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Company;
use App\Models\Expense;
use App\Models\Unit;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Contrato de erro da API: sempre {"message": "..."} em português, sem trace nos erros do cliente (4xx).
 *
 * - Erros gerados pelo framework (404, 405, 413, 401...) vêm em inglês e alguns expõem detalhes internos, como a
 *   classe do model ("No query results for model [App\Models\Company] 9"): recebem mensagem própria.
 * - Exceções de domínio (ResourceInUseException, InvalidCsvFileException...) já trazem a mensagem em português:
 *   só perdem o trace, que o modo debug anexaria mesmo sendo um erro previsto.
 * - Erro inesperado (500) mantém o trace com APP_DEBUG ligado; em produção, só a mensagem genérica.
 * - Erros de validação (422 com "errors" por campo) seguem a renderização padrão: este renderer devolve null.
 */
final class ApiExceptionRenderer
{
    // abort(404) sem mensagem, por exemplo
    private const FALLBACK_MESSAGE = 'Não foi possível concluir a requisição.';

    private const MODEL_NOT_FOUND = [
        Company::class => 'Empresa não encontrada.',
        Unit::class => 'Unidade não encontrada.',
        Expense::class => 'Despesa não encontrada.',
    ];

    public function __invoke(Throwable $exception, Request $request): ?JsonResponse
    {
        // Validação (422 com "errors" por campo) e respostas prontas seguem a renderização do Laravel. Sem este
        // retorno, em produção (APP_DEBUG=false) elas cairiam no caso do erro inesperado e virariam 500
        if (! $request->is('api/*') || $exception instanceof ValidationException || $exception instanceof HttpResponseException) {
            return null;
        }

        // O handler já converteu ModelNotFoundException em NotFoundHttpException e AuthorizationException em
        // AccessDeniedHttpException: a exceção original fica em getPrevious()
        $previous = $exception->getPrevious();

        [$status, $message] = match (true) {
            $exception instanceof NotFoundHttpException && $previous instanceof ModelNotFoundException => [404, $this->modelNotFound($previous)],
            // Nenhuma rota casou com a URL
            $exception instanceof NotFoundHttpException && $request->route() === null => [404, 'Rota não encontrada.'],
            $exception instanceof MethodNotAllowedHttpException => [405, "Método {$request->method()} não permitido nesta rota. Métodos aceitos: {$this->allowedMethods($exception)}."],
            $exception instanceof PostTooLargeException => [413, 'O corpo da requisição excede o tamanho máximo permitido.'],
            $exception instanceof AuthenticationException => [401, 'Não autenticado.'],
            $exception instanceof AccessDeniedHttpException && $previous instanceof AuthorizationException => [403, 'Você não tem permissão para esta ação.'],
            $exception instanceof ThrottleRequestsException => [429, 'Muitas requisições. Tente novamente em instantes.'],
            // Erros de domínio e demais erros HTTP: a mensagem é nossa (em português); só o trace sai
            $exception instanceof HttpExceptionInterface => [$exception->getStatusCode(), $exception->getMessage() ?: self::FALLBACK_MESSAGE],
            // Em desenvolvimento o erro inesperado segue com o trace completo; em produção, só a mensagem
            ! config('app.debug') => [500, 'Erro interno do servidor.'],
            default => [null, null],
        };

        if ($status === null) {
            return null;
        }

        $headers = $exception instanceof HttpExceptionInterface ? $exception->getHeaders() : [];

        return new JsonResponse(['message' => $message], $status, $headers);
    }

    /**
     * @param  ModelNotFoundException<Model>  $exception
     */
    private function modelNotFound(ModelNotFoundException $exception): string
    {
        return self::MODEL_NOT_FOUND[$exception->getModel()] ?? 'Registro não encontrado.';
    }

    private function allowedMethods(MethodNotAllowedHttpException $exception): string
    {
        return $exception->getHeaders()['Allow'] ?? '';
    }
}
