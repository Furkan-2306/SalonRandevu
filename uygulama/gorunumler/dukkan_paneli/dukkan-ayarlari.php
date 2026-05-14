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

$kullanici_id = $_SESSION['kullanici_id'];


$dukkanSorgu = $db->prepare("
    SELECT d.*, p.telefon 
    FROM dukkanlar d
    LEFT JOIN kullanici_profilleri p ON d.sahip_id = p.kullanici_id
    WHERE d.sahip_id = :sahip_id LIMIT 1
");
$dukkanSorgu->execute(['sahip_id' => $kullanici_id]);
$dukkan = $dukkanSorgu->fetch(PDO::FETCH_ASSOC);

if (!$dukkan) {
    header("Location: /kuafor-randevu/isletme-kayit-adimlari");
    exit;
}

$gercek_dukkan_id = $dukkan['id']; 


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 1. GENEL BİLGİLERİ GÜNCELLE
    if (isset($_POST['genel_guncelle'])) {
        $ad = trim(htmlspecialchars($_POST['ad']));
        $aciklama = trim(htmlspecialchars($_POST['aciklama']));
        $db->prepare("UPDATE dukkanlar SET ad = ?, aciklama = ? WHERE sahip_id = ?")->execute([$ad, $aciklama, $kullanici_id]);
        $_SESSION['mesaj_turu'] = "basari";
        $_SESSION['mesaj'] = "Genel bilgiler güncellendi.";
        header("Location: /kuafor-randevu/uygulama/gorunumler/dukkan_paneli/dukkan-ayarlari.php");
        exit;
    }

    if (isset($_POST['lokasyon_guncelle'])) {
        $sehir = trim(htmlspecialchars($_POST['sehir']));
        $ilce = trim(htmlspecialchars($_POST['ilce']));
        $adres = trim(htmlspecialchars($_POST['adres']));
        $telefon = trim(htmlspecialchars($_POST['telefon'] ?? '')); 
        $enlem = !empty($_POST['enlem']) ? (float)$_POST['enlem'] : null;
        $boylam = !empty($_POST['boylam']) ? (float)$_POST['boylam'] : null;

        $db->prepare("UPDATE dukkanlar SET sehir=?, ilce=?, adres=?, enlem=?, boylam=? WHERE sahip_id=?")->execute([$sehir, $ilce, $adres, $enlem, $boylam, $kullanici_id]);
        if (!empty($telefon)) {
            $db->prepare("UPDATE kullanici_profilleri SET telefon=? WHERE kullanici_id=?")->execute([$telefon, $kullanici_id]);
        }
        $_SESSION['mesaj_turu'] = "basari";
        $_SESSION['mesaj'] = "Lokasyon güncellendi.";
        header("Location: /kuafor-randevu/uygulama/gorunumler/dukkan_paneli/dukkan-ayarlari.php");
        exit;
    }

    if (isset($_POST['foto_yukle'])) {
        $hedef_dizin = $_SERVER['DOCUMENT_ROOT'] . '/kuafor-randevu/yuklemeler/dukkanlar/';
        if (!file_exists($hedef_dizin)) {
            mkdir($hedef_dizin, 0777, true);
        }

        $dosya = $_FILES['dukkan_fotografi'];
        
        if ($dosya['error'] === UPLOAD_ERR_OK) {
            $uzanti = strtolower(pathinfo($dosya['name'], PATHINFO_EXTENSION));
            $izin_verilen_uzantilar = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (in_array($uzanti, $izin_verilen_uzantilar)) {
                $yeni_dosya_adi = uniqid('vitrin_' . $gercek_dukkan_id . '_') . '.' . $uzanti;
                $hedef_yol = $hedef_dizin . $yeni_dosya_adi;
                
                if (move_uploaded_file($dosya['tmp_name'], $hedef_yol)) {
                    $db_kayit_yolu = 'yuklemeler/dukkanlar/' . $yeni_dosya_adi;
                    
                    $db->prepare("INSERT INTO dukkan_gorselleri (dukkan_id, gorsel_yolu, ana_gorsel_mi) VALUES (?, ?, 0)")
                       ->execute([$gercek_dukkan_id, $db_kayit_yolu]);
                       
                    $_SESSION['mesaj_turu'] = "basari";
                    $_SESSION['mesaj'] = "Fotoğraf başarıyla vitrine eklendi.";
                } else {
                    $_SESSION['mesaj_turu'] = "hata";
                    $_SESSION['mesaj'] = "Dosya sunucuya yüklenirken hata oluştu.";
                }
            } else {
                $_SESSION['mesaj_turu'] = "hata";
                $_SESSION['mesaj'] = "Sadece JPG, PNG ve WEBP formatında resim yükleyebilirsiniz.";
            }
        } else {
            $_SESSION['mesaj_turu'] = "hata";
            $_SESSION['mesaj'] = "Lütfen geçerli bir resim seçin.";
        }
        header("Location: /kuafor-randevu/uygulama/gorunumler/dukkan_paneli/dukkan-ayarlari.php#galeri");
        exit;
    }

    if (isset($_POST['foto_sil'])) {
        $silinecek_id = $_POST['foto_id'];
        
        $fotoBul = $db->prepare("SELECT gorsel_yolu FROM dukkan_gorselleri WHERE id = ? AND dukkan_id = ?");
        $fotoBul->execute([$silinecek_id, $gercek_dukkan_id]);
        $silinecekFoto = $fotoBul->fetch();

        if ($silinecekFoto) {
            $fiziksel_yol = $_SERVER['DOCUMENT_ROOT'] . '/kuafor-randevu/' . $silinecekFoto['gorsel_yolu'];
            
            if (file_exists($fiziksel_yol)) {
                unlink($fiziksel_yol); 
            }
            
            $db->prepare("DELETE FROM dukkan_gorselleri WHERE id = ?")->execute([$silinecek_id]);
            
            $_SESSION['mesaj_turu'] = "basari";
            $_SESSION['mesaj'] = "Fotoğraf başarıyla silindi.";
        }
        header("Location: /kuafor-randevu/uygulama/gorunumler/dukkan_paneli/dukkan-ayarlari.php#galeri");
        exit;
    }
}


