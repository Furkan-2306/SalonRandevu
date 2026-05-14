<?php
// GÜVENLİK VE OTURUM KONTROLÜ
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Europe/Istanbul'); // Saat sapmasını önlemek için

if (!isset($_SESSION['giris_yapildi']) || $_SESSION['kullanici_rol'] != 'dukkan_sahibi') {
    header("Location: /kuafor-randevu/giris-kayit");
    exit;
}

require_once __DIR__ . '/../../ayarlar/veritabani.php';
$vt = new Veritabani();
$db = $vt->baglantiGetir();

$dukkan_id = $_SESSION['kullanici_id']; 

$dukkanSorgu = $db->prepare("SELECT * FROM dukkanlar WHERE sahip_id = :sahip_id LIMIT 1");
$dukkanSorgu->execute(['sahip_id' => $dukkan_id]);
$dukkan = $dukkanSorgu->fetch(PDO::FETCH_ASSOC);

if (!$dukkan) {
    header("Location: /kuafor-randevu/isletme-kayit-adimlari");
    exit;
}
$gercek_dukkan_id = $dukkan['id'];

// ==========================================
// DURUM GÜNCELLEME VE MAİL GÖNDERME İŞLEMİ
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['durum_guncelle'])) {
    $guncellenecek_id = $_POST['randevu_id'];
    $yeni_durum = $_POST['yeni_durum'];

    // 1. Mail atabilmek için randevu ve müşteri bilgilerini çekiyoruz
    $randevuDetaySorgu = $db->prepare("
        SELECT r.durum as eski_durum, r.randevu_tarih_saat, d.ad as dukkan_adi, k.eposta, kp.ad as musteri_ad, kp.soyad as musteri_soyad
        FROM randevular r
        JOIN dukkanlar d ON r.dukkan_id = d.id
        JOIN kullanicilar k ON r.musteri_id = k.id
        LEFT JOIN kullanici_profilleri kp ON k.id = kp.kullanici_id
        WHERE r.id = :id AND r.dukkan_id = :dukkan_id
    ");
    $randevuDetaySorgu->execute(['id' => $guncellenecek_id, 'dukkan_id' => $gercek_dukkan_id]);
    $randevuDetay = $randevuDetaySorgu->fetch(PDO::FETCH_ASSOC);

    // 2. Randevunun durumunu veritabanında güncelliyoruz
    $guncelleSorgu = $db->prepare("UPDATE randevular SET durum = :durum WHERE id = :id AND dukkan_id = :dukkan_id");
    
    if($guncelleSorgu->execute(['durum' => $yeni_durum, 'id' => $guncellenecek_id, 'dukkan_id' => $gercek_dukkan_id])) {
        
        // 3. Eğer durum GERÇEKTEN değiştiyse maili fırlat
        if($randevuDetay && $randevuDetay['eski_durum'] != $yeni_durum) {
            require_once __DIR__ . '/../../ayarlar/mail_islemleri.php';
            $mailIslemi = new MailIslemleri();

            $alici_ad = (!empty($randevuDetay['musteri_ad'])) ? $randevuDetay['musteri_ad'] . ' ' . $randevuDetay['musteri_soyad'] : 'Değerli Müşterimiz';
            $tarih = date('d.m.Y', strtotime($randevuDetay['randevu_tarih_saat']));
            $saat = date('H:i', strtotime($randevuDetay['randevu_tarih_saat']));

            if($yeni_durum == 'onaylandi') {
                $mailIslemi->randevuOnayMailiGonder($randevuDetay['eposta'], $alici_ad, $randevuDetay['dukkan_adi'], $tarih, $saat);
            } elseif($yeni_durum == 'iptal') {
                $mailIslemi->randevuIptalMailiGonder($randevuDetay['eposta'], $alici_ad, $randevuDetay['dukkan_adi'], $tarih, $saat);
            } elseif($yeni_durum == 'tamamlandi') {
                $mailIslemi->randevuTamamlandiMailiGonder($randevuDetay['eposta'], $alici_ad, $randevuDetay['dukkan_adi'], $tarih, $saat);
            }
        }

        $_SESSION['mesaj_turu'] = "basari";
        $_SESSION['mesaj'] = "Durum başarıyla güncellendi ve müşteriye bildirildi.";
    } else {
        $_SESSION['mesaj_turu'] = "hata";
        $_SESSION['mesaj'] = "Güncelleme sırasında bir hata oluştu.";
    }
    
    $geri_donus_url = $_SERVER['REQUEST_URI'];
    header("Location: $geri_donus_url");
    exit;
}

// LİSTELEME SORGULARI
$secili_tarih = $_GET['tarih'] ?? date('Y-m-d');
$secili_durum = $_GET['durum'] ?? '';

$sql = "
    SELECT r.*, 
           p.ad AS musteri_ad, p.soyad AS musteri_soyad, p.telefon AS musteri_telefon,
           h.ad AS hizmet_adi, h.sure_dakika,
           pers.ad AS personel_ad, pers.soyad AS personel_soyad
    FROM randevular r
    LEFT JOIN kullanici_profilleri p ON r.musteri_id = p.kullanici_id
    LEFT JOIN hizmetler h ON r.hizmet_id = h.id
    LEFT JOIN personeller pers ON r.personel_id = pers.id
    WHERE r.dukkan_id = :dukkan_id 
";
$parametreler = ['dukkan_id' => $gercek_dukkan_id];

if (!empty($secili_tarih)) {
    $sql .= " AND DATE(r.randevu_tarih_saat) = :tarih";
    $parametreler['tarih'] = $secili_tarih;
}

if (!empty($secili_durum)) {
    $sql .= " AND r.durum = :durum";
    $parametreler['durum'] = $secili_durum;
}

$sql .= " ORDER BY r.randevu_tarih_saat ASC"; 

$randevuSorgu = $db->prepare($sql);
$randevuSorgu->execute($parametreler);
$randevular = $randevuSorgu->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../ortak/ust_bilgi.php'; 
?>

<div class="container py-5 mb-5">
    
    <?php if(isset($_SESSION['mesaj'])): ?>
        <div class="alert alert-<?= ($_SESSION['mesaj_turu'] == 'basari') ? 'success' : 'danger' ?> alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="bi <?= ($_SESSION['mesaj_turu'] == 'basari') ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
            <?= $_SESSION['mesaj']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['mesaj']); unset($_SESSION['mesaj_turu']); ?>
    <?php endif; ?>

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

            <div class="list-group list-group-flush rounded-4 shadow-sm overflow-hidden">
                <a href="/kuafor-randevu/dukkan-ozet-paneli" class="list-group-item list-group-item-action py-3 fw-semibold text-dark">
                    <i class="bi bi-grid-1x2 me-2"></i> Yönetim Paneli
                </a>
                <a href="/kuafor-randevu/dukkan-randevu-yonetimi" class="list-group-item list-group-item-action py-3 fw-semibold active" style="background-color: var(--luks-koyu); color: var(--luks-altin); border-color: var(--luks-koyu);">
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

        <div class="col-lg-9">
            
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <h3 class="fw-bold m-0">Randevu Yönetimi</h3>
                
                <form action="" method="GET" class="d-flex gap-2">
                    <input type="date" name="tarih" class="form-control rounded-3 border-secondary-subtle" value="<?= htmlspecialchars($secili_tarih) ?>" onchange="this.form.submit()">
                    <select name="durum" class="form-select rounded-3 border-secondary-subtle" onchange="this.form.submit()">
                        <option value="">Tüm Durumlar</option>
                        <option value="bekliyor" <?= ($secili_durum == 'bekliyor') ? 'selected' : '' ?>>Bekleyenler</option>
                        <option value="onaylandi" <?= ($secili_durum == 'onaylandi') ? 'selected' : '' ?>>Onaylananlar</option>
                        <option value="tamamlandi" <?= ($secili_durum == 'tamamlandi') ? 'selected' : '' ?>>Tamamlananlar</option>
                        <option value="iptal" <?= ($secili_durum == 'iptal') ? 'selected' : '' ?>>İptal / Gelmedi</option>
                    </select>
                </form>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted small">
                                <tr>
                                    <th class="ps-4 py-3">Müşteri / İletişim</th>
                                    <th class="py-3">Hizmet & Personel</th>
                                    <th class="py-3">Tarih & Saat</th>
                                    <th class="py-3">Tutar</th>
                                    <th class="py-3">Durum</th>
                                    <th class="text-end pe-4 py-3">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($randevular) > 0): ?>
                                    <?php foreach($randevular as $r): 
                                        
                                        // Tarihi "Bugün/Dün/Yarın" şeklinde akıllı gösterme
                                        $r_tarih = date('Y-m-d', strtotime($r['randevu_tarih_saat']));
                                        $bugun = date('Y-m-d');
                                        $dun = date('Y-m-d', strtotime('-1 day'));
                                        $yarin = date('Y-m-d', strtotime('+1 day'));

                                        if ($r_tarih == $bugun) $tarih_metin = "Bugün";
                                        elseif ($r_tarih == $dun) $tarih_metin = "Dün";
                                        elseif ($r_tarih == $yarin) $tarih_metin = "Yarın";
                                        else $tarih_metin = date('d.m.Y', strtotime($r_tarih));
                                        
                                        $saat = date('H:i', strtotime($r['randevu_tarih_saat']));
                                    ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($r['musteri_ad'] . ' ' . $r['musteri_soyad']) ?></div>
                                            <div class="text-muted small"><i class="bi bi-telephone-fill small me-1"></i> <?= htmlspecialchars($r['musteri_telefon'] ?? 'Belirtilmemiş') ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark small"><?= htmlspecialchars($r['hizmet_adi'] ?? 'Silinmiş Hizmet') ?> <?= isset($r['sure_dakika']) ? "({$r['sure_dakika']} Dk)" : '' ?></div>
                                            <div class="text-muted" style="font-size: 0.75rem;"><i class="bi bi-person-badge"></i> <?= htmlspecialchars($r['personel_ad'] ?? 'Personel Seçilmedi') ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-bold <?= ($r['durum'] == 'iptal') ? 'text-muted text-decoration-line-through' : 'text-dark' ?>"><?= $tarih_metin ?></div>
                                            <div class="<?= ($r['durum'] == 'iptal') ? 'text-muted text-decoration-line-through' : 'text-primary fw-bold' ?>"><?= $saat ?></div>
                                        </td>
                                        <td><span class="fw-bold text-dark"><?= number_format($r['toplam_tutar'], 0, ',', '.') ?> ₺</span></td>
                                        <td>
                                            <?php if($r['durum'] == 'bekliyor'): ?>
                                                <span class="badge bg-warning text-dark border border-warning">Onay Bekliyor</span>
                                            <?php elseif($r['durum'] == 'onaylandi'): ?>
                                                <span class="badge bg-info text-dark border border-info">Onaylandı</span>
                                            <?php elseif($r['durum'] == 'tamamlandi'): ?>
                                                <span class="badge bg-success border border-success">Tamamlandı</span>
                                            <?php elseif($r['durum'] == 'iptal'): ?>
                                                <span class="badge bg-danger border border-danger">İptal / Gelmedi</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <?php if($r['durum'] == 'tamamlandi' || $r['durum'] == 'iptal'): ?>
                                                <button class="btn btn-sm btn-light text-muted rounded-pill px-3 fw-semibold border" disabled>Kapatıldı</button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-primary rounded-pill px-3 fw-semibold" 
                                                        onclick="modalDoldur(<?= $r['id'] ?>, '<?= htmlspecialchars($r['musteri_ad'] . ' ' . $r['musteri_soyad']) ?>', '<?= htmlspecialchars($r['hizmet_adi']) ?>', '<?= $tarih_metin ?> <?= $saat ?>', '<?= $r['durum'] ?>')" 
                                                        data-bs-toggle="modal" data-bs-target="#durumModal">Güncelle</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="bi bi-calendar-x fs-1 text-light mb-2 d-block"></i>
                                            Seçilen tarih ve duruma ait randevu bulunamadı.
                                        </td>
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

