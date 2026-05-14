<?php 
// GÜVENLİK VE OTURUM KONTROLÜ
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$kullanici_rolu = $_SESSION['rol'] ?? $_SESSION['kullanici_rol'] ?? '';
if (!isset($_SESSION['giris_yapildi']) || $kullanici_rolu !== 'admin') {
    header("Location: /kuafor-randevu/giris-kayit");
    exit;
}

// VERİTABANI BAĞLANTISI
require_once __DIR__ . '/../../ayarlar/veritabani.php';
$vt = new Veritabani();
$db = $vt->baglantiGetir();

// ==========================================
// AYARLARI KAYDETME İŞLEMİ (POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ayarlari_kaydet'])) {
    
    // Checkbox (Bakım Modu) seçilmezse POST'ta gelmez, bu yüzden kontrol ediyoruz
    $bakim_modu = isset($_POST['bakim_modu']) ? '1' : '0';
    $site_baslik = trim($_POST['site_baslik']);
    $komisyon_orani = (int)$_POST['komisyon_orani'];
    $iptal_siniri = (int)$_POST['iptal_siniri'];
    $eposta = trim($_POST['eposta']);
    $tel = trim($_POST['tel']);

    try {
        $db->beginTransaction();
        
        $guncelle = $db->prepare("UPDATE genel_ayarlar SET ayar_degeri = ? WHERE ayar_adi = ?");
        $guncelle->execute([$bakim_modu, 'bakim_modu']);
        $guncelle->execute([$site_baslik, 'site_baslik']);
        $guncelle->execute([$komisyon_orani, 'komisyon_orani']);
        $guncelle->execute([$iptal_siniri, 'iptal_siniri_saat']);
        $guncelle->execute([$eposta, 'iletisim_eposta']);
        $guncelle->execute([$tel, 'iletisim_tel']);

        $db->commit();
        $_SESSION['mesaj_turu'] = "basari";
        $_SESSION['mesaj'] = "Genel sistem ayarları başarıyla güncellendi.";

    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['mesaj_turu'] = "hata";
        $_SESSION['mesaj'] = "Ayarlar kaydedilirken hata oluştu: " . $e->getMessage();
    }
    
    header("Location: /kuafor-randevu/yonetici-genel-ayarlar");
    exit;
}

// ==========================================
// MEVCUT AYARLARI VERİTABANINDAN ÇEK
// ==========================================
$ayarlarSorgu = $db->query("SELECT ayar_adi, ayar_degeri FROM genel_ayarlar");
$ayarlarListesi = $ayarlarSorgu->fetchAll(PDO::FETCH_KEY_PAIR);

// Eğer veritabanı boşsa varsayılan değerler atıyoruz
$bakim_modu = $ayarlarListesi['bakim_modu'] ?? '0';
$site_baslik = $ayarlarListesi['site_baslik'] ?? 'Salon Randevu Sistemi';
$komisyon_orani = $ayarlarListesi['komisyon_orani'] ?? '10';
$iptal_siniri = $ayarlarListesi['iptal_siniri_saat'] ?? '2';
$eposta = $ayarlarListesi['iletisim_eposta'] ?? '';
$tel = $ayarlarListesi['iletisim_tel'] ?? '';

// Bildirim için onay bekleyen sayısını çekelim
$bekleyenSorgu = $db->query("SELECT COUNT(id) as toplam FROM dukkanlar WHERE aktif_mi = 0");
$toplamBekleyen = $bekleyenSorgu->fetch(PDO::FETCH_ASSOC)['toplam'];

include __DIR__ . '/../ortak/ust_bilgi.php'; 
?>

