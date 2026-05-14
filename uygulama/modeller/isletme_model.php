<?php
class IsletmeModel {
    private $conn;
    

    private $tablo_dukkanlar = "dukkanlar";
    private $tablo_personeller = "personeller";
    private $tablo_hizmetler = "hizmetler";

    public function __construct($db) {
        $this->conn = $db;
    }


    public function dukkanKaydet($sahip_id, $dukkanVerisi, $personelVerisi, $hizmetVerisi) {
        try {

            $this->conn->beginTransaction();


            $sorguDukkan = "INSERT INTO " . $this->tablo_dukkanlar . " 
                            (sahip_id, ad, vergi_no, tc_kimlik, sehir, ilce, adres, enlem, boylam, aciklama, aktif_mi) 
                            VALUES (:sahip_id, :ad, :vergi_no, :tc_kimlik, :sehir, :ilce, :adres, :enlem, :boylam, :aciklama, 0)";
            
            $stmtDukkan = $this->conn->prepare($sorguDukkan);
            
            $stmtDukkan->bindParam(':sahip_id', $sahip_id);
            $stmtDukkan->bindParam(':ad', $dukkanVerisi['ad']);
            $stmtDukkan->bindParam(':vergi_no', $dukkanVerisi['vergi_no']);
            $stmtDukkan->bindParam(':tc_kimlik', $dukkanVerisi['tc_kimlik']);
            $stmtDukkan->bindParam(':sehir', $dukkanVerisi['sehir']);
            $stmtDukkan->bindParam(':ilce', $dukkanVerisi['ilce']);
            $stmtDukkan->bindParam(':adres', $dukkanVerisi['adres']);
            $stmtDukkan->bindParam(':enlem', $dukkanVerisi['enlem']);
            $stmtDukkan->bindParam(':boylam', $dukkanVerisi['boylam']);
            $stmtDukkan->bindParam(':aciklama', $dukkanVerisi['aciklama']);
            
            $stmtDukkan->execute();
            
            $yeni_dukkan_id = $this->conn->lastInsertId();



            if (!empty($personelVerisi)) {
                $sorguPersonel = "INSERT INTO " . $this->tablo_personeller . " (dukkan_id, ad, soyad) VALUES (:dukkan_id, :ad, :soyad)";
                $stmtPersonel = $this->conn->prepare($sorguPersonel);

                // Formdan gelen her bir personel için döngü
                foreach ($personelVerisi as $personel) {
                    $stmtPersonel->bindParam(':dukkan_id', $yeni_dukkan_id);
                    $stmtPersonel->bindParam(':ad', $personel['ad']);
                    $stmtPersonel->bindParam(':soyad', $personel['soyad']);
                    $stmtPersonel->execute();
                }
            }



            if (!empty($hizmetVerisi)) {
                $sorguHizmet = "INSERT INTO " . $this->tablo_hizmetler . " (dukkan_id, ad, sure_dakika, fiyat) VALUES (:dukkan_id, :ad, :sure, :fiyat)";
                $stmtHizmet = $this->conn->prepare($sorguHizmet);

                
                foreach ($hizmetVerisi as $hizmet) {
                    $stmtHizmet->bindParam(':dukkan_id', $yeni_dukkan_id);
                    $stmtHizmet->bindParam(':ad', $hizmet['ad']);
                    $stmtHizmet->bindParam(':sure', $hizmet['sure']);
                    $stmtHizmet->bindParam(':fiyat', $hizmet['fiyat']);
                    $stmtHizmet->execute();
                }
            }

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
           
            $this->conn->rollBack();
            error_log("İşletme Kayıt Hatası: " . $e->getMessage());
            return false;
        }
    }
}
?>