<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) ? $title . ' — Natural Gypsum & PVC' : 'Login — Natural Gypsum & PVC' }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">

    <!-- Vite Assets -->
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])

    <!-- Livewire Styles -->
    @livewireStyles
</head>
<body class="min-vh-100 d-flex align-items-center justify-content-center py-5" style="background-color: #FAF8F5;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6 col-xl-5">
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center gap-3 mb-2 px-3 py-2">
                        <img src="{{ asset('images/logo-natural-modern-kop.svg') }}" alt="Logo Natural Gypsum & PVC" style="height: 62px; width: auto;">
                        <div class="text-start lh-1">
                            <div class="fw-bolder" style="color: #18181B; font-size: 2rem; letter-spacing: 0.12em;">NATURAL</div>
                            <div style="height: 2px; background-color: #18181B; margin: 5px 0 6px 0;"></div>
                            <div class="fw-bold" style="color: #B87D4B; font-size: 0.85rem; letter-spacing: 0.26em;">GYPSUM &amp; PVC</div>
                        </div>
                    </div>
                    <h4 class="visually-hidden">Natural Gypsum & PVC</h4>
                    <p class="text-muted small mb-0 fw-medium" style="letter-spacing: 0.03em;">Aplikator &amp; Penyedia Bahan Gypsum dan PVC &bull; Sistem Operasional</p>
                </div>

                @include('components.flash-messages')

                {{ $slot ?? '' }}
                @yield('content')

                <div class="text-center mt-4">
                    <small class="text-muted">&copy; {{ date('Y') }} Natural Gypsum & PVC. All rights reserved.</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Livewire Scripts -->
    @livewireScripts
</body>
</html>
