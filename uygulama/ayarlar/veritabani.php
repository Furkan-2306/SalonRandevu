<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

class Veritabani {
    private $host = "localhost";
    private $db_name = "kuafor_randevu_sistemi"; 
    private $username = "root";
    private $password = ""; 
    public $conn;

    public function baglantiGetir() {
        $this->conn = null;

        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4", $this->username, $this->password);
            
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
        } catch(PDOException $exception) {
            echo "<div style='background:#ff4444; color:white; padding:10px; text-align:center;'>
                    <strong>Veritabanı Bağlantı Hatası:</strong> " . $exception->getMessage() . "
                  </div>";
            die();
        }

        return $this->conn;
    }
}
?>