<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Eğer doğrudan bu URL'ye girmeye çalışırsa veya session'da veri yoksa anasayfaya at
if (!isset($_SESSION['son_randevu'])) {
    header("Location: /kuafor-randevu/");
    exit;
}

$randevu = $_SESSION['son_randevu'];
// Veriyi ekranda gösterdikten sonra bir daha yenileyince aynı sayfa çıkmasın diye siliyoruz
unset($_SESSION['son_randevu']);

include 'uygulama/gorunumler/ortak/ust_bilgi.php'; 
?>

<div class="container py-5 mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 text-center">
            
            <div class="mb-4">
                <div class="d-inline-flex align-items-center justify-content-center bg-success text-white rounded-circle shadow-sm" style="width: 100px; height: 100px;">
                    <i class="bi bi-check2-circle" style="font-size: 4rem;"></i>
                </div>
            </div>

            <h2 class="fw-bold mb-3 text-dark">İşleminiz Başarıyla Alındı!</h2>
            <p class="text-muted lead mb-4">Ödeme provizyonunuz gerçekleşti ve randevu talebiniz işletmeye iletildi. Randevunuz şu an <strong class="text-warning">Onay Bekliyor</strong> aşamasındadır.</p>

            <div class="card border-0 shadow-sm rounded-4 text-start mb-5 bg-white">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-muted mb-3 pb-2 border-bottom">RANDEVU ÖZETİ</h6>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted"><i class="bi bi-shop me-2"></i>İşletme:</span>
                        <span class="fw-bold text-dark"><?= htmlspecialchars($randevu['dukkan_adi']) ?></span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted"><i class="bi bi-calendar-event me-2"></i>Tarih / Saat:</span>
                        <span class="fw-bold text-dark"><?= $randevu['tarih'] ?> - <?= $randevu['saat'] ?></span>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-3 pt-3 border-top">
                        <span class="text-muted fw-bold">Toplam Tutar:</span>
                        <span class="fw-bold text-success fs-5"><?= number_format($randevu['tutar'], 2, ',', '.') ?> ₺</span>
                    </div>
                </div>
            </div>

            <div class="alert alert-info rounded-3 text-start small mb-4">
                <i class="bi bi-envelope-check-fill me-2"></i> Randevu detaylarınız ve onay durumu e-posta adresinize gönderilmiştir. Ayrıca tüm süreçleri panelinizden takip edebilirsiniz.
            </div>

            <div class="d-grid gap-3 d-md-flex justify-content-md-center">
                <a href="/kuafor-randevu/" class="btn btn-outline-secondary fw-bold px-4 py-2 rounded-pill">Ana Sayfaya Dön</a>
                <a href="/kuafor-randevu/musteri-randevularim" class="btn btn-primary fw-bold px-4 py-2 rounded-pill shadow-sm">Randevularımı Görüntüle</a>
            </div>

        </div>
    </div>
</div>

<?php include 'uygulama/gorunumler/ortak/alt_bilgi.php'; ?>