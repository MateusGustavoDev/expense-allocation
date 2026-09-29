<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ConversionStatus;
use App\Enums\Currency;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListExpensesRequest extends FormRequest
{
    /**
     * @return array<string, array<int, ValidationRule|string|\Stringable>>
     */
    public function rules(): array
    {
        return [
            // Data inicial do período (inclusiva), AAAA-MM-DD
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            // Data final do período (inclusiva), AAAA-MM-DD
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'currency' => ['nullable', Rule::enum(Currency::class)],
            'conversion_status' => ['nullable', Rule::enum(ConversionStatus::class)],
            // Despesas rateadas para esta unidade
            'unit_id' => ['nullable', 'integer'],
        ];
    }
}
