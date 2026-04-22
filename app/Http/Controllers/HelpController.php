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

            return [
                'slug' => $slug,
                'title' => $title,
                'html' => Str::markdown($markdown),
            ];
        })->values()->all();

        return Inertia::render('Help/Index', [
            'sections' => $sections,
        ]);
    }
}
