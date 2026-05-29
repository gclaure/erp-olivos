<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class ProductTemplateExport implements WithHeadings, WithTitle, WithStyles, WithColumnWidths, WithEvents
{
    use Exportable;

    public function headings(): array
    {
        return [
            'codigo_producto',
            'descripcion',
            'unidad_de_medida',
            'categoria',
            'fecha_compra',
            'cantidad',
            'costo_unitario',
            'tiene_vencimiento',
            'unidades_por_empaque',
            'nombre_empaque'
        ];
    }

    public function title(): string
    {
        return 'Plantilla Productos';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 50,
            'C' => 20,
            'D' => 20,
            'E' => 15,
            'F' => 15,
            'G' => 15,
            'H' => 15,
        ];
    }

    public function styles(Worksheet $sheet)
    {

        return [
            1    => [
                'font' => ['bold' => true],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Obtener la validación para la columna H (tiene_vencimiento)
                $validation = $event->sheet->getDelegate()->getCell('H2')->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setErrorTitle('Valor inválido');
                $validation->setError('Debes seleccionar SI o NO de la lista.');
                $validation->setPromptTitle('Elegir Opción');
                $validation->setPrompt('Elige SI o NO.');
                $validation->setFormula1('"SI,NO"');

                // Clonar la validación hasta la fila 1000
                for ($i = 3; $i <= 1000; $i++) {
                    $event->sheet->getDelegate()->getCell("H{$i}")->setDataValidation(clone $validation);
                }
            },
        ];
    }
}
