<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// YOLLAR DÜZELTİLDİ (index.php'den çağrıldığı için)
require_once 'uygulama/ayarlar/veritabani.php';
$vt = new Veritabani();
$db = $vt->baglantiGetir();

$kod = $_GET['kod'] ?? ($_POST['kod'] ?? '');
$kod_gecerli = false;
$hata_mesaji = '';

// ==========================================
// 1. ADIM: FORM GÖNDERİLDİYSE ŞİFREYİ GÜNCELLE
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['sifre_guncelle'])) {
    $yeni_sifre = $_POST['yeni_sifre'];
    $sifre_tekrar = $_POST['sifre_tekrar'];

    if (empty($yeni_sifre) || empty($sifre_tekrar)) {
        $hata_mesaji = "Lütfen tüm alanları doldurun.";
    } elseif ($yeni_sifre !== $sifre_tekrar) {
        $hata_mesaji = "Şifreler birbiriyle eşleşmiyor.";
    } elseif (strlen($yeni_sifre) < 6) {
        $hata_mesaji = "Şifreniz en az 6 karakter olmalıdır.";
    } else {
        $sorgu = $db->prepare("SELECT id, sifre_sifirlama_tarihi FROM kullanicilar WHERE sifre_sifirlama_kodu = ?");
        $sorgu->execute([$kod]);
        $kullanici = $sorgu->fetch(PDO::FETCH_ASSOC);

        if ($kullanici && strtotime($kullanici['sifre_sifirlama_tarihi']) > time()) {
            
            $yeni_hash = password_hash($yeni_sifre, PASSWORD_DEFAULT);
            
            $guncelle = $db->prepare("UPDATE kullanicilar SET sifre_hash = ?, sifre_sifirlama_kodu = NULL, sifre_sifirlama_tarihi = NULL WHERE id = ?");
            $guncelle->execute([$yeni_hash, $kullanici['id']]);

            $_SESSION['mesaj_turu'] = "basari";
            $_SESSION['mesaj'] = "Şifreniz başarıyla güncellendi! Yeni şifrenizle sisteme giriş yapabilirsiniz.";
            $_SESSION['aktif_sekme'] = "giris";
            
            header("Location: /kuafor-randevu/giris-kayit");
            exit;
        } else {
            $hata_mesaji = "Sıfırlama bağlantınız geçersiz veya süresi dolmuş. Lütfen yeni bir bağlantı talep edin.";
        }
    }
}

// ==========================================
// 2. ADIM: E-POSTADAN TIKLAYIP GELDİYSE KODU KONTROL ET
// ==========================================
if (empty($hata_mesaji) && !empty($kod)) {
    $sorgu = $db->prepare("SELECT id, sifre_sifirlama_tarihi FROM kullanicilar WHERE sifre_sifirlama_kodu = ?");
    $sorgu->execute([$kod]);
    $kullanici = $sorgu->fetch(PDO::FETCH_ASSOC);

    if ($kullanici && strtotime($kullanici['sifre_sifirlama_tarihi']) > time()) {
        $kod_gecerli = true; 
    }
}

// YOL DÜZELTİLDİ
include 'uygulama/gorunumler/ortak/ust_bilgi.php'; 
?>

<div class="container py-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            
            <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-primary text-white border-0 pt-4 pb-3 px-4 text-center">
                    <div class="bg-white text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow" style="width: 70px; height: 70px; font-size: 2rem;">
                        <i class="bi bi-key-fill"></i>
                    </div>
                    <h4 class="fw-bold mb-0 text-white">Yeni Şifre Belirleme</h4>
                </div>
                
                <div class="card-body p-4 pt-4">
                    
                    <?php if(!empty($hata_mesaji)): ?>
                        <div class="alert alert-danger rounded-3 small fw-semibold">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $hata_mesaji ?>
                        </div>
                    <?php endif; ?>

                    <?php if($kod_gecerli): ?>
                        
                        <p class="text-muted small text-center mb-4">Hesabınızın güvenliği için güçlü bir şifre belirlediğinizden emin olun.</p>

                        <form action="" method="POST">
                            <input type="hidden" name="sifre_guncelle" value="1">
                            <input type="hidden" name="kod" value="<?= htmlspecialchars($kod) ?>">

                            <div class="form-floating mb-3 position-relative">
                                <input type="password" class="form-control rounded-3 pe-5" id="yeniSifre" name="yeni_sifre" placeholder="Yeni Şifre" required minlength="6">
                                <label for="yeniSifre">Yeni Şifreniz</label>
                                <i class="bi bi-eye-slash password-toggle" onclick="sifreGoster('yeniSifre', this)" style="position: absolute; right: 15px; top: 18px; cursor: pointer; color: #6c757d; font-size: 1.2rem; z-index: 10;"></i>
                            </div>

                            <div class="form-floating mb-4 position-relative">
                                <input type="password" class="form-control rounded-3 pe-5" id="sifreTekrar" name="sifre_tekrar" placeholder="Şifre Tekrar" required minlength="6">
                                <label for="sifreTekrar">Yeni Şifreniz (Tekrar)</label>
                                <i class="bi bi-eye-slash password-toggle" onclick="sifreGoster('sifreTekrar', this)" style="position: absolute; right: 15px; top: 18px; cursor: pointer; color: #6c757d; font-size: 1.2rem; z-index: 10;"></i>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-3 rounded-3 fw-bold fs-5 shadow-sm">Şifremi Güncelle</button>
                        </form>

                    <?php else: ?>
                        
                        <div class="text-center py-3">
                            <i class="bi bi-x-circle text-danger mb-3 d-block" style="font-size: 4rem;"></i>
                            <h5 class="fw-bold text-dark">Bağlantı Geçersiz</h5>
                            <p class="text-muted small mb-4">Kullanmaya çalıştığınız sıfırlama bağlantısı geçersiz veya güvenlik sebebiyle 1 saatlik süresi dolmuş.</p>
                            
                            <a href="/kuafor-randevu/giris-kayit" class="btn btn-primary w-100 py-2 rounded-pill fw-bold">Yeni Bağlantı İste</a>
                        </div>
                        
                    <?php endif; ?>

                </div>
            </div>
            
        </div>
    </div>
</div>

<script>
// Şifre Göster/Gizle Fonksiyonu (Giris-Kayit'taki ile aynı)
function sifreGoster(inputId, iconElement) {
    var input = document.getElementById(inputId);
    if (input.type === "password") {
        input.type = "text";
        iconElement.classList.remove("bi-eye-slash");
        iconElement.classList.add("bi-eye");
    } else {
        input.type = "password";
        iconElement.classList.remove("bi-eye");
        iconElement.classList.add("bi-eye-slash");
    }
}
</script>

<?php include 'uygulama/gorunumler/ortak/alt_bilgi.php'; ?>