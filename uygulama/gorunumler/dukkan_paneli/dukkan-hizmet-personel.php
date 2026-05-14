<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['giris_yapildi']) || $_SESSION['kullanici_rol'] != 'dukkan_sahibi') {
    header("Location: /kuafor-randevu/giris-kayit");
    exit;
}

require_once __DIR__ . '/../../ayarlar/veritabani.php';
$vt = new Veritabani();
$db = $vt->baglantiGetir();

$dukkanSorgu = $db->prepare("SELECT * FROM dukkanlar WHERE sahip_id = :sahip_id LIMIT 1");
$dukkanSorgu->execute(['sahip_id' => $_SESSION['kullanici_id']]);
$dukkan = $dukkanSorgu->fetch(PDO::FETCH_ASSOC);

if (!$dukkan) {
    header("Location: /kuafor-randevu/isletme-kayit-adimlari");
    exit;
}
$gercek_dukkan_id = $dukkan['id'];


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // YENİ HİZMET EKLEME
    if (isset($_POST['hizmet_ekle'])) {
        $ad = trim(htmlspecialchars($_POST['ad']));
        $sure = (int)$_POST['sure_dakika'];
        $fiyat = (float)$_POST['fiyat'];

        $ekleSorgu = $db->prepare("INSERT INTO hizmetler (dukkan_id, ad, sure_dakika, fiyat) VALUES (:dukkan_id, :ad, :sure, :fiyat)");
        if($ekleSorgu->execute(['dukkan_id' => $gercek_dukkan_id, 'ad' => $ad, 'sure' => $sure, 'fiyat' => $fiyat])) {
            $_SESSION['mesaj_turu'] = "basari";
            $_SESSION['mesaj'] = "Yeni hizmet başarıyla eklendi.";
        }
    }

    if (isset($_POST['hizmet_sil'])) {
        $hizmet_id = $_POST['silinecek_hizmet_id'];
        $silSorgu = $db->prepare("DELETE FROM hizmetler WHERE id = :id AND dukkan_id = :dukkan_id");
        if($silSorgu->execute(['id' => $hizmet_id, 'dukkan_id' => $gercek_dukkan_id])) {
            $_SESSION['mesaj_turu'] = "basari";
            $_SESSION['mesaj'] = "Hizmet başarıyla silindi.";
        }
    }

    if (isset($_POST['personel_ekle'])) {
        $ad = trim(htmlspecialchars($_POST['ad']));
        $soyad = trim(htmlspecialchars($_POST['soyad']));
        $mesai_baslangic = $_POST['mesai_baslangic'];
        $mesai_bitis = $_POST['mesai_bitis'];
        $izinli_gunler = $_POST['izinli_gunler'] ?? []; // Seçilen günlerin dizisi

        try {
            $db->beginTransaction();

            $ekleSorgu = $db->prepare("INSERT INTO personeller (dukkan_id, ad, soyad) VALUES (:dukkan_id, :ad, :soyad)");
            $ekleSorgu->execute(['dukkan_id' => $gercek_dukkan_id, 'ad' => $ad, 'soyad' => $soyad]);
            $yeni_personel_id = $db->lastInsertId(); // Eklenen personelin ID'sini al

            $mesaiSorgu = $db->prepare("INSERT INTO personel_mesai (personel_id, haftanin_gunu, baslangic_saati, bitis_saati, izinli_mi) VALUES (:p_id, :gun, :bas, :bit, :izin)");

            for ($gun = 1; $gun <= 7; $gun++) {
                $izinli_mi = in_array($gun, $izinli_gunler) ? 1 : 0;
                $bas = $izinli_mi ? "00:00:00" : $mesai_baslangic;
                $bit = $izinli_mi ? "00:00:00" : $mesai_bitis;

                $mesaiSorgu->execute([
                    'p_id' => $yeni_personel_id,
                    'gun'  => $gun,
                    'bas'  => $bas,
                    'bit'  => $bit,
                    'izin' => $izinli_mi
                ]);
            }

            $db->commit(); 
            $_SESSION['mesaj_turu'] = "basari";
            $_SESSION['mesaj'] = "Personel ve haftalık çalışma saatleri başarıyla eklendi.";
        } catch (Exception $e) {
            $db->rollBack(); 
            $_SESSION['mesaj_turu'] = "hata";
            $_SESSION['mesaj'] = "Personel eklenirken bir hata oluştu: " . $e->getMessage();
        }
    }

    if (isset($_POST['personel_sil'])) {
        $personel_id = $_POST['silinecek_personel_id'];
        try {
            $db->beginTransaction();
            
            $silMesai = $db->prepare("DELETE FROM personel_mesai WHERE personel_id = :id");
            $silMesai->execute(['id' => $personel_id]);

            $silSorgu = $db->prepare("DELETE FROM personeller WHERE id = :id AND dukkan_id = :dukkan_id");
            $silSorgu->execute(['id' => $personel_id, 'dukkan_id' => $gercek_dukkan_id]);

            $db->commit();
            $_SESSION['mesaj_turu'] = "basari";
            $_SESSION['mesaj'] = "Personel ve tüm mesai bilgileri sistemden silindi.";
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['mesaj_turu'] = "hata";
            $_SESSION['mesaj'] = "Personel silinirken bir hata oluştu.";
        }
    }

    header("Location: /kuafor-randevu/uygulama/gorunumler/dukkan_paneli/dukkan-hizmet-personel.php");
    exit;
}


