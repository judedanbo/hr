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
        $baseSource = base_path('tests/Browser/screenshots');
        $baseDestination = base_path('docs/screenshots');
        $totalCopied = 0;

        foreach (['light', 'dark'] as $mode) {
            $source = "{$baseSource}/{$mode}";
            $destination = "{$baseDestination}/{$mode}";

            if (! File::isDirectory($source)) {
                $this->warn("Skipping {$mode} mode: source directory not found ({$source})");

                continue;
            }

            $files = File::glob("{$source}/*.png");

            if (empty($files)) {
                $this->warn("Skipping {$mode} mode: no screenshots found");

                continue;
            }

            File::ensureDirectoryExists($destination);

            $this->info("Copying {$mode} mode screenshots:");
            foreach ($files as $file) {
                $filename = basename($file);
                File::copy($file, "{$destination}/{$filename}");
                $this->line("  {$filename}");
                $totalCopied++;
            }
        }

        if ($totalCopied === 0) {
            $this->error('No screenshots were copied. Run Dusk tests first:');
            $this->line('  php artisan dusk --filter=HelpScreenshotTest');
            $this->line('  SCREENSHOT_MODE=dark php artisan dusk --filter=HelpScreenshotTest');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info("Copied {$totalCopied} screenshots total to docs/screenshots/");

        return self::SUCCESS;
    }
}
