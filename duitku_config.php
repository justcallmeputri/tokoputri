
<?php

// Konfigurasi Duitku
$merchantCode = "DS38200";
$apiKey = "a9ac5646e0d81ee4c2c1c036ed92fad6";

// Gunakan Sandbox untuk pengujian
$duitkuEnvironment = "sandbox";

if ($duitkuEnvironment === "sandbox") {
    $duitkuUrl = "https://sandbox.duitku.com/webapi/api/merchant/v2/inquiry";
} else {
    $duitkuUrl = "https://passport.duitku.com/webapi/api/merchant/v2/inquiry";
}

?>
