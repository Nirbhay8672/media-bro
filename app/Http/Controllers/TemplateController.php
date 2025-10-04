<?php

namespace App\Http\Controllers;

use App\Models\Template;
use App\Services\VisitorTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TemplateController extends Controller
{
    use AuthorizesRequests;

    protected $visitorTrackingService;

    public function __construct(VisitorTrackingService $visitorTrackingService)
    {
        $this->visitorTrackingService = $visitorTrackingService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $templates = Template::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Templates/Index', [
            'templates' => $templates
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Templates/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        return $this->save($request);
    }

    /**
     * Display the specified resource.
     */
    public function show(Template $template)
    {
        return Inertia::render('Templates/Show', [
            'template' => $template
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Template $template)
    {
        return Inertia::render('Templates/Create', [
            'template' => $template
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Template $template)
    {
        return $this->save($request, $template);
    }

    /**
     * Save template (create or update)
     */
    private function save(Request $request, Template $template = null)
    {
        $isUpdate = $template !== null;
        
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'width' => 'required|integer|min:100|max:4000',
            'height' => 'required|integer|min:100|max:4000',
            'background_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
        ]);

        if ($isUpdate) {
            // Debug logging for update
            Log::info('Template update request:', [
                'template_id' => $template->id,
                'request_data' => $request->all(),
                'canvas_data' => $request->canvas_data
            ]);
        } else {
            // Debug logging for create
            Log::info('Canvas data being saved:', [
                'raw' => $request->canvas_data,
                'parsed' => $request->canvas_data,
                'width' => $request->width,
                'height' => $request->height
            ]);
        }

        if (!$isUpdate) {
            $template = new Template();
            $template->user_id = auth()->id();
        }

        $template->name = $request->name;
        $template->description = $request->description;
        $template->width = $request->width;
        $template->height = $request->height;
        
        // Parse canvas_data if it's a JSON string
        $canvasData = $request->canvas_data ?? [];
        if (is_string($canvasData)) {
            $canvasData = json_decode($canvasData, true) ?? [];
        }
        $template->canvas_data = $canvasData;

        if ($request->hasFile('background_image')) {
            // Delete old background image if updating
            if ($isUpdate && $template->background_image) {
                Storage::disk('public')->delete($template->background_image);
            }
            $template->background_image = $request->file('background_image')->store('templates/backgrounds', 'public');
        }

        $template->save();

        if ($isUpdate) {
            return back()->with('success', 'Template updated successfully!');
        } else {
            return back()->with('success', 'Template created successfully!');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Template $template)
    {
        // Delete background image if exists
        if ($template->background_image) {
            Storage::disk('public')->delete($template->background_image);
        }

        $template->delete();

        return redirect()->route('templates.index')->with('success', 'Template deleted successfully!');
    }

    /**
     * Show public template for editing
     */
    public function share(Request $request, $token)
    {
        $template = Template::where('share_token', $token)
            ->where('is_active', true)
            ->firstOrFail();

        // Track the visit
        $this->visitorTrackingService->trackVisit($template, $request);

        return Inertia::render('Templates/Share', [
            'template' => $template
        ]);
    }

    /**
     * Generate final image
     */
    public function generate(Request $request, $token)
    {
        $template = Template::where('share_token', $token)
            ->where('is_active', true)
            ->firstOrFail();

        try {
            // Get canvas data from request
            $canvasData = json_decode($request->input('canvas_data'), true);

            Log::info('Canvas data received:', $canvasData);
            Log::info('Template dimensions:', ['width' => $template->width, 'height' => $template->height]);

            // Create the final image
            $filename = 'generated_' . $template->id . '_' . time() . '.png';
            $filepath = storage_path('app/public/generated/' . $filename);

            // Ensure directory exists
            if (!file_exists(dirname($filepath))) {
                mkdir(dirname($filepath), 0755, true);
            }

            // Create main canvas
            $image = imagecreate($template->width, $template->height);
            $white = imagecolorallocate($image, 255, 255, 255);
            $black = imagecolorallocate($image, 0, 0, 0);

            // Fill with white background
            imagefill($image, 0, 0, $white);

            // Add background image if exists
            if ($template->background_image) {
                $backgroundPath = storage_path('app/public/' . $template->background_image);
                if (file_exists($backgroundPath)) {
                    $background = imagecreatefromstring(file_get_contents($backgroundPath));
                    if ($background) {
                        // Resize background to fit template dimensions
                        $resizedBackground = imagecreatetruecolor($template->width, $template->height);
                        imagecopyresampled($resizedBackground, $background, 0, 0, 0, 0,
                            $template->width, $template->height, imagesx($background), imagesy($background));
                        imagecopy($image, $resizedBackground, 0, 0, 0, 0, $template->width, $template->height);
                        imagedestroy($background);
                        imagedestroy($resizedBackground);
                    }
                }
            }

            // Process each canvas element
            $imageIndex = 0;
            foreach ($canvasData as $element) {
                $this->renderElement($image, $element, $request, $imageIndex);
                if ($element['type'] === 'image') {
                    $imageIndex++;
                }
            }

            // Save the image
            imagepng($image, $filepath);
            imagedestroy($image);

            // Return download URL
            $downloadUrl = asset('storage/generated/' . $filename);

            return response()->json([
                'success' => true,
                'message' => 'Image generated successfully!',
                'download_url' => $downloadUrl,
            ]);

        } catch (\Exception $e) {
            Log::error('Image generation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate image. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Render a single element on the image
     */
    private function renderElement($image, $element, $request, $imageIndex = 0)
    {
        $x = $element['x'] ?? 0;
        $y = $element['y'] ?? 0;
        $width = $element['width'] ?? 100;
        $height = $element['height'] ?? 100;
        $properties = $element['properties'] ?? [];

        if ($element['type'] === 'text') {
            $this->renderTextElement($image, $x, $y, $width, $height, $properties);
        } elseif ($element['type'] === 'image') {
            $this->renderImageElement($image, $x, $y, $width, $height, $properties, $request, $imageIndex);
        }
    }

    /**
     * Render text element
     */
    private function renderTextElement($image, $x, $y, $width, $height, $properties)
    {
        $text = $properties['text'] ?? '';
        $fontSize = $properties['fontSize'] ?? 16;
        $color = $properties['color'] ?? '#000000';
        $fontWeight = $properties['fontWeight'] ?? 'normal';

        if (empty($text)) return;

        // Convert hex color to RGB
        $rgb = $this->hexToRgb($color);
        $textColor = imagecolorallocate($image, $rgb['r'], $rgb['g'], $rgb['b']);

        // Calculate text position (center of element)
        $textX = $x + ($width / 2);
        $textY = $y + ($height / 2);

        // Choose font size based on fontSize property
        $font = 5; // Default font
        if ($fontSize <= 12) $font = 2;
        elseif ($fontSize <= 16) $font = 3;
        elseif ($fontSize <= 20) $font = 4;
        elseif ($fontSize <= 24) $font = 5;
        else $font = 5;

        // Calculate text width for centering
        $textWidth = strlen($text) * imagefontwidth($font);
        $textHeight = imagefontheight($font);

        // Center the text
        $finalX = $textX - ($textWidth / 2);
        $finalY = $textY - ($textHeight / 2);

        // Add text to image
        imagestring($image, $font, $finalX, $finalY, $text, $textColor);
    }

    /**
     * Render image element
     */
    private function renderImageElement($image, $x, $y, $width, $height, $properties, $request, $imageIndex = 0)
    {
        // Check if there's an uploaded file for this element
        $uploadedFiles = $request->allFiles();
        $imageFile = null;

        // Look for the specific image file based on index
        $targetKey = 'image_' . $imageIndex;
        if (isset($uploadedFiles[$targetKey])) {
            $imageFile = $uploadedFiles[$targetKey];
        }

        if (!$imageFile) return;

        try {
            // Create image from uploaded file
            $uploadedImage = imagecreatefromstring(file_get_contents($imageFile->getPathname()));
            if (!$uploadedImage) return;

            // Resize uploaded image to fit element dimensions
            $resizedImage = imagecreatetruecolor($width, $height);
            imagecopyresampled($resizedImage, $uploadedImage, 0, 0, 0, 0,
                $width, $height, imagesx($uploadedImage), imagesy($uploadedImage));

            // Place resized image on main canvas
            imagecopy($image, $resizedImage, $x, $y, 0, 0, $width, $height);

            // Clean up
            imagedestroy($uploadedImage);
            imagedestroy($resizedImage);
        } catch (\Exception $e) {
            Log::error('Failed to process uploaded image: ' . $e->getMessage());
        }
    }

    /**
     * Track download
     */
    public function trackDownload(Request $request, $token)
    {
        Log::info('Download tracking request received', [
            'token' => $token,
            'request_data' => $request->all(),
            'ip' => $request->ip()
        ]);

        $template = Template::where('share_token', $token)
            ->where('is_active', true)
            ->firstOrFail();

        $request->validate([
            'file_name' => 'required|string|max:255',
            'file_size' => 'nullable|integer|min:0'
        ]);

        try {
            // Track the download
            $this->visitorTrackingService->trackDownload(
                $template, 
                $request, 
                $request->file_name,
                $request->file_size
            );

            Log::info('Download tracked successfully', [
                'template_id' => $template->id,
                'file_name' => $request->file_name
            ]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Failed to track download', [
                'template_id' => $template->id,
                'error' => $e->getMessage()
            ]);

            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Convert hex color to RGB
     */
    private function hexToRgb($hex)
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) == 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2))
        ];
    }

}
