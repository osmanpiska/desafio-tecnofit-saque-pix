<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saque PIX Concluído</title>
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
            background: #4CAF50;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background: #f9f9f9;
            padding: 30px;
            border: 1px solid #ddd;
        }
        .amount {
            font-size: 24px;
            font-weight: bold;
            color: #4CAF50;
            text-align: center;
            margin: 20px 0;
        }
        .details {
            background: white;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .label {
            font-weight: bold;
            color: #666;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Saque PIX Concluído</h1>
    </div>

    <div class="content">
        <p>Olá,</p>
        <p>Seu saque via PIX foi processado com sucesso!</p>

        <div class="amount">
            R$ {{ number_format($amount, 2, ',', '.') }}
        </div>

        <div class="details">
            <div class="detail-row">
                <span class="label">Data e Hora:</span>
                <span>{{ $processed_at }}</span>
            </div>
            <div class="detail-row">
                <span class="label">Código do Saque:</span>
                <span>{{ $withdraw_id }}</span>
            </div>
            <div class="detail-row">
                <span class="label">Tipo de Chave:</span>
                <span>{{ strtoupper($pix_type) }}</span>
            </div>
            <div class="detail-row">
                <span class="label">Chave PIX:</span>
                <span>{{ $pix_key }}</span>
            </div>
        </div>

        <p style="text-align: center; margin-top: 30px;">
            <small>Este é um email automático. Por favor, não responda.</small>
        </p>
    </div>

    <div class="footer">
        <p>Saque PIX - Sistema de Saque Automatizado</p>
        <p>&copy; {{ date('Y') }} - Todos os direitos reservados</p>
    </div>
</body>
</html>
