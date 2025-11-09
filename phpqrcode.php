<?php
// phpqrcode.php - Simple QR Code Generator tanpa GD
class SimpleQRCode {
    public static function generate($data, $filename = null, $size = 200) {
        $url = "https://api.qrserver.com/v1/create-qr-code/?size=" . $size . "x" . $size . "&data=" . urlencode($data);
        
        if ($filename) {
            $qr = file_get_contents($url);
            file_put_contents($filename, $qr);
            return $filename;
        }
        
        return $url;
    }
}
?>