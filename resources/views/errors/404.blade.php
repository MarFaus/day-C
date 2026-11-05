<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page not found | WorldSkills Events</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
            background: #f4f6f8;
            color: #17212b;
        }
        .card {
            width: min(560px, calc(100% - 40px));
            padding: 48px;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 24px 60px rgba(16, 32, 48, .12);
            text-align: center;
        }
        .code {
            font-size: 88px;
            line-height: 1;
            font-weight: 800;
            color: #1f6feb;
            letter-spacing: -.06em;
        }
        h1 { margin: 16px 0 8px; font-size: 28px; }
        p { margin: 0 0 28px; color: #5b6b7c; line-height: 1.6; }
        a {
            display: inline-block;
            padding: 12px 22px;
            border-radius: 999px;
            background: #17212b;
            color: #fff;
            text-decoration: none;
            font-weight: 600;
        }
        a:hover { background: #1f6feb; }
    </style>
</head>
<body>
    <div class="card">
        <div class="code">404</div>
        <h1>Page not found</h1>
        <p>The page you are looking for does not exist or was moved.</p>
        <a href="{{ url('/app') }}">Browse events</a>
    </div>
</body>
</html>
