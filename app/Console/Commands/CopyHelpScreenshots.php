<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CopyHelpScreenshots extends Command
{
    protected $signature = 'help:copy-screenshots';

    protected $description = 'Copy Dusk screenshots to docs/screenshots for help documentation';

    public function handle(): int
    {
        $source = base_path('tests/Browser/screenshots');
        $destination = base_path('docs/screenshots');

        if (! File::isDirectory($source)) {
            $this->error("Source directory not found: {$source}");
            $this->info('Run `php artisan dusk --filter=HelpScreenshotTest` first.');

            return self::FAILURE;
        }

        $files = File::glob("{$source}/*.png");

        if (empty($files)) {
            $this->warn('No screenshots found in ' . $source);

            return self::FAILURE;
        }

        File::ensureDirectoryExists($destination);

        $copied = 0;
        foreach ($files as $file) {
            $filename = basename($file);
            File::copy($file, "{$destination}/{$filename}");
            $this->line("  Copied: {$filename}");
            $copied++;
        }

        $this->info("Copied {$copied} screenshots to docs/screenshots/");

        return self::SUCCESS;
    }
}
