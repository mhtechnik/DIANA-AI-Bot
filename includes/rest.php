<?php
if (!defined('ABSPATH')) exit;

/* OpenAI Request mit Retries */
function diana_openai_post($url, $apiKey, array $payload, $timeout = 60, $maxRetry = 3){
  $attempt = 0; $lastErr = null;

  $stripUnsupported = function($msg, &$payload){
    $changed = false;
    if (stripos($msg, "Unsupported parameter: 'temperature'") !== false && isset($payload['temperature'])) { unset($payload['temperature']); $changed = true; }
    if (stripos($msg, "Unsupported parameter: 'stop'") !== false && isset($payload['stop'])) { unset($payload['stop']); $changed = true; }
    return $changed;
  };

  while ($attempt < $maxRetry) {
    $attempt++;
    $res = wp_remote_post($url, [
      'headers' => [
        'Authorization' => 'Bearer ' . $apiKey,
        'Content-Type'  => 'application/json',
        'Accept'        => 'application/json',
      ],
      'body'        => wp_json_encode($payload),
      'timeout'     => $timeout,
      'httpversion' => '1.1',
      'decompress'  => true,
      'blocking'    => true,
    ]);

    if (is_wp_error($res)) {
      $lastErr = $res;
      $msg = $res->get_error_message();
      if ($attempt < $maxRetry && (stripos($msg, 'cURL error 28') !== false || stripos($msg, 'timed out') !== false || stripos($msg, 'connection') !== false)) {
        usleep(200000 * $attempt);
        continue;
      }
      break;
    }

    $code = (int) wp_remote_retrieve_response_code($res);
    $body = (string) wp_remote_retrieve_body($res);

    if ($code === 429 || ($code >= 500 && $code < 600)) {
      if ($attempt < $maxRetry) {
        $retryAfter = (int) (wp_remote_retrieve_header($res, 'retry-after') ?: 0);
        $delayMs = max($retryAfter * 1000, 200 * $attempt);
        usleep($delayMs * 1000);
        continue;
      }
    }

    $trim = ltrim($body, "\xEF\xBB\xBF \t\r\n");
    $json = json_decode($trim, true);

    if ($code >= 400) {
      $apiMsg = $json['error']['message'] ?? '';
      if ($stripUnsupported($apiMsg, $payload)) {
        $res2 = wp_remote_post($url, [
          'headers' => [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
          ],
          'body'        => wp_json_encode($payload),
          'timeout'     => $timeout,
          'httpversion' => '1.1',
          'decompress'  => true,
          'blocking'    => true,
        ]);
        if (!is_wp_error($res2)) {
          $code = (int) wp_remote_retrieve_response_code($res2);
          $trim2 = ltrim((string) wp_remote_retrieve_body($res2), "\xEF\xBB\xBF \t\r\n");
          $json  = json_decode($trim2, true);
        }
      }
    }

    return [$code, $json, $body, null];
  }

  return [0, null, '', $lastErr];
}

/* Antwort extrahieren */
function diana_extract_reply($json){
  if (!is_array($json)) return '';
  if (!empty($json['output_text'])) return is_array($json['output_text']) ? implode('', $json['output_text']) : (string)$json['output_text'];
  if (!empty($json['output']) && is_array($json['output'])) {
    $buf=''; foreach ($json['output'] as $blk) {
      if (!empty($blk['text'])) $buf .= $blk['text'];
      if (!empty($blk['content']) && is_array($blk['content'])) foreach ($blk['content'] as $c) if (!empty($c['text'])) $buf .= $c['text'];
    } if ($buf !== '') return $buf;
  }
  if (isset($json['choices'][0]['message']['content'])) return (string)$json['choices'][0]['message']['content'];
  if (isset($json['choices'][0]['text'])) return (string)$json['choices'][0]['text'];
  if (!empty($json['message']['content'])) return (string)$json['message']['content'];
  return '';
}

/* REST Endpoint */
add_action('rest_api_init', function () {
  register_rest_route('diana/v1', '/chat', [
    'methods' => 'POST',
    'permission_callback' => '__return_true',
    'callback' => function ($req) {
      $rl = diana_check_rate_limit(); if (is_wp_error($rl)) return new WP_REST_Response(['error'=>$rl->get_error_message()], (int)$rl->get_error_data()['status']);
      $oc = diana_check_origin();    if (is_wp_error($oc)) return new WP_REST_Response(['error'=>$oc->get_error_message()], (int)$oc->get_error_data()['status']);

      $msg = trim(wp_strip_all_tags($req->get_param('message') ?? ''));
      if ($msg === '') return new WP_REST_Response(['error'=>'no message'], 400);
      if (strlen($msg) > 4000) return new WP_REST_Response(['error'=>'Nachricht zu lang'], 413);

      $apiKey = trim(get_option('diana_openai_api_key',''));
      if ($apiKey === '') return new WP_REST_Response(['error'=>'API Key fehlt'], 500);

      $base = trim(get_option('diana_base_url','https://api.openai.com'));
      if ($base === '' || !wp_http_validate_url($base)) $base = 'https://api.openai.com';
      $base = rtrim($base, '/');

      $model   = get_option('diana_model','gpt-5');
      $maxTok  = (int) get_option('diana_max_tokens', 1000);
      $tempOpt = get_option('diana_temperature','');
      $stopCsv = trim(get_option('diana_stop_sequences',''));
      $system  = get_option('diana_system_prompt','Du bist Diana, eine ruhige Co Moderatorin. Antworte kurz und klar.');

      $url = $base . '/v1/responses';
      $sendTemperature = !(stripos($model,'gpt-5') === 0);

      $stop = [];
      if ($stopCsv !== '') $stop = array_values(array_filter(array_map('trim', explode(',', $stopCsv))));

      $payload = [
        'model' => $model,
        'input' => [
          ['role'=>'system','content'=>$system],
          ['role'=>'user',  'content'=>$msg],
        ],
        'max_output_tokens' => $maxTok
      ];
      if ($sendTemperature && $tempOpt !== '') $payload['temperature'] = (float) $tempOpt;
      if (!empty($stop)) $payload['stop'] = $stop;

      list($code, $json, $raw, $err) = diana_openai_post($url, $apiKey, $payload, 60, 3);
      if ($err instanceof WP_Error) return new WP_REST_Response(['error'=>$err->get_error_message()], 502);
      if ($code < 200 || $code >= 300) {
        $msgErr = is_array($json) ? ($json['error']['message'] ?? '') : '';
        return new WP_REST_Response(['error'=>$msgErr ?: ('HTTP '.$code)], max(400,$code ?: 502));
      }

      $reply = diana_extract_reply($json);
      if ($reply === '') {
        if (is_string($raw) && trim($raw) !== '') $reply = trim($raw);
      }
      if ($reply === '') return new WP_REST_Response(['error'=>'empty response'], 502);

      return new WP_REST_Response(['reply'=>$reply], 200);
    }
  ]);
});
