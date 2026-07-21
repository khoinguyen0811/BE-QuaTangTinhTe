<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MediaController extends Controller
{
    public function __construct(private readonly CloudinaryService $cloudinaryService)
    {
    }

    public function index(Request $request)
    {
        $activeFolder = $request->query('folder', 'general');
        $folders = $this->cloudinaryService->listFolders();
        
        if (!in_array($activeFolder, $folders) && $activeFolder !== 'all') {
            $activeFolder = 'general';
        }

        $resources = $this->cloudinaryService->listResources($activeFolder);
        
        return view('admin.media.index', [
            'folders' => $folders,
            'activeFolder' => $activeFolder,
            'resources' => $resources,
            'isConfigured' => $this->cloudinaryService->isConfigured(),
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'files' => 'nullable|array',
            'files.*' => 'required|file|max:10240', // 10MB limit per file
            'file' => 'nullable|file|max:10240',
            'folder' => 'nullable|string',
        ]);

        $folder = $request->input('folder', 'general');
        $uploadedUrls = [];
        
        try {
            $files = [];
            if ($request->hasFile('files')) {
                $files = $request->file('files');
            } elseif ($request->hasFile('file')) {
                $files = [$request->file('file')];
            }

            if (empty($files)) {
                throw new \Exception("Không có file được chọn để tải lên.");
            }

            foreach ($files as $file) {
                $uploadedUrls[] = $this->cloudinaryService->uploadFile($file, $folder);
            }
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'urls' => $uploadedUrls,
                    'url' => $uploadedUrls[0] ?? null,
                    'message' => __('catalog.media.upload_success')
                ]);
            }

            return redirect()->back()->with('success', __('catalog.media.upload_success'));
        } catch (\Exception $e) {
            Log::error("Media Controller Upload Error: " . $e->getMessage());
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 500);
            }

            return redirect()->back()->withErrors(['file' => $e->getMessage()]);
        }
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'public_id' => 'required|string',
        ]);

        $publicId = $request->input('public_id');

        try {
            $deleted = $this->cloudinaryService->deleteResource($publicId);
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => $deleted,
                    'message' => $deleted ? __('catalog.media.delete_success') : __('catalog.media.delete_failed')
                ]);
            }

            if ($deleted) {
                return redirect()->back()->with('success', __('catalog.media.delete_success'));
            }
            return redirect()->back()->withErrors(['public_id' => __('catalog.media.delete_failed')]);
        } catch (\Exception $e) {
            Log::error("Media Controller Delete Error: " . $e->getMessage());

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 500);
            }

            return redirect()->back()->withErrors(['public_id' => $e->getMessage()]);
        }
    }
}
