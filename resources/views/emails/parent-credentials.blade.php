<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credenziali di Accesso</title>
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
            background-color: #F59E0B;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f9fafb;
            padding: 30px;
            border: 1px solid #e5e7eb;
            border-top: none;
        }
        .credentials-box {
            background-color: white;
            border: 2px solid #3b82f6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .credential-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .credential-row:last-child {
            border-bottom: none;
        }
        .label {
            font-weight: bold;
            color: #4b5563;
        }
        .value {
            color: #1f2937;
            font-family: monospace;
            font-size: 14px;
        }
        .button {
            display: inline-block;
            background-color: #3b82f6;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
            font-weight: bold;
        }
        .button:hover {
            background-color: #2563eb;
        }
        .footer {
            text-align: center;
            color: #6b7280;
            font-size: 12px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        .warning {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Benvenuto in San Vincenzo Calcio</h1>
    </div>
    
    <div class="content">
        <p>Ciao <strong>{{ $user->name }}</strong>,</p>
        
        <p>Il tuo account genitore è stato creato con successo. Di seguito trovi le tue credenziali di accesso:</p>
        
        <div class="credentials-box">
            <div class="credential-row">
                <span class="label">Email:</span>
                <span class="value">{{ $user->email }}</span>
            </div>
            <div class="credential-row">
                <span class="label">Password temporanea:</span>
                <span class="value">{{ $password }}</span>
            </div>
        </div>
        
        <div style="text-align: center;">
            <a href="{{ $loginUrl }}" class="button">Accedi al Portale</a>
        </div>
        
        <div class="warning">
            <strong>⚠️ Importante:</strong> Per motivi di sicurezza, ti consigliamo di cambiare la password al primo accesso. Puoi farlo dalla sezione "Profilo" dopo aver effettuato il login.
        </div>
        
        <p>Se hai domande o hai bisogno di assistenza, non esitare a contattarci.</p>
        
        <p>Saluti,<br>
        <strong>Team San Vincenzo Calcio</strong></p>
    </div>
    
    <div class="footer">
        <p>Questa email è stata inviata automaticamente. Non rispondere a questo messaggio.</p>
        <p>&copy; {{ date('Y') }} San Vincenzo Calcio. Tutti i diritti riservati.</p>
    </div>
</body>
</html>



