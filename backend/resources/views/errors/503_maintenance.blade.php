<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Strona w budowie</title>
    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #0f172a;
            color: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            text-align: center;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            background-color: #1e293b;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3), 0 4px 6px -4px rgba(0, 0, 0, 0.3);
            border: 1px solid #334155;
        }
        .icon {
            font-size: 48px;
            margin-bottom: 20px;
        }
        h1 {
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 700;
        }
        p {
            font-size: 16px;
            color: #94a3b8;
            line-height: 1.6;
        }
        .footer {
            margin-top: 30px;
            font-size: 14px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🛠️</div>
        <h1>Prace konserwacyjne</h1>
        <p>{{ $message }}</p>
        <div class="footer">
            Dziękujemy za wyrozumiałość.
        </div>
    </div>
</body>
</html>
