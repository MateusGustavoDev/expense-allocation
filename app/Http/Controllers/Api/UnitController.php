<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UnitRequest;
use App\Http\Resources\UnitResource;
use App\Models\Unit;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

#[Group('Unidades', weight: 2)]
final class UnitController extends Controller
{
    /**
     * Listar unidades
     *
     * Ordenadas por nome, com a empresa a que pertencem.
     */
    #[QueryParameter('company_id', description: 'Filtra as unidades de uma empresa.', type: 'int')]
    public function index(Request $request): AnonymousResourceCollection
    {
        $units = Unit::query()
            ->with('company')
            ->when($request->integer('company_id'), fn ($query, int $companyId) => $query->where('company_id', $companyId))
            ->orderBy('name')
            ->paginate();

        return UnitResource::collection($units);
    }

    /**
     * Cadastrar unidade
     */
    public function store(UnitRequest $request): UnitResource
    {
        /** @status 201 */
        return UnitResource::make(Unit::create($request->validated())->load('company'));
    }

    /**
     * Exibir unidade
     */
    public function show(Unit $unit): UnitResource
    {
        return UnitResource::make($unit->load('company'));
    }

    /**
     * Atualizar unidade
     */
    public function update(UnitRequest $request, Unit $unit): UnitResource
    {
        $unit->update($request->validated());

        return UnitResource::make($unit->load('company'));
    }

    /**
     * Remover unidade
     */
    public function destroy(Unit $unit): Response
    {
        $unit->delete();

        return response()->noContent();
    }
}
