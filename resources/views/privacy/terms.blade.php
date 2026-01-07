<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>Terms & Conditions</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            padding: 20px;
            line-height: 1.6;
            color: #333;
        }
        h1, h2, h3 {
            color: #222;
        }
    </style>
</head>
<body>
    <h2>Terms & Conditions</h2>
    <div>{!! $content !!}</div>
</body>
</html>