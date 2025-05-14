<?php

/*$encUrl = $_POST['url'];
$mediaKeyBase64 = $_POST['mediaKey'];
$mediaType = $_POST['mediaType'];*/
//
// Dados fornecidos pelo webhook do WhatsApp
$encUrl = 'https://mmg.whatsapp.net/v/t62.7117-24/23375481_703250688953362_101768846171896830_n.enc?ccb=11-4&oh=01_Q5Aa1gEtCz_y1KxAr6ynhCIc1JY6KNNc2-YKb8vdnzuaQZrjmA&oe=684C7D9C&_nc_sid=5e03e0&mms3=true';
$mediaKeyBase64 = '3lj3QOzAHs1vpiduOmUIRAXUGxYKlNttg3KeUuEa3LM=';
$mediaType = 'audio'; // Pode ser image, video, document, etc.*/

// 1. Baixa o arquivo .enc
$encFile = file_get_contents($encUrl);
if (!$encFile) {
    die("Erro ao baixar o arquivo .enc");
}

// 2. Deriva as chaves via HKDF (RFC 5869)
function hkdf(string $ikm, int $length, string $info = '', string $salt = '', string $algo = 'sha256'): string {
    $salt = $salt ?: str_repeat("\0", strlen(hash($algo, '', true)));
    $prk = hash_hmac($algo, $ikm, $salt, true);
    $t = '';
    $last_block = '';
    for ($i = 1; strlen($t) < $length; $i++) {
        $last_block = hash_hmac($algo, $last_block . $info . chr($i), $prk, true);
        $t .= $last_block;
    }
    return substr($t, 0, $length);
}

// 3. Converte a mediaKey
$mediaKey = base64_decode($mediaKeyBase64);
$infoString = "WhatsApp " . ucfirst($mediaType) . " Keys";
$expandedKey = hkdf($mediaKey, 112, $infoString);

// 4. Extrai chaves
$iv = substr($expandedKey, 0, 16);
$cipherKey = substr($expandedKey, 16, 32);

// 5. Descriptografa
$decrypted = openssl_decrypt($encFile, 'aes-256-cbc', $cipherKey, OPENSSL_RAW_DATA, $iv);
if (!$decrypted) {
    die("Erro ao descriptografar o áudio.");
}

// 6. Salva como .ogg
$outputFile = 'audio-decrypted.ogg';
file_put_contents($outputFile, $decrypted);

echo "Áudio descriptografado com sucesso: {$outputFile}" . PHP_EOL;
