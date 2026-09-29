<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\SquishesInput;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CompanyRequest extends FormRequest
{
    use SquishesInput;

    /**
     * @return array<string, array<int, ValidationRule|string|\Stringable>>
     */
    public function rules(): array
    {
        return [
            // Na edição, ignora a própria empresa na checagem de nome único
            'name' => ['required', 'string', 'max:120', Rule::unique('companies')->ignore($this->route('company'))],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->squish('name');
    }
}
