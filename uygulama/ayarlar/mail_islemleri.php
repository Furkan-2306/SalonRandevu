<?php
// PHPMailer sınıflarını çağırıyoruz
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// İŞTE SİHİRLİ KISIM BURASI: __DIR__ kullanarak tam yolu kilitliyoruz.
require_once __DIR__ . '/Exception.php';
require_once __DIR__ . '/PHPMailer.php';
require_once __DIR__ . '/SMTP.php';

class MailIslemleri {
    private $mail;

    public function __construct() {
        $this->mail = new PHPMailer(true);

        try {
            // Sunucu Ayarları 
            $this->mail->isSMTP();
            $this->mail->Host       = 'smtp.gmail.com'; 
            $this->mail->SMTPAuth   = true;
            $this->mail->Username   = 'salonrandevual@gmail.com'; 
            $this->mail->Password   = 'oyobzxdtgzftfjap'; 
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
            $this->mail->Port       = 465;
            $this->mail->CharSet    = 'UTF-8'; 

            // Gönderici Bilgisi (GÖNDERİCİ MAİLİ DÜZELTİLDİ)
            $this->mail->setFrom('salonrandevual@gmail.com', 'Salon Randevu Sistemi'); 
            
        } catch (Exception $e) {
            error_log("Mail Ayar Hatasi: {$this->mail->ErrorInfo}");
        }
    }

    public function dogrulamaMailiGonder($alici_eposta, $alici_ad, $onay_linki) {
        try {
            $this->mail->addAddress($alici_eposta, $alici_ad);

            $this->mail->isHTML(true);
            $this->mail->Subject = 'Hesabinizi Dogrulayin - Salon Randevu Sistemi';
            
            $this->mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                    <h2 style='color: #2c3e50; text-align: center;'>Aramiza Hos Geldiniz!</h2>
                    <p>Merhaba <b>{$alici_ad}</b>,</p>
                    <p>Salon Randevu Sistemi'ne kayit oldugunuz icin tesekkur ederiz. Hesabinizi aktiflestirmek ve randevu almaya baslamak icin lutfen asagidaki butona tiklayin:</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='{$onay_linki}' style='background-color: #d4af37; color: #fff; padding: 15px 30px; text-decoration: none; font-weight: bold; border-radius: 5px; display: inline-block;'>Hesabimi Dogrula</a>
                    </div>
                    <p style='font-size: 12px; color: #7f8c8d;'>Bu link 24 saat boyunca gecerlidir. Eger bu kaydi siz yapmadiysaniz bu maili dikkate almayiniz.</p>
                </div>
            ";

            $this->mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Dogrulama Maili Gonderilemedi: {$this->mail->ErrorInfo}");
            return false;
        }
    }

    
    public function randevuTalepMailiGonder($alici_eposta, $alici_ad, $dukkan_adi, $tarih, $saat, $tutar) {
        try {
            $this->mail->clearAddresses(); // Önceki adresleri temizle
            $this->mail->addAddress($alici_eposta, $alici_ad);

            $this->mail->isHTML(true);
            $this->mail->Subject = 'Randevu Talebiniz Alindi - Salon Randevu Sistemi';
            
            $this->mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                    <div style='text-align: center; margin-bottom: 20px;'>
                        <h1 style='color: #c89f65; font-size: 40px; margin:0;'>✓</h1>
                        <h2 style='color: #2c3e50; margin-top:10px;'>Randevu Talebiniz Alındı</h2>
                    </div>
                    <p>Merhaba <b>{$alici_ad}</b>,</p>
                    <p><b>{$dukkan_adi}</b> adlı işletmeden oluşturduğunuz randevu talebi ve ödeme provizyonu sisteme ulaştı. İşletme randevunuzu onayladığında işleminiz kesinleşecektir.</p>
                    <div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #c89f65;'>
                        <p style='margin: 5px 0;'><b>Tarih / Saat:</b> {$tarih} - {$saat}</p>
                        <p style='margin: 5px 0;'><b>Toplam Tutar:</b> {$tutar} ₺</p>
                        <p style='margin: 5px 0;'><b>Durum:</b> Onay Bekliyor ⏳</p>
                    </div>
                    <p style='font-size: 12px; color: #7f8c8d; text-align: center;'>Bizi tercih ettiğiniz için teşekkür ederiz.</p>
                </div>
            ";

            $this->mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Randevu Maili Gonderilemedi: {$this->mail->ErrorInfo}");
            return false;
        }
    }

