<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <title>Nowe zapytanie o wycenę portretu</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f4f5;
            color: #18181b;
            margin: 0;
            padding: 24px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: 1px solid #e4e4e7;
        }
        .header {
            background-color: #18181b;
            color: #ffffff;
            padding: 24px 32px;
            text-align: left;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: -0.025em;
        }
        .header p {
            margin: 4px 0 0;
            font-size: 13px;
            color: #a1a1aa;
        }
        .content {
            padding: 32px;
        }
        .badge {
            display: inline-block;
            background-color: #fef3c7;
            color: #92400e;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .info-grid {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
        }
        .info-row {
            display: flex;
            padding: 6px 0;
            border-bottom: 1px solid #edf2f7;
            font-size: 14px;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            width: 140px;
            color: #64748b;
            font-weight: 600;
            flex-shrink: 0;
        }
        .info-value {
            color: #0f172a;
            word-break: break-word;
        }
        .message-box {
            background-color: #ffffff;
            border-left: 4px solid #E0115F;
            padding: 16px 20px;
            border-radius: 0 8px 8px 0;
            background-color: #fff1f2;
            margin-bottom: 24px;
        }
        .message-box h3 {
            margin: 0 0 8px 0;
            font-size: 14px;
            color: #9f1239;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .message-text {
            margin: 0;
            font-size: 15px;
            line-height: 1.6;
            color: #334155;
            white-space: pre-wrap;
        }
        .attachments {
            margin-bottom: 24px;
            padding: 16px;
            background-color: #f9fafb;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        .attachments h4 {
            margin: 0 0 12px 0;
            font-size: 14px;
            color: #374151;
        }
        .attachment-item {
            margin: 6px 0;
            font-size: 14px;
        }
        .attachment-item a {
            color: #E0115F;
            text-decoration: underline;
            font-weight: 500;
        }
        .cta-btn {
            display: inline-block;
            background-color: #E0115F;
            color: #ffffff !important;
            padding: 12px 28px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            text-decoration: none;
            text-align: center;
        }
        .footer {
            padding: 20px 32px;
            background-color: #fafafa;
            border-top: 1px solid #e4e4e7;
            font-size: 12px;
            color: #71717a;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Hello Kostek — Nowe Zapytanie o Portret</h1>
            <p>Formularz wyceny i kontaktu ze strony hellokostek.pl</p>
        </div>

        <div class="content">
            <span class="badge">NOWE ZAPYTANIE DO OBSŁUGI</span>

            <div class="info-grid">
                <div class="info-row">
                    <span class="info-label">Klient:</span>
                    <span class="info-value"><strong>{{ $inquiry->name }}</strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">E-mail:</span>
                    <span class="info-value"><a href="mailto:{{ $inquiry->email }}">{{ $inquiry->email }}</a></span>
                </div>
                @if (filled($inquiry->phone))
                <div class="info-row">
                    <span class="info-label">Telefon:</span>
                    <span class="info-value"><a href="tel:{{ $inquiry->phone }}">{{ $inquiry->phone }}</a></span>
                </div>
                @endif
                @if (filled($inquiry->subject))
                <div class="info-row">
                    <span class="info-label">Temat:</span>
                    <span class="info-value">{{ $inquiry->subject }}</span>
                </div>
                @endif
                <div class="info-row">
                    <span class="info-label">Data nadesłania:</span>
                    <span class="info-value">{{ $inquiry->created_at?->format('d.m.Y H:i') ?? date('d.m.Y H:i') }}</span>
                </div>
            </div>

            <div class="message-box">
                <h3>Treść wiadomości od klienta:</h3>
                <p class="message-text">{{ $inquiry->message }}</p>
            </div>

            @php
                $payload = is_array($inquiry->payload) ? $inquiry->payload : (json_decode($inquiry->payload ?? '[]', true) ?: []);
                $attachments = $payload['attachments'] ?? [];
            @endphp

            @if (!empty($payload))
                @php
                    $customFields = array_filter($payload, fn ($k) => $k !== 'attachments', ARRAY_FILTER_USE_KEY);
                @endphp
                @if (!empty($customFields))
                    <div class="attachments" style="margin-bottom: 24px;">
                        <h4>Szczegóły z konfiguratora portretu:</h4>
                        <ul style="margin: 0; padding-left: 20px; font-size: 14px; color: #374151;">
                            @foreach ($customFields as $k => $v)
                                <li><strong>{{ ucfirst(str_replace('_', ' ', $k)) }}:</strong> {{ is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endif

            @if (!empty($attachments))
                <div class="attachments">
                    <h4>Załączone zdjęcia referencyjne ({{ count($attachments) }}):</h4>
                    @foreach ($attachments as $idx => $url)
                        <div class="attachment-item">
                            📷 <a href="{{ $url }}" target="_blank" rel="noopener">Zdjęcie referencyjne #{{ $idx + 1 }} (otwórz w pełnym rozmiarze)</a>
                        </div>
                    @endforeach
                </div>
            @endif

            <div style="text-align: center; margin-top: 32px;">
                <a href="{{ $cmsUrl }}" class="cta-btn">Otwórz zapytanie w panelu CMS</a>
            </div>
        </div>

        <div class="footer">
            Wiadomość wygenerowana automatycznie przez system Hello Kostek.<br>
            Możesz odpowiedzieć bezpośrednio na ten e-mail, aby napisać do klienta ({{ $inquiry->email }}).
        </div>
    </div>
</body>
</html>
