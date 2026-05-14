<?php
session_start();

require_once __DIR__ . '/../ayarlar/veritabani.php';
require_once __DIR__ . '/../modeller/kullanici_model.php';


if (isset($_GET['kod'])) {
    $kod = trim(htmlspecialchars($_GET['kod']));

    $veritabani = new Veritabani();
    $db = $veritabani->baglantiGetir();
    $kullaniciModel = new KullaniciModel($db);


    if ($kullaniciModel->hesabiDogrula($kod)) {
        $_SESSION['mesaj_turu'] = "basari";
        $_SESSION['mesaj'] = "Harika! Hesabınız başarıyla doğrulandı. Şimdi e-posta ve şifrenizle giriş yapabilirsiniz.";
        $_SESSION['aktif_sekme'] = "giris";
    } else {
        $_SESSION['mesaj_turu'] = "hata";
        $_SESSION['mesaj'] = "Geçersiz veya süresi dolmuş (24 saati geçmiş) bir doğrulama bağlantısı kullandınız!";
        $_SESSION['aktif_sekme'] = "giris";
    }
} else {
    $_SESSION['mesaj_turu'] = "hata";
    $_SESSION['mesaj'] = "Doğrulama kodu bulunamadı!";
    $_SESSION['aktif_sekme'] = "giris";
}

header("Location: /kuafor-randevu/giris-kayit");
exit;
?>