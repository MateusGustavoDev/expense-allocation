<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Models\Unit;
use App\Validation\UnitRules;
use Livewire\Form;

// Formulário de unidade: mesmas regras e normalização do slug da API via UnitRules
final class UnitForm extends Form
{
    public string $company_id = '';

    public string $name = '';

    public string $slug = '';

    public function fillFrom(Unit $unit): void
    {
        $this->company_id = (string) $unit->company_id;
        $this->name = $unit->name;
        $this->slug = $unit->slug;
    }

    public function save(?Unit $unit = null): Unit
    {
        /** @var array{company_id: string, name: string, slug?: string} $normalized */
        $normalized = UnitRules::normalize(['company_id' => $this->company_id, 'name' => $this->name, 'slug' => $this->slug]);
        $this->name = $normalized['name'];
        $this->slug = $normalized['slug'] ?? '';

        /** @var array{company_id: string, name: string, slug: string} $validated */
        $validated = $this->validate(UnitRules::rules($this->company_id, $unit));

        if ($unit !== null) {
            $unit->update($validated);

            return $unit;
        }

        return Unit::create($validated);
    }
}
