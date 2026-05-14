<?php 
// 1. GÜVENLİK VE OTURUM KONTROLÜ
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['giris_yapildi'])) {
    header("Location: /kuafor-randevu/giris-kayit");
    exit;
}

require_once __DIR__ . '/../../ayarlar/veritabani.php';
$vt = new Veritabani();
$db = $vt->baglantiGetir();

// ==========================================
// FORM GÖNDERİLDİĞİNDE: GÜNCELLEME İŞLEMİ
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['profil_guncelle'])) {
    $yeni_ad = trim(htmlspecialchars($_POST['ad']));
    $yeni_soyad = trim(htmlspecialchars($_POST['soyad']));
    $yeni_cinsiyet = trim(htmlspecialchars($_POST['cinsiyet']));
    $yeni_telefon = trim(htmlspecialchars($_POST['telefon']));

    $eski_sifre = $_POST['eski_sifre'] ?? '';
    $yeni_sifre = $_POST['yeni_sifre'] ?? '';
    $yeni_sifre_tekrar = $_POST['yeni_sifre_tekrar'] ?? '';

    try {
        // Hata olursa her şeyi geri almak için işlemi başlat
        $db->beginTransaction();

        // 1. ADIM: PROFİL BİLGİLERİNİ GÜNCELLE (`kullanici_profilleri` tablosu)
        $profilGuncelle = $db->prepare("UPDATE kullanici_profilleri SET ad = :ad, soyad = :soyad, cinsiyet = :cinsiyet, telefon = :telefon WHERE kullanici_id = :id");
        $profilGuncelle->execute([
            'ad' => $yeni_ad,
            'soyad' => $yeni_soyad,
            'cinsiyet' => $yeni_cinsiyet,
            'telefon' => $yeni_telefon,
            'id' => $_SESSION['kullanici_id']
        ]);

        // 2. ADIM: ŞİFRE GÜNCELLEME KONTROLÜ (`kullanicilar` tablosu)
        if (!empty($eski_sifre) || !empty($yeni_sifre)) {
            
            // Boş alan var mı?
            if (empty($eski_sifre) || empty($yeni_sifre) || empty($yeni_sifre_tekrar)) {
                throw new Exception("Şifre değiştirmek için tüm şifre alanlarını eksiksiz doldurmalısınız.");
            }
            // Yeni şifreler uyuşuyor mu?
            if ($yeni_sifre !== $yeni_sifre_tekrar) {
                throw new Exception("Girdiğiniz yeni şifreler birbiriyle eşleşmiyor.");
            }

            // Eski şifre gerçekten doğru mu? (Veritabanından hash'i çekip doğruluyoruz)
            $sifreSorgu = $db->prepare("SELECT sifre_hash FROM kullanicilar WHERE id = :id");
            $sifreSorgu->execute(['id' => $_SESSION['kullanici_id']]);
            $mevcutBilgi = $sifreSorgu->fetch(PDO::FETCH_ASSOC);

            if (!password_verify($eski_sifre, $mevcutBilgi['sifre_hash'])) {
                throw new Exception("Mevcut şifrenizi hatalı girdiniz. Güvenlik gereği şifreniz değiştirilmedi.");
            }

            // Her şey doğruysa yeni şifreyi Hash'le (Şifrele) ve Kaydet
            $yeni_hash = password_hash($yeni_sifre, PASSWORD_DEFAULT);
            $sifreGuncelle = $db->prepare("UPDATE kullanicilar SET sifre_hash = :hash WHERE id = :id");
            $sifreGuncelle->execute([
                'hash' => $yeni_hash,
                'id' => $_SESSION['kullanici_id']
            ]);
        }

        // Hata çıkmadıysa tüm işlemleri veritabanına yaz
        $db->commit();
        $_SESSION['mesaj_turu'] = "basari";
        $_SESSION['mesaj'] = "Profil bilgileriniz başarıyla güncellendi.";
        
        // Sayfayı yenile ki yeni bilgiler anında ekrana yansısın
        header("Location: /kuafor-randevu/uygulama/gorunumler/musteri_paneli/musteri-profil.php");
        exit;

    } catch (Exception $e) {
        // Hata yakalanırsa işlemi iptal et ve hata mesajını göster
        $db->rollBack();
        $_SESSION['mesaj_turu'] = "hata";
        $_SESSION['mesaj'] = $e->getMessage();
        header("Location: /kuafor-randevu/uygulama/gorunumler/musteri_paneli/musteri-profil.php");
        exit;
    }
}

