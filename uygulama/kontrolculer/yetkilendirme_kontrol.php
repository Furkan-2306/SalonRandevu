<?php
session_start();

require_once __DIR__ . '/../ayarlar/veritabani.php';
require_once __DIR__ . '/../modeller/kullanici_model.php';
require_once __DIR__ . '/../ayarlar/mail_islemleri.php'; 

$veritabani = new Veritabani();
$db = $veritabani->baglantiGetir();
$kullaniciModel = new KullaniciModel($db);

// ==========================================
// YENİ KAYIT İŞLEMİ
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['kayit_islemi'])) {
    
    $ad = trim(htmlspecialchars($_POST['ad']));
    $soyad = trim(htmlspecialchars($_POST['soyad']));
    $cinsiyet = $_POST['cinsiyet'];
    $telefon = trim(htmlspecialchars($_POST['telefon']));
    $eposta = trim(htmlspecialchars($_POST['eposta']));
    $sifre = $_POST['sifre'];

    if (!filter_var($eposta, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['mesaj_turu'] = "hata";
        $_SESSION['mesaj'] = "Lütfen geçerli bir e-posta adresi giriniz (Örn: isim@ornek.com).";
        header("Location: /kuafor-randevu/giris-kayit");
        exit;
    }

    // Bu E-posta adresi sistemde zaten var mı?
    $epostaKontrol = $kullaniciModel->emailKontrol($eposta);

    if ($epostaKontrol) {
        $_SESSION['mesaj_turu'] = "hata";
        $_SESSION['mesaj'] = "Bu e-posta adresi zaten kullanımda! Lütfen giriş yapmayı deneyin.";
        header("Location: /kuafor-randevu/giris-kayit");
        exit;
    } else {
        $sifre_hash = password_hash($sifre, PASSWORD_DEFAULT);
        $dogrulama_kodu = bin2hex(random_bytes(16)); 

        $kayitDurumu = $kullaniciModel->musteriKaydet($eposta, $sifre_hash, $ad, $soyad, $telefon, $cinsiyet, $dogrulama_kodu);

        if ($kayitDurumu) {
            $onay_linki = "http://localhost/kuafor-randevu/uygulama/kontrolculer/dogrula.php?kod=" . $dogrulama_kodu;
            
            $mailGonderici = new MailIslemleri();
            $mailDurumu = $mailGonderici->dogrulamaMailiGonder($eposta, $ad, $onay_linki);

            if ($mailDurumu) {
                $_SESSION['mesaj_turu'] = "basari";
                $_SESSION['mesaj'] = "Kaydınız başarıyla oluşturuldu. Lütfen e-posta kutunuzu (ve spam/gereksiz klasörünü) kontrol ederek hesabınızı doğrulayın.";
            } else {
                $_SESSION['mesaj_turu'] = "hata";
                $_SESSION['mesaj'] = "Hesabınız oluşturuldu fakat doğrulama e-postası gönderilirken bir sunucu hatası oluştu. Lütfen yöneticiyle iletişime geçin.";
            }
            
            header("Location: /kuafor-randevu/giris-kayit");
            exit;
        } else {
            $_SESSION['mesaj_turu'] = "hata";
            $_SESSION['mesaj'] = "Kayıt sırasında sistemsel bir hata oluştu. Lütfen tekrar deneyin.";
            header("Location: /kuafor-randevu/giris-kayit");
            exit;
        }
    }
}

// ==========================================
// GİRİŞ İŞLEMİ
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['giris_islemi'])) {
    
    $eposta = trim(htmlspecialchars($_POST['eposta']));
    $sifre = $_POST['sifre'];

    $kullanici = $kullaniciModel->emailKontrol($eposta);

    // E-posta bulundu ve şifre doğruysa
    if ($kullanici && password_verify($sifre, $kullanici['sifre_hash'])) {
        
        // 1. KONTROL: Hesabı askıya alınmış mı? (Yeni eklediğimiz sütun)
        if (isset($kullanici['aktif_mi']) && $kullanici['aktif_mi'] == 0) {
            $_SESSION['mesaj_turu'] = "hata";
            $_SESSION['mesaj'] = "Hesabınız yönetici tarafından geçici olarak askıya alınmıştır. Giriş yapamazsınız.";
            $_SESSION['aktif_sekme'] = "giris";
            header("Location: /kuafor-randevu/giris-kayit");
            exit;
        }

        // 2. KONTROL: E-postasını doğrulamış mı?
        if ($kullanici['dogrulandi_mi'] == 0) {
            $_SESSION['mesaj_turu'] = "hata";
            $_SESSION['mesaj'] = "Hesabınız henüz doğrulanmamış. Lütfen e-postanıza gönderilen onay linkine tıklayın.";
            $_SESSION['aktif_sekme'] = "giris";
            header("Location: /kuafor-randevu/giris-kayit");
            exit;
        }

        // 3. KONTROL: Sistem Bakım Modunda mı?
        $bakimSorgu = $db->query("SELECT ayar_degeri FROM genel_ayarlar WHERE ayar_adi = 'bakim_modu'");
        $bakimModu = $bakimSorgu->fetchColumn();

        if ($bakimModu == '1' && $kullanici['rol'] !== 'admin') {
            $_SESSION['mesaj_turu'] = "hata";
            $_SESSION['mesaj'] = "Sistem şu anda bakım modundadır. Sadece yöneticiler giriş yapabilir.";
            $_SESSION['aktif_sekme'] = "giris";
            header("Location: /kuafor-randevu/giris-kayit");
            exit;
        }

        // TÜM KONTROLLERDEN GEÇTİYSE: Giriş Başarılı!
        $_SESSION['kullanici_id'] = $kullanici['id'];
        $_SESSION['kullanici_rol'] = $kullanici['rol']; // Bazı yerlerde 'kullanici_rol' kullanıyorsun
        $_SESSION['rol'] = $kullanici['rol']; // Bazı yerlerde 'rol' kullanıyorsun (İkisini de atayalım garanti olsun)
        $_SESSION['giris_yapildi'] = true;

        $_SESSION['mesaj_turu'] = "basari";
        $_SESSION['mesaj'] = "Sisteme başarıyla giriş yaptınız. Hoş geldiniz!";

        // Rolüne göre yönlendirme
        if ($kullanici['rol'] == 'admin') {
            header("Location: /kuafor-randevu/yonetici-ozet");
        } elseif ($kullanici['rol'] == 'dukkan_sahibi') {
            header("Location: /kuafor-randevu/dukkan-ozet-paneli");
        } else {
            header("Location: /kuafor-randevu/musteri-randevularim");
        }
        exit;

    } else {
        // E-posta veya Şifre Hatalı
        $_SESSION['mesaj_turu'] = "hata";
        $_SESSION['mesaj'] = "E-posta adresi veya şifre hatalı!";
        $_SESSION['aktif_sekme'] = "giris"; 
        header("Location: /kuafor-randevu/giris-kayit");
        exit;
    }

    
}