$d_ad = $dukkan['ad'] ?? '';
$d_aciklama = $dukkan['aciklama'] ?? '';
$d_sehir = $dukkan['sehir'] ?? '';
$d_ilce = $dukkan['ilce'] ?? '';
$d_adres = $dukkan['adres'] ?? '';
$d_telefon = $dukkan['telefon'] ?? '';
$d_enlem = $dukkan['enlem'] ?? '';
$d_boylam = $dukkan['boylam'] ?? '';

// Galerideki Fotoğrafları Çek
$gorsellerSorgu = $db->prepare("SELECT * FROM dukkan_gorselleri WHERE dukkan_id = ? ORDER BY id DESC");
$gorsellerSorgu->execute([$gercek_dukkan_id]);
$gorseller = $gorsellerSorgu->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../ortak/ust_bilgi.php'; 
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

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
                    <div class="bg-dark text-white rounded-circle d-inline-flex align-items-center justify-content-center fw-bold shadow position-relative" style="width: 80px; height: 80px; font-size: 1.5rem;">
                        <i class="bi bi-shop"></i>
                    </div>
                </div>
                <h5 class="fw-bold mb-1"><?= htmlspecialchars($d_ad) ?></h5>
                <p class="text-muted small mb-2"><?= htmlspecialchars($d_ilce . ', ' . $d_sehir) ?></p>
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
                    <a href="/kuafor-randevu/uygulama/gorunumler/dukkan_paneli/dukkan-hizmet-personel.php" class="list-group-item list-group-item-action py-3 fw-semibold text-dark">
                        <i class="bi bi-person-lines-fill me-2"></i> Hizmetler & Personel
                    </a>
                    <a href="/kuafor-randevu/uygulama/gorunumler/dukkan_paneli/dukkan-ayarlari.php" class="list-group-item list-group-item-action py-3 fw-semibold active" style="background-color: var(--luks-koyu); color: var(--luks-altin); border-color: var(--luks-koyu);">
                        <i class="bi bi-gear me-2"></i> Dükkan Ayarları
                    </a>
                    <a href="/kuafor-randevu/uygulama/kontrolculer/cikis.php" class="list-group-item list-group-item-action py-3 fw-semibold text-danger">
                        <i class="bi bi-box-arrow-right me-2"></i> Çıkış Yap
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            
            <h3 class="fw-bold mb-4">Dükkan Ayarları</h3>

            <ul class="nav nav-tabs mb-4 border-bottom-0" id="ayarlarTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold border-0 border-bottom border-3 text-dark px-4" id="genel-tab" data-bs-toggle="tab" data-bs-target="#genel" type="button" role="tab">Genel Bilgiler</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold border-0 text-muted px-4" id="lokasyon-tab" data-bs-toggle="tab" data-bs-target="#lokasyon" type="button" role="tab">Lokasyon & Harita</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold border-0 text-muted px-4" id="galeri-tab" data-bs-toggle="tab" data-bs-target="#galeri" type="button" role="tab">Medya & Galeri</button>
                </li>
            </ul>

            <div class="tab-content" id="ayarlarTabsContent">
                
                <div class="tab-pane fade show active" id="genel" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <form action="" method="POST">
                                <input type="hidden" name="genel_guncelle" value="1">
                                <div class="row g-4 mb-3">
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control rounded-3" id="ayarDukkanAdi" name="ad" value="<?= htmlspecialchars($d_ad) ?>" required>
                                            <label for="ayarDukkanAdi">Dükkan / Salon Adı</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <select class="form-select rounded-3" id="ayarHizmetTuru" name="hizmet_turu">
                                                <option value="erkek" selected>Erkek Kuaförü / Berber</option>
                                            </select>
                                            <label for="ayarHizmetTuru">Hizmet Türü</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-floating mb-4">
                                    <textarea class="form-control rounded-3" id="ayarHakkimizda" name="aciklama" style="height: 150px" required><?= htmlspecialchars($d_aciklama) ?></textarea>
                                    <label for="ayarHakkimizda">Hakkımızda / Açıklama</label>
                                </div>
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary px-5 py-2 fw-bold rounded-pill">Bilgileri Güncelle</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="lokasyon" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <form action="" method="POST">
                                <input type="hidden" name="lokasyon_guncelle" value="1">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <select class="form-select rounded-3" id="sehir" name="sehir" required>
                                                <option value="<?= htmlspecialchars($d_sehir) ?>" selected><?= htmlspecialchars($d_sehir) ?></option>
                                            </select>
                                            <label for="sehir">İl</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <select class="form-select rounded-3" id="ilce" name="ilce" required>
                                                <option value="<?= htmlspecialchars($d_ilce) ?>" selected><?= htmlspecialchars($d_ilce) ?></option>
                                            </select>
                                            <label for="ilce">İlçe</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <input type="tel" class="form-control rounded-3" id="ayarTel" name="telefon" value="<?= htmlspecialchars($d_telefon) ?>" required>
                                            <label for="ayarTel">İletişim Numarası</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-floating mb-4">
                                    <textarea class="form-control rounded-3" id="ayarAdres" name="adres" style="height: 80px" required><?= htmlspecialchars($d_adres) ?></textarea>
                                    <label for="ayarAdres">Açık Adres</label>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label small text-muted ms-1 mb-2 fw-semibold">Haritadan Tam Konumunuzu İşaretleyin</label>
                                    <div id="haritaAlan" style="height: 300px; width: 100%; border-radius: 1rem; z-index: 1; border: 2px solid #dee2e6;"></div>
                                </div>
                                <div class="row g-3 mb-4 d-none">
                                    <div class="col-6"><input type="text" class="form-control" id="enlem_kutu" name="enlem" value="<?= htmlspecialchars($d_enlem) ?>"></div>
                                    <div class="col-6"><input type="text" class="form-control" id="boylam_kutu" name="boylam" value="<?= htmlspecialchars($d_boylam) ?>"></div>
                                </div>

                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary px-5 py-2 fw-bold rounded-pill">Lokasyon Bilgilerini Güncelle</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="galeri" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-3">Vitrin Fotoğrafları</h5>
                            <p class="text-muted small mb-4">Müşterilerinizin dükkan detay sayfasında göreceği fotoğrafları buradan yükleyebilir veya silebilirsiniz.</p>
                            
                            <div class="row g-3 mb-4">
                                
                                <?php foreach($gorseller as $index => $gorsel): ?>
                                    <div class="col-md-4">
                                        <div class="position-relative rounded-4 overflow-hidden border shadow-sm" style="height: 150px;">
                                            <img src="/kuafor-randevu/<?= htmlspecialchars($gorsel['gorsel_yolu']) ?>" class="w-100 h-100" style="object-fit: cover;" alt="Vitrin">
                                            
                                            <form action="" method="POST" class="d-inline" onsubmit="return confirm('Bu fotoğrafı vitrinden kaldırmak istediğinize emin misiniz?');">
                                                <input type="hidden" name="foto_sil" value="1">
                                                <input type="hidden" name="foto_id" value="<?= $gorsel['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2 rounded-circle shadow" title="Sil"><i class="bi bi-trash"></i></button>
                                            </form>
                                            
                                            <?php if($index === 0): ?>
                                                <span class="badge bg-primary position-absolute bottom-0 start-0 m-2 shadow">Kapak Resmi</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <div class="col-md-4">
                                    <form action="" method="POST" enctype="multipart/form-data" id="fotoYukleForm" class="h-100">
                                        <input type="hidden" name="foto_yukle" value="1">
                                        
                                        <label class="w-100 h-100 d-flex flex-column align-items-center justify-content-center border border-2 border-primary border-dashed rounded-4 text-primary bg-light" style="min-height: 150px; cursor: pointer; transition: 0.3s;">
                                            <i class="bi bi-cloud-arrow-up-fill fs-1 mb-1"></i>
                                            <span class="fw-bold">Fotoğraf Seç & Yükle</span>
                                            <input type="file" name="dukkan_fotografi" class="d-none" accept="image/png, image/jpeg, image/webp" onchange="document.getElementById('fotoYukleForm').submit();">
                                        </label>
                                    </form>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>

