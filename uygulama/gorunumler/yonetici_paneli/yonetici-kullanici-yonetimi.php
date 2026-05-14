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
// KULLANICI MÜDAHALE (POST) İŞLEMLERİ
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['islem'])) {
    $hedef_id = (int)$_POST['kullanici_id'];
    $islem = $_POST['islem'];
    $sebep = trim($_POST['ceza_sebebi'] ?? '');

    // Önce kullanıcının mailini ve adını çekelim (Silindikten sonra ulaşamayız)
    $uSorgu = $db->prepare("SELECT k.eposta, kp.ad, kp.soyad FROM kullanicilar k LEFT JOIN kullanici_profilleri kp ON k.id = kp.kullanici_id WHERE k.id = ?");
    $uSorgu->execute([$hedef_id]);
    $kullanici_verisi = $uSorgu->fetch(PDO::FETCH_ASSOC);

    if ($kullanici_verisi) {
        $alici_eposta = $kullanici_verisi['eposta'];
        $alici_ad = (!empty($kullanici_verisi['ad'])) ? $kullanici_verisi['ad'] . ' ' . $kullanici_verisi['soyad'] : 'Sayın Kullanıcımız';

        require_once __DIR__ . '/../../ayarlar/mail_islemleri.php';
        $mailIslemi = new MailIslemleri();

        if ($islem == 'askiya_al') {
            $db->prepare("UPDATE kullanicilar SET aktif_mi = 0 WHERE id = ?")->execute([$hedef_id]);
            $mailIslemi->hesapAskiMailiGonder($alici_eposta, $alici_ad, $sebep);
            $_SESSION['mesaj'] = "Kullanıcı askıya alındı ve mail gönderildi.";
            $_SESSION['mesaj_turu'] = "basari";

        } elseif ($islem == 'kilidi_ac') {
            $db->prepare("UPDATE kullanicilar SET aktif_mi = 1 WHERE id = ?")->execute([$hedef_id]);
            $mailIslemi->hesapAktifMailiGonder($alici_eposta, $alici_ad);
            $_SESSION['mesaj'] = "Kullanıcı kilidi açıldı ve mail gönderildi.";
            $_SESSION['mesaj_turu'] = "basari";

        } elseif ($islem == 'kalici_sil') {
            $db->prepare("DELETE FROM kullanicilar WHERE id = ?")->execute([$hedef_id]);
            $mailIslemi->hesapSilindiMailiGonder($alici_eposta, $alici_ad, $sebep);
            $_SESSION['mesaj'] = "Kullanıcı silindi ve bilgilendirme maili gönderildi.";
            $_SESSION['mesaj_turu'] = "basari";
        }
    }
    
    header("Location: /kuafor-randevu/yonetici-kullanici-yonetimi");
    exit;
}

// ... Listeleme sorguları aynı kalacak ...
// (Buradan aşağısı senin mevcut listeleme ve HTML kodlarınla devam edecek)

// ==========================================
// ARAMA, FİLTRELEME VE KULLANICILARI ÇEKME
// ==========================================
$rol_filtre = $_GET['rol_filtre'] ?? 'tumu';
$arama = trim($_GET['arama'] ?? '');

// Adminleri listede göstermiyoruz (Kendi kendimizi silmeyelim diye)
$sql = "SELECT k.id, k.eposta, k.rol, k.kayit_tarihi, COALESCE(k.aktif_mi, 1) as aktif_mi, 
               p.ad, p.soyad, p.telefon 
        FROM kullanicilar k 
        LEFT JOIN kullanici_profilleri p ON k.id = p.kullanici_id 
        WHERE k.rol != 'admin'"; 
$params = [];

if ($rol_filtre == 'musteri') {
    $sql .= " AND k.rol = 'musteri'";
} elseif ($rol_filtre == 'isletme') {
    $sql .= " AND k.rol = 'dukkan_sahibi'";
}

if (!empty($arama)) {
    $sql .= " AND (p.ad LIKE ? OR p.soyad LIKE ? OR k.eposta LIKE ? OR p.telefon LIKE ?)";
    $aramaParam = "%$arama%";
    array_push($params, $aramaParam, $aramaParam, $aramaParam, $aramaParam);
}

$sql .= " ORDER BY k.kayit_tarihi DESC";

