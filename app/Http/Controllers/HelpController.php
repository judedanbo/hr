<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class HelpController extends Controller
{
    public function index(): Response
    {
        $filePath = base_path('docs/HELP.md');

        $content = file_exists($filePath)
            ? Str::markdown(file_get_contents($filePath))
            : '<p>Help documentation not found.</p>';

        return Inertia::render('Help/Index', [
            'content' => $content,
        ]);
    }
}