// ==========================================
// ŞİFRE SIFIRLAMA TALEBİ GÖNDERME
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['islem']) && $_POST['islem'] == 'sifre_sifirlama_talebi') {
    $eposta = trim(htmlspecialchars($_POST['reset_eposta']));

    // 1. Sistemde bu e-posta var mı?
    $kullanici = $kullaniciModel->emailKontrol($eposta);

    if ($kullanici) {
        // Kullanıcının adı soyadını profil tablosundan çekelim
        $uSorgu = $db->prepare("SELECT ad, soyad FROM kullanici_profilleri WHERE kullanici_id = ?");
        $uSorgu->execute([$kullanici['id']]);
        $profil = $uSorgu->fetch(PDO::FETCH_ASSOC);
        
        $alici_ad = (!empty($profil['ad'])) ? $profil['ad'] . ' ' . $profil['soyad'] : 'Değerli Kullanıcımız';

        // 2. Güvenli, rastgele bir token (şifre) üret
        $reset_kodu = bin2hex(random_bytes(20));
        
        // 3. Geçerlilik süresi belirle (Şu andan itibaren tam 1 Saat)
        $gecerlilik = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // 4. Veritabanına kaydet
        $sorgu = $db->prepare("UPDATE kullanicilar SET sifre_sifirlama_kodu = ?, sifre_sifirlama_tarihi = ? WHERE eposta = ?");
        $sorgu->execute([$reset_kodu, $gecerlilik, $eposta]);

        // 5. Mail gönder
        // NOT: Buradaki localhost/kuafor-randevu yolunu kendi klasör adına göre teyit et
        $sifirlama_linki = "http://localhost/kuafor-randevu/sifre-sifirla?kod=" . $reset_kodu;
        
        $mailGonderici = new MailIslemleri();
        $mailGonderici->sifreSifirlamaMailiGonder($eposta, $alici_ad, $sifirlama_linki);
    }

    // GÜVENLİK DETAYI: 
    // Email sistemde olsa da olmasa da kullanıcıya "AYNI MESAJI" vermeliyiz. 
    // Aksi takdirde kötü niyetli biri hangi e-postaların sistemde kayıtlı olduğunu deneyerek bulabilir.
    $_SESSION['mesaj_turu'] = "basari";
    $_SESSION['mesaj'] = "Eğer sistemimizde bu e-posta adresiyle eşleşen bir hesabınız varsa, şifre sıfırlama bağlantısı gönderilmiştir. Lütfen e-posta kutunuzu kontrol edin.";
    $_SESSION['aktif_sekme'] = "giris";
    
    header("Location: /kuafor-randevu/giris-kayit");
    exit;
}
?>