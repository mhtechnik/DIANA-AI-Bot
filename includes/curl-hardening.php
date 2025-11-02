<?php
if (!defined('ABSPATH')) exit;

/* cURL Feintuning */
add_action('http_api_curl', function($handle, $r, $url){
  if (strpos($url, 'api.openai.com') !== false) {
    if (defined('CURL_IPRESOLVE_V4')) curl_setopt($handle, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    if (defined('CURL_HTTP_VERSION_1_1')) curl_setopt($handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($handle, CURLOPT_TIMEOUT, 60);

    $hdrs = [];
    if (!empty($r['headers'])) foreach ($r['headers'] as $k => $v) $hdrs[] = $k . ': ' . $v;
    $hdrs[] = 'Expect:';            // kein 100-continue
    $hdrs[] = 'Connection: close';  // sauber schließen
    curl_setopt($handle, CURLOPT_HTTPHEADER, $hdrs);
  }
}, 10, 3);
