<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New Form Submission</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #1a1a2e;
            color: #ffffff;
            padding: 20px;
            text-align: center;
        }
        .content {
            padding: 20px;
            background-color: #f8f9fa;
        }
        .field {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #ffffff;
            border-left: 3px solid #c9a050;
        }
        .field-label {
            font-weight: bold;
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
        }
        .field-value {
            margin-top: 5px;
            color: #333;
        }
        .footer {
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $formName }}</h1>
        <p>New submission from {{ $pageName }}</p>
    </div>

    <div class="content">
        @foreach($formData as $field => $value)
            <div class="field">
                <div class="field-label">{{ ucfirst(str_replace('_', ' ', $field)) }}</div>
                <div class="field-value">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <div class="footer">
        <p>This email was sent from your website's contact form.</p>
        <p>Submitted at {{ now()->format('F j, Y \a\t g:i A') }}</p>
    </div>
</body>
</html>
