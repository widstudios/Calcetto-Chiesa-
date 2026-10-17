<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>@yield('title') · {{ \App\Models\Setting::siteName() }}</title>
    <style>
        @page { margin: 18mm 14mm 20mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #111; }
        .head { width: 100%; border-bottom: 2px solid #166534; margin-bottom: 14px; padding-bottom: 8px; }
        .head td { vertical-align: middle; border: 0; padding: 0; }
        .head img { height: 46px; width: auto; }
        .head .site { font-size: 9pt; color: #555; }
        .head .when { text-align: right; font-size: 8pt; color: #777; }
        h1 { font-size: 16pt; margin: 2px 0 0; }
        h2 { font-size: 12pt; margin: 18px 0 6px; color: #166534; }
        .sub { margin: 2px 0 0; font-size: 10pt; color: #333; }
        table.grid { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.grid th, table.grid td { border: 1px solid #999; padding: 4px 6px; text-align: left; }
        table.grid th { background: #ecf3ee; font-size: 8.5pt; }
        table.grid tr:nth-child(even) td { background: #f7f7f7; }
        table.grid .num { text-align: center; white-space: nowrap; width: 1%; }
        table.grid .write { height: 22px; }
        .muted { color: #777; }
        .small { font-size: 8.5pt; }
        .yes { color: #166534; font-weight: bold; }
        .no { color: #b91c1c; }
        .sign { width: 100%; margin-top: 40px; }
        .sign td { width: 50%; border: 0; border-top: 1px solid #000; padding-top: 4px; text-align: center; font-size: 9pt; }
        .sign td.gap { width: 6%; border-top: 0; }
        .avoid { page-break-inside: avoid; }
    </style>
</head>
<body>
    <table class="head">
        <tr>
            @if ($logoPath)
                <td style="width: 60px"><img src="{{ $logoPath }}" alt=""></td>
            @endif
            <td>
                <div class="site">{{ \App\Models\Setting::siteName() }}@hasSection('eyebrow') · @yield('eyebrow')@endif</div>
                <h1>@yield('title')</h1>
                @hasSection('subtitle')<p class="sub">@yield('subtitle')</p>@endif
            </td>
            <td class="when">Creato il {{ now()->format('d/m/Y H:i') }}</td>
        </tr>
    </table>

    @yield('content')
</body>
</html>
