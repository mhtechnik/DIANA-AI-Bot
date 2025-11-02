<?php
if (!defined('ABSPATH')) exit;

class Diana_Chat_Rest {
  public static function init(){
    add_action('rest_api_init', [__CLASS__, 'routes']);
  }

  public static function routes(){
    register_rest_route('diana/v1', '/chat', [
      'methods'  => 'POST',
      'permission_callback' => '__return_true',
      'callback' => [__CLASS__, 'handle'],
    ]);
  }

  public static function handle(WP_REST_Request $req){
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

    $make_request = function(array $payload) use ($url, $apiKey) {
      return wp_remote_post($url, [
        'headers' => [
          'Authorization' => 'Bearer ' . $apiKey,
          'Content-Type'  => 'application/json',
        ],
        'timeout' => 60,
        'body'    => wp_json_encode($payload),
      ]);
    };

    $res  = $make_request($payload);
    $code = is_wp_error($res) ? 0 : (int) wp_remote_retrieve_response_code($res);
    $json = is_wp_error($res) ? null : json_decode(wp_remote_retrieve_body($res), true);

    if (!$code || $code >= 400) {
      $msgErr = is_array($json) ? ($json['error']['message'] ?? '') : '';
      $changed = false;
      if (stripos($msgErr, "Unsupported parameter: 'temperature'") !== false && isset($payload['temperature'])) { unset($payload['temperature']); $changed = true; }
      if (stripos($msgErr, "Unsupported parameter: 'stop'") !== false && isset($payload['stop'])) { unset($payload['stop']); $changed = true; }
      if ($changed) {
        $res  = $make_request($payload);
        $code = is_wp_error($res) ? 0 : (int) wp_remote_retrieve_response_code($res);
        $json = is_wp_error($res) ? null : json_decode(wp_remote_retrieve_body($res), true);
      }
    }

    if (is_wp_error($res)) {
      $err = $res->get_error_message();
      if (stripos($err, 'cURL error 28') !== false || stripos($err, 'timed out') !== false) {
        usleep(2000000);
        $res  = $make_request($payload);
        if (is_wp_error($res)) { usleep(5000000); $res = $make_request($payload); }
        $code = is_wp_error($res) ? 0 : (int) wp_remote_retrieve_response_code($res);
        $json = is_wp_error($res) ? null : json_decode(wp_remote_retrieve_body($res), true);
      }
    }

    if (is_wp_error($res)) {
      error_log('[Diana Chat] HTTP Fehler: '.$res->get_error_message());
      return new WP_REST_Response(['error'=>$res->get_error_message()], 502);
    }

    if ($code < 200 || $code >= 300) {
      $err = $json['error']['message'] ?? ('HTTP ' . $code);
      if ($code === 401) $err .= ' - API Key prüfen';
      if ($code === 429) $err .= ' - Rate Limit erreicht';
      return new WP_REST_Response(['error'=>$err], $code);
    }

    $reply = '';
    if (isset($json['output_text'])) $reply = is_array($json['output_text']) ? implode('', $json['output_text']) : (string)$json['output_text'];
    if ($reply === '' && !empty($json['output']) && is_array($json['output'])) {
      $buf=''; foreach ($json['output'] as $blk) {
        if (!empty($blk['text'])) $buf .= $blk['text'];
        if (!empty($blk['content']) && is_array($blk['content'])) {
          foreach ($blk['content'] as $c) if (!empty($c['text'])) $buf .= $c['text'];
        }
      } $reply=$buf;
    }
    if ($reply === '' && isset($json['choices'][0]['message']['content'])) $reply = (string)$json['choices'][0]['message']['content'];
    if ($reply === '' && isset($json['choices'][0]['text'])) $reply = (string)$json['choices'][0]['text'];
    if ($reply === '') return new WP_REST_Response(['error'=>'empty response'], 502);

    return new WP_REST_Response(['reply'=>$reply], 200);
  }
}
Diana_Chat_Rest::init();
