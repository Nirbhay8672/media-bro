<?php

namespace App\Http\Controllers;

use App\Services\PdfTemplateService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PdfTemplateController extends Controller
{
    public function __construct(
        protected PdfTemplateService $pdfTemplateService
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('PdfTemplate/Index');
    }

    public function uploadExcel(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        $file = $request->file('excel_file');
        $data = $this->pdfTemplateService->readExcelFile($file);

        return response()->json([
            'success' => true,
            'data' => $data,
            'columns' => !empty($data) ? array_keys($data[0]) : [],
        ]);
    }

    public function uploadPdf(Request $request)
    {
        try {
            $request->validate([
                'pdf_file' => 'required|mimes:pdf|max:51200', // Increased to 50MB for live environment
            ], [
                'pdf_file.required' => 'Please select a PDF file to upload.',
                'pdf_file.mimes' => 'The file must be a PDF file.',
                'pdf_file.max' => 'The PDF file size must not exceed 50MB.',
            ]);

            $file = $request->file('pdf_file');
            
            if (!$file || !$file->isValid()) {
                return response()->json([
                    'message' => 'The pdf file failed to upload.',
                    'errors' => [
                        'pdf_file' => ['The uploaded file is invalid or corrupted.']
                    ]
                ], 422);
            }

            $result = $this->pdfTemplateService->uploadPdfFile($file);

            return response()->json([
                'success' => true,
                'file_path' => $result['file_path'],
                'file_url' => $result['file_url'],
                'dimensions' => $result['dimensions'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'The pdf file failed to upload.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('PDF Upload Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'The pdf file failed to upload.',
                'errors' => [
                    'pdf_file' => [$e->getMessage() ?: 'An error occurred while uploading the file.']
                ]
            ], 422);
        }
    }

    public function generatePdfs(Request $request)
    {
        $request->validate([
            'template' => 'required|array',
            'excel_data' => 'required|array',
            'column_mapping' => 'required|array',
            'pdf_file_path' => 'nullable|string',
            'pdf_page_image' => 'nullable', // Can be string (single page) or array (multiple pages)
        ]);

        $template = $request->input('template');
        $excelData = $request->input('excel_data');
        $columnMapping = $request->input('column_mapping');
        $pdfFilePath = $request->input('pdf_file_path');
        $pdfPageImage = $request->input('pdf_page_image');

        // Log for debugging (remove in production)
        \Log::info('PDF Generation', [
            'template_fields' => count($template['fields'] ?? []),
            'excel_rows' => count($excelData),
            'column_mapping' => $columnMapping,
            'has_pdf_file' => !empty($pdfFilePath),
            'first_row_keys' => !empty($excelData) ? array_keys($excelData[0] ?? []) : [],
        ]);

        $pdfPreviews = $this->pdfTemplateService->generatePdfsFromTemplate(
            $template,
            $excelData,
            $columnMapping,
            $pdfFilePath,
            $pdfPageImage
        );

        return response()->json([
            'success' => true,
            'message' => 'PDFs generated successfully',
            'pdfs' => $pdfPreviews,
        ]);
    }
}


