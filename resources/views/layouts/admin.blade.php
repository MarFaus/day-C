<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>@yield('title', 'Event Backend') | WorldSkills Events</title>
    <link href="{{ asset('assets/admin/css/bootstrap.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/admin/css/custom.css') }}" rel="stylesheet">
    <style>
        .invalid-feedback { display: block; }
        .session-actions { white-space: nowrap; }
        .stat-line { color: #6c757d; }
        .rating-stars { color: #f0a500; letter-spacing: .08em; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark fixed-top bg-dark flex-md-nowrap p-0 shadow">
    <a class="navbar-brand col-sm-3 col-md-2 mr-0" href="{{ route('admin.events.index') }}">Event Platform</a>
    <span class="navbar-organizer w-100">{{ $organizer->name ?? '' }}</span>
    <ul class="navbar-nav px-3">
        <li class="nav-item text-nowrap">
            <a class="nav-link" id="logout" href="{{ route('login') }}"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Sign out</a>
            <form id="logout-form" method="POST" action="{{ route('logout') }}" class="d-none">
                @csrf
            </form>
        </li>
    </ul>
</nav>

<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 d-none d-md-block bg-light sidebar">
            <div class="sidebar-sticky">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.events.index') ? 'active' : '' }}"
                           href="{{ route('admin.events.index') }}">Manage Events</a>
                    </li>
                </ul>

                @isset($event)
                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                        <span>{{ $event->name }}</span>
                    </h6>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.events.show') ? 'active' : '' }}"
                               href="{{ route('admin.events.show', $event) }}">Overview</a>
                        </li>
                    </ul>

                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                        <span>Reports</span>
                    </h6>
                    <ul class="nav flex-column mb-2">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}"
                               href="{{ route('admin.reports.room-capacity', $event) }}">Room capacity</a>
                        </li>
                    </ul>
                @endisset
            </div>
        </nav>

        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4">
            @if (session('success'))
                <div class="alert alert-success mt-3 mb-0" role="alert">{{ session('success') }}</div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
