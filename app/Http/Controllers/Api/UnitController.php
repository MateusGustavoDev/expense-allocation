<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UnitRequest;
use App\Http\Resources\UnitResource;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class UnitController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $units = Unit::query()
            ->with('company')
            ->when($request->integer('company_id'), fn ($query, int $companyId) => $query->where('company_id', $companyId))
            ->orderBy('name')
            ->paginate();

        return UnitResource::collection($units);
    }

    public function store(UnitRequest $request): UnitResource
    {
        return UnitResource::make(Unit::create($request->validated())->load('company'));
    }

    public function show(Unit $unit): UnitResource
    {
        return UnitResource::make($unit->load('company'));
    }

    public function update(UnitRequest $request, Unit $unit): UnitResource
    {
        $unit->update($request->validated());

        return UnitResource::make($unit->load('company'));
    }

    public function destroy(Unit $unit): Response
    {
        $unit->delete();

        return response()->noContent();
    }
}
