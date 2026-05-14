<?php 
// 1. GÜVENLİK VE OTURUM KONTROLÜ
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Bütün katı kuralları (rol veya giris_yapildi kontrolünü) kaldırdık. 
// Sadece kullanici_id var mı diye bakıyoruz.
if (empty($_SESSION['kullanici_id'])) {
    header("Location: /kuafor-randevu/giris-kayit");
    exit;
}

// 2. VERİTABANI BAĞLANTISI
require_once __DIR__ . '/../../ayarlar/veritabani.php';
$vt = new Veritabani();
$db = $vt->baglantiGetir();

$musteri_id = $_SESSION['kullanici_id'];

// SOL MENÜ İÇİN KULLANICI BİLGİLERİNİ ÇEKME
$sorgu = $db->prepare("
    SELECT k.eposta, p.ad, p.soyad      
    FROM kullanicilar k 
    LEFT JOIN kullanici_profilleri p ON k.id = p.kullanici_id 
    WHERE k.id = :id
");
$sorgu->execute(['id' => $musteri_id]);
$kullanici = $sorgu->fetch(PDO::FETCH_ASSOC);

$ad = $kullanici['ad'] ?? '';
$soyad = $kullanici['soyad'] ?? '';
$eposta = $kullanici['eposta'] ?? '';

$ilk_harf = !empty($ad) ? mb_substr($ad, 0, 1, 'UTF-8') : 'U';
$soyad_ilk_harf = !empty($soyad) ? mb_substr($soyad, 0, 1, 'UTF-8') : '';
$profil_harfleri = mb_strtoupper($ilk_harf . $soyad_ilk_harf, 'UTF-8');

// ---------------------------------------------------------
// 3. RANDEVULARI VE YORUMLARI VERİTABANINDAN ÇEKME 
// ---------------------------------------------------------

// Türkçe Aylar (Tarih formatlamak için)
$aylar = ['', 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];

// Virgülle ayrılmış hizmet ID'lerini isimlere çeviren yardımcı fonksiyon
function hizmetIsimleriniGetir($db, $hizmet_idler) {
    if (empty($hizmet_idler)) return "Belirtilmemiş";
    $idler = explode(',', $hizmet_idler);
    $soruIsaretleri = str_repeat('?,', count($idler) - 1) . '?';
    $sorgu = $db->prepare("SELECT ad FROM hizmetler WHERE id IN ($soruIsaretleri)");
    $sorgu->execute($idler);
    return implode(' + ', $sorgu->fetchAll(PDO::FETCH_COLUMN));
}

// A) BEKLEYEN RANDEVULAR
$bekleyenSorgu = $db->prepare("
    SELECT r.*, d.ad as dukkan_adi, d.ilce, d.sehir, p.ad as personel_ad, p.soyad as personel_soyad 
    FROM randevular r
    JOIN dukkanlar d ON r.dukkan_id = d.id
    JOIN personeller p ON r.personel_id = p.id
    WHERE r.musteri_id = :m_id AND r.durum IN ('bekliyor', 'onaylandi')
    ORDER BY r.randevu_tarih_saat ASC
");
$bekleyenSorgu->execute(['m_id' => $musteri_id]);
$bekleyenRandevular = $bekleyenSorgu->fetchAll(PDO::FETCH_ASSOC);

// B) GEÇMİŞ VEYA İPTAL EDİLMİŞ RANDEVULAR
$gecmisSorgu = $db->prepare("
    SELECT r.*, d.ad as dukkan_adi, d.ilce, d.sehir, p.ad as personel_ad, p.soyad as personel_soyad 
    FROM randevular r
    JOIN dukkanlar d ON r.dukkan_id = d.id
    JOIN personeller p ON r.personel_id = p.id
    WHERE r.musteri_id = :m_id AND r.durum IN ('tamamlandi', 'iptal')
    ORDER BY r.randevu_tarih_saat DESC
");
$gecmisSorgu->execute(['m_id' => $musteri_id]);
$gecmisRandevular = $gecmisSorgu->fetchAll(PDO::FETCH_ASSOC);

// C) KULLANICININ DAHA ÖNCE YORUM YAPTIĞI DÜKKANLAR (YENİ EKLENDİ)
$yorumSorgu = $db->prepare("SELECT dukkan_id FROM degerlendirmeler WHERE musteri_id = :m_id");
$yorumSorgu->execute(['m_id' => $musteri_id]);
$degerlendirilen_dukkanlar = $yorumSorgu->fetchAll(PDO::FETCH_COLUMN);

include __DIR__ . '/../ortak/ust_bilgi.php'; 
?>

<div class="container py-5 mb-5">
    <div class="row g-4">
        
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 text-center p-4 mb-4">
                <div class="mb-3">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center fw-bold shadow" style="width: 80px; height: 80px; font-size: 2rem;">
                        <?= $profil_harfleri ?>
                    </div>
                </div>
                <h5 class="fw-bold mb-1">
                    <?= !empty($ad) ? htmlspecialchars($ad . ' ' . $soyad) : 'İsimsiz Kullanıcı' ?>
                </h5>
                <p class="text-muted small mb-3"><?= htmlspecialchars($eposta) ?></p>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="list-group list-group-flush">
                    <a href="/kuafor-randevu/musteri-randevularim" class="list-group-item list-group-item-action py-3 fw-semibold active" style="background-color: var(--luks-koyu); color: var(--luks-altin); border-color: var(--luks-koyu);">
                        <i class="bi bi-calendar-check me-2"></i> Randevularım
                    </a>
                    <a href="/kuafor-randevu/uygulama/gorunumler/musteri_paneli/musteri-profil.php" class="list-group-item list-group-item-action py-3 fw-semibold text-dark">
                        <i class="bi bi-person me-2"></i> Müşteri Panelim
                    </a>
                    <a href="/kuafor-randevu/uygulama/kontrolculer/cikis.php" class="list-group-item list-group-item-action py-3 fw-semibold text-danger">
                        <i class="bi bi-box-arrow-right me-2"></i> Çıkış Yap
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            
            <?php if(isset($_SESSION['mesaj'])): ?>
                <div class="alert alert-<?= $_SESSION['mesaj_turu'] == 'basari' ? 'success' : 'danger' ?> alert-dismissible fade show rounded-4 shadow-sm" role="alert">
                    <i class="bi bi-<?= $_SESSION['mesaj_turu'] == 'basari' ? 'check-circle' : 'exclamation-circle' ?>-fill me-2"></i>
                    <?= $_SESSION['mesaj'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['mesaj']); unset($_SESSION['mesaj_turu']); ?>
            <?php endif; ?>

            <h3 class="fw-bold mb-4">Randevularım</h3>

            <ul class="nav nav-tabs mb-4 border-bottom-0" id="randevuTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold border-0 border-bottom border-3 text-dark px-4" id="bekleyen-tab" data-bs-toggle="tab" data-bs-target="#bekleyen" type="button" role="tab">Bekleyen (<?= count($bekleyenRandevular) ?>)</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold border-0 text-muted px-4" id="gecmis-tab" data-bs-toggle="tab" data-bs-target="#gecmis" type="button" role="tab">Geçmiş (<?= count($gecmisRandevular) ?>)</button>
                </li>
            </ul>

            <div class="tab-content" id="randevuTabsContent">
                
                <div class="tab-pane fade show active" id="bekleyen" role="tabpanel">
                    
                    <?php if(count($bekleyenRandevular) > 0): ?>
                        <?php foreach($bekleyenRandevular as $randevu): 
                            $timestamp = strtotime($randevu['randevu_tarih_saat']);
                            $gun = date('d', $timestamp);
                            $ay = $aylar[(int)date('m', $timestamp)];
                            $yil = date('Y', $timestamp);
                            $saat = date('H:i', $timestamp);
                            $hizmetler_metni = hizmetIsimleriniGetir($db, $randevu['hizmet_id']);
                        ?>
                            <div class="card border-0 shadow-sm rounded-4 mb-3 position-relative overflow-hidden">
                                <div class="position-absolute top-0 start-0 w-100" style="height: 4px; background-color: var(--luks-altin);"></div>
                                <div class="card-body p-4">
                                    <div class="row align-items-center">
                                        <div class="col-md-3 text-center border-end border-light d-none d-md-block">
                                            <div class="fs-1 fw-bold text-dark"><?= $gun ?></div>
                                            <div class="text-uppercase text-muted fw-semibold small"><?= $ay . ' ' . $yil ?></div>
                                            <div class="text-primary fw-bold mt-1 fs-5"><?= $saat ?></div>
                                        </div>
                                        <div class="col-md-6 mt-3 mt-md-0 ps-md-4">
                                            
                                            <?php if($randevu['durum'] == 'bekliyor'): ?>
                                                <span class="badge bg-warning text-dark mb-2 px-3 py-2 rounded-pill"><i class="bi bi-hourglass-split"></i> Onay Bekliyor</span>
                                            <?php else: ?>
                                                <span class="badge bg-info text-dark mb-2 px-3 py-2 rounded-pill"><i class="bi bi-calendar-check-fill"></i> Randevu Onaylandı</span>
                                            <?php endif; ?>

                                            <h5 class="fw-bold mb-1"><?= htmlspecialchars($randevu['dukkan_adi']) ?></h5>
                                            <p class="text-muted small mb-2"><i class="bi bi-geo-alt-fill text-danger"></i> <?= htmlspecialchars($randevu['ilce'] . ', ' . $randevu['sehir']) ?></p>
                                            
                                            <div class="text-dark small fw-semibold mb-1">
                                                <i class="bi bi-scissors me-1 text-primary"></i> <?= htmlspecialchars($hizmetler_metni) ?>
                                            </div>
                                            <div class="text-secondary small fw-semibold">
                                                <i class="bi bi-person-badge me-1"></i> Uzman: <?= htmlspecialchars($randevu['personel_ad'] . ' ' . $randevu['personel_soyad']) ?>
                                            </div>
                                        </div>
                                        <div class="col-md-3 text-md-end mt-3 mt-md-0">
                                            <h4 class="fw-bold text-success mb-3"><?= number_format($randevu['toplam_tutar'], 2, ',', '.') ?> ₺</h4>
                                            
                                            <form action="/kuafor-randevu/uygulama/kontrolculer/randevu_kontrol.php" method="POST" onsubmit="return confirm('Bu randevuyu iptal etmek istediğinize emin misiniz?');">
                                                <input type="hidden" name="islem" value="randevu_iptal_musteri">
                                                <input type="hidden" name="randevu_id" value="<?= $randevu['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-4 py-2 fw-semibold">İptal Et</button>
                                            </form>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-5 bg-white rounded-4 shadow-sm">
                            <div class="mb-3"><i class="bi bi-calendar-x text-muted" style="font-size: 4rem;"></i></div>
                            <h5 class="fw-bold text-dark">Bekleyen Randevunuz Yok</h5>
                            <p class="text-muted small">Şu anda yaklaşan veya onay bekleyen bir randevunuz bulunmuyor.</p>
                            <a href="/kuafor-randevu/" class="btn btn-primary rounded-pill px-4 mt-2">Hemen Randevu Al</a>
                        </div>
                    <?php endif; ?>

                </div>

                <div class="tab-pane fade" id="gecmis" role="tabpanel">
                    
                    <?php if(count($gecmisRandevular) > 0): ?>
                        <?php foreach($gecmisRandevular as $randevu): 
                            $timestamp = strtotime($randevu['randevu_tarih_saat']);
                            $gun = date('d', $timestamp);
                            $ay = $aylar[(int)date('m', $timestamp)];
                            $yil = date('Y', $timestamp);
                        ?>
                            <div class="card border-0 shadow-sm rounded-4 mb-3 opacity-75">
                                <div class="card-body p-4">
                                    <div class="row align-items-center">
                                        <div class="col-md-3 text-center border-end border-light d-none d-md-block">
                                            <div class="fs-3 fw-bold text-muted"><?= $gun ?></div>
                                            <div class="text-uppercase text-muted fw-semibold small" style="font-size: 0.7rem;"><?= $ay . ' ' . $yil ?></div>
                                        </div>
                                        <div class="col-md-6 mt-3 mt-md-0 ps-md-4">
                                            <?php if($randevu['durum'] == 'tamamlandi'): ?>
                                                <span class="badge bg-success mb-2"><i class="bi bi-check-circle"></i> Tamamlandı</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger mb-2"><i class="bi bi-x-circle"></i> İptal Edildi</span>
                                            <?php endif; ?>
                                            
                                            <h5 class="fw-bold text-muted mb-1"><?= htmlspecialchars($randevu['dukkan_adi']) ?></h5>
                                            <p class="text-muted small mb-0 fw-semibold"><?= number_format($randevu['toplam_tutar'], 2, ',', '.') ?> ₺</p>
                                        </div>
                                        
                                        <div class="col-md-3 text-md-end mt-3 mt-md-0">
                                            <?php if($randevu['durum'] == 'tamamlandi'): ?>
                                                <?php if(in_array($randevu['dukkan_id'], $degerlendirilen_dukkanlar)): ?>
                                                    <button class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-semibold" disabled>
                                                        <i class="bi bi-check-all me-1" style="font-size: 0.8rem;"></i> Değerlendirildi
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#degerlendirmeModal" onclick="degerlendirmeAyarla(<?= $randevu['id'] ?>, <?= $randevu['dukkan_id'] ?>, '<?= addslashes($randevu['dukkan_adi']) ?>')">
                                                        <i class="bi bi-star-fill me-1" style="font-size: 0.8rem;"></i> Değerlendir
                                                    </button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                        
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-5 bg-white rounded-4 shadow-sm">
                            <div class="mb-3"><i class="bi bi-clock-history text-muted" style="font-size: 4rem;"></i></div>
                            <h5 class="fw-bold text-dark">Geçmiş Randevunuz Yok</h5>
                            <p class="text-muted small">Daha önce tamamlanmış veya iptal edilmiş bir işleminiz bulunmuyor.</p>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="degerlendirmeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow-lg">
      <div class="modal-header border-bottom-0 pb-0 mt-2 mx-2">
        <h5 class="modal-title fw-bold">Deneyiminizi Puanlayın</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
      </div>
      <div class="modal-body py-4 px-4 text-center">
        <p class="text-muted small mb-4"><strong id="modalDukkanAdi">İşletme</strong> işletmesindeki randevunuz nasıldı?</p>
        
        <form action="/kuafor-randevu/uygulama/kontrolculer/degerlendirme_kontrol.php" method="POST">
            <input type="hidden" name="islem" value="puan_ver">
            <input type="hidden" name="randevu_id" id="modalRandevuId">
            <input type="hidden" name="dukkan_id" id="modalDukkanId">
            <input type="hidden" name="puan" id="secilenPuan" value="5">

            <div class="fs-1 text-warning mb-4" id="yildizlar" style="cursor: pointer; letter-spacing: 5px;">
                <i class="bi bi-star-fill" onclick="puanla(1)"></i>
                <i class="bi bi-star-fill" onclick="puanla(2)"></i>
                <i class="bi bi-star-fill" onclick="puanla(3)"></i>
                <i class="bi bi-star-fill" onclick="puanla(4)"></i>
                <i class="bi bi-star-fill" onclick="puanla(5)"></i>
            </div>

            <div class="form-floating mb-4">
                <textarea class="form-control rounded-3" name="yorum" placeholder="Yorumunuzu buraya yazın" id="yorumAlani" style="height: 100px" required></textarea>
                <label for="yorumAlani">Yorumunuz</label>
            </div>
            
            <button type="submit" class="btn btn-primary w-100 fw-bold py-2 rounded-3">Gönder</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
    // JS FONKSİYONU GÜNCELLENDİ: r_id EKLENDİ
    function degerlendirmeAyarla(r_id, d_id, ad) {
        document.getElementById('modalRandevuId').value = r_id;
        document.getElementById('modalDukkanId').value = d_id;
        document.getElementById('modalDukkanAdi').innerText = ad;
        puanla(5); // Varsayılan 5 yıldızla açılsın
    }

    function puanla(deger) {
        document.getElementById('secilenPuan').value = deger;
        let yildizlar = document.getElementById('yildizlar').children;
        for(let i=0; i<5; i++) {
            if(i < deger) {
                yildizlar[i].className = "bi bi-star-fill";
            } else {
                yildizlar[i].className = "bi bi-star";
            }
        }
    }
</script>

<?php include __DIR__ . '/../ortak/alt_bilgi.php'; ?>