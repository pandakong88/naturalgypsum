<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) ? $title . ' — Natural Gypsum & PVC' : 'Natural Gypsum & PVC' }}</title>

    <!-- Vite Assets -->
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])

    <!-- Livewire Styles -->
    @livewireStyles
</head>
<body x-data="{ sidebarOpen: false }">
    <div id="app-wrapper">
        <!-- Sidebar Overlay (Mobile) -->
        <div class="sidebar-overlay d-lg-none" x-show="sidebarOpen" @click="sidebarOpen = false" x-cloak></div>

        <!-- Sidebar Navigation -->
        @include('partials.sidebar')

        <!-- Main Content Area -->
        <div id="main-content">
            <!-- Topbar -->
            @include('partials.topbar')

            <!-- Body Container -->
            <main class="content-body">
                @include('components.flash-messages')

                {{ $slot ?? '' }}
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Livewire Scripts -->
    @livewireScripts
</body>
</html>