    // 1. ONAY MAİLİ (Yeşil Tema)
    public function randevuOnayMailiGonder($alici_eposta, $alici_ad, $dukkan_adi, $tarih, $saat) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($alici_eposta, $alici_ad);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Randevunuz Onaylandi - Salon Randevu Sistemi';
            
            $this->mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                    <div style='text-align: center; margin-bottom: 20px;'>
                        <h1 style='color: #10b981; font-size: 40px; margin:0;'>✓</h1>
                        <h2 style='color: #2c3e50; margin-top:10px;'>Randevunuz Onaylandı!</h2>
                    </div>
                    <p>Merhaba <b>{$alici_ad}</b>,</p>
                    <p><b>{$dukkan_adi}</b> işletmesindeki randevunuz işletme tarafından onaylanmıştır. Sizi bekliyoruz!</p>
                    <div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #10b981;'>
                        <p style='margin: 5px 0;'><b>Tarih:</b> {$tarih}</p>
                        <p style='margin: 5px 0;'><b>Saat:</b> {$saat}</p>
                    </div>
                </div>";
            $this->mail->send();
            return true;
        } catch (Exception $e) { return false; }
    }

    // 2. İPTAL MAİLİ (Kırmızı Tema)
    public function randevuIptalMailiGonder($alici_eposta, $alici_ad, $dukkan_adi, $tarih, $saat) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($alici_eposta, $alici_ad);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Randevu Iptali - Salon Randevu Sistemi';
            
            $this->mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                    <div style='text-align: center; margin-bottom: 20px;'>
                        <h1 style='color: #ef4444; font-size: 40px; margin:0;'>✕</h1>
                        <h2 style='color: #2c3e50; margin-top:10px;'>Randevunuz İptal Edildi</h2>
                    </div>
                    <p>Merhaba <b>{$alici_ad}</b>,</p>
                    <p><b>{$dukkan_adi}</b> işletmesindeki <b>{$tarih} - {$saat}</b> tarihli randevunuz iptal edilmiştir. Yeni bir randevu almak için sistemimizi ziyaret edebilirsiniz.</p>
                </div>";
            $this->mail->send();
            return true;
        } catch (Exception $e) { return false; }
    }

    // 3. TAMAMLANDI VE YORUM MAİLİ (Altın/Yıldız Teması)
    public function randevuTamamlandiMailiGonder($alici_eposta, $alici_ad, $dukkan_adi, $tarih, $saat) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($alici_eposta, $alici_ad);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Bizi Tercih Ettiginiz Icin Tesekkurler!';
            
            $this->mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                    <div style='text-align: center; margin-bottom: 20px;'>
                        <h1 style='color: #c89f65; font-size: 40px; margin:0;'>★</h1>
                        <h2 style='color: #2c3e50; margin-top:10px;'>İşleminiz Tamamlandı</h2>
                    </div>
                    <p>Merhaba <b>{$alici_ad}</b>,</p>
                    <p><b>{$dukkan_adi}</b> işletmesindeki randevunuz başarıyla tamamlanmış ve ödemeniz alınmıştır. Bizi tercih ettiğiniz için teşekkür ederiz.</p>
                    <p>Hizmetimizden memnun kaldınız mı? Lütfen deneyiminizi puanlayarak işletmemize destek olun:</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='http://localhost/kuafor-randevu/musteri-randevularim' style='background-color: #c89f65; color: #fff; padding: 15px 30px; text-decoration: none; font-weight: bold; border-radius: 5px; display: inline-block;'>Deneyimimi Puanla</a>
                    </div>
                </div>";
            $this->mail->send();
            return true;
        } catch (Exception $e) { return false; }
    }

    // 1. HESAP ASKIYA ALINDI MAİLİ
    public function hesapAskiMailiGonder($alici_eposta, $alici_ad, $sebep) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($alici_eposta, $alici_ad);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Hesabiniz Askiya Alindi - Salon Randevu Sistemi';
            
            $sebep_metni = !empty($sebep) ? "<p><b>Sebep:</b> {$sebep}</p>" : "";

            $this->mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                    <div style='text-align: center; margin-bottom: 20px;'>
                        <h1 style='color: #dc3545; font-size: 40px; margin:0;'>!</h1>
                        <h2 style='color: #2c3e50; margin-top:10px;'>Hesabınız Askıya Alındı</h2>
                    </div>
                    <p>Merhaba <b>{$alici_ad}</b>,</p>
                    <p>Salon Randevu Sistemi üzerindeki hesabınız, yönetici kararıyla geçici olarak askıya alınmıştır. Bu süre zarfında sisteme giriş yapamazsınız.</p>
                    {$sebep_metni}
                    <p>Konuyla ilgili itirazınız veya sorunuz varsa destek ekibimizle iletişime geçebilirsiniz.</p>
                </div>";
            $this->mail->send();
            return true;
        } catch (Exception $e) { return false; }
    }

    // 2. HESAP KİLİDİ AÇILDI MAİLİ
    public function hesapAktifMailiGonder($alici_eposta, $alici_ad) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($alici_eposta, $alici_ad);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Hesabiniz Tekrar Aktif - Salon Randevu Sistemi';
            
            $this->mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                    <div style='text-align: center; margin-bottom: 20px;'>
                        <h1 style='color: #198754; font-size: 40px; margin:0;'>✓</h1>
                        <h2 style='color: #2c3e50; margin-top:10px;'>Hoş Geldiniz! Hesabınız Aktif</h2>
                    </div>
                    <p>Merhaba <b>{$alici_ad}</b>,</p>
                    <p>Daha önce askıya alınan hesabınız yönetici tarafından tekrar aktif edilmiştir. Artık sisteme giriş yapabilir ve randevularınızı yönetmeye devam edebilirsiniz.</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='http://localhost/kuafor-randevu/giris-kayit' style='background-color: #212529; color: #fff; padding: 15px 30px; text-decoration: none; font-weight: bold; border-radius: 5px; display: inline-block;'>Sisteme Giriş Yap</a>
                    </div>
                </div>";
            $this->mail->send();
            return true;
        } catch (Exception $e) { return false; }
    }

    // 3. HESAP KALICI OLARAK SİLİNDİ MAİLİ
    public function hesapSilindiMailiGonder($alici_eposta, $alici_ad, $sebep) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($alici_eposta, $alici_ad);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Hesabiniz Silindi - Salon Randevu Sistemi';
            
            $sebep_metni = !empty($sebep) ? "<p><b>Belirtilen Sebep:</b> {$sebep}</p>" : "";

            $this->mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                    <h2 style='color: #2c3e50;'>Hesabınız Kalıcı Olarak Silindi</h2>
                    <p>Sayın <b>{$alici_ad}</b>,</p>
                    <p>Sistemimizdeki üyeliğiniz ve tüm verileriniz yönetici tarafından kalıcı olarak silinmiştir.</p>
                    {$sebep_metni}
                    <p>Bizimle çalıştığınız için teşekkür ederiz.</p>
                </div>";
            $this->mail->send();
            return true;
        } catch (Exception $e) { return false; }
    }

    // ŞİFRE SIFIRLAMA MAİLİ
    public function sifreSifirlamaMailiGonder($alici_eposta, $alici_ad, $sifirlama_linki) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($alici_eposta, $alici_ad);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Sifre Sifirlama Talebi - Salon Randevu Sistemi';
            
            $this->mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                    <div style='text-align: center; margin-bottom: 20px;'>
                        <h1 style='color: #0d6efd; font-size: 40px; margin:0;'>🔑</h1>
                        <h2 style='color: #2c3e50; margin-top:10px;'>Şifre Sıfırlama Talebi</h2>
                    </div>
                    <p>Merhaba <b>{$alici_ad}</b>,</p>
                    <p>Hesabınız için bir şifre sıfırlama talebi aldık. Yeni şifrenizi güvenli bir şekilde belirlemek için lütfen aşağıdaki butona tıklayın:</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='{$sifirlama_linki}' style='background-color: #0d6efd; color: #fff; padding: 15px 30px; text-decoration: none; font-weight: bold; border-radius: 5px; display: inline-block;'>Şifrenizi Sıfırlayın</a>
                    </div>
                    <p style='font-size: 12px; color: #7f8c8d;'>Bu bağlantı sadece 1 saat boyunca geçerlidir. Eğer bu talebi siz oluşturmadıysanız, lütfen bu e-postayı dikkate almayın; hesabınız güvendedir.</p>
                </div>";
            $this->mail->send();
            return true;
        } catch (Exception $e) { return false; }
    }
}

?>