<div class="modal fade" id="durumModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow-lg">
      <div class="modal-header border-bottom-0 pb-0 mt-2 mx-2">
        <h5 class="modal-title fw-bold">Randevu Durumunu Güncelle</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
      </div>
      <div class="modal-body py-4 px-4">
        
        <div class="mb-4 bg-light p-3 rounded-3 border">
            <h6 class="fw-bold text-dark mb-1" id="modalMusteriAd">Müşteri Adı</h6>
            <p class="text-muted small mb-0"><span id="modalHizmet">Hizmet</span> • <span id="modalSaat">Saat</span></p>
        </div>

        <form action="" method="POST">
            <input type="hidden" name="durum_guncelle" value="1">
            <input type="hidden" name="randevu_id" id="modalRandevuId">

            <div class="form-floating mb-4">
                <select class="form-select rounded-3" name="yeni_durum" id="modalDurumSelect">
                    <option value="bekliyor">Onay Bekliyor</option>
                    <option value="onaylandi">Onayla (Aktif)</option>
                    <option value="tamamlandi">Tamamlandı (Ödeme Alındı)</option>
                    <option value="iptal">İptal Et / Gelmedi</option>
                </select>
                <label for="modalDurumSelect">Yeni Durum Seçin</label>
            </div>
            
            <button type="submit" class="btn btn-primary w-100 fw-bold py-2 rounded-3">Güncelle ve Kaydet</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function modalDoldur(id, musteri, hizmet, saat, mevcutDurum) {
    document.getElementById('modalRandevuId').value = id;
    document.getElementById('modalMusteriAd').innerText = musteri;
    document.getElementById('modalHizmet').innerText = hizmet;
    document.getElementById('modalSaat').innerText = saat;
    
    let selectKutusu = document.getElementById('modalDurumSelect');
    selectKutusu.value = mevcutDurum;
}
</script>

<?php include __DIR__ . '/../ortak/alt_bilgi.php'; ?>