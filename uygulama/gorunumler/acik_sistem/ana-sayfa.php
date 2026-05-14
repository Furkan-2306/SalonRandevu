<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../ayarlar/veritabani.php';
$vt = new Veritabani();
$db = $vt->baglantiGetir();

// ==========================================
// ARAMA, FİLTRELEME VE PUAN MANTIĞI
// ==========================================

// Cinsiyet seçimi varsayılanı 'hepsi'
$secili_cinsiyet = $_GET['cinsiyet'] ?? 'hepsi';

$sql = "SELECT d.*, 
            (SELECT gorsel_yolu FROM dukkan_gorselleri WHERE dukkan_id = d.id ORDER BY ana_gorsel_mi DESC, id ASC LIMIT 1) as kapak_resmi,
            COALESCE(AVG(deg.puan), 0) as ortalama_puan,
            COUNT(deg.id) as yorum_sayisi
        FROM dukkanlar d 
        LEFT JOIN degerlendirmeler deg ON d.id = deg.dukkan_id
        WHERE d.aktif_mi = 1";

$parametreler = [];

// 1. Cinsiyet Filtresi
if ($secili_cinsiyet != 'hepsi') {
    // Veritabanındaki 'cinsiyet_tipi' sütununa göre filtreler
    $sql .= " AND d.cinsiyet_tipi = :cinsiyet";
    $parametreler['cinsiyet'] = $secili_cinsiyet;
}

// 2. Metin Araması (Ad veya İlçe)
if (!empty($_GET['arama'])) {
    $sql .= " AND (d.ad LIKE :arama OR d.ilce LIKE :arama)";
    $parametreler['arama'] = '%' . $_GET['arama'] . '%';
}

// 3. Şehir Filtresi
if (!empty($_GET['sehir']) && $_GET['sehir'] != 'Tüm Şehirler') {
    $sql .= " AND d.sehir = :sehir";
    $parametreler['sehir'] = $_GET['sehir'];
}

// 4. İlçe Filtresi
if (!empty($_GET['ilce']) && $_GET['ilce'] != 'Tüm İlçeler') {
    $sql .= " AND d.ilce = :ilce";
    $parametreler['ilce'] = $_GET['ilce'];
}

// Minimum Puan Filtresi (Offcanvas'tan gelirse)
if (!empty($_GET['min_puan']) && $_GET['min_puan'] > 0) {
    // Having kullanmak yerine alt sorgu sonucuyla filtrelemek için GROUP BY sonrası kontrol gerekir
    $sql .= " GROUP BY d.id HAVING ortalama_puan >= :min_puan";
    $parametreler['min_puan'] = $_GET['min_puan'];
} else {
    $sql .= " GROUP BY d.id";
}

// SIRALAMA: En yüksek puanlılar ve en çok yorum alanlar en üstte
$sql .= " ORDER BY ortalama_puan DESC, yorum_sayisi DESC, d.kayit_tarihi DESC";

$sorgu = $db->prepare($sql);
$sorgu->execute($parametreler);
$dukkanlar = $sorgu->fetchAll(PDO::FETCH_ASSOC);

include 'uygulama/gorunumler/ortak/ust_bilgi.php'; 
?>