$sorgu = $db->prepare($sql);
$sorgu->execute($params);
$kullanicilar = $sorgu->fetchAll(PDO::FETCH_ASSOC);

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
                    <a href="/kuafor-randevu/yonetici-kullanici-yonetimi" class="list-group-item list-group-item-action py-3 fw-semibold active border-0" style="background-color: var(--luks-altin); color: var(--luks-koyu);">
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
            
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <h3 class="fw-bold m-0 text-dark">Kullanıcı Yönetimi</h3>
                    <p class="text-muted small mt-1 mb-0">Sistemdeki tüm müşteri ve işletme hesaplarını yönetin.</p>
                </div>
                
                <form method="GET" class="d-flex gap-2 w-100" style="max-width: 500px;">
                    <select name="rol_filtre" class="form-select rounded-3 border-secondary-subtle" style="width: 140px;" onchange="this.form.submit()">
                        <option value="tumu" <?= $rol_filtre == 'tumu' ? 'selected' : '' ?>>Tümü</option>
                        <option value="musteri" <?= $rol_filtre == 'musteri' ? 'selected' : '' ?>>Müşteriler</option>
                        <option value="isletme" <?= $rol_filtre == 'isletme' ? 'selected' : '' ?>>İşletmeler</option>
                    </select>
                    <div class="input-group">
                        <input type="text" name="arama" class="form-control rounded-start-3 border-secondary-subtle" placeholder="İsim, Email veya Tel..." value="<?= htmlspecialchars($arama) ?>">
                        <button class="btn btn-primary rounded-end-3" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                </form>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted small">
                                <tr>
                                    <th class="ps-4 py-3">Kullanıcı Bilgisi</th>
                                    <th class="py-3">Rol / Hesap Tipi</th>
                                    <th class="py-3">Kayıt Tarihi</th>
                                    <th class="py-3">Durum</th>
                                    <th class="text-end pe-4 py-3">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($kullanicilar) > 0): ?>
                                    <?php foreach($kullanicilar as $k): 
                                        $adSoyad = (!empty($k['ad'])) ? $k['ad'] . ' ' . $k['soyad'] : 'İsimsiz Kullanıcı';
                                    ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold <?= $k['aktif_mi'] == 0 ? 'text-decoration-line-through text-muted' : 'text-dark' ?>"><?= htmlspecialchars($adSoyad) ?></div>
                                                <div class="text-muted small"><?= htmlspecialchars($k['eposta']) ?></div>
                                            </td>
                                            <td>
                                                <?php if($k['rol'] == 'dukkan_sahibi'): ?>
                                                    <span class="badge" style="background-color: var(--luks-altin); color: var(--luks-koyu);"><i class="bi bi-shop me-1"></i> İşletme Sahibi</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary rounded-pill px-3">Müşteri</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="text-dark small fw-semibold"><?= date('d M Y', strtotime($k['kayit_tarihi'])) ?></div>
                                            </td>
                                            <td>
                                                <?php if($k['aktif_mi'] == 1): ?>
                                                    <span class="badge bg-success border border-success">Aktif</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger border border-danger">Askıya Alındı</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end pe-4">
                                                <?php if($k['aktif_mi'] == 1): ?>
                                                    <button class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-semibold" title="Müdahale Et" data-bs-toggle="modal" data-bs-target="#islemModal" onclick="kullaniciAyarla(<?= $k['id'] ?>, '<?= addslashes($adSoyad) ?>', '<?= addslashes($k['eposta']) ?>', '<?= $k['rol'] ?>')">
                                                        <i class="bi bi-shield-slash"></i> Müdahale Et
                                                    </button>
                                                <?php else: ?>
                                                    <form action="" method="POST" class="d-inline">
                                                        <input type="hidden" name="islem" value="kilidi_ac">
                                                        <input type="hidden" name="kullanici_id" value="<?= $k['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-success rounded-pill px-3 fw-semibold" title="Kilidi Aç">
                                                            <i class="bi bi-unlock"></i> Kilidi Aç
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="bi bi-search fs-2 mb-2 d-block opacity-50"></i>
                                            Arama veya filtrenize uygun kullanıcı bulunamadı.
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

<div class="modal fade" id="islemModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow-lg">
      <div class="modal-header bg-danger text-white border-bottom-0 pb-3 rounded-top-4">
        <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-octagon-fill me-2"></i>Hesap Müdahalesi</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Kapat"></button>
      </div>
      <div class="modal-body py-4 px-4">
        <div class="mb-4">
            <h6 class="fw-bold text-dark mb-1" id="modalAd">Kullanıcı Adı</h6>
            <p class="text-muted small"><span id="modalRol">Rol</span> • <span id="modalEmail">Email</span></p>
        </div>

        <form action="" method="POST" id="mudahaleFormu">
            <input type="hidden" name="kullanici_id" id="modalKullaniciId" value="">
            
            <div class="form-floating mb-3">
                <select class="form-select rounded-3 border-danger" name="islem" id="cezaTuru" required>
                    <option value="askiya_al" selected>Geçici Olarak Askıya Al (Giriş Yapamaz)</option>
                    <option value="kalici_sil">Hesabı Tamamen Sil (Tüm Veriler Gider)</option>
                </select>
                <label for="cezaTuru">Uygulanacak İşlem</label>
            </div>
            
            <div class="form-floating mb-4">
                <textarea class="form-control rounded-3" id="cezaSebebi" style="height: 100px" placeholder="Sebep"></textarea>
                <label for="cezaSebebi">İşlem Sebebi (Yönetici Notu)</label>
            </div>
            
            <div class="alert alert-warning small py-2 mb-4">
                <i class="bi bi-info-circle-fill me-1"></i> Bu işlem kritik önem taşır. Lütfen emin olmadan onaylamayın.
            </div>

            <div class="d-flex gap-2 justify-content-end">
                <button type="button" class="btn btn-light fw-bold px-4 rounded-pill" data-bs-dismiss="modal">İptal</button>
                <button type="submit" class="btn btn-danger fw-bold px-4 rounded-pill" onclick="return confirm('Bu işlemi uygulamak istediğinize emin misiniz?');">İşlemi Onayla</button>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function kullaniciAyarla(id, adSoyad, email, rol) {
    document.getElementById('modalKullaniciId').value = id;
    document.getElementById('modalAd').innerText = adSoyad;
    document.getElementById('modalEmail').innerText = email;
    document.getElementById('modalRol').innerText = (rol === 'dukkan_sahibi') ? 'İşletme Sahibi' : 'Müşteri';
}
</script>

<?php include __DIR__ . '/../ortak/alt_bilgi.php'; ?>