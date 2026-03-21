<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PurgePdsTempFiles extends Command
{
    protected $signature = 'pds:purge-temp';

    protected $description = 'Delete orphaned PDS temporary export files older than one hour';

    public function handle(): int
    {
        $tempPath = storage_path('app/temp');

        if (!File::isDirectory($tempPath)) {
            $this->info('PDS temp directory does not exist.');
            return self::SUCCESS;
        }

        $threshold = now()->subHour()->getTimestamp();
        $deleted = 0;

        foreach (File::files($tempPath) as $file) {
            if (!str_starts_with($file->getFilename(), 'pds_')) {
                continue;
            }

            if ($file->getMTime() >= $threshold) {
                continue;
            }

            File::delete($file->getPathname());
            $deleted++;
        }

        $this->info("Deleted {$deleted} old PDS temp file(s).");

        return self::SUCCESS;
    }
}
