<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\ExpenseData;
use App\Http\Requests\Concerns\SquishesInput;
use App\Validation\ExpenseRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class StoreExpenseRequest extends FormRequest
{
    use SquishesInput;

    /**
     * @return array<string, array<int, ValidationRule|string|\Stringable>>
     */
    public function rules(): array
    {
        return ExpenseRules::rules();
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [ExpenseRules::allocationsSumToOneHundred()];
    }

    public function toData(): ExpenseData
    {
        return ExpenseData::fromValidated($this->validated());
    }

    protected function prepareForValidation(): void
    {
        $this->squish('description', 'supplier');
    }
}
