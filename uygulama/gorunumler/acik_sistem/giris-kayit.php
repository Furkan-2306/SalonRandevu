<?php 
// Oturum başlatılmamışsa en üstte başlatıyoruz
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'uygulama/gorunumler/ortak/ust_bilgi.php'; 

// Kullanıcının rolünü güvenli bir şekilde al (Eğer giriş yapmamışsa varsayılan boş döner)
$aktif_rol = $_SESSION['rol'] ?? $_SESSION['kullanici_rol'] ?? 'musteri';
?>

<div class="container mt-4">
    <?php if(isset($_SESSION['mesaj']) && !isset($_SESSION['aktif_sekme'])): ?>
        <div class="alert alert-<?php echo ($_SESSION['mesaj_turu'] == 'basari') ? 'success' : 'danger'; ?> alert-dismissible fade show rounded-3 shadow-sm" role="alert">
            <i class="bi <?php echo ($_SESSION['mesaj_turu'] == 'basari') ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?> me-2"></i>
            <?php 
                echo $_SESSION['mesaj']; 
                unset($_SESSION['mesaj']); 
                unset($_SESSION['mesaj_turu']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
        </div>
    <?php endif; ?>
</div>

<?php 
// EĞER KULLANICI ZATEN GİRİŞ YAPMIŞSA (Formu gizle, panelleri göster)
if (isset($_SESSION['giris_yapildi']) && $_SESSION['giris_yapildi'] === true): 
?>
    <div class="container py-5 mb-5 text-center">
        <div class="card shadow-lg border-0 rounded-4 p-5 mx-auto" style="max-width: 600px;">
            <div class="mb-4">
                <?php if($aktif_rol == 'admin'): ?>
                    <i class="bi bi-shield-lock-fill text-danger" style="font-size: 5rem;"></i>
                <?php elseif($aktif_rol == 'dukkan_sahibi'): ?>
                    <i class="bi bi-shop text-success" style="font-size: 5rem;"></i>
                <?php else: ?>
                    <i class="bi bi-person-check-fill text-primary" style="font-size: 5rem;"></i>
                <?php endif; ?>
            </div>
            <h2 class="fw-bold mb-3">Tekrar Hoş Geldiniz!</h2>
            <p class="text-muted mb-4 fs-5">Sisteme zaten giriş yapmış durumdasınız. Aşağıdaki butonu kullanarak panelinize geçiş yapabilirsiniz.</p>
            
            <div class="d-flex justify-content-center gap-3 flex-wrap mt-2">
                
                <?php if($aktif_rol == 'admin'): ?>
                    <a href="/kuafor-randevu/yonetici-ozet" class="btn btn-danger px-5 py-3 fw-bold rounded-pill shadow-sm">
                        <i class="bi bi-speedometer2 me-2"></i> Yönetici Paneline Git
                    </a>
                    <a href="/kuafor-randevu/isletme-kayit-adimlari" class="btn btn-outline-dark px-5 py-3 fw-bold rounded-pill shadow-sm">
                        <i class="bi bi-shop me-2"></i> İşletme Ekle
                    </a>
                <?php elseif($aktif_rol == 'dukkan_sahibi'): ?>
                    <a href="/kuafor-randevu/dukkan-ozet-paneli" class="btn btn-success px-5 py-3 fw-bold rounded-pill shadow-sm">
                        <i class="bi bi-grid-1x2 me-2"></i> İşletme Paneline Git
                    </a>
                <?php else: ?>
                    <a href="/kuafor-randevu/musteri-randevularim" class="btn btn-primary px-5 py-3 fw-bold rounded-pill shadow-sm">
                        <i class="bi bi-calendar-check me-2"></i> Randevularım'a Git
                    </a>
                    <a href="/kuafor-randevu/isletme-kayit-adimlari" class="btn btn-outline-dark px-5 py-3 fw-bold rounded-pill shadow-sm">
                        <i class="bi bi-shop me-2"></i> İşletme Ekle
                    </a>
                <?php endif; ?>

            </div>
        </div>
    </div>

<?php else: ?>
<div class="container py-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-5">
            
            <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                    <ul class="nav nav-pills nav-justified mb-3" id="pills-tab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold rounded-pill" id="pills-giris-tab" data-bs-toggle="pill" data-bs-target="#pills-giris" type="button" role="tab" aria-controls="pills-giris" aria-selected="true">Giriş Yap</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold rounded-pill" id="pills-kayit-tab" data-bs-toggle="pill" data-bs-target="#pills-kayit" type="button" role="tab" aria-controls="pills-kayit" aria-selected="false">Yeni Hesap Oluştur</button>
                        </li>
                    </ul>
                </div>
                
                <div class="card-body p-4 pt-2">
                    <div class="tab-content" id="pills-tabContent">
                        
                        <div class="tab-pane fade show active" id="pills-giris" role="tabpanel" aria-labelledby="pills-giris-tab" tabindex="0">
                            <div class="text-center mb-4 mt-2">
                                <h4 class="fw-bold">Tekrar Hoş Geldiniz!</h4>
                                <p class="text-muted small">Randevularınızı yönetmek için giriş yapın.</p>
                            </div>

                            <?php if(isset($_SESSION['mesaj']) && isset($_SESSION['aktif_sekme']) && $_SESSION['aktif_sekme'] == 'giris'): ?>
                                <div class="alert alert-<?php echo ($_SESSION['mesaj_turu'] == 'basari') ? 'success' : 'danger'; ?> alert-dismissible fade show rounded-3" role="alert">
                                    <?php 
                                        echo $_SESSION['mesaj']; 
                                        unset($_SESSION['mesaj']); 
                                        unset($_SESSION['mesaj_turu']);
                                        unset($_SESSION['aktif_sekme']);
                                    ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
                                </div>
                            <?php endif; ?>

                            <form action="/kuafor-randevu/uygulama/kontrolculer/yetkilendirme_kontrol.php" method="POST">
                                
                                <input type="hidden" name="giris_islemi" value="1">

                                <div class="form-floating mb-3">
                                    <input type="email" class="form-control rounded-3" id="girisEmail" name="eposta" placeholder="isim@ornek.com" required>
                                    <label for="girisEmail">E-posta Adresi</label>
                                </div>
                                <div class="form-floating mb-3 position-relative">
                                    <input type="password" class="form-control rounded-3 pe-5" id="girisSifre" name="sifre" placeholder="Şifre" required>
                                    <label for="girisSifre">Şifre</label>
                                    <i class="bi bi-eye-slash password-toggle" onclick="sifreGoster('girisSifre', this)"></i>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center mb-4 small">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="beniHatirla">
                                        <label class="form-check-label text-muted" for="beniHatirla">Beni Hatırla</label>
                                    </div>
                                    <a href="#" class="text-decoration-none fw-semibold" data-bs-toggle="modal" data-bs-target="#sifremiUnuttumModal">Şifremi Unuttum</a>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 py-3 rounded-3 fw-bold fs-5">Giriş Yap</button>
                            </form>
                        </div>

                        <div class="tab-pane fade" id="pills-kayit" role="tabpanel" aria-labelledby="pills-kayit-tab" tabindex="0">
                            <div class="text-center mb-4 mt-2">
                                <h4 class="fw-bold">Aramıza Katılın</h4>
                                <p class="text-muted small">Hızlıca hesabınızı oluşturun ve ilk randevunuzu alın.</p>
                            </div>

                            <form action="/kuafor-randevu/uygulama/kontrolculer/yetkilendirme_kontrol.php" method="POST">
                                
                                <input type="hidden" name="kayit_islemi" value="1">

                                <?php if(isset($_SESSION['mesaj']) && isset($_SESSION['aktif_sekme']) && $_SESSION['aktif_sekme'] == 'kayit'): ?>
                                    <div class="alert alert-<?php echo ($_SESSION['mesaj_turu'] == 'basari') ? 'success' : 'danger'; ?> alert-dismissible fade show rounded-3" role="alert">
                                        <?php 
                                            echo $_SESSION['mesaj']; 
                                            unset($_SESSION['mesaj']); 
                                            unset($_SESSION['mesaj_turu']);
                                            unset($_SESSION['aktif_sekme']);
                                        ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
                                    </div>
                                <?php endif; ?>

                                <div class="row g-2 mb-3">
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control rounded-3" id="kayitAd" name="ad" placeholder="Adınız" required>
                                            <label for="kayitAd">Adınız</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control rounded-3" id="kayitSoyad" name="soyad" placeholder="Soyadınız" required>
                                            <label for="kayitSoyad">Soyadınız</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label small text-muted ms-1 mb-1">Cinsiyetiniz (Size uygun salonları önermek için)</label>
                                    <div class="d-flex gap-2">
                                        <input type="radio" class="btn-check" name="cinsiyet" value="kadin" id="kayitKadin" autocomplete="off" checked>
                                        <label class="btn btn-outline-primary w-50 rounded-3" for="kayitKadin">Kadın</label>

                                        <input type="radio" class="btn-check" name="cinsiyet" value="erkek" id="kayitErkek" autocomplete="off">
                                        <label class="btn btn-outline-primary w-50 rounded-3" for="kayitErkek">Erkek</label>
                                    </div>      
                                </div>

                                <div class="form-floating mb-3">
                                    <input type="tel" class="form-control rounded-3" id="kayitTelefon" name="telefon" placeholder="0555..." required>
                                    <label for="kayitTelefon">Telefon Numarası</label>
                                </div>

                                <div class="form-floating mb-3">
                                    <input type="email" class="form-control rounded-3" id="kayitEmail" name="eposta" placeholder="isim@ornek.com" required>
                                    <label for="kayitEmail">E-posta Adresi</label>
                                </div>

                                <div class="form-floating mb-4 position-relative">
                                    <input type="password" class="form-control rounded-3 pe-5" id="kayitSifre" name="sifre" placeholder="Şifre" required>
                                    <label for="kayitSifre">Şifre Belirleyin</label>
                                    <i class="bi bi-eye-slash password-toggle" onclick="sifreGoster('kayitSifre', this)"></i>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 py-3 rounded-3 fw-bold fs-5 mb-3">Hesap Oluştur</button>
                                
                                <p class="text-center text-muted" style="font-size: 0.75rem;">
                                    Kayıt olarak <a href="#" class="text-decoration-none">Kullanıcı Sözleşmesi</a>'ni ve <a href="#" class="text-decoration-none">Gizlilik Politikası</a>'nı kabul etmiş olursunuz.
                                </p>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>

<div class="modal fade" id="sifremiUnuttumModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow-lg">
      <div class="modal-header border-bottom-0 pb-0 mt-2 mx-2">
        <h5 class="modal-title fw-bold text-dark"><i class="bi bi-key me-2 text-primary"></i>Şifre Sıfırlama</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
      </div>
      <div class="modal-body py-4 px-4">
        <p class="text-muted small mb-4">Sisteme kayıtlı e-posta adresinizi girin. Size şifrenizi güvenli bir şekilde sıfırlamanız için bağlantı göndereceğiz.</p>
        
        <form action="/kuafor-randevu/uygulama/kontrolculer/yetkilendirme_kontrol.php" method="POST">
            <input type="hidden" name="islem" value="sifre_sifirlama_talebi">
            
            <div class="form-floating mb-4">
                <input type="email" class="form-control rounded-3" id="resetEmail" name="reset_eposta" placeholder="isim@ornek.com" required>
                <label for="resetEmail">Kayıtlı E-posta Adresiniz</label>
            </div>
            
            <button type="submit" class="btn btn-primary w-100 fw-bold py-2 rounded-3">Sıfırlama Bağlantısı Gönder</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php endif; ?> 
<?php include 'uygulama/gorunumler/ortak/alt_bilgi.php'; ?>