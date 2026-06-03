<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Leave Report')</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .meta { color: #6b7280; font-size: 10px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 5px 6px; text-align: left; }
        th { background: #f3f4f6; }
        tr:nth-child(even) td { background: #fafafa; }
    </style>
</head>
<body>
    <h1>@yield('title', 'Leave Report')</h1>
    <div class="meta">
        {{ $subtitle ?? '' }} — generated {{ now()->format('Y-m-d H:i') }}
    </div>
    @yield('content')
</body>
</html>
