<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 600px;
        }
        h1 {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
        }
        .version {
            color: #764ba2;
            font-size: 1.2rem;
            margin-bottom: 2rem;
        }
        .info {
            background: #f7f7f7;
            padding: 1.5rem;
            border-radius: 10px;
            margin-top: 2rem;
        }
        .info p {
            margin: 0.5rem 0;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Laravel</h1>
        <p class="version">Najnowsza wersja z Dockerem</p>
        <div class="info">
            <p><strong>Aplikacja działa poprawnie!</strong></p>
            <p>Środowisko: Docker</p>
            <p>PHP: 8.3</p>
        </div>
    </div>
</body>
</html>
