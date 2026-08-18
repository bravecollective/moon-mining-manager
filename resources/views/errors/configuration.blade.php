<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Application incorrectly configured</title>
    <style>
        body { margin: 3rem auto; max-width: 48rem; padding: 0 1.5rem; font: 1rem/1.5 sans-serif; color: #252525; }
        h1 { line-height: 1.2; }
        code { background: #f2f2f2; padding: .15rem .35rem; }
    </style>
</head>
<body>
    <h1>Application incorrectly configured</h1>
    <p>Set the following required environment variables, then restart the application:</p>
    <ul>
        @foreach ($missingVariables as $variable)
            <li><code>{{ $variable }}</code></li>
        @endforeach
    </ul>
</body>
</html>
