<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\BaseDrawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use App\Models\Signataire;


class ConsultationExport implements
    FromView,
    WithColumnWidths,
    WithDrawings,
    WithEvents
{
    protected $consultation;
    protected $type;
    protected $procesverbal;
    protected $repartition = [];
    protected $egalites = [];
    protected $doyen;

    public function __construct(
        $consultation,
        $type = 'consultation',
        $repartition = [],
        $egalites = []
    ) {
        $this->consultation = $consultation;
        $this->type = $type;

        /*
        |--------------------------------------------------------------------------
        | Signataire - Doyen
        |--------------------------------------------------------------------------
        */
        $this->doyen = Signataire::where('type', 'العميد')
            ->where('actif', true)
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Données de répartition
        |--------------------------------------------------------------------------
        */
        $this->repartition = $repartition;
        $this->egalites = $egalites;

        /*
        |--------------------------------------------------------------------------
        | PV
        |--------------------------------------------------------------------------
        */
        if ($type === 'pv') {
            $this->procesverbal =
                $consultation->procesverbal;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | VIEW
    |--------------------------------------------------------------------------
    */

    public function view(): View
    {
        /*
        |--------------------------------------------------------------------------
        | Répartition
        |--------------------------------------------------------------------------
        */

        if ($this->type === 'repartition') {

            return view(
                'documents.repartition_excel',
                [
                    'consultation' => $this->consultation,
                    'repartition' => $this->repartition,
                    'egalites' => $this->egalites,
                    'doyen' => $this->doyen,
                ]
            );
        }


        /*
        |--------------------------------------------------------------------------
        | PV
        |--------------------------------------------------------------------------
        */

        if ($this->type === 'pv') {

            $hasDescription =
                $this->consultation
                    ->articles
                    ->contains(function ($article) {

                        return trim(
                            $article->description ?? ''
                        ) !== '';
                    });


            return view(
                'procesverbal.excel',
                [
                    'consultation' => $this->consultation,

                    'procesverbal' =>
                        $this->procesverbal,

                    'hasDescription' =>
                        $hasDescription,
                ]
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Consultation
        |--------------------------------------------------------------------------
        */

        $hasDescription =
            $this->consultation
                ->articles
                ->contains(function ($article) {

                    return trim(
                        $article->description ?? ''
                    ) !== '';
                });


        return view(
            'consultation.excel',
            [
                'consultation' =>
                    $this->consultation,

                'hasDescription' =>
                    $hasDescription,
                'doyen' => $this->doyen,
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | COLUMN WIDTHS
    |--------------------------------------------------------------------------
    */

    public function columnWidths(): array
    {
        /*
        |--------------------------------------------------------------------------
        | RÉPARTITION
        |--------------------------------------------------------------------------
        */

        if ($this->type === 'repartition') {

            $hasDescription =
                $this->consultation
                    ->articles
                    ->contains(function ($article) {

                        return trim(
                            $article->description ?? ''
                        ) !== '';
                    });


            if ($hasDescription) {

                return [
                    'A' => 9,
                    'B' => 32,
                    'C' => 60,
                    'D' => 12,
                    'E' => 24,
                    'F' => 24,
                ];
            }


            return [
                'A' => 9,
                'B' => 42,
                'C' => 60,
                'D' => 24,
                'E' => 24,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | PV
        |--------------------------------------------------------------------------
        */

        if ($this->type === 'pv') {

            $hasDescription =
                $this->consultation
                    ->articles
                    ->contains(function ($article) {

                        return trim(
                            $article->description ?? ''
                        ) !== '';
                    });


            /*
            |--------------------------------------------------------------------------
            | PV AVEC DESCRIPTION
            |--------------------------------------------------------------------------
            */

            if ($hasDescription) {

                return [
                    'A' => 30,
                    'B' => 34,
                    'C' => 38,
                    'D' => 12,
                    'E' => 23,
                    'F' => 23,
                    'G' => 23,
                    'H' => 24,
                ];
            }


            /*
            |--------------------------------------------------------------------------
            | PV SANS DESCRIPTION
            |--------------------------------------------------------------------------
            */

            return [
                'A' => 30,
                'B' => 40,
                'C' => 12,
                'D' => 23,
                'E' => 23,
                'F' => 23,
                'G' => 24,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | CONSULTATION
        |--------------------------------------------------------------------------
        */

        $hasDescription =
            $this->consultation
                ->articles
                ->contains(function ($article) {

                    return trim(
                        $article->description ?? ''
                    ) !== '';
                });


        if ($hasDescription) {

            return [
                'A' => 9,
                'B' => 34,
                'C' => 38,
                'D' => 12,
                'E' => 23,
                'F' => 24,
            ];
        }


        return [
            'A' => 9,
            'B' => 42,
            'C' => 12,
            'D' => 24,
            'E' => 24,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | DRAWINGS / LOGO
    |--------------------------------------------------------------------------
    */

    public function drawings(): BaseDrawing|array
    {
        $logoPath =
            public_path('images/fs.png');


        if (!file_exists($logoPath)) {
            return [];
        }


        $drawing = new Drawing();

        $drawing->setName('Logo FSG');
        $drawing->setDescription('Logo Faculté des Sciences de Gabès');
        $drawing->setPath($logoPath);
        $drawing->setHeight(120);


        /*
        |--------------------------------------------------------------------------
        | PV
        |--------------------------------------------------------------------------
        */

        if ($this->type === 'pv') {

            $hasDescription =
                $this->consultation
                    ->articles
                    ->contains(function ($article) {

                        return trim(
                            $article->description ?? ''
                        ) !== '';
                    });


            $drawing->setCoordinates(
                $hasDescription ? 'H1' : 'G1'
            );

            $drawing->setOffsetX(8);
            $drawing->setOffsetY(7);

            return [$drawing];
        }


        /*
        |--------------------------------------------------------------------------
        | RÉPARTITION
        |--------------------------------------------------------------------------
        */

        if ($this->type === 'repartition') {

            $hasDescription =
                $this->consultation
                    ->articles
                    ->contains(function ($article) {

                        return trim(
                            $article->description ?? ''
                        ) !== '';
                    });


            $drawing->setCoordinates(
                $hasDescription ? 'F1' : 'E1'
            );

            $drawing->setOffsetX(8);
            $drawing->setOffsetY(7);

            return [$drawing];
        }


        /*
        |--------------------------------------------------------------------------
        | CONSULTATION
        |--------------------------------------------------------------------------
        */

        $hasDescription =
            $this->consultation
                ->articles
                ->contains(function ($article) {

                    return trim(
                        $article->description ?? ''
                    ) !== '';
                });


        $drawing->setCoordinates(
            $hasDescription ? 'F1' : 'E1'
        );

        $drawing->setOffsetX(8);
        $drawing->setOffsetY(7);


        return [$drawing];
    }


    /*
    |--------------------------------------------------------------------------
    | EVENTS
    |--------------------------------------------------------------------------
    */

    public function registerEvents(): array
    {
        return [

            AfterSheet::class => function (AfterSheet $event) {

                $sheet =
                    $event->sheet
                        ->getDelegate();


                /*
                |--------------------------------------------------------------------------
                | GENERAL
                |--------------------------------------------------------------------------
                */

                $sheet
                    ->setRightToLeft(true);

                
                $highestRow =
                    $sheet->getHighestRow();

                $highestColumn =
                    $sheet->getHighestColumn();

                $lastColIndex =
                    Coordinate::columnIndexFromString(
                        $highestColumn
                    );


                $sheet
                    ->getStyle(
                        "A1:{$highestColumn}{$highestRow}"
                    )
                    ->getFont()
                    ->setName('Arial')
                    ->setSize(11)
                    ->setBold(false);


                $sheet
                    ->getStyle(
                        "A1:{$highestColumn}{$highestRow}"
                    )
                    ->getAlignment()
                    ->setWrapText(false);


/*
|--------------------------------------------------------------------------
| RÉPARTITION
|--------------------------------------------------------------------------
*/

if ($this->type === 'repartition') {

    $hasDescription =
        $this->consultation
            ->articles
            ->contains(function ($article) {
                return trim(
                    $article->description ?? ''
                ) !== '';
            });

    /*
    |--------------------------------------------------------------------------
    | TOP
    |--------------------------------------------------------------------------
    */

    $sheet
        ->getStyle(
            "A1:{$highestColumn}1"
        )
        ->getFont()
        ->setSize(12)
        ->setBold(false);

    $sheet
        ->getStyle(
            "A1:{$highestColumn}1"
        )
        ->getAlignment()
        ->setHorizontal(
            Alignment::HORIZONTAL_RIGHT
        )
        ->setVertical(
            Alignment::VERTICAL_TOP
        )
        ->setWrapText(true);

    $sheet
        ->getStyle(
            "A1:{$highestColumn}1"
        )
        ->getBorders()
        ->getAllBorders()
        ->setBorderStyle(
            Border::BORDER_NONE
        );

    $sheet
        ->getRowDimension(1)
        ->setRowHeight(85);

    /*
    |--------------------------------------------------------------------------
    | RESTE DU HEADER
    |--------------------------------------------------------------------------
    */

    for (
        $row = 2;
        $row <= 6 && $row <= $highestRow;
        $row++
    ) {

        $sheet
            ->getStyle(
                "A{$row}:{$highestColumn}{$row}"
            )
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_RIGHT
            )
            ->setVertical(
                Alignment::VERTICAL_CENTER
            )
            ->setWrapText(true);

        $sheet
            ->getStyle(
                "A{$row}:{$highestColumn}{$row}"
            )
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(
                Border::BORDER_NONE
            );

        $sheet
            ->getRowDimension($row)
            ->setRowHeight(30);
    }

    /*
    |--------------------------------------------------------------------------
    | RECHERCHE DU DÉBUT DU TABLEAU
    |--------------------------------------------------------------------------
    */

    $tableStart = null;

    for (
        $row = 1;
        $row <= $highestRow;
        $row++
    ) {

        for (
            $col = 1;
            $col <= $lastColIndex;
            $col++
        ) {

            $cellAddress =
                Coordinate::stringFromColumnIndex(
                    $col
                ) . $row;

            $value =
                $sheet
                    ->getCell($cellAddress)
                    ->getValue();

            if (
                trim((string) $value) === 'ع/ر'
            ) {
                $tableStart = $row;
                break 2;
            }
        }
    }

    if ($tableStart === null) {
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | HEADER DU TABLEAU
    |--------------------------------------------------------------------------
    */

    $sheet
        ->getStyle(
            "A{$tableStart}:{$highestColumn}{$tableStart}"
        )
        ->getFont()
        ->setName('Arial')
        ->setSize(10)
        ->setBold(false);

    $sheet
        ->getStyle(
            "A{$tableStart}:{$highestColumn}{$tableStart}"
        )
        ->getAlignment()
        ->setHorizontal(
            Alignment::HORIZONTAL_CENTER
        )
        ->setVertical(
            Alignment::VERTICAL_CENTER
        )
        ->setWrapText(true);

    $sheet
        ->getStyle(
            "A{$tableStart}:{$highestColumn}{$tableStart}"
        )
        ->getBorders()
        ->getAllBorders()
        ->setBorderStyle(
            Border::BORDER_THIN
        );

    $sheet
        ->getStyle(
            "A{$tableStart}:{$highestColumn}{$tableStart}"
        )
        ->getFill()
        ->setFillType(
            Fill::FILL_SOLID
        )
        ->getStartColor()
        ->setARGB('FFF2F2F2');

    $sheet
        ->getRowDimension($tableStart)
        ->setRowHeight(45);

    /*
    |--------------------------------------------------------------------------
    | COLONNES
    |--------------------------------------------------------------------------
    */

    $quantityColumn =
        $hasDescription ? 'D' : 'C';

    $priceColumn =
        $hasDescription ? 'E' : 'D';

    $totalColumn =
        $hasDescription ? 'F' : 'E';

    /*
    |--------------------------------------------------------------------------
    | RECHERCHE DES LIGNES TOTAL
    |--------------------------------------------------------------------------
    */

    $totalRows = [];

    for (
        $row = $tableStart + 1;
        $row <= $highestRow;
        $row++
    ) {

        for (
            $col = 1;
            $col <= $lastColIndex;
            $col++
        ) {

            $cellAddress =
                Coordinate::stringFromColumnIndex(
                    $col
                ) . $row;

            $value =
                $sheet
                    ->getCell($cellAddress)
                    ->getValue();

            if (
                trim((string) $value)
                === 'المجموع باعتبار كل الأداء'
            ) {

                $totalRows[] = $row;

                break;
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | LIGNES
    |--------------------------------------------------------------------------
    */

    for (
        $row = $tableStart + 1;
        $row <= $highestRow;
        $row++
    ) {

        /*
        |--------------------------------------------------------------------------
        | NE PAS TRAITER LA SIGNATURE
        |--------------------------------------------------------------------------
        */

        if (
            !empty($totalRows)
            && $row > max($totalRows)
        ) {
            break;
        }

        /*
        |--------------------------------------------------------------------------
        | TOTAL
        |--------------------------------------------------------------------------
        */

        if (in_array($row, $totalRows)) {

            $sheet
                ->getStyle(
                    "A{$row}:{$highestColumn}{$row}"
                )
                ->getFont()
                ->setBold(true);

            $sheet
                ->getStyle(
                    "A{$row}:{$highestColumn}{$row}"
                )
                ->getAlignment()
                ->setVertical(
                    Alignment::VERTICAL_CENTER
                );

            $sheet
                ->getStyle(
                    "A{$row}:{$highestColumn}{$row}"
                )
                ->getBorders()
                ->getAllBorders()
                ->setBorderStyle(
                    Border::BORDER_THIN
                );

            $sheet
                ->getStyle(
                    "A{$row}:{$highestColumn}{$row}"
                )
                ->getFill()
                ->setFillType(
                    Fill::FILL_SOLID
                )
                ->getStartColor()
                ->setARGB('FFE2F0D9');

            $sheet
                ->getRowDimension($row)
                ->setRowHeight(32);

            /*
            |--------------------------------------------------------------------------
            | TOTAL À DROITE
            |--------------------------------------------------------------------------
            */

            $sheet
                ->getStyle(
                    "{$totalColumn}{$row}"
                )
                ->getAlignment()
                ->setHorizontal(
                    Alignment::HORIZONTAL_RIGHT
                );

            $sheet
                ->getStyle(
                    "{$totalColumn}{$row}"
                )
                ->getNumberFormat()
                ->setFormatCode('0.000');

            continue;
        }

        /*
        |--------------------------------------------------------------------------
        | LIGNE ARTICLE
        |--------------------------------------------------------------------------
        */

        $sheet
            ->getStyle(
                "A{$row}:{$highestColumn}{$row}"
            )
            ->getFont()
            ->setBold(false);

        $sheet
            ->getStyle(
                "A{$row}:{$highestColumn}{$row}"
            )
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(
                Border::BORDER_THIN
            );

        $sheet
            ->getStyle(
                "A{$row}:{$highestColumn}{$row}"
            )
            ->getAlignment()
            ->setVertical(
                Alignment::VERTICAL_CENTER
            )
            ->setWrapText(true);

        $sheet
            ->getRowDimension($row)
            ->setRowHeight(
                $hasDescription ? 100 : 36
            );

        /*
        |--------------------------------------------------------------------------
        | N°
        |--------------------------------------------------------------------------
        */

        $sheet
            ->getStyle("A{$row}")
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            );

        /*
        |--------------------------------------------------------------------------
        | DÉSIGNATION
        |--------------------------------------------------------------------------
        */

        $sheet
            ->getStyle("B{$row}")
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_RIGHT
            )
            ->setWrapText(true);

        /*
        |--------------------------------------------------------------------------
        | DESCRIPTION
        |--------------------------------------------------------------------------
        */

        if ($hasDescription) {

            $sheet
                ->getStyle("C{$row}")
                ->getAlignment()
                ->setHorizontal(
                    Alignment::HORIZONTAL_RIGHT
                )
                ->setWrapText(true);
        }

        /*
        |--------------------------------------------------------------------------
        | QUANTITÉ
        |--------------------------------------------------------------------------
        */

        $sheet
            ->getStyle(
                "{$quantityColumn}{$row}"
            )
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            );

        /*
        |--------------------------------------------------------------------------
        | PRIX + TOTAL
        |--------------------------------------------------------------------------
        */

        $sheet
            ->getStyle(
                "{$priceColumn}{$row}:{$totalColumn}{$row}"
            )
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            );

        /*
        |--------------------------------------------------------------------------
        | FORMAT 3 DÉCIMALES
        |--------------------------------------------------------------------------
        */

        $sheet
            ->getStyle(
                "{$priceColumn}{$row}:{$totalColumn}{$row}"
            )
            ->getNumberFormat()
            ->setFormatCode('0.000');
    }

    /*
    |--------------------------------------------------------------------------
    | COLORER LE PRIX MINIMUM
    |--------------------------------------------------------------------------
    */

    $priceRows = [];

    foreach ($this->repartition as $rep) {

        foreach ($rep['articles'] as $article) {

            $priceRows[] = [
                'designation' =>
                    trim(
                        $article['designation'] ?? ''
                    ),

                'prix' =>
                    (float) (
                        $article['prix'] ?? 0
                    ),
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | COLORATION DES PRIX MINIMUMS
    |--------------------------------------------------------------------------
    */

    if (!empty($totalRows)) {

        for (
            $row = $tableStart + 1;
            $row <= max($totalRows);
            $row++
        ) {

            if (in_array($row, $totalRows)) {
                continue;
            }

            $designation =
                trim(
                    (string) $sheet
                        ->getCell("B{$row}")
                        ->getValue()
                );

            $prixMinimum = null;

            foreach ($priceRows as $priceRow) {

                if (
                    $priceRow['designation']
                    === $designation
                ) {

                    $prixMinimum =
                        $priceRow['prix'];

                    break;
                }
            }

            if ($prixMinimum !== null) {

                $sheet
                    ->getStyle(
                        "{$priceColumn}{$row}"
                    )
                    ->getFill()
                    ->setFillType(
                        Fill::FILL_SOLID
                    )
                    ->getStartColor()
                    ->setARGB('FFE2F0D9');
            }

            /*
            |--------------------------------------------------------------------------
            | FORMAT DU TOTAL
            |--------------------------------------------------------------------------
            */

            $sheet
                ->getStyle(
                    "{$totalColumn}{$row}"
                )
                ->getNumberFormat()
                ->setFormatCode('0.000');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SIGNATURE DU DOYEN
    |--------------------------------------------------------------------------
    |
    | Après le dernier total :
    |
    | ligne suivante = vide
    | ligne suivante = Gabès, le ...
    | ligne suivante = العميـــد
    | ligne suivante = nom du doyen
    |
    */

    if (!empty($totalRows)) {

        $lastTotalRow = max($totalRows);

        /*
        |--------------------------------------------------------------------------
        | Une ligne vide après le total
        |--------------------------------------------------------------------------
        */

        $signatureStartRow = $lastTotalRow + 2;

        /*
        |--------------------------------------------------------------------------
        | Feuille RTL :
        | la dernière colonne est physiquement à gauche.
        |--------------------------------------------------------------------------
        */

        $signatureColumn = $highestColumn;

        /*
        |--------------------------------------------------------------------------
        | DATE
        |--------------------------------------------------------------------------
        */

        $sheet->setCellValue(
            "{$signatureColumn}{$signatureStartRow}",
            '    قابس في     ' . date('d/m/Y')
        );

        /*
        |--------------------------------------------------------------------------
        | FONCTION
        |--------------------------------------------------------------------------
        */

        $sheet->setCellValue(
            "{$signatureColumn}" . ($signatureStartRow + 1),
            'العميـــد'
        );

        /*
        |--------------------------------------------------------------------------
        | NOM DU DOYEN
        |--------------------------------------------------------------------------
        */

        $sheet->setCellValue(
            "{$signatureColumn}" . ($signatureStartRow + 2),
            $this->doyen?->nom ?? '........................'
        );

        /*
        |--------------------------------------------------------------------------
        | STYLE DE LA SIGNATURE
        |--------------------------------------------------------------------------
        */

        $sheet
            ->getStyle(
                "{$signatureColumn}{$signatureStartRow}:{$signatureColumn}" .
                ($signatureStartRow + 2)
            )
            ->getFont()
            ->setName('Arial')
            ->setSize(11)
            ->setBold(true);

        /*
        |--------------------------------------------------------------------------
        | ALIGNEMENT
        |--------------------------------------------------------------------------
        */

        $sheet
            ->getStyle(
                "{$signatureColumn}{$signatureStartRow}:{$signatureColumn}" .
                ($signatureStartRow + 2)
            )
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_LEFT
            )
            ->setVertical(
                Alignment::VERTICAL_CENTER
            )
            ->setWrapText(true);

        /*
        |--------------------------------------------------------------------------
        | AUCUNE BORDURE
        |--------------------------------------------------------------------------
        */

        $sheet
            ->getStyle(
                "{$signatureColumn}{$signatureStartRow}:{$signatureColumn}" .
                ($signatureStartRow + 2)
            )
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(
                Border::BORDER_NONE
            );

        /*
        |--------------------------------------------------------------------------
        | HAUTEUR DES LIGNES
        |--------------------------------------------------------------------------
        */

        $sheet
            ->getRowDimension($signatureStartRow)
            ->setRowHeight(30);

        $sheet
            ->getRowDimension($signatureStartRow + 1)
            ->setRowHeight(30);

        $sheet
            ->getRowDimension($signatureStartRow + 2)
            ->setRowHeight(30);
    }

    return;
}


                /*
|--------------------------------------------------------------------------
| PV
|--------------------------------------------------------------------------
*/

if ($this->type === 'pv') {

    $hasDescription =
        $this->consultation
            ->articles
            ->contains(function ($article) {
                return trim($article->description ?? '') !== '';
            });

    $lastColLetter = $highestColumn;
    $lastColIndex  = Coordinate::columnIndexFromString($highestColumn);

    /*
    |--------------------------------------------------------------------------
    | RECHERCHE DU DÉBUT DU TABLEAU
    |--------------------------------------------------------------------------
    */

    $tableStart = null;

    for ($row = 1; $row <= $highestRow; $row++) {
        for ($col = 1; $col <= $lastColIndex; $col++) {

            $cellAddress =
                Coordinate::stringFromColumnIndex($col) . $row;

            $value = $sheet->getCell($cellAddress)->getValue();

            if (trim((string) $value) === 'ع/ر') {
                $tableStart = $row;
                break 2;
            }
        }
    }

    if ($tableStart === null) {
        $tableStart = 12;
    }

    /*
    |--------------------------------------------------------------------------
    | COLONNES DYNAMIQUES
    |--------------------------------------------------------------------------
    |
    | On calcule les lettres des colonnes clés :
    |
    |   $colNumero       = A
    |   $colDesignation  = B
    |   $colDescription  = C (si description) sinon null
    |   $colQuantite     = C ou D
    |   $colFournisseurs = D... ou E...
    |
    */

    $colNumero      = 'A';
    $colDesignation = 'B';
    $colDescription = $hasDescription ? 'C' : null;
    $colQuantite    = $hasDescription ? 'D' : 'C';
    $colFournisseursStart = $hasDescription ? 'E' : 'D';

    /*
    |--------------------------------------------------------------------------
    | PREMIÈRE LIGNE / EN-TÊTE ADMIN
    |--------------------------------------------------------------------------
    */

    $sheet->getStyle("A1")->getFont()
        ->setName('Arial')->setSize(12)->setBold(true);

    $sheet->getStyle("A1")->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
        ->setVertical(Alignment::VERTICAL_CENTER)
        ->setWrapText(true);

    $sheet->getStyle("A1")->getBorders()
        ->getAllBorders()
        ->setBorderStyle(Border::BORDER_NONE);

    $sheet->getRowDimension(1)->setRowHeight(200);
    $sheet->mergeCells('A1:B1');




    $sheet->getStyle('A5')->getFont()->setBold(true);
    $sheet->getStyle('A6')->getFont()->setBold(true);
    $sheet->getStyle('A7')->getFont()->setBold(true);
    $sheet->getStyle('A10')->getFont()->setBold(true)->setUnderline(true);

    /*
    |--------------------------------------------------------------------------
    | LIGNE 3 (TITRE)
    |--------------------------------------------------------------------------
    */

    $sheet->mergeCells("A3:{$lastColLetter}3");

    $sheet->getStyle("A3:{$lastColLetter}3")->getFont()
        ->setSize(20)->setBold(true);

    $sheet->getStyle("A3:{$lastColLetter}3")->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
        ->setVertical(Alignment::VERTICAL_CENTER);

    $sheet->getRowDimension(10)->setRowHeight(35);

    /*
    |--------------------------------------------------------------------------
    | LIGNES 6 À 11 (INFOS + COMMISSION)
    |--------------------------------------------------------------------------
    */
    $sheet->getColumnDimension('A')->setWidth(5);

    for ($row = 6; $row <= 11 && $row <= $highestRow; $row++) {

    $sheet->getStyle("A{$row}:{$lastColLetter}{$row}")
        ->getFont()
        ->setName('Arial')
        ->setSize(12)
        ->setBold(false);

    $sheet->getStyle("A{$row}:{$lastColLetter}{$row}")
        ->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_RIGHT)
        ->setVertical(Alignment::VERTICAL_CENTER)
        ->setWrapText(false);

    $sheet->getStyle("A{$row}:{$lastColLetter}{$row}")
        ->getBorders()
        ->getAllBorders()
        ->setBorderStyle(Border::BORDER_NONE);

    $sheet->getRowDimension($row)->setRowHeight(25);
}

    

    /*
    |--------------------------------------------------------------------------
    | HEADER DU TABLEAU
    |--------------------------------------------------------------------------
    */

    $tableHeaderEnd = $tableStart + 1;

    $sheet->getStyle("A{$tableStart}:{$lastColLetter}{$tableHeaderEnd}")
        ->getFont()->setName('Arial')->setSize(11)->setBold(true);

    $sheet->getStyle("A{$tableStart}:{$lastColLetter}{$tableHeaderEnd}")
        ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

    $sheet->getStyle("A{$tableStart}:{$lastColLetter}{$tableHeaderEnd}")
        ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

    $sheet->getStyle("A{$tableStart}:{$lastColLetter}{$tableHeaderEnd}")
        ->getFill()->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('FFF2F2F2');

    $sheet->getRowDimension($tableStart)->setRowHeight(42);
    $sheet->getRowDimension($tableHeaderEnd)->setRowHeight(48);

    /*
    |--------------------------------------------------------------------------
    | ALIGNEMENTS PAR COLONNE (DYNAMIQUE)
    |--------------------------------------------------------------------------
    */

    // N°
    $sheet->getStyle("{$colNumero}{$tableStart}:{$colNumero}{$highestRow}")
        ->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
        ->setVertical(Alignment::VERTICAL_CENTER);

    // Désignation
    $sheet->getStyle("{$colDesignation}{$tableStart}:{$colDesignation}{$highestRow}")
        ->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_RIGHT)
        ->setVertical(Alignment::VERTICAL_CENTER)
        ->setWrapText(true);

    // Description (seulement si activée)
    if ($hasDescription) {
        $sheet->getStyle("{$colDescription}{$tableStart}:{$colDescription}{$highestRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
    }

    // Quantité
    $sheet->getStyle("{$colQuantite}{$tableStart}:{$colQuantite}{$highestRow}")
        ->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
        ->setVertical(Alignment::VERTICAL_CENTER);

    // Fournisseurs + Prix min + Prix total
    $sheet->getStyle("{$colFournisseursStart}{$tableStart}:{$lastColLetter}{$highestRow}")
        ->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
        ->setVertical(Alignment::VERTICAL_CENTER);

    /*
    |--------------------------------------------------------------------------
    | RECHERCHE DE LA LIGNE TOTAL
    |--------------------------------------------------------------------------
    */

    $totalRow = null;

    for ($row = $tableHeaderEnd + 1; $row <= $highestRow; $row++) {
        for ($col = 1; $col <= $lastColIndex; $col++) {

            $cellAddress =
                Coordinate::stringFromColumnIndex($col) . $row;

            $value = $sheet->getCell($cellAddress)->getValue();

            if (trim((string) $value) === 'المجموع') {
                $totalRow = $row;
                break 2;
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | LIGNES ARTICLES
    |--------------------------------------------------------------------------
    */

    $dataStart = $tableHeaderEnd + 1;

    if ($totalRow !== null) {

        for ($row = $dataStart; $row < $totalRow; $row++) {

            $sheet->getStyle("A{$row}:{$lastColLetter}{$row}")
                ->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);

            $sheet->getRowDimension($row)
                ->setRowHeight($hasDescription ? 55 : 42);
        }

        /*
        |--------------------------------------------------------------------------
        | LIGNE TOTAL
        |--------------------------------------------------------------------------
        */

        $sheet->getStyle("A{$totalRow}:{$lastColLetter}{$totalRow}")
            ->getFont()->setBold(true);

        $sheet->getStyle("A{$totalRow}:{$lastColLetter}{$totalRow}")
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);

        $sheet->getStyle("A{$totalRow}:{$lastColLetter}{$totalRow}")
            ->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $sheet->getStyle("A{$totalRow}:{$lastColLetter}{$totalRow}")
            ->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE2F0D9');

        $sheet->getRowDimension($totalRow)->setRowHeight(34);
    }

    /*
    |--------------------------------------------------------------------------
    | COLORATION DYNAMIQUE DU PRIX LE PLUS BAS
    |--------------------------------------------------------------------------
    |
    | On parcourt les lignes d'articles et on colore en vert
    | les cellules dont la valeur égale le minimum de la ligne.
    |
    */

    if ($totalRow !== null) {

        $fournisseursStartIndex =
            Coordinate::columnIndexFromString($colFournisseursStart);

        $lastColIndexForLoop =
            Coordinate::columnIndexFromString($lastColLetter);

        // Les 2 dernières colonnes = السعر الأدنى + السعر الجملي
        $prixMinIndex = $lastColIndexForLoop - 1;
        $prixTotalIndex = $lastColIndexForLoop;

        for ($row = $dataStart; $row < $totalRow; $row++) {

            /*
            |----------------------------------------------------------------------
            | Récupérer les prix des fournisseurs de cette ligne
            |----------------------------------------------------------------------
            */

            $prix = [];

            for (
                $col = $fournisseursStartIndex;
                $col < $prixMinIndex;
                $col++
            ) {

                $colLetter =
                    Coordinate::stringFromColumnIndex($col);

                $value =
                    $sheet->getCell("{$colLetter}{$row}")->getValue();

                if (
                    $value !== null &&
                    trim((string) $value) !== '' &&
                    strtoupper(trim((string) $value)) !== 'ND'
                ) {

                    $prix[] = (float) str_replace(',', '.', $value);
                }
            }

            $minimum = count($prix) > 0 ? min($prix) : null;

            /*
            |----------------------------------------------------------------------
            | Colorer les cellules égales au minimum
            |----------------------------------------------------------------------
            */

            for (
                $col = $fournisseursStartIndex;
                $col < $prixMinIndex;
                $col++
            ) {

                $colLetter =
                    Coordinate::stringFromColumnIndex($col);

                $value =
                    $sheet->getCell("{$colLetter}{$row}")->getValue();

                if (
                    $value !== null &&
                    trim((string) $value) !== '' &&
                    strtoupper(trim((string) $value)) !== 'ND' &&
                    $minimum !== null &&
                    (float) str_replace(',', '.', $value) == $minimum
                ) {

                    $sheet
                        ->getStyle("{$colLetter}{$row}")
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setARGB('FFE2F0D9');
                }
            }

            /*
            |----------------------------------------------------------------------
            | Colorer la colonne "السعر الأدنى"
            |----------------------------------------------------------------------
            */

            $minColLetter =
                Coordinate::stringFromColumnIndex($prixMinIndex);

            if ($minimum !== null) {

                $sheet
                    ->getStyle("{$minColLetter}{$row}")
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('FFE2F0D9');
            }

            /*
            |----------------------------------------------------------------------
            | Colorer la colonne "السعر الجملي"
            |----------------------------------------------------------------------
            */

            $totalColLetter =
                Coordinate::stringFromColumnIndex($prixTotalIndex);

            $sheet
                ->getStyle("{$totalColLetter}{$row}")
                ->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setARGB('FFE2F0D9');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SIGNATURES
    |--------------------------------------------------------------------------
    */

   if ($totalRow !== null) {

    for ($row = $totalRow + 1; $row <= $highestRow; $row++) {

        $sheet->getStyle("A{$row}:{$lastColLetter}{$row}")
            ->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_NONE);

        $sheet->getStyle("A{$row}:{$lastColLetter}{$row}")
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);

        // Ligne vide après le total
        if ($row === $totalRow + 1) {

            $sheet->getRowDimension($row)->setRowHeight(30);
        }

        // Ligne des signatures
        elseif ($row === $totalRow + 2) {

            // Augmenter la hauteur
            $sheet->getRowDimension($row)->setRowHeight(100);

            // Augmenter la largeur des colonnes de la signature
            $sheet->getColumnDimension('A')->setWidth(25);
            $sheet->getColumnDimension('C')->setWidth(25);
        }

        // Sécurité pour les autres lignes
        else {

            $sheet->getRowDimension($row)->setRowHeight(40);
        }
    }
}

    return;
}


                /*
                |--------------------------------------------------------------------------
                | CONSULTATION
                |--------------------------------------------------------------------------
                */

                $hasDescription =
                    $this->consultation
                        ->articles
                        ->contains(function ($article) {

                            return trim(
                                $article->description ?? ''
                            ) !== '';
                        });


                /*
                |--------------------------------------------------------------------------
                | STYLE DU HEADER
                |--------------------------------------------------------------------------
                */

                // ==========================================================================
                // REGROUPER LES LIGNES 1, 2, 3 ET 4 DANS LA LIGNE 1
                // ==========================================================================

                $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString(
                    $highestColumn
                );

                for ($col = 1; $col <= $highestColumnIndex; $col++) {

                    $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);

                    $values = [];

                    // Récupérer le contenu des lignes 1 à 4
                    for ($r = 1; $r <= 4; $r++) {

                        $value = $sheet->getCell("{$column}{$r}")->getValue();

                        if ($value !== null && trim((string) $value) !== '') {
                            $values[] = $value;
                        }
                    }

                    // Mettre les contenus dans la ligne 1
                    if (!empty($values)) {
                        $sheet->setCellValue(
                            "{$column}1",
                            implode("\n", $values)
                        );
                    }
                }

                // Supprimer les anciennes lignes 2, 3 et 4
                $sheet->removeRow(2, 3);


                // ==========================================================================
                // STYLE DE LA LIGNE 1
                // ==========================================================================

                $sheet
                    ->getStyle(
                        "A1:{$highestColumn}1"
                    )
                    ->getAlignment()
                    ->setHorizontal(
                        Alignment::HORIZONTAL_RIGHT
                    )
                    ->setVertical(
                        Alignment::VERTICAL_TOP
                    )
                    ->setWrapText(true);


                // ==========================================================================
                // SUPPRIMER LES BORDURES DE LA LIGNE 1
                // ==========================================================================

                $sheet
                    ->getStyle(
                        "A1:{$highestColumn}1"
                    )
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(
                        Border::BORDER_NONE
                    );


                // ==========================================================================
                // HAUTEUR DE LA LIGNE 1
                // ==========================================================================

                $sheet
                    ->getRowDimension(1)
                    ->setRowHeight(85);


                // ==========================================================================
                // AUTRES LIGNES
                // ==========================================================================

                $highestRow = $sheet->getHighestRow();

                for (
                    $row = 2;
                    $row <= $highestRow;
                    $row++
                ) {

                    $sheet
                        ->getStyle(
                            "A{$row}:{$highestColumn}{$row}"
                        )
                        ->getAlignment()
                        ->setVertical(
                            Alignment::VERTICAL_CENTER
                        )
                        ->setWrapText(true);
                }
            },
        ];
    }
}