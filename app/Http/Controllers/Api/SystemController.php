<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class SystemController extends Controller
{
    public function migrate(Request $request)
    {
        $expectedSecret = env('SYS_DEPLOY_TOKEN');
        if (empty($expectedSecret)) {
            return response()->json([
                'success' => false,
                'error' => 'SYS_DEPLOY_TOKEN is not configured on server.'
            ], 500);
        }

        $authHeader = $request->header('Authorization', '');
        $secret = '';
        if (str_starts_with($authHeader, 'Bearer ')) {
            $secret = substr($authHeader, 7);
        } elseif (!empty($authHeader)) {
            $secret = $authHeader;
        }

        if (!hash_equals($expectedSecret, $secret)) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized access. Secret token mismatch.'
            ], 403);
        }

        $isProduction = app()->environment('production') || config('app.env') === 'production';
        $fresh = $request->boolean('fresh') || $request->input('fresh') === '1';
        $seed = $request->boolean('seed') || $request->input('seed') === '1';

        // Block fresh migration on production to prevent data loss
        if ($isProduction && $fresh) {
            return response()->json([
                'success' => false,
                'error' => 'Fresh migration is DISABLED on production to prevent data loss.'
            ], 403);
        }

        $logs = [];
        $logs[] = 'CWD: ' . getcwd();
        $logs[] = 'Base: ' . base_path();
        $logs[] = 'Resources views exists: ' . (is_dir(resource_path('views')) ? 'YES' : 'NO');
        if (is_dir(resource_path('views'))) {
            $logs[] = 'Views files: ' . implode(', ', scandir(resource_path('views')));
        }

        try {
            // Ensure standard Laravel storage directory structure exists
            $storagePaths = [
                storage_path('app'),
                storage_path('app/public'),
                storage_path('framework'),
                storage_path('framework/cache'),
                storage_path('framework/cache/data'),
                storage_path('framework/sessions'),
                storage_path('framework/testing'),
                storage_path('framework/views'),
                storage_path('logs'),
            ];
            
            foreach ($storagePaths as $path) {
                if (!is_dir($path)) {
                    mkdir($path, 0775, true);
                    $logs[] = 'Created directory: ' . $path;
                }
            }

            // Explicitly force view compiled path config update
            config(['view.compiled' => storage_path('framework/views')]);

            if ($fresh) {
                $logs[] = 'Running migrate:fresh...';
                Artisan::call('migrate:fresh', ['--force' => true]);
                $logs[] = Artisan::output();
            } else {
                $logs[] = 'Running migrate...';
                Artisan::call('migrate', ['--force' => true]);
                $logs[] = Artisan::output();
            }

            $seederClass = $request->input('class', '');
            if ($seed) {
                $params = ['--force' => true];
                if (!empty($seederClass)) {
                    $params['--class'] = $seederClass;
                }
                $logs[] = 'Running db:seed ' . ($seederClass ? 'with class ' . $seederClass : '') . '...';
                Artisan::call('db:seed', $params);
                $logs[] = Artisan::output();
            }

            // Perform post-migration caching / optimizations requested by the user
            $logs[] = 'Clearing and optimizing configurations...';
            Artisan::call('optimize:clear');
            $logs[] = Artisan::output();

            // Create storage symlink if not already created
            if (!file_exists(public_path('storage'))) {
                Artisan::call('storage:link');
                $logs[] = 'Storage symlink created.';
            }

            return response()->json([
                'success' => true,
                'message' => 'Database operation completed successfully.',
                'logs' => array_filter(array_map('trim', explode("\n", implode("\n", $logs))))
            ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            Log::error('Migration trigger failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'logs' => $logs
            ], 500, [], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
        }
    }
}
