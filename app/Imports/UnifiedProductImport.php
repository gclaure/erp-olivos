<?php

declare(strict_types=1);

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UnifiedProductImport implements ToCollection, WithHeadingRow
{
    private array $data = [];

    public function collection(Collection $rows)
    {
        $this->data = $rows->toArray();
    }

    public function getData(): array
    {
        return $this->data;
    }
}
