<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ZipArchive;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use App\Models\Admin\Setting;

class DispatcherZipFilesAddons implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     * 
     */
    protected $zipFileName;
    protected $zipFilePath;
    public function __construct($zipFileName, $zipFilePath)
    {
        $this->zipFileName = $zipFileName;
        $this->zipFilePath = $zipFilePath;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $uploadPath = storage_path('app/public/');
        $zip = new ZipArchive;
        if ($zip->open($this->zipFilePath) === TRUE) {

            // Extract ZIP to temp folder
            $tempExtractPath = $uploadPath . '/extracted';
            if (!file_exists($tempExtractPath)) {
                mkdir($tempExtractPath, 0777, true);
            }

            $zip->extractTo($tempExtractPath);
            $zip->close();

            // ✅ Loop through extracted files
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($tempExtractPath, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            $fileMoved = false;

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $relativePath = str_replace($tempExtractPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $relativePath = str_replace('\\', '/', $relativePath); 
                    $targetPath = null;

                    // Determine destination based on top folder
                    if (str_starts_with($relativePath, 'dispatch/')) {
                        $targetPath = base_path('resources/js/Pages/' . substr($relativePath, strlen('dispatch/')));
                    } 
                    elseif (str_starts_with($relativePath, 'Auth/')) {
                        $targetPath = base_path('resources/js/Pages/Auth/' . substr($relativePath, strlen('Auth/')));
                    }
                     elseif (str_starts_with($relativePath, 'table/')) {
                        $targetPath = base_path('database/migrations/' . substr($relativePath, strlen('table/')));
                    }
                    elseif (str_starts_with($relativePath, 'Components/')) {
                        $targetPath = base_path('resources/js/Components/' . substr($relativePath, strlen('Components/')));
                    }
                     elseif (str_starts_with($relativePath, 'Layouts/')) {
                        $targetPath = base_path('resources/js/Layouts/' . substr($relativePath, strlen('Layouts/')));
                    }
                    elseif (str_starts_with($relativePath, 'controller/')) {
                        $targetPath = base_path('app/Http/Controllers/' . substr($relativePath, strlen('controller/')));
                    } 
                     elseif (str_starts_with($relativePath, 'model/')) {
                        $targetPath = base_path('app/Models/Request/' . substr($relativePath, strlen('model/')));
                    } 
                    elseif (str_starts_with($relativePath, 'web-create-request/')) {
                        $targetPath = base_path('app/Http/Controllers/Web/Admin/' . substr($relativePath, strlen('web-create-request/')));
                    } 
                    elseif (str_starts_with($relativePath, 'profile/')) {
                        $targetPath = base_path('resources/js/Pages/pages/' . substr($relativePath, strlen('profile/')));
                    } 
                    elseif (str_starts_with($relativePath, 'routes/')) {
                        $targetPath = base_path('routes/web/' . substr($relativePath, strlen('routes/')));
                    } 
                    elseif (str_starts_with($relativePath, 'filter/')) {
                        $targetPath = base_path('app/base/filters/Admin/' . substr($relativePath, strlen('filter/')));
                    } 
                    // else {
                    //     // Default folder for unrecognized paths
                    //     $targetPath = storage_path('app/misc/' . $relativePath);
                    // }

                    // // Ensure target folder exists
                    // File::ensureDirectoryExists(dirname($targetPath));

                    // // Move the file
                    // File::move($file->getPathname(), $targetPath);

                      // ✅ Only move if recognized target path found
                if ($targetPath) {
                    File::ensureDirectoryExists(dirname($targetPath));
                    File::move($file->getPathname(), $targetPath);
                    $fileMoved = true; //  Mark that at least one file was moved
                }
                }
            }

            //  Clean up temp files
            File::deleteDirectory($tempExtractPath);
            File::delete($this->zipFilePath);

            // Setting::firstOrCreate(
            //    ['name' => 'dispatcher-addons'], 
            //    ['value' => '1']   
            // );

            if ($fileMoved) {
                Setting::updateOrCreate(
                    ['name' => 'dispatcher-addons'],
                    ['value' => '1']
                );
            }
        }
    }
}
