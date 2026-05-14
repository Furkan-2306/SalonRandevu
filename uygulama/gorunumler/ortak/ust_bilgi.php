<?php 
// Oturum başlatılmamışsa en üstte başlatıyoruz
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// VERİTABANI BAĞLANTISI (Bakım modu ve dükkan kontrolü için gerekli)
require_once __DIR__ . '/../../ayarlar/veritabani.php';
$vt = new Veritabani();
$db = $vt->baglantiGetir();

// ==========================================
// SİSTEM BAKIM MODU KONTROLÜ (GÜVENLİK BEKÇİSİ)
// ==========================================
$bakimSorgu = $db->query("SELECT ayar_degeri FROM genel_ayarlar WHERE ayar_adi = 'bakim_modu'");
$bakimModu = $bakimSorgu->fetchColumn();

$kullanici_rolu = $_SESSION['rol'] ?? $_SESSION['kullanici_rol'] ?? '';

// Linklerde .php uzantısı olmasa bile kusursuz çalışması için URI (bağlantı) üzerinden arama yapıyoruz.
$mevcut_url = $_SERVER['REQUEST_URI'];
$istisna_kelimeler = ['bakim', 'giris-kayit', 'yetkilendirme_kontrol'];
$bakimdan_muaf_mi = false;

foreach ($istisna_kelimeler as $istisna) {
    if (strpos($mevcut_url, $istisna) !== false) {
        $bakimdan_muaf_mi = true;
        break;
    }
}

// Eğer bakım modu 1 ise VE kullanıcı admin değilse VE sayfa muaf değilse -> Bakıma yolla!
if ($bakimModu == '1' && $kullanici_rolu !== 'admin' && !$bakimdan_muaf_mi) {
    header("Location: /kuafor-randevu/bakim.php");
    exit;
}

// ==========================================
// İŞLETME ONAY DURUMU KONTROLLERİ
// ==========================================
// "Onay Bekliyor" butonuna tıklandığında çalışacak yönlendirme kodu
if (isset($_GET['islem']) && $_GET['islem'] == 'onay_bekliyor') {
    $_SESSION['mesaj_turu'] = "hata"; 
    $_SESSION['mesaj'] = "İşletme başvurunuz şu anda yönetici onayı bekliyor. İncelenip onaylandıktan sonra panelinize erişebilirsiniz.";
    // Mevcut sayfada kalmak için HTTP_REFERER kullanıyoruz (yoksa anasayfaya atar)
    $geri_don = $_SERVER['HTTP_REFERER'] ?? '/kuafor-randevu/';
    header("Location: " . $geri_don);
    exit;
}

// Kullanıcının onay bekleyen bir dükkanı var mı diye veritabanından kontrol ediyoruz
$onay_bekleyen_dukkan = false;
if (isset($_SESSION['giris_yapildi']) && $_SESSION['kullanici_rol'] == 'musteri') {
    $sorgu = $db->prepare("SELECT aktif_mi FROM dukkanlar WHERE sahip_id = :id LIMIT 1");
    $sorgu->execute(['id' => $_SESSION['kullanici_id']]);
    $dukkan = $sorgu->fetch(PDO::FETCH_ASSOC);
    
    if ($dukkan && $dukkan['aktif_mi'] == 0) {
        $onay_bekleyen_dukkan = true;
    }
}

