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
// İŞLETME ONAYLAMA VEYA REDDETME İŞLEMİ
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['islem'])) {
    $dukkan_id = (int)$_POST['dukkan_id'];

    if ($_POST['islem'] == 'onayla') {
        try {
            // İşlemleri garantilemek için Transaction (İşlem Bloğu) başlatıyoruz
            // Biri başarısız olursa diğeri de iptal olur, veri bütünlüğü korunur.
            $db->beginTransaction();

            // 1. Dükkanın sahibinin ID'sini bul
            $sahipSorgu = $db->prepare("SELECT sahip_id FROM dukkanlar WHERE id = ?");
            $sahipSorgu->execute([$dukkan_id]);
            $dukkan = $sahipSorgu->fetch(PDO::FETCH_ASSOC);

            if ($dukkan) {
                // 2. Dükkanı aktif et
                $dukkanOnay = $db->prepare("UPDATE dukkanlar SET aktif_mi = 1 WHERE id = ?");
                $dukkanOnay->execute([$dukkan_id]);

                // 3. Müşterinin rolünü 'dukkan_sahibi' olarak GÜNCELLE!
                $rolGuncelle = $db->prepare("UPDATE kullanicilar SET rol = 'dukkan_sahibi' WHERE id = ?");
                $rolGuncelle->execute([$dukkan['sahip_id']]);

                // Tüm işlemleri onayla ve veritabanına kalıcı olarak yaz
                $db->commit();

                $_SESSION['mesaj_turu'] = "basari";
                $_SESSION['mesaj'] = "İşletme onaylandı ve kullanıcının yetkisi 'İşletme Sahibi' olarak güncellendi.";
            } else {
                $db->rollBack();
                $_SESSION['mesaj_turu'] = "hata";
                $_SESSION['mesaj'] = "İşlem yapılacak işletme bulunamadı.";
            }
        } catch (Exception $e) {
            $db->rollBack(); // Hata çıkarsa hiçbir şeyi kaydetme, sistemi eski haline getir
            $_SESSION['mesaj_turu'] = "hata";
            $_SESSION['mesaj'] = "Sistemsel bir hata oluştu: " . $e->getMessage();
        }

    } elseif ($_POST['islem'] == 'reddet') {
        // Reddedilen işletmeyi veritabanından siliyoruz
        $sorgu = $db->prepare("DELETE FROM dukkanlar WHERE id = ?");
        if ($sorgu->execute([$dukkan_id])) {
            $_SESSION['mesaj_turu'] = "basari";
            $_SESSION['mesaj'] = "İşletme başvurusu reddedildi ve kayıt silindi.";
        } else {
            $_SESSION['mesaj_turu'] = "hata";
            $_SESSION['mesaj'] = "Ret işlemi sırasında bir hata oluştu.";
        }
    }
    
    header("Location: /kuafor-randevu/yonetici-onay-bekleyenler");
    exit;
}

