<?php 
// GÜVENLİK VE OTURUM KONTROLÜ
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sadece 'admin' rolüne sahip kişiler bu sayfayı görebilir
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
// YÖNETİCİ İSTATİSTİKLERİNİ CANLI ÇEKME
// ==========================================

// 1. Toplam Onaylı İşletme Sayısı
$aktifDukkanSorgu = $db->query("SELECT COUNT(id) as toplam FROM dukkanlar WHERE aktif_mi = 1");
$toplamAktifDukkan = $aktifDukkanSorgu->fetch(PDO::FETCH_ASSOC)['toplam'];

// 2. Kayıtlı Toplam Müşteri Sayısı
$musteriSorgu = $db->query("SELECT COUNT(id) as toplam FROM kullanicilar WHERE rol = 'musteri'");
$toplamMusteri = $musteriSorgu->fetch(PDO::FETCH_ASSOC)['toplam'];

// 3. Tamamlanan Toplam Randevu Sayısı
$randevuSorgu = $db->query("SELECT COUNT(id) as toplam FROM randevular WHERE durum = 'tamamlandi'");
$tamamlananRandevu = $randevuSorgu->fetch(PDO::FETCH_ASSOC)['toplam'];

// 4. Onay Bekleyen Dükkan Sayısı (Sol menüdeki bildirim için)
$bekleyenSorgu = $db->query("SELECT COUNT(id) as toplam FROM dukkanlar WHERE aktif_mi = 0");
$toplamBekleyen = $bekleyenSorgu->fetch(PDO::FETCH_ASSOC)['toplam'];

// 5. Son Başvurular (Onay Bekleyen İşletmeler) - Sadece ilk 5'i
$sonBasvurularSorgu = $db->query("SELECT id, ad, cinsiyet_tipi, ilce, sehir FROM dukkanlar WHERE aktif_mi = 0 ORDER BY kayit_tarihi ASC LIMIT 5");
$sonBasvurular = $sonBasvurularSorgu->fetchAll(PDO::FETCH_ASSOC);

// Cinsiyet tipini okunabilir metne çeviren küçük bir yardımcı fonksiyon
function cinsiyetTipiYazdir($tip) {
    if ($tip == 'kadin') return 'Kadın Kuaförü';
    if ($tip == 'erkek') return 'Erkek Kuaförü / Berber';
    return 'Unisex Salon';
}

include __DIR__ . '/../ortak/ust_bilgi.php'; 
?>

<div class="container py-5 mb-5">
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
                    <a href="/kuafor-randevu/yonetici-ozet" class="list-group-item list-group-item-action py-3 fw-semibold active border-0" style="background-color: var(--luks-altin); color: var(--luks-koyu);">
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
                    <a href="/kuafor-randevu/yonetici-genel-ayarlar" class="list-group-item list-group-item-action py-3 fw-semibold bg-dark text-white border-bottom border-secondary">
                        <i class="bi bi-gear-fill me-2"></i> Genel Ayarlar
                    </a>
                    <a href="/kuafor-randevu/uygulama/kontrolculer/cikis.php" class="list-group-item list-group-item-action py-3 fw-semibold bg-dark text-danger border-0">
                        <i class="bi bi-box-arrow-right me-2"></i> Güvenli Çıkış
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold m-0 text-dark">Sistem Özeti</h3>
                <button class="btn btn-sm btn-outline-secondary rounded-pill" onclick="window.location.reload();"><i class="bi bi-arrow-clockwise me-1"></i> Verileri Yenile</button>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card bg-dark text-white border-0 shadow-sm rounded-4 h-100 position-relative overflow-hidden">
                        <div class="position-absolute top-0 start-0 w-100" style="height: 4px; background-color: var(--luks-altin);"></div>
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="text-secondary fw-semibold mb-0">Toplam İşletme</h6>
                                <i class="bi bi-shop fs-4 text-secondary"></i>
                            </div>
                            <h2 class="fw-bold mb-0"><?= number_format($toplamAktifDukkan, 0, ',', '.') ?></h2>
                            <p class="text-success small mt-2 mb-0"><i class="bi bi-check-circle-fill"></i> Aktif Kullanımda</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card bg-dark text-white border-0 shadow-sm rounded-4 h-100 position-relative overflow-hidden">
                        <div class="position-absolute top-0 start-0 w-100" style="height: 4px; background-color: #0dcaf0;"></div>
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="text-secondary fw-semibold mb-0">Kayıtlı Müşteri</h6>
                                <i class="bi bi-people fs-4 text-secondary"></i>
                            </div>
                            <h2 class="fw-bold mb-0"><?= number_format($toplamMusteri, 0, ',', '.') ?></h2>
                            <p class="text-info small mt-2 mb-0"><i class="bi bi-person-fill"></i> Sistemdeki üyeler</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card bg-dark text-white border-0 shadow-sm rounded-4 h-100 position-relative overflow-hidden">
                        <div class="position-absolute top-0 start-0 w-100" style="height: 4px; background-color: #198754;"></div>
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="text-secondary fw-semibold mb-0">Tamamlanan Randevu</h6>
                                <i class="bi bi-calendar-check fs-4 text-secondary"></i>
                            </div>
                            <h2 class="fw-bold mb-0"><?= number_format($tamamlananRandevu, 0, ',', '.') ?></h2>
                            <p class="text-secondary small mt-2 mb-0">Tüm zamanlar</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0 text-dark">Son Başvurular</h5>
                            <a href="/kuafor-randevu/yonetici-onay-bekleyenler" class="btn btn-sm btn-outline-primary rounded-pill">Tümünü Gör</a>
                        </div>
                        <div class="card-body px-4 pb-4">
                            <div class="list-group list-group-flush">
                                
                                <?php if(count($sonBasvurular) > 0): ?>
                                    <?php foreach($sonBasvurular as $basvuru): ?>
                                        <div class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center border-bottom">
                                            <div>
                                                <h6 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($basvuru['ad']) ?></h6>
                                                <span class="small text-muted"><?= cinsiyetTipiYazdir($basvuru['cinsiyet_tipi']) ?> • <?= htmlspecialchars($basvuru['ilce'] . '/' . $basvuru['sehir']) ?></span>
                                            </div>
                                            <span class="badge bg-warning text-dark">İnceleme Bekliyor</span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-check-circle text-success fs-2 mb-2 d-block"></i>
                                        <span class="text-muted small">Şu anda onay bekleyen işletme bulunmuyor.</span>
                                    </div>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card bg-dark text-white border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-transparent border-secondary pt-4 pb-2 px-4">
                            <h5 class="fw-bold mb-0" style="color: var(--luks-altin);">Sistem Aktiviteleri</h5>
                        </div>
                        <div class="card-body px-4 pb-4">
                            <ul class="list-unstyled mb-0 small">
                                <li class="mb-3 d-flex">
                                    <i class="bi bi-record-circle text-success me-2 mt-1"></i>
                                    <div>
                                        <span class="d-block text-white">Sistem kontrolleri başarılı şekilde gerçekleştirildi.</span>
                                        <span class="text-secondary" style="font-size: 0.75rem;">Canlı İzleme</span>
                                    </div>
                                </li>
                                <li class="mb-3 d-flex">
                                    <i class="bi bi-record-circle text-primary me-2 mt-1"></i>
                                    <div>
                                        <span class="d-block text-white">Veritabanı bağlantısı stabil çalışıyor.</span>
                                        <span class="text-secondary" style="font-size: 0.75rem;">Sistem Mesajı</span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>

<?php include __DIR__ . '/../ortak/alt_bilgi.php'; ?>