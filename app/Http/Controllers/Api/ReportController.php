<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Reports\GetUnitTotalsReport;
use App\Http\Controllers\Controller;
use App\Http\Requests\UnitTotalsReportRequest;
use App\Http\Resources\UnitTotalsReportResource;
use Dedoc\Scramble\Attributes\Group;

#[Group('Relatórios', weight: 4)]
final class ReportController extends Controller
{
    /**
     * Total em BRL por unidade
     *
     * Soma, por unidade, o rateio em BRL das despesas convertidas no período. Despesas pendentes ou com
     * conversão falha ficam fora do total e são informadas em `unconverted`.
     */
    public function unitTotals(UnitTotalsReportRequest $request, GetUnitTotalsReport $report): UnitTotalsReportResource
    {
        return UnitTotalsReportResource::make($report->execute($request->from(), $request->to()));
    }
}
