<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 — Kesalahan Server</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body class="bg-light min-vh-100 d-flex align-items-center justify-content-center">
    <div class="container text-center py-5">
        <div class="mb-4">
            <i class="bi bi-exclamation-octagon text-danger display-1"></i>
        </div>
        <h1 class="display-5 fw-bold mb-3">500 — Terjadi Kesalahan Server</h1>
        <p class="lead text-muted mb-4">
            Terjadi kendala saat memproses permintaan Anda. Tim teknis kami telah diberitahu.
        </p>
        <a href="{{ url('/') }}" class="btn btn-primary px-4 py-2">
            <i class="bi bi-house-door me-2"></i>Kembali ke Beranda
        </a>
    </div>
</body>
</html>
