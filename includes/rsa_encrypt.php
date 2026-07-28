<?php
// Generate keys: openssl genrsa -out private.pem 2048 && openssl rsa -in private.pem -pubout -out public.pem
function encryptData($data, $publicKeyPath = 'public.pem') {
    $publicKey = file_get_contents($publicKeyPath);
    openssl_public_encrypt($data, $encrypted, $publicKey);
    return base64_encode($encrypted);
}

function decryptData($encryptedData, $privateKeyPath = 'private.pem') {
    $privateKey = file_get_contents($privateKeyPath);
    openssl_private_decrypt(base64_decode($encryptedData), $decrypted, $privateKey);
    return $decrypted;
}
?>