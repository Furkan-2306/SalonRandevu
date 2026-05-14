<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Türkiye Saat Dilimini Sabitleme
date_default_timezone_set('Europe/Istanbul');

// Sadece dükkan sahipleri bu sayfaya girebilir
if (!isset($_SESSION['giris_yapildi']) || $_SESSION['kullanici_rol'] != 'dukkan_sahibi') {
    header("Location: /kuafor-randevu/giris-kayit");
    exit;
}

require_once __DIR__ . '/../../ayarlar/veritabani.php';
$vt = new Veritabani();
$db = $vt->baglantiGetir();

// DÜKKAN BİLGİLERİNİ ÇEK
$dukkanSorgu = $db->prepare("SELECT * FROM dukkanlar WHERE sahip_id = :sahip_id LIMIT 1");
$dukkanSorgu->execute(['sahip_id' => $_SESSION['kullanici_id']]);
$dukkan = $dukkanSorgu->fetch(PDO::FETCH_ASSOC);

// Eğer kullanıcının dükkan kaydı yoksa kayıt ekranına at
if (!$dukkan) {
    header("Location: /kuafor-randevu/isletme-kayit-adimlari");
    exit;
}

$dukkan_id = $dukkan['id'];
$bugun = date('Y-m-d');

// İSTATİSTİKLERİ HESAPLA
$sorguRandevuSayisi = $db->prepare("SELECT COUNT(*) as toplam FROM randevular WHERE dukkan_id = :id AND DATE(randevu_tarih_saat) = :bugun AND durum != 'iptal'");
$sorguRandevuSayisi->execute(['id' => $dukkan_id, 'bugun' => $bugun]);
$bugunkuRandevu = $sorguRandevuSayisi->fetch(PDO::FETCH_ASSOC)['toplam'];

$sorguOnayBekleyen = $db->prepare("SELECT COUNT(*) as bekleyen FROM randevular WHERE dukkan_id = :id AND durum = 'bekliyor'");
$sorguOnayBekleyen->execute(['id' => $dukkan_id]);
$onayBekleyen = $sorguOnayBekleyen->fetch(PDO::FETCH_ASSOC)['bekleyen'];

$sorguKazanc = $db->prepare("SELECT SUM(toplam_tutar) as kazanc FROM randevular WHERE dukkan_id = :id AND DATE(randevu_tarih_saat) = :bugun AND durum = 'tamamlandi'");
$sorguKazanc->execute(['id' => $dukkan_id, 'bugun' => $bugun]);
$bugunkuKazanc = $sorguKazanc->fetch(PDO::FETCH_ASSOC)['kazanc'] ?? 0;