<script>

    document.addEventListener("DOMContentLoaded", function() {
        let hash = window.location.hash;
        if (hash) {
            let tabElement = document.querySelector(`button[data-bs-target="${hash}"]`);
            if (tabElement) {
                let tab = new bootstrap.Tab(tabElement);
                tab.show();
            }
        }
    });

    let turkiyeVerisi = [];
    const mevcutSehir = "<?= htmlspecialchars($d_sehir) ?>";
    const mevcutIlce = "<?= htmlspecialchars($d_ilce) ?>";

    document.addEventListener("DOMContentLoaded", function() {
        fetch('https://turkiyeapi.dev/api/v1/provinces')
            .then(response => response.json())
            .then(res => {
                turkiyeVerisi = res.data;
                let sehirSelect = document.getElementById("sehir");
                turkiyeVerisi.sort((a, b) => a.name.localeCompare(b.name, 'tr'));
                sehirSelect.innerHTML = '<option value="" disabled>Önce İl Seçiniz...</option>';
                
                turkiyeVerisi.forEach(il => {
                    let isSelected = (il.name === mevcutSehir) ? 'selected' : '';
                    sehirSelect.innerHTML += `<option value="${il.name}" ${isSelected}>${il.name}</option>`;
                });
                ilceDoldur(mevcutSehir, mevcutIlce);
            })
            .catch(error => console.error("API Hatası:", error));
    });

    document.getElementById("sehir").addEventListener("change", function() {
        ilceDoldur(this.value, "");
    });

    function ilceDoldur(secilenIlAdi, secilecekIlceAdi) {
        let ilceSelect = document.getElementById("ilce");
        ilceSelect.innerHTML = '<option value="" disabled>İlçe Seçiniz...</option>';
        let secilenIlVerisi = turkiyeVerisi.find(il => il.name === secilenIlAdi);

        if (secilenIlVerisi && secilenIlVerisi.districts) {
            let ilceler = secilenIlVerisi.districts.sort((a, b) => a.name.localeCompare(b.name, 'tr'));
            ilceler.forEach(ilce => {
                let isSelected = (ilce.name === secilecekIlceAdi) ? 'selected' : '';
                ilceSelect.innerHTML += `<option value="${ilce.name}" ${isSelected}>${ilce.name}</option>`;
            });
        }
    }

    let baslangicEnlem = <?= !empty($d_enlem) ? $d_enlem : 41.0082 ?>;
    let baslangicBoylam = <?= !empty($d_boylam) ? $d_boylam : 28.9784 ?>;

    let map = L.map('haritaAlan').setView([baslangicEnlem, baslangicBoylam], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    let marker = L.marker([baslangicEnlem, baslangicBoylam], {draggable: true}).addTo(map);

    marker.on('dragend', function (e) {
        document.getElementById('enlem_kutu').value = marker.getLatLng().lat.toFixed(6);
        document.getElementById('boylam_kutu').value = marker.getLatLng().lng.toFixed(6);
    });

    map.on('click', function(e) {
        marker.setLatLng(e.latlng);
        document.getElementById('enlem_kutu').value = e.latlng.lat.toFixed(6);
        document.getElementById('boylam_kutu').value = e.latlng.lng.toFixed(6);
    });

    document.getElementById('lokasyon-tab').addEventListener('shown.bs.tab', function (e) {
        setTimeout(function() { map.invalidateSize(); }, 100);
    });
</script>

<?php include __DIR__ . '/../ortak/alt_bilgi.php'; ?>