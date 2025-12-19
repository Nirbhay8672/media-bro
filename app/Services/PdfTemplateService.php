<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function uploadPdfFile(UploadedFile $file): array
    {
        // Store the PDF file
        $filename = 'pdf-templates/' . uniqid() . '_' . time() . '.pdf';
        $path = $file->storeAs('pdf-templates', basename($filename), 'public');
        
        // Get PDF dimensions
        $dimensions = $this->getPdfDimensions(storage_path('app/public/' . $path));
        
        return [
            'file_path' => $path,
            'file_url' => Storage::url($path),
            'dimensions' => $dimensions,
        ];
    }

    protected function getPdfDimensions(string $pdfPath): array
    {
        // Try to get dimensions from PDF using a simple method
        // For now, default to A4 - dimensions will be adjusted when PDF is displayed
        // The actual dimensions will be detected from the uploaded PDF in the frontend
        return [
            'width' => 210,  // A4 width in mm
            'height' => 297, // A4 height in mm
        ];
    }

    public function generatePdfsFromTemplate(array $template, array $excelData, array $columnMapping, ?string $pdfFilePath = null, ?string $pdfPageImage = null): array
    {
        $pdfPreviews = [];
        $templateName = $template['name'] ?? 'template';
        $pages = $template['pages'] ?? [];

        // If PDF file is provided, use overlay method; otherwise use HTML generation
        if ($pdfFilePath && Storage::disk('public')->exists($pdfFilePath)) {
            foreach ($excelData as $index => $row) {
                // Generate 6-digit timestamp: use microtime with index to ensure uniqueness
                $baseTime = (int)(microtime(true) * 1000000);
                $timestamp = substr($baseTime + $index, -6);
                $filename = $timestamp . '.pdf';
                
                $binary = $this->overlayFieldsOnPdf(
                    storage_path('app/public/' . $pdfFilePath),
                    $pages,
                    $row,
                    $columnMapping,
                    $pdfPageImage
                );

                $pdfPreviews[] = [
                    'filename' => $filename,
                    'index' => $index + 1,
                    'base64' => base64_encode($binary),
                ];
            }
        } else {
            // Original HTML-based generation
            foreach ($excelData as $index => $row) {
                $html = $this->buildHtmlFromTemplate($template, $row, $columnMapping, $pages);
                // Generate 6-digit timestamp: use microtime with index to ensure uniqueness
                $baseTime = (int)(microtime(true) * 1000000);
                $timestamp = substr($baseTime + $index, -6);
                $filename = $timestamp . '.pdf';

                $binary = $this->generatePdfBinaryFromHtml($html, $template);

                $pdfPreviews[] = [
                    'filename' => $filename,
                    'index' => $index + 1,
                    'base64' => base64_encode($binary),
                ];
            }
        }

        return $pdfPreviews;
    }

    protected function overlayFieldsOnPdf(string $pdfPath, array $pages, array $rowData, array $columnMapping, ?string $pdfPageImageBase64 = null): string
    {
        // Use the base64 image from frontend if available, otherwise try to convert
        $pdfImageUrl = null;
        
        if ($pdfPageImageBase64) {
            // Use the base64 image directly
            $pdfImageUrl = $pdfPageImageBase64;
        } else {
            // Fallback: try to convert PDF to image using server tools
            $pdfImageUrl = $this->convertPdfToImage($pdfPath);
            
            // If conversion failed, try using PDF file URL (may not work)
            if (!$pdfImageUrl) {
                if (strpos($pdfPath, storage_path('app/public/')) !== false) {
                    $relativePath = str_replace(storage_path('app/public/'), '', $pdfPath);
                    $pdfImageUrl = Storage::url($relativePath);
                }
            }
        }
        
        // Get fields from first page
        $fields = $pages[0]['fields'] ?? [];
        $template = ['width' => 210, 'height' => 297]; // Default A4
        
        // Build HTML with PDF image as background and fields overlaid
        $html = $this->buildHtmlWithPdfImageBackground($template, $pdfImageUrl, null, $fields, $rowData, $columnMapping);
        
        // Generate PDF from HTML using DomPDF
        return $this->generatePdfBinaryFromHtml($html, $template);
    }
    
    protected function convertPdfToImage(string $pdfPath): ?string
    {
        // Try to convert PDF first page to image using available tools
        // Use JPG format for faster processing and smaller file size
        // Method 1: Try Ghostscript (gs command)
        if ($this->commandExists('gs')) {
            $imagePath = storage_path('app/public/pdf-templates/' . uniqid() . '_page1.jpg');
            $dir = dirname($imagePath);
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // Use jpeg device with optimized quality and resolution for faster conversion
            $command = sprintf(
                'gs -dNOPAUSE -dBATCH -sDEVICE=jpeg -dJPEGQ=85 -r200 -dFirstPage=1 -dLastPage=1 -sOutputFile=%s %s 2>&1',
                escapeshellarg($imagePath),
                escapeshellarg($pdfPath)
            );
            
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0 && file_exists($imagePath)) {
                return Storage::url(str_replace(storage_path('app/public/'), '', $imagePath));
            }
        }
        
        // Method 2: Try pdftoppm (poppler-utils)
        if ($this->commandExists('pdftoppm')) {
            $imagePath = storage_path('app/public/pdf-templates/' . uniqid() . '_page1');
            $dir = dirname($imagePath);
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // Use jpeg format with optimized quality and resolution for faster conversion
            $command = sprintf(
                'pdftoppm -jpeg -jpegopt quality=85 -f 1 -l 1 -r 200 %s %s 2>&1',
                escapeshellarg($pdfPath),
                escapeshellarg($imagePath)
            );
            
            exec($command, $output, $returnCode);
            
            // pdftoppm creates files with -1 suffix
            $actualImagePath = $imagePath . '-1.jpg';
            if ($returnCode === 0 && file_exists($actualImagePath)) {
                // Rename to remove suffix
                $finalImagePath = $imagePath . '.jpg';
                rename($actualImagePath, $finalImagePath);
                return Storage::url(str_replace(storage_path('app/public/'), '', $finalImagePath));
            }
        }
        
        // If no conversion tool available, return null (will use PDF URL directly)
        \Log::warning('No PDF to image conversion tool available. PDF background may not be included.');
        return null;
    }
    
    protected function commandExists(string $command): bool
    {
        $whereIsCommand = (PHP_OS === 'WINNT') ? 'where' : 'which';
        $process = proc_open(
            "$whereIsCommand $command",
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes
        );
        
        if ($process !== false) {
            $stdout = stream_get_contents($pipes[1]);
            $returnCode = proc_close($process);
            return $returnCode === 0 && !empty($stdout);
        }
        
        return false;
    }
    
    protected function buildHtmlWithPdfImageBackground(array $template, ?string $pdfImageUrl, ?string $pdfFileUrl, array $fields, array $rowData, array $columnMapping): string
    {
        $width = $template['width'] ?? 210;
        $height = $template['height'] ?? 297;
        
        // Get PDF image URL or use PDF file URL as fallback
        $backgroundUrl = $pdfImageUrl;
        if (!$backgroundUrl && $pdfFileUrl) {
            // Fallback: use PDF file URL (DomPDF may not render it, but we try)
            $backgroundUrl = asset($pdfFileUrl);
        }
        
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
        }
        .page {
            width: ' . $width . 'mm;
            height: ' . $height . 'mm;
            position: relative;
            background: white;
            overflow: hidden;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        .pdf-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            object-fit: contain;
            image-rendering: -webkit-optimize-contrast;
            image-rendering: crisp-edges;
            image-rendering: high-quality;
            image-rendering: -moz-crisp-edges;
        }
        .field {
            position: absolute;
            box-sizing: border-box;
            display: block;
            z-index: 2;
            white-space: pre-wrap;
            word-wrap: break-word;
            overflow: visible;
            background: transparent;
        }
    </style>
