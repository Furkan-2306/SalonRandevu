<?php
session_start();

if (!isset($_SESSION['giris_yapildi'])) {
    $_SESSION['mesaj_turu'] = "hata";
    $_SESSION['mesaj'] = "İşletme başvurusu yapmak için önce giriş yapmalısınız.";
    header("Location: /kuafor-randevu/giris-kayit");
    exit;
}

require_once __DIR__ . '/../ayarlar/veritabani.php';
require_once __DIR__ . '/../modeller/isletme_model.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['isletme_kaydet'])) {
    
    $veritabani = new Veritabani();
    $db = $veritabani->baglantiGetir();
    $isletmeModel = new IsletmeModel($db);

    $sahip_id = $_SESSION['kullanici_id'];

    $dukkanVerisi = [
        'ad'        => trim(htmlspecialchars($_POST['dukkan_ad'])),
        'vergi_no'  => trim(htmlspecialchars($_POST['vergi_no'])),
        'tc_kimlik' => trim(htmlspecialchars($_POST['tc_kimlik'])), 
        'sehir'     => trim(htmlspecialchars($_POST['sehir'])),
        'ilce'      => trim(htmlspecialchars($_POST['ilce'])),
        'adres'     => trim(htmlspecialchars($_POST['adres'])),
        'enlem'     => !empty($_POST['enlem']) ? (float)$_POST['enlem'] : null,
        'boylam'    => !empty($_POST['boylam']) ? (float)$_POST['boylam'] : null,
        'aciklama'  => trim(htmlspecialchars($_POST['aciklama'])) 
    ];

    $kayitDurumu = $isletmeModel->dukkanKaydet($sahip_id, $dukkanVerisi, [], []);

    if ($kayitDurumu) {
        // BAŞVURU ALINDI MESAJI VE ANA SAYFAYA YÖNLENDİRME
        $_SESSION['mesaj_turu'] = "basari";
        $_SESSION['mesaj'] = "İşletme başvurunuz başarıyla alınmıştır! Yöneticilerimiz tarafından incelendikten sonra aktif edilecektir.";
        header("Location: /kuafor-randevu/"); 
        exit;
    } else {
        $_SESSION['mesaj_turu'] = "hata";
        $_SESSION['mesaj'] = "Başvuru sırasında bir hata oluştu. Lütfen tekrar deneyin.";
        header("Location: /kuafor-randevu/isletme-kayit-adimlari"); 
        exit;
    }
} else {
    header("Location: /kuafor-randevu/");
    exit;
}
?>