<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Poll System')</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    @stack('styles')
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand navbar-light bg-white shadow-sm sticky-top border-bottom">
        <div class="container">
            <a class="navbar-brand font-weight-bold" href="{{ route('home') }}">
                <i class="fas fa-clipboard-check text-primary mr-1"></i> PollSystem
            </a>

            <div class="ml-auto d-flex align-items-center">
                @auth
                    @if(auth()->user()->is_admin)
                        <a href="{{ route('admin.polls.index') }}" class="nav-link text-secondary">Dashboard</a>
                    @endif
                    <div class="dropdown ml-3">
                        <button class="btn btn-link text-dark dropdown-toggle text-decoration-none p-0 d-flex align-items-center" data-toggle="dropdown">
                            <span class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mr-2" style="width:34px;height:34px;font-size:14px;">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                            <span class="d-none d-sm-inline">{{ auth()->user()->name }}</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item">Sign Out</button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="nav-link text-secondary">Login</a>
                    <a href="{{ route('register') }}" class="btn btn-primary btn-sm ml-2">Register</a>
                @endauth
            </div>
        </div>
    </nav>

    @if(session('success'))
        <div class="position-fixed" style="top:70px;right:20px;z-index:1080;">
            <div id="flash-message" class="alert alert-success shadow mb-0">
                {{ session('success') }}
            </div>
        </div>
    @endif
    @if(session('error'))
        <div class="position-fixed" style="top:70px;right:20px;z-index:1080;">
            <div id="flash-message" class="alert alert-danger shadow mb-0">
                {{ session('error') }}
            </div>
        </div>
    @endif

    <main class="container py-4">
        @yield('content')
    </main>

    <div id="toast-container" class="position-fixed" style="top:70px;right:20px;z-index:1080;"></div>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.min.js"></script>   
    @vite(['resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>

        if (typeof $ !== 'undefined') {

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json'
                }
            });
            
        }

        setTimeout(function() {

            var flash = document.getElementById('flash-message');
            if (flash) $(flash).fadeOut(300, function() { $(this).remove(); });

        }, 3000);

        
        window.showToast = function(message, type) {

            type = type || 'success';
            var alertClass = { success: 'alert-success', error: 'alert-danger', info: 'alert-info' };
            var $toast = $('<div class="alert ' + (alertClass[type] || 'alert-success') + ' shadow mb-2">' + message + '</div>');
            $('#toast-container').append($toast);
            setTimeout(function() { $toast.fadeOut(300, function() { $(this).remove(); }); }, 3000);

        };
    </script>

    @stack('scripts')
</body>
</html>
