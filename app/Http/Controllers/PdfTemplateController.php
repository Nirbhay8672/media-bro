<?php

namespace App\Http\Controllers;

use App\Services\PdfTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
            // Log request details for debugging
            Log::info('PDF Upload Request', [
                'has_file' => $request->hasFile('pdf_file'),
                'all_files' => array_keys($request->allFiles()),
                'content_type' => $request->header('Content-Type'),
                'content_length' => $request->header('Content-Length'),
                'post_max_size' => ini_get('post_max_size'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'max_file_uploads' => ini_get('max_file_uploads'),
            ]);

            // Check if file exists in request
            if (!$request->hasFile('pdf_file')) {
                Log::warning('PDF Upload: No file in request', [
                    'all_input' => array_keys($request->all()),
                    'all_files' => array_keys($request->allFiles()),
                ]);
                
                return response()->json([
                    'message' => 'The pdf file failed to upload.',
                    'errors' => [
                        'pdf_file' => ['No file was uploaded. Please select a PDF file.']
                    ]
                ], 422);
            }

            $request->validate([
                'pdf_file' => 'required|mimes:pdf|max:51200', // Increased to 50MB for live environment
            ], [
                'pdf_file.required' => 'Please select a PDF file to upload.',
                'pdf_file.mimes' => 'The file must be a PDF file.',
                'pdf_file.max' => 'The PDF file size must not exceed 50MB.',
            ]);

            $file = $request->file('pdf_file');
            
            Log::info('PDF File Details', [
                'is_valid' => $file ? $file->isValid() : false,
                'size' => $file ? $file->getSize() : null,
                'mime_type' => $file ? $file->getMimeType() : null,
                'original_name' => $file ? $file->getClientOriginalName() : null,
                'error' => $file ? $file->getError() : null,
            ]);
            
            if (!$file) {
                return response()->json([
                    'message' => 'The pdf file failed to upload.',
                    'errors' => [
                        'pdf_file' => ['File object is null.']
                    ]
                ], 422);
            }
            
            if (!$file->isValid()) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'The file exceeds the upload_max_filesize directive in php.ini.',
                    UPLOAD_ERR_FORM_SIZE => 'The file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
                    UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded.',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                    UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
                ];
                
                $errorCode = $file->getError();
                $errorMessage = $errorMessages[$errorCode] ?? 'Unknown upload error (code: ' . $errorCode . ')';
                
                Log::error('PDF Upload: Invalid file', [
                    'error_code' => $errorCode,
                    'error_message' => $errorMessage,
                    'file_size' => $file->getSize(),
                    'max_upload_size' => ini_get('upload_max_filesize'),
                    'post_max_size' => ini_get('post_max_size'),
                ]);
                
                return response()->json([
                    'message' => 'The pdf file failed to upload.',
                    'errors' => [
                        'pdf_file' => [$errorMessage]
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
            Log::error('PDF Upload: Validation Error', [
                'errors' => $e->errors(),
            ]);
            
            return response()->json([
                'message' => 'The pdf file failed to upload.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('PDF Upload Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
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