// Randevu Sorgusu
$randevuSorgu = $db->prepare("
    SELECT r.*, 
           p.ad AS musteri_ad, p.soyad AS musteri_soyad, p.telefon AS musteri_telefon,
           h.ad AS hizmet_adi
    FROM randevular r
    LEFT JOIN kullanici_profilleri p ON r.musteri_id = p.kullanici_id
    LEFT JOIN hizmetler h ON r.hizmet_id = h.id
    WHERE r.dukkan_id = :dukkan_id AND DATE(r.randevu_tarih_saat) = :bugun
    ORDER BY r.randevu_tarih_saat ASC
    LIMIT 10
");
$randevuSorgu->execute(['dukkan_id' => $dukkan_id, 'bugun' => $bugun]);
$randevular = $randevuSorgu->fetchAll(PDO::FETCH_ASSOC);

$aylar = ['', 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
$gunler = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];
$turkceTarih = date('j') . ' ' . $aylar[date('n')] . ' ' . date('Y') . ', ' . $gunler[date('w')];

include __DIR__ . '/../ortak/ust_bilgi.php'; 
?>

<div class="container py-5 mb-5">
    <div class="row g-4">
        
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 text-center p-4 mb-4">
                <div class="mb-3">
                    <div class="bg-dark text-white rounded-circle d-inline-flex align-items-center justify-content-center fw-bold shadow" style="width: 80px; height: 80px; font-size: 1.5rem;">
                        <i class="bi bi-shop"></i>
                    </div>
                </div>
                <h5 class="fw-bold mb-1"><?= htmlspecialchars($dukkan['ad']) ?></h5>
                <p class="text-muted small mb-2"><?= htmlspecialchars($dukkan['ilce'] . ', ' . $dukkan['sehir']) ?></p>
                
                <?php if($dukkan['aktif_mi'] == 1): ?>
                    <span class="badge bg-success rounded-pill mb-2"><i class="bi bi-check-circle me-1"></i> Profil Aktif</span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark rounded-pill mb-2"><i class="bi bi-hourglass-split me-1"></i> Onay Bekliyor</span>
                <?php endif; ?>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="list-group list-group-flush">
                    <a href="/kuafor-randevu/dukkan-ozet-paneli" class="list-group-item list-group-item-action py-3 fw-semibold active" style="background-color: var(--luks-koyu); color: var(--luks-altin); border-color: var(--luks-koyu);">
                        <i class="bi bi-grid-1x2 me-2"></i> Yönetim Paneli
                    </a>
                    <a href="/kuafor-randevu/dukkan-randevu-yonetimi" class="list-group-item list-group-item-action py-3 fw-semibold text-dark">
                        <i class="bi bi-calendar-event me-2"></i> Randevular
                    </a>
                    <a href="/kuafor-randevu/dukkan-hizmet-personel" class="list-group-item list-group-item-action py-3 fw-semibold text-dark">
                        <i class="bi bi-person-lines-fill me-2"></i> Hizmetler & Personel
                    </a>
                    <a href="/kuafor-randevu/dukkan-ayarlari" class="list-group-item list-group-item-action py-3 fw-semibold text-dark">
                        <i class="bi bi-gear me-2"></i> Dükkan Ayarları
                    </a>
                    <a href="/kuafor-randevu/uygulama/kontrolculer/cikis.php" class="list-group-item list-group-item-action py-3 fw-semibold text-danger">
                        <i class="bi bi-box-arrow-right me-2"></i> Çıkış Yap
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold m-0">Hoş Geldiniz, <?= htmlspecialchars($dukkan['ad']) ?></h3>
                <span class="text-muted small"><i class="bi bi-calendar3 me-1"></i> <?= $turkceTarih ?></span>
            </div>

            <?php if($dukkan['aktif_mi'] == 0): ?>
                <div class="alert alert-warning border-0 shadow-sm rounded-4 mb-4">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> Dükkanınız yönetici onayı bekliyor. Onaylandıktan sonra randevu almaya başlayabilirsiniz.
                </div>
            <?php endif; ?>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100" style="border-left: 4px solid var(--luks-altin) !important;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="text-muted fw-semibold mb-0">Bugünkü Randevular</h6>
                                <div class="bg-light text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                    <i class="bi bi-people-fill"></i>
                                </div>
                            </div>
                            <h2 class="fw-bold text-dark mb-0"><?= $bugunkuRandevu ?></h2>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100" style="border-left: 4px solid #f59e0b !important;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="text-muted fw-semibold mb-0">Onay Bekleyenler</h6>
                                <div class="bg-light text-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                    <i class="bi bi-hourglass-split"></i>
                                </div>
                            </div>
                            <h2 class="fw-bold text-dark mb-0"><?= $onayBekleyen ?></h2>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100" style="border-left: 4px solid #10b981 !important;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="text-muted fw-semibold mb-0">Bugünkü Kazanç</h6>
                                <div class="bg-light text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                    <i class="bi bi-wallet2"></i>
                                </div>
                            </div>
                            <h2 class="fw-bold text-dark mb-0"><?= number_format($bugunkuKazanc, 0, ',', '.') ?> ₺</h2>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Yaklaşan Randevular (Bugün)</h5>
                    <a href="/kuafor-randevu/dukkan-randevu-yonetimi" class="btn btn-sm btn-outline-primary rounded-pill">Tümünü Gör</a>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="text-muted small">
                                <tr>
                                    <th>Müşteri</th>
                                    <th>Hizmet</th>
                                    <th>Saat</th>
                                    <th>Durum</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($randevular) > 0): ?>
                                    <?php foreach($randevular as $randevu): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold text-dark"><?= htmlspecialchars($randevu['musteri_ad'] . ' ' . $randevu['musteri_soyad']) ?></div>
                                                <div class="text-muted small"><?= htmlspecialchars($randevu['musteri_telefon'] ?? 'Belirtilmemiş') ?></div>
                                            </td>
                                            <td><?= htmlspecialchars($randevu['hizmet_adi'] ?? 'Hizmet Silinmiş') ?></td>
                                            <td>
                                                <?php if($randevu['durum'] == 'tamamlandi' || $randevu['durum'] == 'iptal'): ?>
                                                    <span class="fw-bold text-muted text-decoration-line-through"><?= date('H:i', strtotime($randevu['randevu_tarih_saat'])) ?></span>
                                                <?php else: ?>
                                                    <span class="fw-bold"><?= date('H:i', strtotime($randevu['randevu_tarih_saat'])) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if($randevu['durum'] == 'bekliyor'): ?>
                                                    <span class="badge bg-warning text-dark">Bekliyor</span>
                                                <?php elseif($randevu['durum'] == 'onaylandi'): ?>
                                                    <span class="badge bg-info text-dark">Onaylandı</span>
                                                <?php elseif($randevu['durum'] == 'tamamlandi'): ?>
                                                    <span class="badge bg-success">Tamamlandı</span>
                                                <?php elseif($randevu['durum'] == 'iptal'): ?>
                                                    <span class="badge bg-danger">İptal Edildi</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">Bugün için planlanmış bir randevunuz bulunmamaktadır.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include __DIR__ . '/../ortak/alt_bilgi.php'; ?>