// ==========================================
// ONAY BEKLEYEN İŞLETMELERİ ÇEK
// ==========================================
$bekleyenSorgu = $db->query("
    SELECT d.*, 
           kp.ad as sahip_ad, kp.soyad as sahip_soyad, kp.telefon
    FROM dukkanlar d
    LEFT JOIN kullanici_profilleri kp ON d.sahip_id = kp.kullanici_id
    WHERE d.aktif_mi = 0
    ORDER BY d.kayit_tarihi ASC
");
$bekleyenler = $bekleyenSorgu->fetchAll(PDO::FETCH_ASSOC);
$toplamBekleyen = count($bekleyenler);

function cinsiyetTipiYazdir($tip) {
    if ($tip == 'kadin') return 'Kadın Kuaförü';
    if ($tip == 'erkek') return 'Erkek Kuaförü / Berber';
    return 'Unisex Salon';
}

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
                    <a href="/kuafor-randevu/yonetici-onay-bekleyenler" class="list-group-item list-group-item-action py-3 fw-semibold active border-0" style="background-color: var(--luks-altin); color: var(--luks-koyu);">
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
                <div>
                    <h3 class="fw-bold m-0 text-dark">Onay Bekleyen İşletmeler</h3>
                    <p class="text-muted small mt-1 mb-0">Sisteme kayıt olan yeni işletmeleri inceleyin ve onaylayın.</p>
                </div>
                <span class="badge bg-warning text-dark px-3 py-2 fs-6 rounded-pill"><?= $toplamBekleyen ?> Bekleyen Başvuru</span>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted small">
                                <tr>
                                    <th class="ps-4 py-3">İşletme Adı / Vergi No</th>
                                    <th class="py-3">Sahibi & İletişim</th>
                                    <th class="py-3">Kategori & Lokasyon</th>
                                    <th class="py-3">Başvuru Tarihi</th>
                                    <th class="text-end pe-4 py-3">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($toplamBekleyen > 0): ?>
                                    <?php foreach($bekleyenler as $dukkan): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($dukkan['ad']) ?></div>
                                                <div class="text-muted small"><i class="bi bi-file-earmark-text"></i> VN: <?= htmlspecialchars($dukkan['vergi_no'] ?? 'Belirtilmedi') ?></div>
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-dark small"><?= htmlspecialchars(($dukkan['sahip_ad'] ?? 'İsimsiz') . ' ' . ($dukkan['sahip_soyad'] ?? '')) ?></div>
                                                <div class="text-muted" style="font-size: 0.75rem;"><i class="bi bi-telephone"></i> <?= htmlspecialchars($dukkan['telefon'] ?? 'Belirtilmedi') ?></div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border mb-1"><?= cinsiyetTipiYazdir($dukkan['cinsiyet_tipi'] ?? 'hepsi') ?></span>
                                                <div class="text-muted fw-semibold" style="font-size: 0.75rem;"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($dukkan['ilce'] . ', ' . $dukkan['sehir']) ?></div>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark small"><?= date('d.m.Y', strtotime($dukkan['kayit_tarihi'])) ?></div>
                                                <div class="text-muted" style="font-size: 0.75rem;"><?= date('H:i', strtotime($dukkan['kayit_tarihi'])) ?></div>
                                            </td>
                                            <td class="text-end pe-4">
                                                <button class="btn btn-sm btn-success rounded-pill px-3 fw-semibold me-1" title="Onayla" data-bs-toggle="modal" data-bs-target="#onayModal" onclick="islemAyarla(<?= $dukkan['id'] ?>, '<?= addslashes($dukkan['ad']) ?>', 'onayla')">
                                                    <i class="bi bi-check-lg me-1"></i> Aktif Et
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-semibold" title="Reddet" data-bs-toggle="modal" data-bs-target="#redModal" onclick="islemAyarla(<?= $dukkan['id'] ?>, '<?= addslashes($dukkan['ad']) ?>', 'reddet')">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5">
                                            <i class="bi bi-check-circle text-success fs-1 mb-3 d-block"></i>
                                            <h5 class="fw-bold text-muted">Harika, her şey tamam!</h5>
                                            <p class="text-secondary small mb-0">Şu anda onay bekleyen herhangi bir işletme başvurusu bulunmuyor.</p>
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

<div class="modal fade" id="onayModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow-lg">
      <div class="modal-header border-bottom-0 pb-0 mt-2 mx-2">
        <h5 class="modal-title fw-bold text-success"><i class="bi bi-check-circle-fill me-2"></i>İşletmeyi Onayla</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
      </div>
      <div class="modal-body py-4 px-4 text-center">
        <h5 class="fw-bold text-dark mb-3" id="onayDukkanAd">İşletme Adı</h5>
        <p class="text-muted mb-4">Bu işletmeyi onayladığınızda, dükkan profilindeki sistem kısıtlamaları kaldırılacak ve müşteriler bu salondan randevu almaya başlayabilecektir.</p>
        
        <form action="" method="POST">
            <input type="hidden" name="islem" value="onayla">
            <input type="hidden" name="dukkan_id" id="onayDukkanId" value="">
            <div class="d-flex gap-2 justify-content-center">
                <button type="button" class="btn btn-light fw-bold px-4 rounded-pill" data-bs-dismiss="modal">İptal</button>
                <button type="submit" class="btn btn-success fw-bold px-4 rounded-pill">Evet, Aktif Et</button>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="redModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow-lg">
      <div class="modal-header border-bottom-0 pb-0 mt-2 mx-2">
        <h5 class="modal-title fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Başvuruyu Reddet</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
      </div>
      <div class="modal-body py-4 px-4">
        <p class="text-muted small mb-3"><strong id="redDukkanAd" class="text-dark">İşletme Adı</strong> adlı işletmenin başvurusunu reddetmek üzeresiniz. Bu işlem başvuruyu veritabanından tamamen silecektir.</p>
        
        <form action="" method="POST">
            <input type="hidden" name="islem" value="reddet">
            <input type="hidden" name="dukkan_id" id="redDukkanId" value="">
            
            <div class="form-floating mb-4">
                <textarea class="form-control rounded-3" name="ret_sebebi" id="retSebebi" style="height: 100px" placeholder="Sebep"></textarea>
                <label for="retSebebi">Ret Sebebi / Açıklama (Opsiyonel)</label>
            </div>
            <div class="d-flex gap-2 justify-content-end">
                <button type="button" class="btn btn-light fw-bold px-4 rounded-pill" data-bs-dismiss="modal">İptal</button>
                <button type="submit" class="btn btn-danger fw-bold px-4 rounded-pill">Başvuruyu Reddet</button>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
// Modal açılırken dükkan ID ve Adını formların içine gizlice yerleştirir
function islemAyarla(id, ad, islemTuru) {
    if(islemTuru === 'onayla') {
        document.getElementById('onayDukkanId').value = id;
        document.getElementById('onayDukkanAd').innerText = ad;
    } else {
        document.getElementById('redDukkanId').value = id;
        document.getElementById('redDukkanAd').innerText = ad;
    }
}
</script>

<?php include __DIR__ . '/../ortak/alt_bilgi.php'; ?>