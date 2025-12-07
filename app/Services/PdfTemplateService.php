<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class PdfTemplateService
{
    public function readExcelFile(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $worksheet = $spreadsheet->getActiveSheet();
        $data = [];

        // Get headers from first row
        $headers = [];
        $highestColumn = $worksheet->getHighestColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $columnLetter = Coordinate::stringFromColumnIndex($col);
            $cell = $worksheet->getCell($columnLetter . '1');
            $cellValue = $cell->getCalculatedValue();
            // Handle arrays by converting to string
            if (is_array($cellValue)) {
                $cellValue = implode(', ', $cellValue);
            }
            $headers[$col] = $cellValue ?? "Column{$col}";
        }

        // Get data rows
        $highestRow = $worksheet->getHighestRow();
        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = [];
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $columnLetter = Coordinate::stringFromColumnIndex($col);
                $cell = $worksheet->getCell($columnLetter . $row);
                $cellValue = $cell->getCalculatedValue();
                // Handle arrays by converting to string
                if (is_array($cellValue)) {
                    $cellValue = implode(', ', $cellValue);
                }
                $rowData[$headers[$col]] = $cellValue ?? '';
            }
            if (!empty(array_filter($rowData))) {
                $data[] = $rowData;
            }
        }

        return $data;
    }

    public function generatePdfsFromTemplate(array $template, array $excelData, array $columnMapping): array
    {
        $pdfPreviews = [];
        $templateName = $template['name'] ?? 'template';
        $pages = $template['pages'] ?? [];

        foreach ($excelData as $index => $row) {
            $html = $this->buildHtmlFromTemplate($template, $row, $columnMapping, $pages);
            $timestamp = date('Y-m-d_H-i-s');
            $filename = 'bromi_' . $templateName . '_' . ($index + 1) . '_' . $timestamp . '.pdf';

            $binary = $this->generatePdfBinaryFromHtml($html, $template);

            $pdfPreviews[] = [
                'filename' => $filename,
                'index' => $index + 1,
                'base64' => base64_encode($binary),
            ];
        }

        return $pdfPreviews;
    }

    protected function buildHtmlFromTemplate(array $template, array $rowData, array $columnMapping, array $pages = []): string
    {
        // Ensure A4 size (210mm x 297mm)
        $width = 210; // A4 width in mm (fixed)
        $height = 297; // A4 height in mm (fixed)
        
        // If pages structure exists, use it; otherwise fallback to fields
        if (empty($pages)) {
            $pages = [['fields' => $template['fields'] ?? []]];
        }

        // Convert mm to pixels for HTML (1mm = 3.779527559 pixels at 96 DPI)
        $widthPx = $width * 3.779527559;
        $heightPx = $height * 3.779527559;
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: white;
        }
        .page {
            width: ' . $width . 'mm;
            height: ' . $height . 'mm;
            position: relative;
            page-break-after: always;
            background: white;
            overflow: hidden;
        }
        .page:last-child {
            page-break-after: auto;
        }
        .field {
            position: absolute;
            box-sizing: border-box;
            display: block;
            color: #000000;
            white-space: pre-wrap;
            word-wrap: break-word;
            overflow: visible;
        }
    </style>
</head>
<body>';

        // Generate pages
        foreach ($pages as $page) {
            $html .= '<div class="page">';
            $fields = $page['fields'] ?? [];
            
            foreach ($fields as $field) {
                $x = $field['x'] ?? 0;
                $y = $field['y'] ?? 0;
                $width = $field['width'] ?? 100;
                $height = $field['height'] ?? 20;
                $columnName = $field['column'] ?? '';
                $fontSize = $field['fontSize'] ?? 12;
                $textAlign = $field['textAlign'] ?? 'left';
                $fontFamily = $field['fontFamily'] ?? 'Arial';
                $fontColor = $field['fontColor'] ?? '#000000';
                $fontWeight = $field['fontWeight'] ?? 'normal';
                $fontStyle = $field['fontStyle'] ?? 'normal';
                $textDecoration = $field['textDecoration'] ?? 'none';

                // Get value from row data using column mapping
                $value = '';
                if (!empty($columnName) && isset($columnMapping[$columnName])) {
                    $excelColumnName = $columnMapping[$columnName];
                    if (isset($rowData[$excelColumnName])) {
                        $cellValue = $rowData[$excelColumnName];
                        // Handle arrays, objects, and convert to string
                        if (is_array($cellValue)) {
                            $cellValue = implode(', ', array_filter($cellValue, function($item) {
                                return !is_array($item) && !is_object($item);
                            }));
                        } elseif (is_object($cellValue)) {
                            $cellValue = json_encode($cellValue);
                        }
                        // Convert to string and handle null/empty values
                        $value = $cellValue !== null && $cellValue !== '' ? (string)$cellValue : '';
                        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                    }
                }

                // Build style string with all font properties
                $style = sprintf(
                    'left: %smm; top: %smm; width: %smm; min-height: %smm; font-size: %spx; text-align: %s; padding: 2mm; overflow: visible; word-wrap: break-word; font-family: %s; color: %s; font-weight: %s; font-style: %s; text-decoration: %s;',
                    $x,
                    $y,
                    $width,
                    $height,
                    $fontSize,
                    $textAlign,
                    htmlspecialchars($fontFamily, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($fontColor, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($fontWeight, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($fontStyle, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($textDecoration, ENT_QUOTES, 'UTF-8')
                );
                
                $html .= sprintf(
                    '<div class="field" style="%s">%s</div>',
                    $style,
                    $value
                );
            }
            
            $html .= '</div>';
        }

        $html .= '</body></html>';

        return $html;
    }

    protected function generatePdfBinaryFromHtml(string $html, array $template): string
    {
        // Ensure A4 size (210mm x 297mm)
        $width = 210; // A4 width in mm (fixed)
        $height = 297; // A4 height in mm (fixed)
        
        // Convert mm to points (1mm = 2.83465 points)
        $widthPoints = $width * 2.83465;
        $heightPoints = $height * 2.83465;
        
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultPaperSize', 'a4'); // Use A4 paper size
        $options->set('defaultPaperWidth', $width . 'mm');
        $options->set('defaultPaperHeight', $height . 'mm');
        // Note: Default font is set per field in the template, but we keep Arial as fallback
        $options->set('defaultFont', 'Arial');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        // Set paper size in points (width, height) - A4 dimensions
        $dompdf->setPaper([0, 0, $widthPoints, $heightPoints], 'portrait');
        $dompdf->render();
        return $dompdf->output();
    }
}


