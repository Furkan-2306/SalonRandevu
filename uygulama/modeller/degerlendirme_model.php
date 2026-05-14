<?php
class DegerlendirmeModel {
    private $conn;
    private $tablo = "degerlendirmeler";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Müşterinin bu dükkana daha önce yorum yapıp yapmadığını kontrol eder
    public function yorumVarMi($dukkan_id, $musteri_id) {
        $sorgu = $this->conn->prepare("SELECT id FROM " . $this->tablo . " WHERE dukkan_id = ? AND musteri_id = ?");
        $sorgu->execute([$dukkan_id, $musteri_id]);
        return $sorgu->rowCount() > 0;
    }

    // Yeni değerlendirmeyi veritabanına ekler
    public function yorumEkle($randevu_id, $dukkan_id, $musteri_id, $puan, $yorum, $tarih) {
        try {
            $sorgu = $this->conn->prepare("INSERT INTO " . $this->tablo . " (randevu_id, dukkan_id, musteri_id, puan, yorum, tarih) VALUES (?, ?, ?, ?, ?, ?)");
            return $sorgu->execute([$randevu_id, $dukkan_id, $musteri_id, $puan, $yorum, $tarih]);
        } catch (Exception $e) {
            error_log("Değerlendirme Kayıt Hatası: " . $e->getMessage());
            return false;
        }
    }
}
?>