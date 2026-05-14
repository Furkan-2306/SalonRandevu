<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../ayarlar/veritabani.php';
$vt = new Veritabani();
$db = $vt->baglantiGetir();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /kuafor-randevu/");
    exit;
}

$dukkan_id = (int)$_GET['id'];

$dukkanSorgu = $db->prepare("SELECT * FROM dukkanlar WHERE id = ? AND aktif_mi = 1");
$dukkanSorgu->execute([$dukkan_id]);
$dukkan = $dukkanSorgu->fetch(PDO::FETCH_ASSOC);

if (!$dukkan) {
    echo "<div class='alert alert-danger text-center mt-5'>Bu dükkan bulunamadı veya henüz onaylanmamış.</div>";
    exit;
}

$galeriSorgu = $db->prepare("SELECT gorsel_yolu FROM dukkan_gorselleri WHERE dukkan_id = ? ORDER BY ana_gorsel_mi DESC, id ASC");
$galeriSorgu->execute([$dukkan_id]);
$gorseller = $galeriSorgu->fetchAll(PDO::FETCH_ASSOC);

$hizmetSorgu = $db->prepare("SELECT * FROM hizmetler WHERE dukkan_id = ? ORDER BY fiyat ASC");
$hizmetSorgu->execute([$dukkan_id]);
$hizmetler = $hizmetSorgu->fetchAll(PDO::FETCH_ASSOC);

$personelSorgu = $db->prepare("SELECT * FROM personeller WHERE dukkan_id = ?");
$personelSorgu->execute([$dukkan_id]);
$personeller = $personelSorgu->fetchAll(PDO::FETCH_ASSOC);

