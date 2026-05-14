<?php
session_start();

$sayfa = isset($_GET['sayfa']) && !empty($_GET['sayfa']) ? $_GET['sayfa'] : 'ana-sayfa';
$sayfa = preg_replace('/[^a-z0-9\-]/', '', $sayfa);

// Sistemin arama yapacağı görünüm klasörleri
$klasorler = [
    'acik_sistem',
    'musteri_paneli',
    'dukkan_paneli',
    'yonetici_paneli'
];

$bulundu = false;

// Klasörleri sırayla gez ve çağrılan dosyayı bul
foreach ($klasorler as $klasor) {
    $dosya_yolu = "uygulama/gorunumler/" . $klasor . "/" . $sayfa . ".php";
    if (file_exists($dosya_yolu)) {
        require_once $dosya_yolu;
        $bulundu = true;
        break;
    }
}

if (!$bulundu) {
    echo "<div style='text-align:center; margin-top:100px; font-family:sans-serif;'>
            <h1>404 - Sayfa Bulunamadı!</h1>
            <p>Makasımız kaydı, aradığınız sayfayı bulamadık.</p>
            <a href='/kuafor-randevu/'>Ana Sayfaya Dön</a>
          </div>";
}

?>
