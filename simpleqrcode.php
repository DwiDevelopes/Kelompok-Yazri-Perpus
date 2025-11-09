<?php
// simpleqrcode.php - QR Code Generator menggunakan API external
class SimpleQRCode {
    public static function generate($data, $filename = null, $size = 300) {
        // Encode data untuk URL
        $encoded_data = urlencode($data);
        
        // URL API QR Code Generator
        $api_url = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data={$encoded_data}";
        
        try {
            // Download QR code
            $qr_content = file_get_contents($api_url);
            
            if ($qr_content === false) {
                throw new Exception("Gagal mengakses API QR Code");
            }
            
            // Simpan ke file jika filename diberikan
            if ($filename) {
                $result = file_put_contents($filename, $qr_content);
                if ($result === false) {
                    throw new Exception("Gagal menyimpan file QR Code");
                }
                return $filename;
            }
            
            // Return sebagai data URL jika tidak ada filename
            return 'data:image/png;base64,' . base64_encode($qr_content);
            
        } catch (Exception $e) {
            throw new Exception("Error generating QR Code: " . $e->getMessage());
        }
    }
    
    // Alternative method using Google Charts API
    public static function generateGoogle($data, $filename = null, $size = 300) {
        $encoded_data = urlencode($data);
        $api_url = "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl={$encoded_data}&choe=UTF-8";
        
        try {
            $qr_content = file_get_contents($api_url);
            
            if ($qr_content === false) {
                throw new Exception("Gagal mengakses Google Charts API");
            }
            
            if ($filename) {
                file_put_contents($filename, $qr_content);
                return $filename;
            }
            
            return 'data:image/png;base64,' . base64_encode($qr_content);
            
        } catch (Exception $e) {
            throw new Exception("Error generating QR Code with Google: " . $e->getMessage());
        }
    }
}
?>