</head>
<body>
    <div class="page">
';
        
        // Add PDF background image if available
        if ($backgroundUrl) {
            // Check if it's a base64 data URI
            if (strpos($backgroundUrl, 'data:image') === 0) {
                // Use base64 data URI directly - this preserves maximum quality
                // DomPDF will embed the image directly without re-compression
                $html .= '<img class="pdf-background" src="' . htmlspecialchars($backgroundUrl, ENT_QUOTES, 'UTF-8') . '" alt="PDF Background" />';
            } else {
                // Use absolute URL for DomPDF
                $absoluteUrl = $backgroundUrl;
                if (!preg_match('/^https?:\/\//', $absoluteUrl)) {
                    // Convert relative URL to absolute
                    $absoluteUrl = url($backgroundUrl);
                }
                $html .= '<img class="pdf-background" src="' . htmlspecialchars($absoluteUrl, ENT_QUOTES, 'UTF-8') . '" alt="PDF Background" style="max-width: 100%; max-height: 100%; object-fit: contain;" />';
            }
        }
        
        // Add fields
        foreach ($fields as $field) {
            $columnName = $field['column'] ?? '';
            $value = '';
            
            // Get value from row data using column mapping
            if (!empty($columnName) && isset($columnMapping[$columnName])) {
                $excelColumnName = $columnMapping[$columnName];
                if (isset($rowData[$excelColumnName])) {
                    $cellValue = $rowData[$excelColumnName];
                    if (is_array($cellValue)) {
                        $cellValue = implode(', ', array_filter($cellValue, function($item) {
                            return !is_array($item) && !is_object($item);
                        }));
                    } elseif (is_object($cellValue)) {
                        $cellValue = json_encode($cellValue);
                    }
                    $value = $cellValue !== null && $cellValue !== '' ? (string)$cellValue : '';
                    $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                }
            }
            
            if (empty($value)) {
                continue;
            }
            
            $x = $field['x'] ?? 0;
            $y = $field['y'] ?? 0;
            $width = $field['width'] ?? 100;
            $height = $field['height'] ?? 20;
            $fontSize = $field['fontSize'] ?? 12;
            $textAlign = $field['textAlign'] ?? 'left';
            $fontFamily = $field['fontFamily'] ?? 'Arial';
            $fontColor = $field['fontColor'] ?? '#000000';
            $fontWeight = $field['fontWeight'] ?? 'normal';
            $fontStyle = $field['fontStyle'] ?? 'normal';
            $textDecoration = $field['textDecoration'] ?? 'none';
            
            // Font size is stored as pixels in the frontend, but for PDF we use points
            // At 96 DPI: 1px = 0.75pt, but for accurate rendering we need to account for DPI
            // For 300 DPI PDF output: 1px (96 DPI) = 0.75pt, but we want to maintain visual size
            // So we convert: fontSize in px (96 DPI) to pt (PDF standard)
            // The correct conversion is: pt = px * (72/96) = px * 0.75
            // However, to match what user sees on screen, we should use the fontSize directly as points
            // Since users typically think in points for PDFs, we'll use fontSize directly as points
            $fontSizePt = $fontSize; // Use fontSize directly as points (user input is treated as points)
            
            // Build text alignment
            $textAlignStyle = '';
            if ($textAlign === 'center') {
                $textAlignStyle = 'text-align: center;';
            } elseif ($textAlign === 'right') {
                $textAlignStyle = 'text-align: right;';
            } else {
                $textAlignStyle = 'text-align: left;';
            }
            
            // Build font styles
            $fontWeightStyle = $fontWeight === 'bold' ? 'font-weight: bold;' : 'font-weight: normal;';
            $fontStyleCss = $fontStyle === 'italic' ? 'font-style: italic;' : 'font-style: normal;';
            $textDecorationCss = $textDecoration === 'underline' ? 'text-decoration: underline;' : 'text-decoration: none;';
            
            // Use points for font size and ensure crisp rendering
            // Position is already in mm, use it directly without any offset
            $style = sprintf(
                'position: absolute; left: %smm; top: %smm; width: %smm; min-height: %smm; font-size: %spt; %s padding: 0; margin: 0; overflow: visible; word-wrap: break-word; white-space: pre-wrap; font-family: %s; color: %s; %s %s %s line-height: 1.2; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; text-rendering: optimizeLegibility; box-sizing: border-box;',
                $x,
                $y,
                $width,
                $height,
                $fontSizePt,
                $textAlignStyle,
                htmlspecialchars($fontFamily, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($fontColor, ENT_QUOTES, 'UTF-8'),
                $fontWeightStyle,
                $fontStyleCss,
                $textDecorationCss
            );
            
            $html .= sprintf(
                '<div class="field" style="%s">%s</div>',
                $style,
                $value
            );
        }
        
        $html .= '
    </div>
</body>
</html>';
        
        return $html;
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
        $options->set('isRemoteEnabled', true); // Enable remote images
        $options->set('isPhpEnabled', true);
        $options->set('defaultPaperSize', 'a4'); // Use A4 paper size
        $options->set('defaultPaperWidth', $width . 'mm');
        $options->set('defaultPaperHeight', $height . 'mm');
        // Note: Default font is set per field in the template, but we keep Arial as fallback
        $options->set('defaultFont', 'Arial');
        // Optimize for speed: Use 150 DPI for faster generation (still good quality)
        $options->set('dpi', 150); // 150 DPI for faster processing while maintaining good quality
        $options->set('enableFontSubsetting', true);
        $options->set('enableCssFloat', true);
        $options->set('isJavascriptEnabled', false);
        $options->set('isFontSubsettingEnabled', true);
        // Optimize image handling
        $options->set('isRemoteEnabled', true);
        $options->set('debugKeepTemp', false);
        $options->set('debugCss', false);
        $options->set('debugLayout', false);
        // Enable compression for faster downloads and smaller file sizes
        $options->set('compress', true);
        // Enable better image rendering
        $options->set('enableHtml5Parser', true);
        // Set image DPI to 200 for faster processing (reduced from 360)
        $options->set('img_dpi', 200);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        // Set paper size in points (width, height) - A4 dimensions
        $dompdf->setPaper([0, 0, $widthPoints, $heightPoints], 'portrait');
        $dompdf->render();
        return $dompdf->output();
    }
}