<section class="bg-primary text-white py-5 text-center" style="background: linear-gradient(135deg, #161819 0%, #2c3035 100%);">
    <div class="container py-4">
        <h1 class="display-5 fw-bold mb-3 text-white">Size En Uygun Salonu Bulun</h1>
        <p class="lead mb-4 opacity-75">Şehrinizdeki en iyi kuaför, berber ve güzellik merkezlerinden anında randevu alın.</p>

        <div class="bg-white p-4 rounded-4 shadow mx-auto text-dark" style="max-width: 900px;">
            <form action="" method="GET" id="anaAramaFormu">
                <div class="row g-3 align-items-center">
                    <div class="col-md-4">
                        <div class="btn-group w-100 shadow-sm" role="group">
                            <input type="radio" class="btn-check" name="cinsiyet" id="hepsi" value="hepsi" <?= ($secili_cinsiyet == 'hepsi') ? 'checked' : '' ?> onchange="this.form.submit()">
                            <label class="btn btn-outline-primary fw-semibold" for="hepsi">Tümü</label>

                            <input type="radio" class="btn-check" name="cinsiyet" id="kadin" value="kadin" <?= ($secili_cinsiyet == 'kadin') ? 'checked' : '' ?> onchange="this.form.submit()">
                            <label class="btn btn-outline-primary fw-semibold" for="kadin">Kadın</label>

                            <input type="radio" class="btn-check" name="cinsiyet" id="erkek" value="erkek" <?= ($secili_cinsiyet == 'erkek') ? 'checked' : '' ?> onchange="this.form.submit()">
                            <label class="btn btn-outline-primary fw-semibold" for="erkek">Erkek</label>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control border-start-0 rounded-end-3" placeholder="Salon adı veya ilçe ara..." name="arama" value="<?= htmlspecialchars($_GET['arama'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100 fw-bold rounded-3 py-2 shadow-sm">Ara</button>
                    </div>
                </div>
                
                <div class="mt-3 d-flex justify-content-between align-items-center">
                    <button class="btn btn-sm btn-link text-decoration-none fw-semibold text-secondary" type="button" data-bs-toggle="offcanvas" data-bs-target="#filtreOffcanvas">
                        <i class="bi bi-sliders2-vertical me-1"></i> Gelişmiş Filtreleme
                    </button>
                    <?php if(!empty($_GET) && count($_GET) > 1 || (isset($_GET['cinsiyet']) && $_GET['cinsiyet'] != 'hepsi')): ?>
                        <a href="/kuafor-randevu/" class="btn btn-sm btn-outline-danger rounded-pill"><i class="bi bi-x-circle"></i> Filtreleri Temizle</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</section>

<section class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold m-0 text-dark">
            <?php 
                if($secili_cinsiyet == 'kadin') echo 'Kadın Salonları';
                elseif($secili_cinsiyet == 'erkek') echo 'Erkek Berberleri';
                else echo 'Önerilen Popüler Salonlar';
            ?>
        </h3>
        <span class="badge bg-light text-dark border fw-semibold px-3 py-2 rounded-pill"><?= count($dukkanlar) ?> işletme listeleniyor</span>
    </div>

    <div class="row g-4">
        <?php if(count($dukkanlar) > 0): ?>
            <?php foreach($dukkanlar as $dukkan): 
                $hizmetSorgu = $db->prepare("SELECT ad FROM hizmetler WHERE dukkan_id = ? LIMIT 2");
                $hizmetSorgu->execute([$dukkan['id']]);
                $hizmetler = $hizmetSorgu->fetchAll(PDO::FETCH_COLUMN);
                $hizmet_metni = !empty($hizmetler) ? implode(', ', $hizmetler) . '...' : 'Hizmetler detay sayfasında.';
            ?>
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 shadow-sm border-0 rounded-4 overflow-hidden position-relative business-card" onclick="location.href='/kuafor-randevu/dukkan-detay?id=<?= $dukkan['id'] ?>'">
                        
                        <div class="position-absolute top-0 end-0 m-3 z-1">
                            <?php if($dukkan['yorum_sayisi'] > 0): ?>
                                <span class="badge bg-white text-dark shadow-sm rounded-pill px-2 py-1">
                                    <i class="bi bi-star-fill text-warning"></i> <?= number_format($dukkan['ortalama_puan'], 1, ',', '.') ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-success shadow-sm rounded-pill px-2 py-1">Yeni</span>
                            <?php endif; ?>
                        </div>

                        <div class="overflow-hidden" style="height: 180px;">
                            <?php if(!empty($dukkan['kapak_resmi'])): ?>
                                <img src="/kuafor-randevu/<?= htmlspecialchars($dukkan['kapak_resmi']) ?>" class="card-img-top h-100 w-100" style="object-fit: cover; transition: transform 0.5s;" alt="Dükkan">
                            <?php else: ?>
                                <div class="bg-light h-100 d-flex align-items-center justify-content-center text-muted">
                                    <i class="bi bi-image fs-1 opacity-25"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="card-body">
                            <h5 class="card-title fw-bold mb-1 text-truncate"><?= htmlspecialchars($dukkan['ad']) ?></h5>
                            <p class="text-muted small mb-2"><i class="bi bi-geo-alt-fill text-danger"></i> <?= htmlspecialchars($dukkan['ilce']) ?></p>
                            <p class="card-text small text-secondary mb-0" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 38px;">
                                <?= htmlspecialchars($hizmet_metni) ?>
                            </p>
                        </div>
                        
                        <div class="card-footer bg-white border-0 pb-3 pt-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-primary fw-bold small"><?= $dukkan['yorum_sayisi'] ?> Değerlendirme</span>
                                <a href="/kuafor-randevu/dukkan-detay?id=<?= $dukkan['id'] ?>" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold">İncele</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <div class="mb-3 opacity-25"><i class="bi bi-search" style="font-size: 5rem;"></i></div>
                <h4 class="fw-bold text-muted">Aradığınız kriterlere uygun salon bulunamadı.</h4>
                <p class="text-secondary">Filtreleri temizleyerek tüm seçenekleri görebilirsiniz.</p>
                <a href="/kuafor-randevu/" class="btn btn-primary rounded-pill px-4">Tümünü Göster</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
    .business-card { transition: all 0.3s ease; }
    .business-card:hover { transform: translateY(-10px); cursor: pointer; box-shadow: 0 15px 30px rgba(0,0,0,0.1) !important; }
    .business-card:hover img { transform: scale(1.1); }
</style>

<div class="offcanvas offcanvas-end" tabindex="-1" id="filtreOffcanvas" aria-labelledby="filtreOffcanvasLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold text-primary" id="filtreOffcanvasLabel"><i class="bi bi-sliders"></i> Gelişmiş Filtreleme</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Kapat"></button>
    </div>
    <div class="offcanvas-body">
        <form action="" method="GET">
            <input type="hidden" name="cinsiyet" value="<?= $secili_cinsiyet ?>">
            <div class="mb-4">
                <label class="form-label fw-semibold">Şehir</label>
                <select class="form-select rounded-3" name="sehir" id="filtreSehir">
                    <option value="Tüm Şehirler">Tüm Şehirler</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">İlçe</label>
                <select class="form-select rounded-3" name="ilce" id="filtreIlce" disabled>
                    <option value="Tüm İlçeler">Tüm İlçeler</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Minimum Puan</label>
                <select class="form-select rounded-3" name="min_puan">
                    <option value="0" selected>Farketmez</option>
                    <option value="4" <?= (isset($_GET['min_puan']) && $_GET['min_puan'] == '4') ? 'selected' : '' ?>>⭐ 4.0 ve üzeri</option>
                    <option value="3" <?= (isset($_GET['min_puan']) && $_GET['min_puan'] == '3') ? 'selected' : '' ?>>⭐ 3.0 ve üzeri</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary w-100 fw-bold mt-2 py-2 rounded-3">Sonuçları Göster</button>
        </form>
    </div>
</div>

<script>
    // ... Mevcut API scriptin aynen kalacak ...
</script>

<?php include 'uygulama/gorunumler/ortak/alt_bilgi.php'; ?>