// ==========================================
// DİNAMİK SİTE BAŞLIĞI
// ==========================================
$baslikSorgu = $db->query("SELECT ayar_degeri FROM genel_ayarlar WHERE ayar_adi = 'site_baslik'");
$site_basligi = $baslikSorgu->fetchColumn() ?: 'Premium Kuaför & Berber Randevu Sistemi';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($site_basligi) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/kuafor-randevu/genel/css/ozel-stil.css">
</head>
<body class="bg-light">

    <div class="py-2 d-none d-lg-block shadow-sm" style="background-color: var(--luks-koyu); color: #e9ecef; font-size: 0.85rem;">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex gap-4">
                <span><i class="bi bi-telephone-fill me-1" style="color: var(--luks-altin);"></i> +90 (850) 123 45 67</span>
                <span><i class="bi bi-envelope-fill me-1" style="color: var(--luks-altin);"></i> destek@kuaforrandevu.com</span>
            </div>
            <div class="d-flex gap-3 align-items-center">
                <span class="text-muted" style="font-size: 0.75rem; letter-spacing: 1px;">BİZİ TAKİP EDİN</span>
                <a href="#" class="text-light text-decoration-none"><i class="bi bi-instagram"></i></a>
                <a href="#" class="text-light text-decoration-none"><i class="bi bi-facebook"></i></a>
                <a href="#" class="text-light text-decoration-none"><i class="bi bi-twitter-x"></i></a>
            </div>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top py-3">
      <div class="container">
        
        <a class="navbar-brand d-flex align-items-center gap-2" href="/kuafor-randevu/">
            <div class="rounded d-flex align-items-center justify-content-center shadow-sm" style="width: 45px; height: 45px; background-color: var(--luks-koyu);">
                <i class="bi bi-scissors fs-4" style="color: var(--luks-altin);"></i>
            </div>
            <div>
                <span class="fw-bold fs-5 d-block" style="color: var(--luks-koyu); line-height: 1;">Salon<span style="color: var(--luks-altin);">Randevu</span></span>
                <span class="text-muted fw-semibold" style="font-size: 0.65rem; letter-spacing: 2px;">PREMIUM </span>
            </div>
        </a>
        
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#anaMenu">
          <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="anaMenu">
          <ul class="navbar-nav ms-auto align-items-center gap-1 mt-3 mt-lg-0">
            
           <?php if (isset($_SESSION['giris_yapildi']) && $_SESSION['giris_yapildi'] === true): ?>
                
                <li class="nav-item dropdown mt-2 mt-lg-0 w-100 w-lg-auto ms-lg-2">
                    <a class="btn btn-primary fw-bold w-100 px-4 shadow-sm rounded-pill text-nowrap dropdown-toggle" href="#" id="hesabimDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle me-1"></i> Hesabım
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 rounded-3" aria-labelledby="hesabimDropdown">
                        
                        <?php if($kullanici_rolu == 'admin'): ?>
                            <li><a class="dropdown-item fw-bold py-2 text-danger" href="/kuafor-randevu/yonetici-ozet"><i class="bi bi-shield-lock me-2"></i> Yönetici Paneli</a></li>
                            <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>

                        <li><a class="dropdown-item fw-bold py-2" href="/kuafor-randevu/musteri-randevularim"><i class="bi bi-person me-2 text-primary"></i> Müşteri Panelim</a></li>
                        
                        <?php if($kullanici_rolu == 'dukkan_sahibi'): ?>
                            <li><a class="dropdown-item fw-bold py-2" href="/kuafor-randevu/dukkan-ozet-paneli"><i class="bi bi-shop me-2 text-primary"></i> İşletme Panelim</a></li>
                        <?php else: ?>
                            <?php if($onay_bekleyen_dukkan): ?>
                                <li><a class="dropdown-item py-2 text-secondary" href="?islem=onay_bekliyor"><i class="bi bi-shop me-2"></i> İşletme Panelim <span class="badge bg-warning text-dark ms-1" style="font-size: 0.65rem;">Onay Bekliyor</span></a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item py-2" href="/kuafor-randevu/isletme-kayit-adimlari"><i class="bi bi-plus-circle me-2"></i> İşletme Ekle</a></li>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger py-2 fw-semibold" href="/kuafor-randevu/uygulama/kontrolculer/cikis.php"><i class="bi bi-box-arrow-right me-2"></i> Çıkış Yap</a></li>
                    </ul>
                </li>

            <?php else: ?>

                <li class="nav-item mt-2 mt-lg-0 w-100 w-lg-auto ms-lg-2">
                    <a class="btn btn-light border-secondary-subtle fw-bold w-100 px-3 rounded-pill text-nowrap" style="color: var(--luks-koyu);" href="/kuafor-randevu/giris-kayit">
                        <i class="bi bi-person-circle me-1"></i> Giriş / Kayıt
                    </a>
                </li>
                <li class="nav-item mt-2 mt-lg-0 w-100 w-lg-auto">
                    <a class="btn btn-primary fw-bold w-100 px-3 shadow-sm rounded-pill text-nowrap" href="/kuafor-randevu/isletme-kayit-adimlari">
                        <i class="bi bi-shop me-1"></i> İşletme Ekle
                    </a>
                </li>

            <?php endif; ?>

          </ul>
        </div>
        
      </div>
    </nav>