<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Event Booking Platform · WorldSkills</title>
    <meta name="description" content="Browse WorldSkills conferences, explore the agenda and register for talks and workshops.">
    <link rel="stylesheet" href="{{ asset('assets/attendee/app.css') }}?v={{ filemtime(public_path('assets/attendee/app.css')) }}">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='14' fill='%23141b21'/%3E%3Cpath d='M16 44V20h8l8 12 8-12h8v24h-7V31l-9 13-9-13v13z' fill='%23c7f464'/%3E%3C/svg%3E">
</head>
<body>
<header class="site-header">
    <div class="shell header-inner">
        <a class="brand" href="#/">
            <span class="brand-mark" aria-hidden="true">WS</span>
            <span class="brand-text">Event Booking Platform</span>
        </a>

        <nav class="header-nav" id="header-nav" aria-label="Account"></nav>
    </div>
</header>

<main id="app" class="shell" aria-live="polite">
    <div class="loading-state">
        <span class="spinner" aria-hidden="true"></span>
        <p>Loading the programme…</p>
    </div>
</main>

<footer class="site-footer">
    <div class="shell footer-inner">
        <p>WorldSkills Conference Programme</p>
        <p class="footer-muted">Browse events, build your agenda, join the conversation.</p>
    </div>
</footer>

<div id="toast" class="toast" role="status" aria-live="polite"></div>

<script src="{{ asset('assets/attendee/app.js') }}?v={{ filemtime(public_path('assets/attendee/app.js')) }}" defer></script>
</body>
</html>