// ==========================================
// YORUMLARI VE PUAN ORTALAMASINI ÇEK
// ==========================================
$yorumSorgu = $db->prepare("
    SELECT d.puan, d.yorum, d.tarih, p.ad, p.soyad 
    FROM degerlendirmeler d 
    LEFT JOIN kullanici_profilleri p ON d.musteri_id = p.kullanici_id 
    WHERE d.dukkan_id = ? 
    ORDER BY d.tarih DESC
");
$yorumSorgu->execute([$dukkan_id]);
$yorumlar = $yorumSorgu->fetchAll(PDO::FETCH_ASSOC);

$toplam_yorum = count($yorumlar);
$ortalama_puan = 0;
if ($toplam_yorum > 0) {
    $toplam_puan = array_sum(array_column($yorumlar, 'puan'));
    $ortalama_puan = round($toplam_puan / $toplam_yorum, 1);
}

include 'uygulama/gorunumler/ortak/ust_bilgi.php'; 
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
    .modal-stepper-wrapper { position: relative; display: flex; justify-content: space-between; align-items: center; margin: 0 15px 30px 15px; }
    .modal-stepper-line { position: absolute; top: 50%; left: 0; right: 0; height: 4px; background: #e9ecef; transform: translateY(-50%); z-index: 1; }
    .modal-stepper-fill { position: absolute; top: 50%; left: 0; height: 4px; background: #c89f65; transform: translateY(-50%); z-index: 2; transition: width 0.4s ease; }
    .modal-step-item { width: 45px; height: 45px; background-color: #ffffff !important; border: 4px solid #e9ecef; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #adb5bd; z-index: 3 !important; position: relative; transition: all 0.3s; }
    .modal-step-item.active { border-color: #c89f65 !important; background-color: #c89f65 !important; color: #ffffff !important; box-shadow: 0 0 0 5px rgba(200, 159, 101, 0.2); }
    .modal-step-item.completed { border-color: #c89f65 !important; color: #c89f65 !important; background-color: #ffffff !important; }
</style>

<div class="bg-light py-2 border-bottom">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="/kuafor-randevu/" class="text-decoration-none">Ana Sayfa</a></li>
                <li class="breadcrumb-item"><a href="#" class="text-decoration-none text-muted"><?= htmlspecialchars($dukkan['sehir']) ?></a></li>
                <li class="breadcrumb-item"><a href="#" class="text-decoration-none text-muted"><?= htmlspecialchars($dukkan['ilce']) ?></a></li>
                <li class="breadcrumb-item active fw-semibold" aria-current="page"><?= htmlspecialchars($dukkan['ad']) ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="container py-4">
    <div class="row g-4">
        
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="fw-bold mb-1"><?= htmlspecialchars($dukkan['ad']) ?></h2>
                    <p class="text-muted mb-0"><i class="bi bi-geo-alt-fill text-danger"></i> <?= htmlspecialchars($dukkan['ilce'] . ', ' . $dukkan['sehir']) ?></p>
                </div>
                <div class="text-end">
                    <?php if($toplam_yorum > 0): ?>
                        <span class="badge bg-white text-dark border border-warning rounded-pill fs-6 px-3 py-2 shadow-sm">
                            <i class="bi bi-star-fill text-warning me-1"></i> <?= number_format($ortalama_puan, 1, ',', '.') ?> (<?= $toplam_yorum ?>)
                        </span>
                    <?php else: ?>
                        <span class="badge bg-success rounded-pill fs-6 px-3 py-2 shadow-sm">⭐ Yeni İşletme</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if(count($gorseller) > 0): ?>
                <div id="dukkanGaleri" class="carousel slide shadow-sm rounded-4 overflow-hidden mb-4" data-bs-ride="carousel">
                    <div class="carousel-indicators">
                        <?php foreach($gorseller as $index => $gorsel): ?>
                            <button type="button" data-bs-target="#dukkanGaleri" data-bs-slide-to="<?= $index ?>" class="<?= $index === 0 ? 'active' : '' ?>"></button>
                        <?php endforeach; ?>
                    </div>
                    <div class="carousel-inner">
                        <?php foreach($gorseller as $index => $gorsel): ?>
                            <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                <img src="/kuafor-randevu/<?= htmlspecialchars($gorsel['gorsel_yolu']) ?>" class="d-block w-100" style="height: 400px; object-fit: cover;">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if(count($gorseller) > 1): ?>
                        <button class="carousel-control-prev" type="button" data-bs-target="#dukkanGaleri" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
                        <button class="carousel-control-next" type="button" data-bs-target="#dukkanGaleri" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="rounded-4 overflow-hidden shadow-sm mb-4 bg-secondary d-flex align-items-center justify-content-center text-white" style="height: 300px;">
                    <i class="bi bi-camera fs-1 me-2"></i> Görsel Eklenmemiş
                </div>
            <?php endif; ?>

            <h4 class="fw-bold mb-3 mt-5">Hakkımızda</h4>
            <p class="text-muted lh-lg"><?= nl2br(htmlspecialchars($dukkan['aciklama'])) ?></p>

            <hr class="my-5">

            <h4 class="fw-bold mb-3">Konum & İletişim</h4>
            <p class="text-muted mb-3"><i class="bi bi-geo-alt-fill text-danger me-1"></i> <?= htmlspecialchars($dukkan['adres']) ?></p>
            <?php if(!empty($dukkan['enlem'])): ?>
                <div id="haritaAlan" class="rounded-4 overflow-hidden shadow-sm mb-4" style="height: 300px; border: 1px solid #ccc; z-index:1;"></div>
            <?php endif; ?>

            <hr class="my-5">
            <h4 class="fw-bold mb-4">Müşteri Değerlendirmeleri (<?= $toplam_yorum ?>)</h4>
            
            <?php if($toplam_yorum > 0): ?>
                <div class="d-flex align-items-center mb-4 bg-white p-4 rounded-4 shadow-sm border">
                    <h1 class="fw-bold text-dark mb-0 me-4" style="font-size: 3.5rem;"><?= number_format($ortalama_puan, 1, ',', '.') ?></h1>
                    <div>
                        <div class="text-warning fs-4 mb-1">
                            <?php for($i=1; $i<=5; $i++): ?>
                                <i class="bi bi-star<?= $i <= round($ortalama_puan) ? '-fill' : '' ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <span class="text-muted small">Gerçek müşteriler tarafından yapılan <b><?= $toplam_yorum ?></b> değerlendirme baz alınmıştır.</span>
                    </div>
                </div>

                <div class="list-group list-group-flush">
                    <?php foreach($yorumlar as $y): ?>
                        <div class="list-group-item bg-transparent px-0 py-4 border-bottom">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="fw-bold text-dark d-flex align-items-center">
                                    <div class="bg-light text-primary rounded-circle d-inline-flex align-items-center justify-content-center fw-bold me-3 border" style="width: 45px; height: 45px; font-size: 1rem;">
                                        <?= mb_strtoupper(mb_substr($y['ad'] ?? 'U', 0, 1) . mb_substr($y['soyad'] ?? '', 0, 1), 'UTF-8') ?>
                                    </div>
                                    <?= htmlspecialchars(($y['ad'] ?? 'İsimsiz') . ' ' . ($y['soyad'] ?? 'Kullanıcı')) ?>
                                </div>
                                <div class="text-muted small"><i class="bi bi-calendar3 me-1"></i> <?= date('d.m.Y', strtotime($y['tarih'])) ?></div>
                            </div>
                            <div class="text-warning small mb-3 ps-5 ms-2">
                                <?php for($i=1; $i<=5; $i++): ?>
                                    <i class="bi bi-star<?= $i <= $y['puan'] ? '-fill' : '' ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <?php if(!empty($y['yorum'])): ?>
                                <p class="text-muted small mb-0 ps-5 ms-2" style="line-height: 1.6; font-style: italic;">"<?= nl2br(htmlspecialchars($y['yorum'])) ?>"</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-light border rounded-4 text-center py-5">
                    <i class="bi bi-star text-muted fs-1 d-block mb-3"></i>
                    <span class="text-muted fw-semibold d-block">Bu işletme için henüz bir değerlendirme yapılmamış.</span>
                    <span class="text-muted small">Hizmet aldıktan sonra ilk değerlendiren siz olun!</span>
                </div>
            <?php endif; ?>

        </div>

        <div class="col-lg-4">
            <div class="sticky-top" style="top: 20px; z-index: 1020;">
                <div class="card shadow border-0 rounded-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold text-center mb-4 pb-2 border-bottom">Hizmetler & Fiyatlar</h5>
                        <?php if(count($hizmetler) > 0): ?>
                            <div class="accordion accordion-flush mb-4" id="hizmetlerAccordion">
                                <div class="accordion-item border-0">
                                    <h2 class="accordion-header"><button class="accordion-button fw-semibold bg-light rounded-3 mb-2" type="button">Sunulan Tüm Hizmetler</button></h2>
                                    <div class="accordion-collapse collapse show">
                                        <div class="accordion-body p-2 pt-0 pb-3">
                                            <?php foreach($hizmetler as $hizmet): ?>
                                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                                    <div>
                                                        <span class="d-block small fw-bold text-dark"><?= htmlspecialchars($hizmet['ad']) ?></span>
                                                        <span class="text-muted small"><i class="bi bi-clock"></i> <?= $hizmet['sure_dakika'] ?> Dk</span>
                                                    </div>
                                                    <span class="fw-bold text-primary"><?= number_format($hizmet['fiyat'], 2, ',', '.') ?> ₺</span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php if(isset($_SESSION['giris_yapildi'])): ?>
                                <button type="button" class="btn btn-primary w-100 py-3 fw-bold fs-5 rounded-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#randevuAlModal">Hemen Randevu Al</button>
                            <?php else: ?>
                                <button type="button" class="btn btn-primary w-100 py-3 fw-bold fs-5 rounded-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#girisUyarisiModal">Hemen Randevu Al</button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="girisUyarisiModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow-lg">
      <div class="modal-header border-bottom-0 pb-0 mt-2 mx-2">
        <h5 class="modal-title fw-bold text-dark">Randevu Sistemi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body py-4 text-center">
        <div class="mb-3" style="font-size: 3rem;">📅</div>
        <h5 class="fw-semibold mb-3">Randevu oluşturmak için giriş yapmalısınız.</h5>
        <div class="d-grid gap-2 mt-4">
            <a href="/kuafor-randevu/giris-kayit" class="btn btn-primary fw-bold py-2 rounded-3">Giriş Yap / Kayıt Ol</a>
            <button type="button" class="btn btn-light fw-semibold rounded-3" data-bs-dismiss="modal">İptal</button>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="randevuAlModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
      <div class="modal-header bg-primary text-white py-3 px-4">
        <h5 class="modal-title fw-bold mb-0 text-white"><i class="bi bi-calendar-plus-fill me-2"></i>Randevu Sihirbazı</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      
      <form action="/kuafor-randevu/uygulama/kontrolculer/randevu_kontrol.php" method="POST" id="randevuFormu">
          <input type="hidden" name="islem" value="randevu_olustur">
          <input type="hidden" name="dukkan_id" value="<?= $dukkan_id ?>">
          <input type="hidden" name="hizmet_id" id="secili_hizmet_id" required>
          <input type="hidden" name="personel_id" id="secili_personel_id" required>
          <input type="hidden" name="randevu_tarihi" id="secili_tarih" required>
          <input type="hidden" name="randevu_saati" id="secili_saat" required>
          <input type="hidden" name="toplam_tutar" id="secili_tutar" required>
          <input type="hidden" name="toplam_sure" id="secili_sure" required>

          <div class="modal-body p-4">
            
            <div class="modal-stepper-wrapper">
                <div class="modal-stepper-line"></div>
                <div class="modal-stepper-fill" id="stepperFill" style="width: 0%;"></div>
                <div class="modal-step-item active" id="stepDot-1">1</div>
                <div class="modal-step-item" id="stepDot-2">2</div>
                <div class="modal-step-item" id="stepDot-3">3</div>
                <div class="modal-step-item" id="stepDot-4">4</div>
            </div>

            <div class="step-content" id="step-1">
                <h5 class="fw-bold mb-3"><i class="bi bi-check2-circle text-primary me-2"></i>Hangi işlemleri yaptırmak istersiniz?</h5>
                <div class="alert alert-info small py-2 mb-3">Maksimum 60 dakika sınırı bulunmaktadır.</div>
                <div class="list-group mb-3">
                    <?php foreach($hizmetler as $hizmet): ?>
                        <label class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 rounded-3 border-2 mb-2">
                            <div>
                                <input class="form-check-input me-2 hizmet-checkbox" type="checkbox" value="<?= $hizmet['id'] ?>" 
                                       data-sure="<?= $hizmet['sure_dakika'] ?>" data-fiyat="<?= $hizmet['fiyat'] ?>" 
                                       data-ad="<?= htmlspecialchars($hizmet['ad']) ?>" onchange="hizmetSec(this)">
                                <span class="fw-bold"><?= htmlspecialchars($hizmet['ad']) ?></span>
                                <div class="small text-muted ms-4"><?= $hizmet['sure_dakika'] ?> Dakika</div>
                            </div>
                            <span class="fw-bold text-primary"><?= number_format($hizmet['fiyat'], 0, ',', '.') ?> ₺</span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div class="bg-light p-3 rounded-3 d-flex justify-content-between fw-bold">
                    <span>Toplam Seçilen:</span>
                    <span class="text-primary"><span id="toplamSureGosterge">0</span> / 60 Dakika</span>
                </div>
            </div>

            <div class="step-content d-none" id="step-2">
                <h5 class="fw-bold mb-3"><i class="bi bi-person-check text-primary me-2"></i>Hangi personelden hizmet alacaksınız?</h5>
                <div class="row g-3">
                    <?php foreach($personeller as $personel): ?>
                        <div class="col-md-6">
                            <div class="card border-2 rounded-4 cursor-pointer personel-card h-100 p-2" onclick="personelSec(<?= $personel['id'] ?>, '<?= addslashes($personel['ad'].' '.$personel['soyad']) ?>')">
                                <div class="card-body text-center">
                                    <div class="bg-light text-primary rounded-circle d-inline-flex align-items-center justify-content-center fw-bold mb-3" style="width: 50px; height: 50px;">
                                        <?= mb_strtoupper(mb_substr($personel['ad'], 0, 1).mb_substr($personel['soyad'], 0, 1)) ?>
                                    </div>
                                    <h6 class="fw-bold mb-0"><?= htmlspecialchars($personel['ad'].' '.$personel['soyad']) ?></h6>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="step-content d-none" id="step-3">
                <h5 class="fw-bold mb-3"><i class="bi bi-clock text-primary me-2"></i>Uygun bir zaman seçin</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-12">
                        <label class="form-label small fw-bold text-muted">Randevu Tarihi</label>
                        <input type="date" class="form-control rounded-3 py-2 border-2" id="randevuTarihi" min="<?= date('Y-m-d') ?>" onchange="saatleriUret()">
                    </div>
                </div>
                <div id="saatGrid" class="row g-2">
                    <div class="col-12 text-center text-muted py-4">Lütfen önce tarih seçin.</div>
                </div>
            </div>

            <div class="step-content d-none" id="step-4">
                <h5 class="fw-bold mb-4 text-center">Randevu Özeti</h5>
                <div class="card bg-light border-0 rounded-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between mb-2"><span>Hizmetler:</span><span class="fw-bold text-end" id="ozetHizmet"></span></div>
                        <div class="d-flex justify-content-between mb-2"><span>Personel:</span><span class="fw-bold" id="ozetPersonel"></span></div>
                        <div class="d-flex justify-content-between mb-2"><span>Zaman:</span><span class="fw-bold text-primary" id="ozetTarihSaat"></span></div>
                        <hr>
                        <div class="d-flex justify-content-between"><span>Toplam Tutar:</span><span class="fw-bold fs-5 text-success" id="ozetTutar"></span></div>
                    </div>
                </div>
            </div>

          </div>
          <div class="modal-footer bg-light border-top-0 py-3 px-4">
            <button type="button" class="btn btn-secondary fw-bold rounded-pill px-4 d-none" id="btnGeri" onclick="adimDegistir(-1)">Geri</button>
            <button type="button" class="btn btn-primary fw-bold rounded-pill px-5 ms-auto" id="btnIleri" onclick="adimDegistir(1)" disabled>İleri</button>
            <button type="submit" class="btn btn-success fw-bold rounded-pill px-5 d-none" id="btnOnayla">Randevuyu Onayla</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script>
    let mevcutAdim = 1;
    let randevuData = { hizmet_idler: [], hizmet_adlari: [], fiyat: 0, sure: 0, personel_ad: '', tarih: '', saat: '' };

    function hizmetSec(checkbox) {
        let id = checkbox.value;
        let sure = parseInt(checkbox.getAttribute('data-sure'));
        let fiyat = parseFloat(checkbox.getAttribute('data-fiyat'));
        let ad = checkbox.getAttribute('data-ad');

        if (checkbox.checked) {
            if (randevuData.sure + sure > 60) {
                alert("Uyarı: Toplam işlem süresi maksimum 60 dakika olabilir!");
                checkbox.checked = false;
                return;
            }
            randevuData.hizmet_idler.push(id);
            randevuData.hizmet_adlari.push(ad);
            randevuData.sure += sure;
            randevuData.fiyat += fiyat;
        } else {
            randevuData.hizmet_idler = randevuData.hizmet_idler.filter(i => i !== id);
            randevuData.hizmet_adlari = randevuData.hizmet_adlari.filter(i => i !== ad);
            randevuData.sure -= sure;
            randevuData.fiyat -= fiyat;
        }

        document.getElementById('toplamSureGosterge').innerText = randevuData.sure;
        document.getElementById('secili_hizmet_id').value = randevuData.hizmet_idler.join(',');
        document.getElementById('secili_tutar').value = randevuData.fiyat;
        document.getElementById('secili_sure').value = randevuData.sure;
        document.getElementById('btnIleri').disabled = randevuData.hizmet_idler.length === 0;
    }

    function personelSec(id, ad) {
        document.getElementById('secili_personel_id').value = id;
        randevuData.personel_ad = ad;
        document.querySelectorAll('.personel-card').forEach(el => el.classList.remove('border-primary', 'bg-light'));
        event.currentTarget.classList.add('border-primary', 'bg-light');
        document.getElementById('btnIleri').disabled = false;
    }

    function saatSec(saat) {
        document.getElementById('secili_saat').value = saat;
        randevuData.saat = saat;
        document.getElementById('btnIleri').disabled = false;
    }

    async function saatleriUret() {
        let tarih = document.getElementById('randevuTarihi').value;
        let p_id = document.getElementById('secili_personel_id').value;
        if(!tarih || !p_id) return;
        
        document.getElementById('secili_tarih').value = tarih;
        randevuData.tarih = tarih;
        let grid = document.getElementById('saatGrid');
        grid.innerHTML = '<div class="col-12 text-center py-4"><div class="spinner-border text-primary spinner-border-sm"></div></div>';
        
        try {
            const res = await fetch(`/kuafor-randevu/uygulama/kontrolculer/get_dolu_saatler.php?personel_id=${p_id}&tarih=${tarih}`);
            const dolu = await res.json();
            grid.innerHTML = '';
            for(let i = 9; i < 19; i++) {
                [i+":00", i+":30"].forEach(s => {
                    let saat = s.length == 4 ? "0"+s : s;
                    let tam = tarih + " " + saat + ":00";
                    let durum = "musait";
                    dolu.forEach(r => { if(tam >= r.baslangic && tam < r.bitis) durum = r.durum; });

                    let btnCls = "btn-outline-primary", dis = "", stil = "", txt = saat;
                    if(durum === 'bekliyor') { btnCls = "btn-warning text-dark border-warning"; dis = "disabled"; stil="opacity:0.6"; txt += "<br><small style='font-size:9px'>İşlemde</small>"; }
                    else if(durum === 'onaylandi') { btnCls = "btn-danger border-danger"; dis = "disabled"; stil="opacity:0.6"; txt += "<br><small style='font-size:9px'>Dolu</small>"; }

                    grid.innerHTML += `<div class="col-3"><input type="radio" class="btn-check" name="saat_radio" id="s_${saat}" onchange="saatSec('${saat}')" ${dis}>
                        <label class="btn ${btnCls} w-100 rounded-3 fw-bold py-2" for="s_${saat}" style="${stil}">${txt}</label></div>`;
                });
            }
        } catch (e) { grid.innerHTML = 'Hata oluştu.'; }
    }

    function adimDegistir(yon) {
        document.getElementById('step-' + mevcutAdim).classList.add('d-none');
        mevcutAdim += yon;
        document.getElementById('step-' + mevcutAdim).classList.remove('d-none');

        document.getElementById('stepperFill').style.width = ((mevcutAdim - 1) / 3) * 100 + '%';
        for(let i=1; i<=4; i++) {
            let dot = document.getElementById('stepDot-' + i);
            dot.classList.remove('active', 'completed');
            if(i < mevcutAdim) dot.classList.add('completed');
            if(i === mevcutAdim) dot.classList.add('active');
        }

        document.getElementById('btnGeri').classList.toggle('d-none', mevcutAdim === 1);
        if (mevcutAdim === 4) {
            document.getElementById('btnIleri').classList.add('d-none');
            document.getElementById('btnOnayla').classList.remove('d-none');
            document.getElementById('ozetHizmet').innerHTML = randevuData.hizmet_adlari.join('<br>');
            document.getElementById('ozetPersonel').innerText = randevuData.personel_ad;
            let parcalar = randevuData.tarih.split('-');
            document.getElementById('ozetTarihSaat').innerText = parcalar[2] + '.' + parcalar[1] + '.' + parcalar[0] + " / " + randevuData.saat;
            document.getElementById('ozetTutar').innerText = randevuData.fiyat.toLocaleString('tr-TR') + " ₺";
        } else {
            document.getElementById('btnIleri').classList.remove('d-none');
            document.getElementById('btnOnayla').classList.add('d-none');
            document.getElementById('btnIleri').disabled = true;
        }
    }
</script>

<?php if(!empty($dukkan['enlem'])): ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        let lat = <?= $dukkan['enlem'] ?>, lng = <?= $dukkan['boylam'] ?>;
        let map = L.map('haritaAlan').setView([lat, lng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        L.marker([lat, lng]).addTo(map).bindPopup('<b><?= htmlspecialchars($dukkan['ad']) ?></b>').openPopup();
    });
</script>
<?php endif; ?>

<?php include 'uygulama/gorunumler/ortak/alt_bilgi.php'; ?>