<?php
class KullaniciModel {
    private $conn;
    
    private $tablo_kullanicilar = "kullanicilar";
    private $tablo_profiller = "kullanici_profilleri";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function emailKontrol($eposta) {
        $sorgu = "SELECT id, rol, sifre_hash, dogrulandi_mi FROM " . $this->tablo_kullanicilar . " WHERE eposta = :eposta LIMIT 0,1";
        
        $stmt = $this->conn->prepare($sorgu);
        $eposta = htmlspecialchars(strip_tags($eposta));
        $stmt->bindParam(':eposta', $eposta);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    public function musteriKaydet($eposta, $sifre_hash, $ad, $soyad, $telefon, $cinsiyet, $dogrulama_kodu) {
        try {
            $this->conn->beginTransaction();

            $sorgu1 = "INSERT INTO " . $this->tablo_kullanicilar . " (rol, eposta, sifre_hash, dogrulama_kodu, kod_olusturulma_tarihi) VALUES ('musteri', :eposta, :sifre, :kod, NOW())";
            $stmt1 = $this->conn->prepare($sorgu1);
            $stmt1->bindParam(':eposta', $eposta);
            $stmt1->bindParam(':sifre', $sifre_hash);
            $stmt1->bindParam(':kod', $dogrulama_kodu);
            $stmt1->execute();

            $yeni_kullanici_id = $this->conn->lastInsertId();

            $sorgu2 = "INSERT INTO " . $this->tablo_profiller . " (kullanici_id, ad, soyad, telefon, cinsiyet) VALUES (:uid, :ad, :soyad, :tel, :cinsiyet)";
            $stmt2 = $this->conn->prepare($sorgu2);
            $stmt2->bindParam(':uid', $yeni_kullanici_id);
            $stmt2->bindParam(':ad', $ad);
            $stmt2->bindParam(':soyad', $soyad);
            $stmt2->bindParam(':tel', $telefon);
            $stmt2->bindParam(':cinsiyet', $cinsiyet);
            $stmt2->execute();

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function hesabiDogrula($kod) {
        $sorgu = "UPDATE " . $this->tablo_kullanicilar . " 
                  SET dogrulandi_mi = 1, dogrulama_kodu = NULL, kod_olusturulma_tarihi = NULL 
                  WHERE dogrulama_kodu = :kod AND dogrulandi_mi = 0 AND kod_olusturulma_tarihi >= NOW() - INTERVAL 24 HOUR";
        
        $stmt = $this->conn->prepare($sorgu);
        $stmt->bindParam(':kod', $kod);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
}
?>