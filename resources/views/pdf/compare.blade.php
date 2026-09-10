<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>University comparison</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; margin: 0; }
        h1 { font-size: 15px; margin: 0 0 2px; }
        .meta { color: #6b7280; font-size: 9px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 5px 7px; text-align: left; vertical-align: top; }
        thead th { background: #f3f4f6; font-size: 9px; }
        .uni { font-weight: bold; }
        .prog { color: #6b7280; font-size: 8px; }
        .group th { background: #eef2ff; color: #4338ca; text-transform: uppercase; letter-spacing: .04em; font-size: 8px; }
        .field { width: 130px; color: #6b7280; font-size: 8px; text-transform: uppercase; letter-spacing: .04em; }
        .best { background: #ecfdf5; }
        .badge { background: #059669; color: #fff; font-size: 7px; padding: 0 3px; border-radius: 2px; text-transform: uppercase; }
        .foot { margin-top: 12px; color: #6b7280; font-size: 8px; line-height: 1.5; }
    </style>
</head>
<body>
    <h1>University comparison</h1>
    <div class="meta">Generated {{ $generatedAt->format('j M Y, H:i') }} · UniHup</div>

    <table>
        <thead>
            <tr>
                <th class="field">Field</th>
                @foreach ($grid['programs'] as $program)
                    <th>
                        <span class="uni">{{ $program['university'] }}</span><br>
                        <span class="prog">{{ $program['name'] }}</span>
                    </th>
                @endforeach
            </tr>
        </thead>
        @foreach ($grid['groups'] as $group)
            <tbody>
                <tr class="group">
                    <th colspan="{{ count($grid['programs']) + 1 }}">{{ $group['label'] }}</th>
                </tr>
                @foreach ($group['rows'] as $row)
                    <tr>
                        <td class="field">{{ $row['label'] }}</td>
                        @foreach ($row['values'] as $value)
                            <td @class(['best' => $value['best']])>
                                {{ $value['display'] }}
                                @if ($value['best'])<span class="badge">Best</span>@endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        @endforeach
    </table>

    <div class="foot">
        <strong>Best</strong> marks the lowest cost / tuition / duration and the strongest CENSIS position among the programs shown — a shortcut, not advice.
        Estimated yearly cost is a rough first-year total (tuition + living − a possible regional scholarship), assuming ISEE €20,000 and a room in a shared flat.
        Always confirm fees and deadlines on each university's official page before applying.
    </div>
</body>
</html>
