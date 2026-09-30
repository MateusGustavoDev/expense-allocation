<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Data\ImportReport;
use App\Data\ImportRowError;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property ImportReport $resource
 */
final class ImportReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'total_rows' => $this->resource->totalRows,
            'imported' => $this->resource->importedCount,
            'failed' => $this->resource->failedCount(),
            'errors' => array_map(fn (ImportRowError $error): array => [
                'line' => $error->line,
                'content' => $error->content,
                'messages' => $error->messages,
            ], $this->resource->errors),
        ];
    }
}
