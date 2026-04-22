<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class HelpController extends Controller
{
    public function index(): Response
    {
        $files = glob(base_path('docs/help/*.md'));
        sort($files);

        $sections = collect($files)->map(function ($file) {
            $markdown = file_get_contents($file);
            $slug = preg_replace('/^\d+-/', '', pathinfo($file, PATHINFO_FILENAME));

            preg_match('/^##\s+(.+)$/m', $markdown, $matches);
            $title = $matches[1] ?? Str::headline($slug);

            $html = Str::markdown($markdown);
            $html = $this->transformScreenshotPaths($html);

            return [
                'slug' => $slug,
                'title' => $title,
                'html' => $html,
            ];
        })->values()->all();

        return Inertia::render('Help/Index', [
            'sections' => $sections,
        ]);
    }

    /**
     * Transform help screenshot img tags to include light/dark data attributes.
     *
     * Converts: <img src="/help-screenshots/filename.png" alt="..." />
     * Into:     <img src="/help-screenshots/light/filename.png" alt="..."
     *                data-light-src="/help-screenshots/light/filename.png"
     *                data-dark-src="/help-screenshots/dark/filename.png" />
     */
    private function transformScreenshotPaths(string $html): string
    {
        return preg_replace(
            '#<img\s+src="/help-screenshots/([^"/]+\.png)"\s+alt="([^"]*)"\s*/?>#',
            '<img src="/help-screenshots/light/$1" alt="$2" data-light-src="/help-screenshots/light/$1" data-dark-src="/help-screenshots/dark/$1" />',
            $html
        );
    }
}
