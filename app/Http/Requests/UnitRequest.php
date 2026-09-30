<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Unit;
use App\Validation\UnitRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class UnitRequest extends FormRequest
{
    /**
     * @return array<string, array<int, ValidationRule|string|\Stringable>>
     */
    public function rules(): array
    {
        /** @var Unit|null $unit */
        $unit = $this->route('unit');

        return UnitRules::rules($this->input('company_id'), $unit);
    }

    // Slug é opcional na entrada: quando ausente, é derivado do nome. Em ambos os casos é normalizado.
    protected function prepareForValidation(): void
    {
        $this->replace(UnitRules::normalize($this->all()));
    }
}
