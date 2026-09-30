<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Auth\AuthenticateUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

#[Group('Autenticação', weight: 0)]
final class AuthController extends Controller
{
    /**
     * Entrar
     *
     * Troca e-mail e senha por um token. Envie o token nas demais rotas no cabeçalho
     * `Authorization: Bearer {token}`. O token expira em 7 dias.
     *
     * @unauthenticated
     */
    public function login(LoginRequest $request, AuthenticateUser $authenticate): JsonResponse
    {
        $user = $authenticate->execute($request->string('email')->toString(), $request->string('password')->toString(), (string) $request->ip());

        $token = $user->createToken($request->string('device_name')->toString() ?: 'api');

        return response()->json([
            /** Token de acesso: mostrado só nesta resposta */
            'token' => $token->plainTextToken,
            'user' => UserResource::make($user),
        ]);
    }

    /**
     * Usuário autenticado
     */
    public function me(Request $request): UserResource
    {
        /** @var User $user */
        $user = $request->user();

        return UserResource::make($user);
    }

    /**
     * Sair
     *
     * Revoga o token usado nesta requisição. Os demais tokens do usuário continuam válidos.
     */
    public function logout(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $user->currentAccessToken()->delete();

        return response()->noContent();
    }
}
