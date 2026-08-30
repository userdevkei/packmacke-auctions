<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LpoDetailedExport implements WithMultipleSheets
{
    protected $lpos;

    public function __construct($lpos)
    {
        $this->lpos = $lpos;
    }

    public function sheets(): array
    {
        return [
            new LpoSummarySheet($this->lpos),
            new LpoItemsSheet($this->lpos),
        ];
    }
}
