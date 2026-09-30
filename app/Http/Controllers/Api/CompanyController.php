<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Companies\DeleteCompany;
use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

#[Group('Empresas', weight: 1)]
final class CompanyController extends Controller
{
    /**
     * Listar empresas
     *
     * Ordenadas por nome, com a quantidade de unidades de cada uma.
     */
    public function index(): AnonymousResourceCollection
    {
        $companies = Company::query()
            ->withCount('units')
            ->orderBy('name')
            ->paginate();

        return CompanyResource::collection($companies);
    }

    /**
     * Cadastrar empresa
     */
    public function store(CompanyRequest $request): CompanyResource
    {
        /** @status 201 */
        return CompanyResource::make(Company::create($request->validated()));
    }

    /**
     * Exibir empresa
     */
    public function show(Company $company): CompanyResource
    {
        return CompanyResource::make($company->loadCount('units'));
    }

    /**
     * Atualizar empresa
     */
    public function update(CompanyRequest $request, Company $company): CompanyResource
    {
        $company->update($request->validated());

        return CompanyResource::make($company);
    }

    /**
     * Remover empresa
     *
     * Empresas com unidades cadastradas não podem ser removidas.
     */
    public function destroy(Company $company, DeleteCompany $deleteCompany): Response
    {
        $deleteCompany->execute($company);

        return response()->noContent();
    }
}
