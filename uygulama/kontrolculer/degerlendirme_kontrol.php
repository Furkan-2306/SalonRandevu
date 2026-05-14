<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Güvenlik: Giriş yapmamışsa at
if (!isset($_SESSION['giris_yapildi']) || empty($_SESSION['kullanici_id'])) {
    header("Location: /kuafor-randevu/giris-kayit");
    exit;
}

require_once __DIR__ . '/../ayarlar/veritabani.php';
require_once __DIR__ . '/../modeller/degerlendirme_model.php'; // MODELİ ÇAĞIRDIK

$vt = new Veritabani();
$db = $vt->baglantiGetir();
$degerlendirmeModel = new DegerlendirmeModel($db); // MODELİ BAŞLATTIK

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['islem']) && $_POST['islem'] == 'puan_ver') {
    
    $musteri_id = $_SESSION['kullanici_id'];
    $randevu_id = (int)$_POST['randevu_id']; 
    $dukkan_id  = (int)$_POST['dukkan_id'];
    $puan       = (int)$_POST['puan'];
    $yorum      = trim($_POST['yorum']);
    $tarih      = date('Y-m-d H:i:s'); 

    // Puan manipülasyonunu engelle
    if ($puan < 1) $puan = 1;
    if ($puan > 5) $puan = 5;

    // 1. MODEL'E SOR: Bu müşteri bu dükkana zaten yorum yapmış mı?
    if ($degerlendirmeModel->yorumVarMi($dukkan_id, $musteri_id)) {
        
        $_SESSION['mesaj_turu'] = "hata";
        $_SESSION['mesaj'] = "Bu işletmeyi daha önce değerlendirdiniz. Bir işletmeye sadece bir kez yorum yapabilirsiniz.";
        
    } else {
        
        // 2. MODEL'E EMRET: Kaydı ekle
        $kayitDurumu = $degerlendirmeModel->yorumEkle($randevu_id, $dukkan_id, $musteri_id, $puan, $yorum, $tarih);
        
        if ($kayitDurumu) {
            $_SESSION['mesaj_turu'] = "basari";
            $_SESSION['mesaj'] = "Değerlendirmeniz başarıyla kaydedildi. Geri bildiriminiz için teşekkür ederiz!";
        } else {
            $_SESSION['mesaj_turu'] = "hata";
            $_SESSION['mesaj'] = "Değerlendirme kaydedilirken sistemsel bir hata oluştu.";
        }
    }

    header("Location: /kuafor-randevu/musteri-randevularim");
    exit;
} else {
    header("Location: /kuafor-randevu/");
    exit;
}
?>