<div class="container py-5 mb-5">

    <?php if(isset($_SESSION['mesaj'])): ?>
        <div class="alert alert-<?= ($_SESSION['mesaj_turu'] == 'basari') ? 'success' : 'danger' ?> alert-dismissible fade show rounded-4 shadow-sm border-0 mb-4" role="alert">
            <i class="bi <?= ($_SESSION['mesaj_turu'] == 'basari') ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
            <?= $_SESSION['mesaj'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['mesaj']); unset($_SESSION['mesaj_turu']); ?>
    <?php endif; ?>

    <div class="row g-4">
        
        <div class="col-lg-3">
            <div class="card bg-dark text-white border-0 shadow-lg rounded-4 text-center p-4 mb-4">
                <div class="mb-3">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center fw-bold shadow" style="width: 80px; height: 80px; font-size: 2rem;">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>
                </div>
                <h5 class="fw-bold mb-1" style="color: var(--luks-altin);">Süper Admin</h5>
                <p class="text-secondary small mb-2">Sistem Yöneticisi</p>
                <span class="badge bg-success rounded-pill mb-2"><i class="bi bi-circle-fill text-white small me-1"></i> Sistem Aktif</span>
            </div>

            <div class="card bg-dark border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="list-group list-group-flush border-secondary">
                    <a href="/kuafor-randevu/yonetici-ozet" class="list-group-item list-group-item-action py-3 fw-semibold bg-dark text-white border-bottom border-secondary">
                        <i class="bi bi-speedometer2 me-2"></i> Sistem Özeti
                    </a>
                    <a href="/kuafor-randevu/yonetici-onay-bekleyenler" class="list-group-item list-group-item-action py-3 fw-semibold bg-dark text-white border-bottom border-secondary">
                        <i class="bi bi-shop-window me-2"></i> Onay Bekleyenler 
                        <?php if($toplamBekleyen > 0): ?>
                            <span class="badge bg-danger rounded-pill float-end"><?= $toplamBekleyen ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="/kuafor-randevu/yonetici-kullanici-yonetimi" class="list-group-item list-group-item-action py-3 fw-semibold bg-dark text-white border-bottom border-secondary">
                        <i class="bi bi-people-fill me-2"></i> Kullanıcı Yönetimi
                    </a>
                    <a href="/kuafor-randevu/yonetici-genel-ayarlar" class="list-group-item list-group-item-action py-3 fw-semibold active border-0" style="background-color: var(--luks-altin); color: var(--luks-koyu);">
                        <i class="bi bi-gear-fill me-2"></i> Genel Ayarlar
                    </a>
                    <a href="/kuafor-randevu/uygulama/kontrolculer/cikis.php" class="list-group-item list-group-item-action py-3 fw-semibold bg-dark text-danger border-0">
                        <i class="bi bi-box-arrow-right me-2"></i> Güvenli Çıkış
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            
            <div class="mb-4">
                <h3 class="fw-bold m-0 text-dark">Genel Sistem Ayarları</h3>
                <p class="text-muted small mt-1 mb-0">Sistemin temel işleyiş kurallarını, finansal oranlarını ve iletişim bilgilerini yönetin.</p>
            </div>

            <form action="" method="POST">
                <input type="hidden" name="ayarlari_kaydet" value="1">
                
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-0 pt-4 pb-2 px-4">
                        <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-display me-2 text-primary"></i>Sistem Durumu</h5>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <div class="form-check form-switch fs-5 mb-3 p-3 bg-light rounded-3 border">
                            <input class="form-check-input ms-0 me-3" type="checkbox" role="switch" id="bakimModu" name="bakim_modu" <?= $bakim_modu == '1' ? 'checked' : '' ?>>
                            <label class="form-check-label fw-bold" for="bakimModu">Bakım Modunu Aktifleştir</label>
                            <div class="form-text fs-6 mt-1 text-muted">Açıldığında müşteriler ve işletmeler sisteme erişemez. Sadece "Sistem Bakımda" uyarısı görürler.</div>
                        </div>
                        <div class="form-floating mt-3">
                            <input type="text" class="form-control rounded-3 border-secondary-subtle" id="siteBaslik" name="site_baslik" value="<?= htmlspecialchars($site_baslik) ?>" required>
                            <label for="siteBaslik">Site Başlığı (Title)</label>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-0 pt-4 pb-2 px-4">
                        <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-wallet2 me-2 text-success"></i>Finans & Operasyon Kuralları</h5>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Sistem Komisyon Oranı (%)</label>
                                <div class="input-group mb-2">
                                    <input type="number" class="form-control border-secondary-subtle" name="komisyon_orani" value="<?= $komisyon_orani ?>" min="0" max="100" required>
                                    <span class="input-group-text bg-light">%</span>
                                </div>
                                <div class="form-text small">İşletmelerin tamamlanan randevularından kesilecek olan servis bedeli oranıdır.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Randevu İptal Sınırı (Saat)</label>
                                <div class="input-group mb-2">
                                    <input type="number" class="form-control border-secondary-subtle" name="iptal_siniri" value="<?= $iptal_siniri ?>" min="1" required>
                                    <span class="input-group-text bg-light">Saat Kala</span>
                                </div>
                                <div class="form-text small">Müşterilerin randevuyu ücretsiz iptal edebilmesi için ne kadar zaman önce işlemi yapması gerektiğini belirler.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-0 pt-4 pb-2 px-4">
                        <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-envelope-at me-2 text-warning"></i>Kurumsal İletişim Bilgileri</h5>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="email" class="form-control rounded-3 border-secondary-subtle" id="iletisimEposta" name="eposta" value="<?= htmlspecialchars($eposta) ?>" required>
                                    <label for="iletisimEposta">Destek E-posta Adresi</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control rounded-3 border-secondary-subtle" id="iletisimTel" name="tel" value="<?= htmlspecialchars($tel) ?>" required>
                                    <label for="iletisimTel">İletişim / Müşteri Hizmetleri Tel</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-3 mt-4">
                    <button type="button" class="btn btn-light px-4 py-2 fw-bold rounded-pill border shadow-sm" onclick="window.location.reload();"><i class="bi bi-arrow-counterclockwise me-1"></i> İptal</button>
                    <button type="submit" class="btn btn-primary px-5 py-2 fw-bold rounded-pill shadow-sm"><i class="bi bi-check2-circle me-1"></i> Ayarları Kaydet</button>
                </div>

            </form>

        </div>
    </div>
</div>

<?php include __DIR__ . '/../ortak/alt_bilgi.php'; ?>