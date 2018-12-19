#!/usr/bin/php -q
<?php

ini_set('date.timezone', 'Europe/Paris');

$today = new DateTime();
$filepath = $today->format('Y/m/d').'.jpg';

$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => 'http://www.itsbacktothefutureday.com/images/'.$filepath,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET',
    CURLOPT_HTTPHEADER => array(
        'Cache-Control: no-cache',
    ),
));

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    die('cURL Error: ' . $err);
}

require_once __DIR__ .'/vendor/autoload.php';

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

$settings = array(
    'oauth_access_token' => $_ENV['OAUTH_ACCESS_TOKEN'],
    'oauth_access_token_secret' => $_ENV['OAUTH_ACCESS_TOKEN_SECRET'],
    'consumer_key' => $_ENV['CONSUMER_KEY'],
    'consumer_secret' => $_ENV['CONSUMER_SECRET'],
);

$twitter = new TwitterAPIExchange($settings);

$url = 'https://upload.twitter.com/1.1/media/upload.json';
$requestMethod = 'POST';
$postfields = array(
    'media_data' => base64_encode($response)
);

try {
    $data = $twitter->request($url, $requestMethod, $postfields);
    $data = @json_decode($data, true);

    if (empty($data['media_id'])) {
        throw new Exception('No attachment');
    }

    $mediaId = $data['media_id'];
} catch(Exception $e) {
    die('Twitter Upload Error: ' . $e->getMessage());
}

$url = 'https://api.twitter.com/1.1/statuses/update.json';
$requestMethod = 'POST';

$postfields = array(
    'status' => "It's today!",
    'media_ids' => $mediaId
);

switch($today->format('d/m')) {
    case '25/12':
        $postfields['status'] .= ' And happy christmas! 🎁 🎄 🎅';
        break;

    case '01/01':
        $postfields['status'] .= ' And happy new year! 🎉';
        break;
}

try {
    $result = $twitter->request($url, $requestMethod, $postfields);
    $result = @json_decode($result, true);
} catch(Exception $e) {
    die('Twitter Status Error: ' . $e->getMessage());
}

if (isset($result['id'])) {
    echo 'https://twitter.com/'.$_ENV['ACCOUNT_NAME'].'/status/'.$result['id'];
}