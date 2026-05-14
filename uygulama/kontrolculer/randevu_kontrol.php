<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['giris_yapildi'])) {
    $_SESSION['mesaj_turu'] = "hata";
    $_SESSION['mesaj'] = "Randevu alabilmek için lütfen giriş yapın.";
    header("Location: /kuafor-randevu/giris-kayit");
    exit;
}

require_once __DIR__ . '/../ayarlar/veritabani.php';
require_once __DIR__ . '/../modeller/randevu_model.php';

$vt = new Veritabani();
$db = $vt->baglantiGetir();
$randevuModel = new RandevuModel($db);

// ==========================================
// 1. RANDEVU OLUŞTURMA İŞLEMİ
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['islem']) && $_POST['islem'] == 'randevu_olustur') {
    
    $musteri_id   = $_SESSION['kullanici_id'];
    $dukkan_id    = (int)$_POST['dukkan_id'];
    $hizmet_id    = $_POST['hizmet_id']; 
    $personel_id  = (int)$_POST['personel_id'];
    $tarih        = $_POST['randevu_tarihi']; 
    $saat         = $_POST['randevu_saati'];  
    $toplam_tutar = (float)$_POST['toplam_tutar'];
    $toplam_sure  = (int)$_POST['toplam_sure']; 

    // Başlangıç ve Bitiş Saatlerini MySQL Formatına Çevir
    $randevu_baslangic = $tarih . ' ' . $saat . ':00';
    
    // PHP ile toplam süreyi başlangıca ekleyip bitiş datetime'ını buluyoruz
    $baslangic_timestamp = strtotime($randevu_baslangic);
    $bitis_timestamp = $baslangic_timestamp + ($toplam_sure * 60);
    $randevu_bitis = date('Y-m-d H:i:s', $bitis_timestamp);

    // KUSURSUZ ÇAKIŞMA KONTROLÜ
    if ($randevuModel->cakismaVarMi($personel_id, $randevu_baslangic, $randevu_bitis)) {
        $_SESSION['mesaj_turu'] = "hata";
        $_SESSION['mesaj'] = "Üzgünüz, seçtiğiniz personelin bu saat aralığında başka bir randevusu bulunmaktadır. Lütfen farklı bir saat seçin.";
        header("Location: /kuafor-randevu/dukkan-detay?id=" . $dukkan_id);
        exit;
    }

    $kayitDurumu = $randevuModel->randevuOlustur($dukkan_id, $musteri_id, $personel_id, $hizmet_id, $randevu_baslangic, $randevu_bitis, $toplam_tutar);

    if ($kayitDurumu) {
        
        // DÜZELTİLEN KISIM: İKİ TABLOYU JOIN İLE BİRLEŞTİREREK VERİ ÇEKİYORUZ
        $kullaniciSorgu = $db->prepare("
            SELECT k.eposta, kp.ad, kp.soyad 
            FROM kullanicilar k 
            LEFT JOIN kullanici_profilleri kp ON k.id = kp.kullanici_id 
            WHERE k.id = ?
        ");
        $kullaniciSorgu->execute([$musteri_id]);
        $kullanici = $kullaniciSorgu->fetch(PDO::FETCH_ASSOC);

        $dukkanSorgu = $db->prepare("SELECT ad FROM dukkanlar WHERE id = ?");
        $dukkanSorgu->execute([$dukkan_id]);
        $dukkanBilgi = $dukkanSorgu->fetch(PDO::FETCH_ASSOC);

        $trTarih = date('d.m.Y', strtotime($randevu_baslangic));
        $trSaat = date('H:i', strtotime($randevu_baslangic));

        // Müşterinin profili henüz tam dolmadıysa (adı soyadı yoksa) mailde hata vermemesi için önlem
        $alici_ad = (!empty($kullanici['ad'])) ? $kullanici['ad'] . ' ' . $kullanici['soyad'] : 'Değerli Müşterimiz';

        // MAİLİ GÖNDER
        require_once __DIR__ . '/../ayarlar/mail_islemleri.php';
        $mailIslemi = new MailIslemleri();
        $mailIslemi->randevuTalepMailiGonder($kullanici['eposta'], $alici_ad, $dukkanBilgi['ad'], $trTarih, $trSaat, $toplam_tutar);

        // BAŞARILI SAYFASINA VERİ TAŞIMAK İÇİN SESSİONA GEÇİCİ VERİ YÜKLE
        $_SESSION['son_randevu'] = [
            'dukkan_adi' => $dukkanBilgi['ad'],
            'tarih' => $trTarih,
            'saat' => $trSaat,
            'tutar' => $toplam_tutar
        ];
        
        // ÖZEL BAŞARI SAYFASINA YÖNLENDİR
        header("Location: /kuafor-randevu/randevu-basarili");
        exit;
    } else {
        $_SESSION['mesaj_turu'] = "hata";
        $_SESSION['mesaj'] = "Hata oluştu.";
        header("Location: /kuafor-randevu/dukkan-detay?id=" . $dukkan_id);
        exit;
    }

} 
// ==========================================
// 2. MÜŞTERİ TARAFINDAN RANDEVU İPTALİ
// ==========================================
elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['islem']) && $_POST['islem'] == 'randevu_iptal_musteri') {
    
    $randevu_id = (int)$_POST['randevu_id'];
    $musteri_id = $_SESSION['kullanici_id'];

    // Güvenlik: İptal edilmek istenen randevu gerçekten bu müşteriye mi ait?
    $kontrolSorgu = $db->prepare("SELECT id FROM randevular WHERE id = ? AND musteri_id = ?");
    $kontrolSorgu->execute([$randevu_id, $musteri_id]);
    
    if ($kontrolSorgu->rowCount() > 0) {
        // İptal işlemini modele yaptırıyoruz
        $sonuc = $randevuModel->randevuDurumGuncelle($randevu_id, 'iptal');
        
        if ($sonuc) {
            $_SESSION['mesaj_turu'] = "basari";
            $_SESSION['mesaj'] = "Randevunuz başarıyla iptal edildi.";
        } else {
            $_SESSION['mesaj_turu'] = "hata";
            $_SESSION['mesaj'] = "Sistemsel bir hata nedeniyle iptal işlemi gerçekleştirilemedi.";
        }
    } else {
        $_SESSION['mesaj_turu'] = "hata";
        $_SESSION['mesaj'] = "Yetkisiz işlem talebi!";
    }

    // İşlem bitince müşteriyi kendi paneline geri yolla
    header("Location: /kuafor-randevu/musteri-randevularim");
    exit;

} 
// ==========================================
// 3. GEÇERSİZ İŞLEM (DOĞRUDAN URL'YE GİRİLMESİ)
// ==========================================
else {
    header("Location: /kuafor-randevu/");
    exit;
}
?>