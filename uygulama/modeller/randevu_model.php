<?php
class RandevuModel {
    private $conn;
    private $tablo = "randevular";

    public function __construct($db) {
        $this->conn = $db;
    }

    // 1. KUSURSUZ ZAMAN ÇAKIŞMASI KONTROLÜ
    public function cakismaVarMi($personel_id, $yeni_baslangic, $yeni_bitis) {
        $sorgu = "SELECT id FROM " . $this->tablo . " 
                  WHERE personel_id = :p_id 
                  AND durum != 'iptal'
                  AND (
                      (randevu_tarih_saat <= :baslangic AND bitis_tarih_saat > :baslangic) OR
                      (randevu_tarih_saat < :bitis AND bitis_tarih_saat >= :bitis) OR
                      (randevu_tarih_saat >= :baslangic AND bitis_tarih_saat <= :bitis)
                  )";
                  
        $stmt = $this->conn->prepare($sorgu);
        $stmt->execute([
            'p_id'      => $personel_id, 
            'baslangic' => $yeni_baslangic,
            'bitis'     => $yeni_bitis
        ]);
        
        return $stmt->rowCount() > 0;
    }

    // 2. ÇOKLU HİZMET DESTEKLİ KAYIT
    public function randevuOlustur($dukkan_id, $musteri_id, $personel_id, $hizmet_idler, $randevu_tarih_saat, $bitis_tarih_saat, $toplam_tutar) {
        try {
            $sorgu = "INSERT INTO " . $this->tablo . " 
                      (dukkan_id, musteri_id, personel_id, hizmet_id, randevu_tarih_saat, bitis_tarih_saat, durum, toplam_tutar) 
                      VALUES (:d_id, :m_id, :p_id, :h_id, :r_zaman, :b_zaman, 'bekliyor', :tutar)";
            
            $stmt = $this->conn->prepare($sorgu);
            $stmt->execute([
                'd_id'    => $dukkan_id,
                'm_id'    => $musteri_id,
                'p_id'    => $personel_id,
                'h_id'    => $hizmet_idler,
                'r_zaman' => $randevu_tarih_saat,
                'b_zaman' => $bitis_tarih_saat,
                'tutar'   => $toplam_tutar
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log("Randevu Kayıt Hatası: " . $e->getMessage());
            return false;
        }
    }

    // 3. EKSİK OLAN FONKSİYON (GÜNLÜK RANDEVULARI ÇEKME)
    // AJAX'ın saatleri boyamak için kullandığı fonksiyon budur.
    public function getPersonelGunlukRandevulari($personel_id, $tarih) {
        $sorgu = "SELECT randevu_tarih_saat as baslangic, bitis_tarih_saat as bitis, durum 
                  FROM " . $this->tablo . " 
                  WHERE personel_id = :p_id 
                  AND DATE(randevu_tarih_saat) = :tarih 
                  AND durum != 'iptal'";
                  
        $stmt = $this->conn->prepare($sorgu);
        $stmt->execute([
            'p_id'  => $personel_id, 
            'tarih' => $tarih
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // RANDEVU DURUMUNU GÜNCELLE (İptal, Onay, Tamamlandı işlemleri için ortak fonksiyon)
    public function randevuDurumGuncelle($randevu_id, $yeni_durum) {
        try {
            $sorgu = "UPDATE " . $this->tablo . " SET durum = :durum WHERE id = :id";
            $stmt = $this->conn->prepare($sorgu);
            return $stmt->execute([
                'durum' => $yeni_durum,
                'id'    => $randevu_id
            ]);
        } catch (Exception $e) {
            error_log("Randevu Güncelleme Hatası: " . $e->getMessage());
            return false;
        }
    }
}
?>