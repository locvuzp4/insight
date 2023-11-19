<?php

namespace App\Http\Services;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExportExcel implements FromArray, WithStyles, ShouldAutoSize
{
    protected $header;

    protected $body;

    public function __construct($header, $body)
    {
        $this->header = $header;
        $this->body = $body;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function array(): array
    {
        return array_merge([$this->header], $this->body);
    }

    public function export($name)
    {
        return Excel::download($this, $name);
    }
}
