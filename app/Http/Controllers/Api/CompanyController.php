<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Exceptions\ResourceInUseException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class CompanyController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $companies = Company::query()
            ->withCount('units')
            ->orderBy('name')
            ->paginate();

        return CompanyResource::collection($companies);
    }

    public function store(CompanyRequest $request): CompanyResource
    {
        return CompanyResource::make(Company::create($request->validated()));
    }

    public function show(Company $company): CompanyResource
    {
        return CompanyResource::make($company->loadCount('units'));
    }

    public function update(CompanyRequest $request, Company $company): CompanyResource
    {
        $company->update($request->validated());

        return CompanyResource::make($company);
    }

    public function destroy(Company $company): Response
    {
        if ($company->units()->exists()) {
            throw new ResourceInUseException('Não é possível excluir uma empresa com unidades cadastradas.');
        }

        $company->delete();

        return response()->noContent();
    }
}
