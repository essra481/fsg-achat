<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

/**
 * Reads the four headed worksheets used by the procurement import workflow.
 * Validation and persistence deliberately live in ProcurementImportService.
 */
class ProcurementWorkbookImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    public function collection(Collection $rows): void
    {
        // Laravel Excel's toArray() collects each worksheet for the service.
    }
}
