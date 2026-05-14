<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Bakımda | Salon Randevu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #161819; color: #fff; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .luks-altin { color: #c89f65; }
    </style>
</head>
<body>

    <div class="text-center px-4">
        <i class="bi bi-tools luks-altin mb-4 d-block" style="font-size: 6rem;"></i>
        <h1 class="fw-bold mb-3">Sistem Bakımda</h1>
        <p class="text-muted fs-5 mb-5 max-w-500 mx-auto" style="max-width: 500px;">
            Size daha iyi bir deneyim sunabilmek için şu anda sistemlerimizi güncelliyoruz. Kısa bir süre sonra tekrar yayında olacağız. Anlayışınız için teşekkür ederiz.
        </p>
        
        <a href="/kuafor-randevu/giris-kayit" class="text-decoration-none text-secondary small">
            <i class="bi bi-shield-lock me-1"></i> Yönetici Girişi
        </a>
    </div>

</body>
</html>