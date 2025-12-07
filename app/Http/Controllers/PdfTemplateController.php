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

    public function generatePdfs(Request $request)
    {
        $request->validate([
            'template' => 'required|array',
            'excel_data' => 'required|array',
            'column_mapping' => 'required|array',
        ]);

        $template = $request->input('template');
        $excelData = $request->input('excel_data');
        $columnMapping = $request->input('column_mapping');

        // Log for debugging (remove in production)
        \Log::info('PDF Generation', [
            'template_fields' => count($template['fields'] ?? []),
            'excel_rows' => count($excelData),
            'column_mapping' => $columnMapping,
            'first_row_keys' => !empty($excelData) ? array_keys($excelData[0] ?? []) : [],
        ]);

        $pdfPreviews = $this->pdfTemplateService->generatePdfsFromTemplate(
            $template,
            $excelData,
            $columnMapping
        );

        return response()->json([
            'success' => true,
            'message' => 'PDFs generated successfully',
            'pdfs' => $pdfPreviews,
        ]);
    }
}


