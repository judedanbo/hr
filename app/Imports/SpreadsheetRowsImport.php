<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

/**
 * Reads a worksheet verbatim, keeping the heading row and the original column
 * order so callers can resolve columns by their printed heading.
 */
class SpreadsheetRowsImport implements ToArray
{
    /**
     * @param  array<int, array<int, mixed>>  $rows
     * @return array<int, array<int, mixed>>
     */
    public function array(array $rows): array
    {
        return $rows;
    }
}
