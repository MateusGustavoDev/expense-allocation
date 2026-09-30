<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            // E-mail do usuário
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
            // Nome do cliente que recebe o token (ex.: "Postman", "integração ERP"); aparece na lista de tokens
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
