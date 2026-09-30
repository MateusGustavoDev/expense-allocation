<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\SquishesInput;
use App\Models\Company;
use App\Validation\CompanyRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class CompanyRequest extends FormRequest
{
    use SquishesInput;

    /**
     * @return array<string, array<int, ValidationRule|string|\Stringable>>
     */
    public function rules(): array
    {
        /** @var Company|null $company */
        $company = $this->route('company');

        return CompanyRules::rules($company);
    }

    protected function prepareForValidation(): void
    {
        $this->squish('name');
    }
}
