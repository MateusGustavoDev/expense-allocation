<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Models\Company;
use App\Validation\CompanyRules;
use Illuminate\Support\Str;
use Livewire\Form;

// Formulário de empresa (≈ useForm + schema): mesmas regras da API via CompanyRules
final class CompanyForm extends Form
{
    public string $name = '';

    public function fillFrom(Company $company): void
    {
        $this->name = $company->name;
    }

    public function save(?Company $company = null): Company
    {
        $this->name = Str::squish($this->name);

        /** @var array{name: string} $validated */
        $validated = $this->validate(CompanyRules::rules($company));

        if ($company !== null) {
            $company->update($validated);

            return $company;
        }

        return Company::create($validated);
    }
}
