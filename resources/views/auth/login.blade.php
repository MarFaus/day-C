<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Sign in | WorldSkills Event Platform</title>
    <link href="{{ asset('assets/admin/css/bootstrap.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/admin/css/custom.css') }}" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <main class="col-md-6 mx-sm-auto px-4">
            <div class="pt-3 pb-2 mb-3 border-bottom text-center">
                <h1 class="h2">WorldSkills Event Platform</h1>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
            @endif

            <form class="form-signin" method="POST" action="{{ route('login.attempt') }}">
                @csrf
                <h1 class="h3 mb-3 font-weight-normal">Please sign in</h1>

                <label for="inputEmail" class="sr-only">Email</label>
                <input type="email" id="inputEmail" name="email" class="form-control"
                       placeholder="Email" value="{{ old('email') }}" autofocus required>

                <label for="inputPassword" class="sr-only">Password</label>
                <input type="password" id="inputPassword" name="password" class="form-control"
                       placeholder="Password" required>

                <button class="btn btn-lg btn-primary btn-block" id="login" type="submit">Sign in</button>
            </form>

            <p class="text-center text-muted mt-4 mb-0">
                Demo: demo1@worldskills.org / demopass1
            </p>
        </main>
    </div>
</div>
</body>
</html>
