<?php

declare(strict_types=1);

namespace App\Services;

class NumberToWordsService
{
    /**
     * Convert a number to its word representation in Spanish.
     */
    public function convert(float|int $number): string
    {
        $integerPart = (int)floor($number);
        
        if ($integerPart === 0) {
            return 'CERO';
        }

        return $this->convertInteger($integerPart);
    }

    private function convertInteger(int $number): string
    {
        if ($number < 0) {
            return 'MENOS ' . $this->convertInteger(abs($number));
        }

        $words = "";

        if ($number >= 1000000) {
            $millions = (int)floor($number / 1000000);
            $words .= $this->convertInteger($millions) . ($millions > 1 ? ' MILLONES ' : ' MILLÓN ');
            $number %= 1000000;
        }

        if ($number >= 1000) {
            $thousands = (int)floor($number / 1000);
            $words .= ($thousands === 1 ? '' : $this->convertInteger($thousands)) . ' MIL ';
            $number %= 1000;
        }

        if ($number >= 100) {
            $hundreds = (int)floor($number / 100);
            $hundredsList = [
                1 => ($number === 100 ? 'CIEN' : 'CIENTO'),
                2 => 'DOSCIENTOS',
                3 => 'TRESCIENTOS',
                4 => 'CUATROCIENTOS',
                5 => 'QUINIENTOS',
                6 => 'SEISCIENTOS',
                7 => 'SETECIENTOS',
                8 => 'OCHOCIENTOS',
                9 => 'NOVECIENTOS'
            ];
            $words .= $hundredsList[$hundreds] . ' ';
            $number %= 100;
        }

        if ($number >= 20) {
            $tens = (int)floor($number / 10);
            $tensList = [
                2 => ($number === 20 ? 'VEINTE' : 'VEINTI'),
                3 => ($number === 30 ? 'TREINTA' : 'TREINTA Y '),
                4 => ($number === 40 ? 'CUARENTA' : 'CUARENTA Y '),
                5 => ($number === 50 ? 'CINCUENTA' : 'CINCUENTA Y '),
                6 => ($number === 60 ? 'SESENTA' : 'SESENTA Y '),
                7 => ($number === 70 ? 'SETENTA' : 'SETENTA Y '),
                8 => ($number === 80 ? 'OCHOCIENTA' : 'OCHOCIENTA Y '),
                9 => ($number === 90 ? 'NOVENTA' : 'NOVENTA Y ')
            ];
            
            if ($number < 30 && $number > 20) {
                $words .= 'VEINTI';
            } else {
                $words .= $tensList[$tens];
            }
            
            $number %= 10;
            if ($number === 0 && $words !== "" && substr($words, -3) === " Y ") {
                 $words = substr($words, 0, -3);
            }
        }

        if ($number > 0) {
            $unitsList = [
                1 => 'UN',
                2 => 'DOS',
                3 => 'TRES',
                4 => 'CUATRO',
                5 => 'CINCO',
                6 => 'SEIS',
                7 => 'SIETE',
                8 => 'OCHO',
                9 => 'NUEVE',
                10 => 'DIEZ',
                11 => 'ONCE',
                12 => 'DOCE',
                13 => 'TRECE',
                14 => 'CATORCE',
                15 => 'QUINCE',
                16 => 'DIECISÉIS',
                17 => 'DIECISIETE',
                18 => 'DIECIOCHO',
                19 => 'DIECINUEVE'
            ];
            
            if ($number < 20) {
                 $words .= $unitsList[$number];
            } else {
                 $words .= $unitsList[$number];
            }
        }

        return trim($words);
    }
}
