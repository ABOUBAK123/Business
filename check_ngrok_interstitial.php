<?php
$url = $argv[1] ?? '';
if ($url === '') { echo "usage: php check_ngrok_interstitial.php <url>\n"; exit(1);}
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_HEADER => true,
    CURLOPT_NOBODY => false,
]);
$resp = curl_exec($ch);
if ($resp === false) {
    echo 'curl_error=' . curl_error($ch) . PHP_EOL;
    exit(1);
}
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$hsize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$body = substr($resp, $hsize);
curl_close($ch);

echo 'status=' . $code . PHP_EOL;
echo 'has_ngrok_err=' . (strpos($body, 'ERR_NGROK_6024') !== false ? 'YES' : 'NO') . PHP_EOL;
