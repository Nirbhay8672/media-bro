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
                // Check if this is a PHP upload size limit issue
                $contentLength = $request->header('Content-Length');
                $uploadMaxFilesize = ini_get('upload_max_filesize');
                $postMaxSize = ini_get('post_max_size');
                
                // Convert PHP size strings to bytes for comparison
                $uploadMaxBytes = $this->convertPhpSizeToBytes($uploadMaxFilesize);
                $postMaxBytes = $this->convertPhpSizeToBytes($postMaxSize);
                
                Log::warning('PDF Upload: No file in request', [
                    'all_input' => array_keys($request->all()),
                    'all_files' => array_keys($request->allFiles()),
                    'content_length' => $contentLength,
                    'upload_max_filesize' => $uploadMaxFilesize,
                    'post_max_size' => $postMaxSize,
                ]);
                
                // Check if file was too large for PHP
                if ($contentLength && $contentLength > $uploadMaxBytes) {
                    return response()->json([
                        'message' => 'The pdf file failed to upload.',
                        'errors' => [
                            'pdf_file' => [
                                sprintf(
                                    'File size (%.2f MB) exceeds PHP upload_max_filesize limit (%s). Please increase upload_max_filesize in php.ini or contact your server administrator.',
                                    $contentLength / 1024 / 1024,
                                    $uploadMaxFilesize
                                )
                            ]
                        ]
                    ], 422);
                }
                
                if ($contentLength && $contentLength > $postMaxBytes) {
                    return response()->json([
                        'message' => 'The pdf file failed to upload.',
                        'errors' => [
                            'pdf_file' => [
                                sprintf(
                                    'File size (%.2f MB) exceeds PHP post_max_size limit (%s). Please increase post_max_size in php.ini or contact your server administrator.',
                                    $contentLength / 1024 / 1024,
                                    $postMaxSize
                                )
                            ]
                        ]
                    ], 422);
                }
                
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

    /**
     * Convert PHP size string (e.g., "2M", "8M") to bytes
     */
    private function convertPhpSizeToBytes(string $size): int
    {
        $size = trim($size);
        $last = strtolower($size[strlen($size) - 1]);
        $value = (int) $size;
        
        switch ($last) {
            case 'g':
                $value *= 1024;
                // no break
            case 'm':
                $value *= 1024;
                // no break
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }

    public function generatePdfs(Request $request)
    {
        \Log::info('📥 PDF Generation Request Received', [
            'method' => $request->method(),
            'has_template' => $request->has('template'),
            'has_excel_data' => $request->has('excel_data'),
            'has_column_mapping' => $request->has('column_mapping'),
            'content_type' => $request->header('Content-Type'),
            'content_length' => $request->header('Content-Length'),
        ]);
        
        try {
            $request->validate([
                'template' => 'required|array',
                'excel_data' => 'required|array',
                'column_mapping' => 'required|array',
                'pdf_file_path' => 'nullable|string',
                'pdf_page_image' => 'nullable', // Can be string (single page) or array (multiple pages)
            ]);

            \Log::info('✅ Validation passed, processing request...');

            $template = $request->input('template');
            $excelData = $request->input('excel_data');
            $columnMapping = $request->input('column_mapping');
            $pdfFilePath = $request->input('pdf_file_path');
            $pdfPageImage = $request->input('pdf_page_image');
            
            \Log::info('📋 Request data extracted', [
                'template_pages' => count($template['pages'] ?? []),
                'excel_rows' => count($excelData),
                'column_mapping_count' => count($columnMapping),
                'has_pdf_file' => !empty($pdfFilePath),
            ]);

            // Validate template structure
            if (empty($template['pages']) || !is_array($template['pages'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Template must have a pages array.',
                    'pdfs' => [],
                ], 422);
            }

            // Count total fields across all pages
            $totalFields = 0;
            foreach ($template['pages'] ?? [] as $page) {
                $totalFields += count($page['fields'] ?? []);
            }

            // Log for debugging
            \Log::info('PDF Generation Request', [
                'template_pages' => count($template['pages'] ?? []),
                'template_fields' => $totalFields,
                'excel_rows' => count($excelData),
                'column_mapping_count' => count($columnMapping),
                'has_pdf_file' => !empty($pdfFilePath),
                'has_pdf_image' => !empty($pdfPageImage),
            ]);

            // Start timing for generation
            $generationStartTime = microtime(true);
            
            $pdfPreviews = $this->pdfTemplateService->generatePdfsFromTemplate(
                $template,
                $excelData,
                $columnMapping,
                $pdfFilePath,
                $pdfPageImage
            );

            // Validate generated PDFs
            if (empty($pdfPreviews) || !is_array($pdfPreviews)) {
                \Log::warning('PDF Generation: No PDFs generated', [
                    'excel_rows' => count($excelData),
                    'template_pages' => count($template['pages'] ?? []),
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'No PDFs were generated. Please check your template and data.',
                    'pdfs' => [],
                ], 200);
            }

            // Validate each PDF has required fields
            $validPdfs = [];
            foreach ($pdfPreviews as $index => $pdf) {
                if (isset($pdf['base64']) && !empty($pdf['base64']) && is_string($pdf['base64'])) {
                    $validPdfs[] = $pdf;
                } else {
                    \Log::warning('PDF Generation: Invalid PDF at index ' . $index);
                }
            }

            if (empty($validPdfs)) {
                \Log::error('PDF Generation: No valid PDFs generated');
                
                return response()->json([
                    'success' => false,
                    'message' => 'PDFs were generated but are invalid. Please check your template and data.',
                    'pdfs' => [],
                ], 200);
            }

            // Calculate response size for logging
            $responseSize = 0;
            foreach ($validPdfs as $pdf) {
                $responseSize += strlen($pdf['base64'] ?? '');
            }
            $responseSizeMB = round($responseSize / 1024 / 1024, 2);
            $generationDuration = round((microtime(true) - $generationStartTime) * 1000, 2);
            
            \Log::info('✅ PDF Generation Success', [
                'total_pdfs' => count($validPdfs),
                'excel_rows' => count($excelData),
                'response_size_mb' => $responseSizeMB,
                'generation_time_ms' => $generationDuration,
            ]);

            \Log::info('📤 Sending response to client...');
            
            // Return PDFs directly in response (don't store in session - too slow)
            $response = response()->json([
                'success' => true,
                'message' => 'PDFs generated successfully',
                'pdfs' => $validPdfs,
            ], 200, [
                'Content-Type' => 'application/json',
            ]);
            
            \Log::info('✅ Response sent successfully');
            return $response;

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('PDF Generation: Validation Error', [
                'errors' => $e->errors(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'pdfs' => [],
            ], 422);

        } catch (\Exception $e) {
            \Log::error('PDF Generation Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while generating PDFs: ' . $e->getMessage(),
                'pdfs' => [],
            ], 500);
        }
    }

    public function preview(Request $request): Response
    {
        // PDFs will be loaded from localStorage on frontend
        // This page just renders the component
        return Inertia::render('PdfTemplate/Preview', [
            'pdfs' => [], // Empty initially, will be loaded from localStorage
            'timestamp' => now()->timestamp,
        ]);
    }
}


