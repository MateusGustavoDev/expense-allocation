<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UnitRequest extends FormRequest
{
    /**
     * @return array<string, array<int, ValidationRule|string|\Stringable>>
     */
    public function rules(): array
    {
        $unit = $this->route('unit');

        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'name' => [
                'required', 'string', 'max:120',
                Rule::unique('units')->where('company_id', $this->integer('company_id'))->ignore($unit),
            ],
            'slug' => ['required', 'string', 'max:120', 'alpha_dash', Rule::unique('units')->ignore($unit)],
        ];
    }

    // Slug é opcional na entrada: quando ausente, é derivado do nome. Em ambos os casos é normalizado.
    protected function prepareForValidation(): void
    {
        $source = $this->filled('slug') ? $this->string('slug') : $this->string('name');

        if ($source->isNotEmpty()) {
            $this->merge(['slug' => $source->slug()->toString()]);
        }
    }
}