$hizmetler = $db->prepare("SELECT * FROM hizmetler WHERE dukkan_id = :dukkan_id ORDER BY id DESC");
$hizmetler->execute(['dukkan_id' => $gercek_dukkan_id]);
$hizmetListesi = $hizmetler->fetchAll(PDO::FETCH_ASSOC);

$personeller = $db->prepare("SELECT * FROM personeller WHERE dukkan_id = :dukkan_id ORDER BY id DESC");
$personeller->execute(['dukkan_id' => $gercek_dukkan_id]);
$personelListesi = $personeller->fetchAll(PDO::FETCH_ASSOC);

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

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="list-group list-group-flush">
                    <a href="/kuafor-randevu/dukkan-ozet-paneli" class="list-group-item list-group-item-action py-3 fw-semibold text-dark">
                        <i class="bi bi-grid-1x2 me-2"></i> Yönetim Paneli
                    </a>
                    <a href="/kuafor-randevu/dukkan-randevu-yonetimi" class="list-group-item list-group-item-action py-3 fw-semibold text-dark">
                        <i class="bi bi-calendar-event me-2"></i> Randevular
                    </a>
                    <a href="/kuafor-randevu/uygulama/gorunumler/dukkan_paneli/dukkan-hizmet-personel.php" class="list-group-item list-group-item-action py-3 fw-semibold active" style="background-color: var(--luks-koyu); color: var(--luks-altin); border-color: var(--luks-koyu);">
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
            
            <h3 class="fw-bold mb-4">Hizmet ve Personel Yönetimi</h3>

            <ul class="nav nav-tabs mb-4 border-bottom-0" id="yonetimTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold border-0 border-bottom border-3 text-dark px-4" id="hizmetler-tab" data-bs-toggle="tab" data-bs-target="#hizmetler" type="button" role="tab">Hizmetler & Fiyatlar</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold border-0 text-muted px-4" id="personel-tab" data-bs-toggle="tab" data-bs-target="#personel" type="button" role="tab">Personel Listesi</button>
                </li>
            </ul>

            <div class="tab-content" id="yonetimTabsContent">
                
                <div class="tab-pane fade show active" id="hizmetler" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0">Tüm Hizmetleriniz</h5>
                            <button class="btn btn-primary btn-sm rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#yeniHizmetModal">
                                <i class="bi bi-plus-lg"></i> Yeni Hizmet Ekle
                            </button>
                        </div>
                        <div class="card-body px-4 pb-4">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="text-muted small">
                                        <tr>
                                            <th>Hizmet Adı</th>
                                            <th>Süre (Dakika)</th>
                                            <th>Fiyat (₺)</th>
                                            <th class="text-end">İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(count($hizmetListesi) > 0): ?>
                                            <?php foreach($hizmetListesi as $hizmet): ?>
                                                <tr>
                                                    <td class="fw-semibold text-dark"><?= htmlspecialchars($hizmet['ad']) ?></td>
                                                    <td><span class="badge bg-light text-dark border"><?= $hizmet['sure_dakika'] ?> Dk</span></td>
                                                    <td class="fw-bold text-primary"><?= number_format($hizmet['fiyat'], 2, ',', '.') ?> ₺</td>
                                                    <td class="text-end">
                                                        <form action="" method="POST" class="d-inline" onsubmit="return confirm('Bu hizmeti silmek istediğinize emin misiniz?');">
                                                            <input type="hidden" name="silinecek_hizmet_id" value="<?= $hizmet['id'] ?>">
                                                            <button type="submit" name="hizmet_sil" class="btn btn-sm btn-light rounded-circle text-danger"><i class="bi bi-trash3"></i></button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="4" class="text-center py-4 text-muted">Henüz hiç hizmet eklemediniz.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="personel" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0">Çalışan Personelleriniz</h5>
                            <button class="btn btn-primary btn-sm rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#yeniPersonelModal">
                                <i class="bi bi-plus-lg"></i> Personel Ekle
                            </button>
                        </div>
                        <div class="card-body px-4 pb-4">
                            <div class="row g-3">
                                <?php if(count($personelListesi) > 0): ?>
                                    <?php foreach($personelListesi as $personel): 
                                        // Baş Harfleri Bul
                                        $p_ilk_harf = mb_substr($personel['ad'], 0, 1, 'UTF-8');
                                        $p_soyad_ilk_harf = mb_substr($personel['soyad'], 0, 1, 'UTF-8');
                                        $p_bas_harfler = mb_strtoupper($p_ilk_harf . $p_soyad_ilk_harf, 'UTF-8');
                                    ?>
                                        <div class="col-md-6">
                                            <div class="card border rounded-4 shadow-none h-100">
                                                <div class="card-body d-flex align-items-center p-3">
                                                    <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold me-3" style="width: 50px; height: 50px;">
                                                        <?= $p_bas_harfler ?>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h6 class="fw-bold mb-0"><?= htmlspecialchars($personel['ad'] . ' ' . $personel['soyad']) ?></h6>
                                                        <span class="small text-muted">Personel</span>
                                                    </div>
                                                    <div>
                                                        <form action="" method="POST" class="d-inline" onsubmit="return confirm('Bu personeli ve çalışma saatlerini silmek istediğinize emin misiniz?');">
                                                            <input type="hidden" name="silinecek_personel_id" value="<?= $personel['id'] ?>">
                                                            <button type="submit" name="personel_sil" class="btn btn-sm btn-light rounded-circle text-danger"><i class="bi bi-trash3"></i></button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="col-12 text-center py-4 text-muted">Henüz hiç personel eklemediniz.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="yeniHizmetModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow-lg">
      <div class="modal-header border-bottom-0 pb-0 mt-2 mx-2">
        <h5 class="modal-title fw-bold">Yeni Hizmet Ekle</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
      </div>
      <div class="modal-body py-4 px-4">
        <form action="" method="POST">
            <input type="hidden" name="hizmet_ekle" value="1">
            <div class="form-floating mb-3">
                <input type="text" class="form-control rounded-3" id="hizmetAdi" name="ad" placeholder="Hizmet Adı" required>
                <label for="hizmetAdi">Hizmet Adı (Örn: Saç Kesimi)</label>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-6">
                    <div class="form-floating">
                        <input type="number" class="form-control rounded-3" id="hizmetSuresi" name="sure_dakika" placeholder="Süre" required>
                        <label for="hizmetSuresi">Süre (Dakika)</label>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-floating">
                        <input type="number" step="0.01" class="form-control rounded-3" id="hizmetFiyati" name="fiyat" placeholder="Fiyat" required>
                        <label for="hizmetFiyati">Fiyat (₺)</label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 fw-bold py-2 rounded-3">Kaydet</button>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="yeniPersonelModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow-lg">
      <div class="modal-header border-bottom-0 pb-0 mt-2 mx-2">
        <h5 class="modal-title fw-bold">Yeni Personel Ekle</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
      </div>
      <div class="modal-body py-4 px-4">
        <form action="" method="POST">
            <input type="hidden" name="personel_ekle" value="1">
            
            <div class="row g-3 mb-4">
                <div class="col-6">
                    <div class="form-floating">
                        <input type="text" class="form-control rounded-3" id="personelAd" name="ad" placeholder="Ad" required>
                        <label for="personelAd">Adı</label>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-floating">
                        <input type="text" class="form-control rounded-3" id="personelSoyad" name="soyad" placeholder="Soyad" required>
                        <label for="personelSoyad">Soyadı</label>
                    </div>
                </div>
            </div>

            <hr class="text-muted mb-4 opacity-25">
            <h6 class="fw-bold mb-3 text-dark"><i class="bi bi-clock-history me-2"></i>Mesai ve İzin Ayarları</h6>
            
            <div class="row g-3 mb-3">
                <div class="col-6">
                    <div class="form-floating">
                        <input type="time" class="form-control rounded-3" id="mesaiBaslangic" name="mesai_baslangic" value="09:00" required>
                        <label for="mesaiBaslangic">Başlangıç Saati</label>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-floating">
                        <input type="time" class="form-control rounded-3" id="mesaiBitis" name="mesai_bitis" value="19:00" required>
                        <label for="mesaiBitis">Bitiş Saati</label>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small text-muted ms-1 mb-2 fw-semibold">İzinli Olduğu Günler</label>
                <div class="d-flex flex-wrap gap-2">
                    <input type="checkbox" class="btn-check" name="izinli_gunler[]" id="gun1" value="1" autocomplete="off">
                    <label class="btn btn-outline-danger rounded-pill px-3 py-1 fw-semibold" for="gun1">Pzt</label>

                    <input type="checkbox" class="btn-check" name="izinli_gunler[]" id="gun2" value="2" autocomplete="off">
                    <label class="btn btn-outline-danger rounded-pill px-3 py-1 fw-semibold" for="gun2">Sal</label>

                    <input type="checkbox" class="btn-check" name="izinli_gunler[]" id="gun3" value="3" autocomplete="off">
                    <label class="btn btn-outline-danger rounded-pill px-3 py-1 fw-semibold" for="gun3">Çar</label>

                    <input type="checkbox" class="btn-check" name="izinli_gunler[]" id="gun4" value="4" autocomplete="off">
                    <label class="btn btn-outline-danger rounded-pill px-3 py-1 fw-semibold" for="gun4">Per</label>

                    <input type="checkbox" class="btn-check" name="izinli_gunler[]" id="gun5" value="5" autocomplete="off">
                    <label class="btn btn-outline-danger rounded-pill px-3 py-1 fw-semibold" for="gun5">Cum</label>

                    <input type="checkbox" class="btn-check" name="izinli_gunler[]" id="gun6" value="6" autocomplete="off">
                    <label class="btn btn-outline-danger rounded-pill px-3 py-1 fw-semibold" for="gun6">Cmt</label>

                    <input type="checkbox" class="btn-check" name="izinli_gunler[]" id="gun7" value="7" autocomplete="off" checked>
                    <label class="btn btn-outline-danger rounded-pill px-3 py-1 fw-semibold" for="gun7">Paz</label>
                </div>
                <div class="form-text mt-2" style="font-size: 0.75rem;">Seçilen günlerde personel randevu listesinde gözükmeyecektir.</div>
            </div>

            <button type="submit" class="btn btn-primary w-100 fw-bold py-3 rounded-3 mt-2">Personeli ve Saatlerini Kaydet</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../ortak/alt_bilgi.php'; ?>