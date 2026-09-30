<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class UnitTotalsReportRequest extends FormRequest
{
    /**
     * @return array<string, array<int, ValidationRule|string|\Stringable>>
     */
    public function rules(): array
    {
        return [
            // Início do período (inclusivo), AAAA-MM-DD
            'date_from' => ['bail', 'required', 'date_format:Y-m-d'],
            // Fim do período (inclusivo), AAAA-MM-DD
            'date_to' => ['bail', 'required', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ];
    }

    public function from(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('!Y-m-d', $this->string('date_from')->toString()) ?: throw new \UnexpectedValueException('Data inválida.');
    }

    public function to(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('!Y-m-d', $this->string('date_to')->toString()) ?: throw new \UnexpectedValueException('Data inválida.');
    }
}
