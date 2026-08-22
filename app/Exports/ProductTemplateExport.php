<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
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
            'tipo',
            'descripcion',
            'unidad_de_medida',
            'categoria',
            'fecha_compra',
            'cantidad',
            'costo_unitario',
            'tiene_vencimiento',
            'unidades_por_empaque',
            'nombre_empaque',
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
            'B' => 20,
            'C' => 50,
            'D' => 20,
            'E' => 20,
            'F' => 15,
            'G' => 15,
            'H' => 15,
            'I' => 18,
            'J' => 20,
            'K' => 20,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Validación para Columna B (tipo)
                $typeValidation = $event->sheet->getDelegate()->getCell('B2')->getDataValidation();
                $typeValidation->setType(DataValidation::TYPE_LIST);
                $typeValidation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $typeValidation->setAllowBlank(true);
                $typeValidation->setShowInputMessage(true);
                $typeValidation->setShowErrorMessage(true);
                $typeValidation->setShowDropDown(true);
                $typeValidation->setErrorTitle('Tipo inválido');
                $typeValidation->setError('Elige MATERIA_PRIMA o INSUMO.');
                $typeValidation->setPromptTitle('Tipo de Producto');
                $typeValidation->setPrompt('MATERIA_PRIMA (Controla stock/kardex) o INSUMO (Consumo directo sin stock).');
                $typeValidation->setFormula1('"MATERIA_PRIMA,INSUMO"');

                // Validación para Columna I (tiene_vencimiento)
                $expValidation = $event->sheet->getDelegate()->getCell('I2')->getDataValidation();
                $expValidation->setType(DataValidation::TYPE_LIST);
                $expValidation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $expValidation->setAllowBlank(true);
                $expValidation->setShowInputMessage(true);
                $expValidation->setShowErrorMessage(true);
                $expValidation->setShowDropDown(true);
                $expValidation->setErrorTitle('Valor inválido');
                $expValidation->setError('Debes seleccionar SI o NO de la lista.');
                $expValidation->setPromptTitle('Elegir Opción');
                $expValidation->setPrompt('Elige SI o NO.');
                $expValidation->setFormula1('"SI,NO"');

                // Clonar las validaciones hasta la fila 1000
                for ($i = 2; $i <= 1000; $i++) {
                    $event->sheet->getDelegate()->getCell("B{$i}")->setDataValidation(clone $typeValidation);
                    $event->sheet->getDelegate()->getCell("I{$i}")->setDataValidation(clone $expValidation);
                }
            },
        ];
    }
}