// ==========================================
// EKRANA VERİLERİ YAZDIRMAK İÇİN BİLGİLERİ ÇEK
// ==========================================
$sorgu = $db->prepare("
    SELECT 
        k.eposta, k.dogrulandi_mi, k.kayit_tarihi, 
        p.ad, p.soyad, p.telefon, p.cinsiyet 
    FROM kullanicilar k 
    LEFT JOIN kullanici_profilleri p ON k.id = p.kullanici_id 
    WHERE k.id = :id
");
$sorgu->execute(['id' => $_SESSION['kullanici_id']]);
$kullanici = $sorgu->fetch(PDO::FETCH_ASSOC);

$ad       = $kullanici['ad'] ?? '';
$soyad    = $kullanici['soyad'] ?? '';
$eposta   = $kullanici['eposta'] ?? '';
$telefon  = $kullanici['telefon'] ?? '';
$cinsiyet = $kullanici['cinsiyet'] ?? '';
$ilk_harf = !empty($ad) ? mb_substr($ad, 0, 1, 'UTF-8') : 'U';
$soyad_ilk_harf = !empty($soyad) ? mb_substr($soyad, 0, 1, 'UTF-8') : '';
$profil_harfleri = mb_strtoupper($ilk_harf . $soyad_ilk_harf, 'UTF-8');

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
                    <a href="/kuafor-randevu/musteri-randevularim" class="list-group-item list-group-item-action py-3 fw-semibold text-dark">
                        <i class="bi bi-calendar-check me-2"></i> Randevularım
                    </a>
                    <a href="/kuafor-randevu/uygulama/gorunumler/musteri_paneli/musteri-profil.php" class="list-group-item list-group-item-action py-3 fw-semibold active" style="background-color: var(--luks-koyu); color: var(--luks-altin); border-color: var(--luks-koyu);">
                        <i class="bi bi-person me-2"></i> Müşteri Panelim
                    </a>
                    <a href="/kuafor-randevu/uygulama/kontrolculer/cikis.php" class="list-group-item list-group-item-action py-3 fw-semibold text-danger">
                        <i class="bi bi-box-arrow-right me-2"></i> Çıkış Yap
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <h3 class="fw-bold mb-4">Profil Bilgilerim</h3>

            <?php if(isset($_SESSION['mesaj'])): ?>
                <div class="alert alert-<?= ($_SESSION['mesaj_turu'] == 'basari') ? 'success' : 'danger' ?> alert-dismissible fade show rounded-4 border-0 shadow-sm" role="alert">
                    <i class="bi <?= ($_SESSION['mesaj_turu'] == 'basari') ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
                    <?= $_SESSION['mesaj']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php 
                    unset($_SESSION['mesaj']);
                    unset($_SESSION['mesaj_turu']);
                ?>
            <?php endif; ?>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-md-5">
                    
                    <form action="" method="POST">
                        <h5 class="fw-bold mb-4 border-bottom pb-2">Kişisel Bilgiler</h5>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control rounded-3" id="profilAd" name="ad" value="<?= htmlspecialchars($ad) ?>" required>
                                    <label for="profilAd">Adınız</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control rounded-3" id="profilSoyad" name="soyad" value="<?= htmlspecialchars($soyad) ?>" required>
                                    <label for="profilSoyad">Soyadınız</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small text-muted ms-1 mb-1">Cinsiyetiniz</label>
                            <div class="d-flex gap-2">
                                <input type="radio" class="btn-check" name="cinsiyet" value="kadin" id="kadin" autocomplete="off" <?= ($cinsiyet == 'kadin') ? 'checked' : '' ?>>
                                <label class="btn btn-outline-primary w-50 rounded-3" for="kadin">Kadın</label>

                                <input type="radio" class="btn-check" name="cinsiyet" value="erkek" id="erkek" autocomplete="off" <?= ($cinsiyet == 'erkek') ? 'checked' : '' ?>>
                                <label class="btn btn-outline-primary w-50 rounded-3" for="erkek">Erkek</label>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="tel" class="form-control rounded-3" id="profilTelefon" name="telefon" value="<?= htmlspecialchars($telefon) ?>" required>
                                    <label for="profilTelefon">Telefon Numarası</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="email" class="form-control rounded-3 bg-light" id="profilEmail" name="eposta" value="<?= htmlspecialchars($eposta) ?>" readonly>
                                    <label for="profilEmail">E-posta Adresi (Değiştirilemez)</label>
                                </div>
                            </div>
                        </div>

                        <h5 class="fw-bold mb-4 mt-5 border-bottom pb-2">Şifre Değiştirme <span class="text-muted fs-6 fw-normal">(İsteğe Bağlı)</span></h5>

                            <div class="form-floating mb-3 position-relative">
                                <input type="password" class="form-control rounded-3 pe-5" id="eskiSifre" name="eski_sifre" placeholder="Mevcut Şifre" autocomplete="new-password">
                                <label for="eskiSifre">Mevcut Şifre</label>
                                <i class="bi bi-eye-slash password-toggle" onclick="sifreGoster('eskiSifre', this)"></i>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <div class="form-floating position-relative">
                                        <input type="password" class="form-control rounded-3 pe-5" id="yeniSifre" name="yeni_sifre" placeholder="Yeni Şifre" autocomplete="new-password">
                                        <label for="yeniSifre">Yeni Şifre</label>
                                        <i class="bi bi-eye-slash password-toggle" onclick="sifreGoster('yeniSifre', this)"></i>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating position-relative">
                                        <input type="password" class="form-control rounded-3 pe-5" id="yeniSifreTekrar" name="yeni_sifre_tekrar" placeholder="Yeni Şifre (Tekrar)" autocomplete="new-password">
                                        <label for="yeniSifreTekrar">Yeni Şifre (Tekrar)</label>
                                        <i class="bi bi-eye-slash password-toggle" onclick="sifreGoster('yeniSifreTekrar', this)"></i>
                                    </div>
                                </div>
                            </div>

                        <div class="text-end mt-4">
                            <button type="submit" name="profil_guncelle" class="btn btn-primary px-5 py-3 fw-bold rounded-pill">Bilgilerimi Kaydet</button>
                        </div>
                    </form>

                </div>
            </div>

        </div>
    </div>
</div>

<?php include __DIR__ . '/../ortak/alt_bilgi.php'; ?>