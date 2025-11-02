<?php
/**
 * Plugin Name: Diana Chat
 * Description: Chat Widget mit OpenAI Responses API. Markdown, YouTube und PDF, Prompt-Buttons, Tipp-Indicator, Rate-Limit, Origin-Check, Farben, DSGVO-Einwilligung mit Ablauf.
 * Version: 2.0.3
 */

if (!defined('ABSPATH')) exit;

define('DIANA_CHAT_VER', '2.0.3');
if (!defined('DIANA_BURST_10S')) define('DIANA_BURST_10S', 5);
if (!defined('DIANA_HOURLY'))     define('DIANA_HOURLY', 120);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/rest.php';
require_once __DIR__ . '/includes/curl-hardening.php';
if (file_exists(__DIR__ . '/includes/cleanup.php')) {
  require_once __DIR__ . '/includes/cleanup.php';
}

/* Assets */
add_action('wp_enqueue_scripts', function () {
  $base = plugin_dir_url(__FILE__);
  wp_enqueue_style('diana-chat', $base . 'assets/css/diana-chat.css', [], DIANA_CHAT_VER);
  wp_enqueue_script('diana-consent', $base . 'assets/js/diana-consent.js', [], DIANA_CHAT_VER, true);
  wp_enqueue_script('diana-chat', $base . 'assets/js/diana-chat.js', ['diana-consent'], DIANA_CHAT_VER, true);
});

/* Aktivierung Cleanup Job */
register_activation_hook(__FILE__, function(){
  if (!wp_next_scheduled('diana_daily_cleanup')) {
    wp_schedule_event(time() + 3600, 'daily', 'diana_daily_cleanup');
  }
});
register_deactivation_hook(__FILE__, function(){
  $ts = wp_next_scheduled('diana_daily_cleanup');
  if ($ts) wp_unschedule_event($ts, 'diana_daily_cleanup');
});

/* Shortcode */
add_shortcode('diana_chat', function () {
  $name       = esc_html(get_option('diana_assistant_name', 'DiANA'));
  $avatar     = esc_url(get_option('diana_avatar_url', ''));
  $greeting   = wp_kses_post(get_option('diana_greeting', 'Hallo, ich bin DiANA. Wie kann ich helfen?'));
  $suggest    = get_option('diana_suggestions', '');
  $endpoint   = esc_url(rest_url('diana/v1/chat'));
  $consentTxt = wp_kses_post(get_option('diana_consent_text',
    'Um mit DiANA zu chatten, werden Ihre Eingaben an den KI-Dienst OpenAI (USA) übertragen. Bitte stimmen Sie der Verarbeitung gemäß unserer Datenschutzerklärung zu.'
  ));
  $privacyUrl  = esc_url(get_option('diana_privacy_url', home_url('/datenschutz/')));
  $consentDays = max(1, (int) get_option('diana_consent_days', 30)); // NEU

  // PDF-Regeln
  $pdfRulesRaw = (string) get_option('diana_pdfs', '');
  $pdfRules = [];
  if (trim($pdfRulesRaw) !== '') {
    $lines = preg_split('/\r\n|\r|\n/', $pdfRulesRaw);
    foreach ($lines as $line) {
      if (!trim($line)) continue;
      $parts = array_map('trim', explode('|', $line));
      if (count($parts) >= 3) {
        $pdfRules[] = [
          'regex' => $parts[0],
          'title' => $parts[1],
          'url'   => $parts[2],
          'thumb' => $parts[3] ?? ''
        ];
      }
    }
  }
  $buttons = array_values(array_filter(array_map('trim', explode(',', $suggest))));

  // Farben
  $colors = [
    'primary' => get_option('diana_color_primary', '#1a6ce6'),
    'accent'  => get_option('diana_color_accent',  '#09a3e3'),
    'dark'    => get_option('diana_color_dark',    '#0e2a4a'),
    'text'    => get_option('diana_color_text',    '#0b1220'),
    'bg'      => get_option('diana_color_bg',      '#f7fafc'),
    'border'  => get_option('diana_color_border',  '#dbe5f1'),
    'inputbg' => get_option('diana_color_input_bg','#eef6ff'),
  ];

  $wrap_id = 'diana-' . wp_generate_password(6, false, false);

  // Config ins JS, inkl. consentDays
  $cfg = [
    'endpoint'    => $endpoint,
    'name'        => $name,
    'avatar'      => $avatar,
    'greeting'    => wp_strip_all_tags($greeting),
    'suggest'     => $buttons,
    'pdfRules'    => $pdfRules,
    'consentText' => wp_strip_all_tags($consentTxt),
    'privacyUrl'  => $privacyUrl,
    'consentDays' => $consentDays, // NEU
    'lsKey'       => 'diana_chat_log_v10'
  ];
  wp_add_inline_script(
    'diana-chat',
    'window.DIANA_CHAT_CONFIG = ' . wp_json_encode($cfg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ';',
    'before'
  );

  ob_start(); ?>
  <style>
    #<?php echo esc_attr($wrap_id); ?>{
      --diana-primary: <?php echo esc_html($colors['primary']); ?>;
      --diana-accent:  <?php echo esc_html($colors['accent']); ?>;
      --diana-dark:    <?php echo esc_html($colors['dark']); ?>;
      --diana-text:    <?php echo esc_html($colors['text']); ?>;
      --diana-bg:      <?php echo esc_html($colors['bg']); ?>;
      --diana-border:  <?php echo esc_html($colors['border']); ?>;
      --diana-input-bg:<?php echo esc_html($colors['inputbg']); ?>;
    }
  </style>

  <div id="<?php echo esc_attr($wrap_id); ?>" class="diana-wrap">
    <div id="diana-wrap" class="diana-box">
      <div id="diana-log" class="diana-log"></div>

      <?php if (!empty($buttons)) : ?>
        <div class="diana-suggest" id="diana-suggest"></div>
      <?php endif; ?>

      <div class="diana-input" id="diana-input-wrap">
        <input id="diana-input" type="text" placeholder="Frag <?php echo $name; ?>...">
        <button id="diana-send" type="button">Senden</button>
      </div>

      <div class="diana-toolbar">
        <div></div>
        <div class="diana-right">
          <button id="diana-clear" type="button" title="Verlauf löschen">Verlauf löschen</button>
        </div>
      </div>
    </div>
  </div>
  <?php
  return ob_get_clean();
});
