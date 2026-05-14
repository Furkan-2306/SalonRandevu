<?php
require_once __DIR__ . '/../ayarlar/veritabani.php';
require_once __DIR__ . '/../modeller/randevu_model.php';

$vt = new Veritabani();
$db = $vt->baglantiGetir();
$model = new RandevuModel($db);

$personel_id = $_GET['personel_id'] ?? 0;
$tarih = $_GET['tarih'] ?? '';

if(empty($personel_id) || empty($tarih)) {
    echo json_encode([]);
    exit;
}

// Seçilen günün dolu/bekleyen tüm randevularını getir
$randevular = $model->getPersonelGunlukRandevulari($personel_id, $tarih);

header('Content-Type: application/json');
echo json_encode($randevular);