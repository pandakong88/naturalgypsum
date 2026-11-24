<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) ? $title . ' — Natural Gypsum & PVC' : 'Login — Natural Gypsum & PVC' }}</title>

    <!-- Vite Assets -->
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])

    <!-- Livewire Styles -->
    @livewireStyles
</head>
<body class="bg-light min-vh-100 d-flex align-items-center justify-content-center py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6 col-xl-5">
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-circle p-3 mb-3 shadow-sm" style="width: 64px; height: 64px;">
                        <i class="bi bi-box-seam-fill fs-2"></i>
                    </div>
                    <h4 class="fw-bold mb-1">Natural Gypsum & PVC</h4>
                    <p class="text-muted small">Sistem Manajemen Stok & Operasional</p>
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
