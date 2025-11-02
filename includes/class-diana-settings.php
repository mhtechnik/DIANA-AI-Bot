<?php
if (!defined('ABSPATH')) exit;

class Diana_Chat_Settings {
  public static function init(){
    add_action('admin_menu', [__CLASS__, 'menu']);
    add_action('admin_init', [__CLASS__, 'register']);
  }

  public static function menu(){
    add_options_page('Diana Chat Einstellungen', 'Diana Chat', 'manage_options', 'diana-chat', [__CLASS__, 'page']);
  }

  public static function register(){
    $fields = [
      'diana_openai_api_key' => 'sanitize_text_field',
      'diana_base_url'       => 'esc_url_raw',
      'diana_model'          => 'sanitize_text_field',
      'diana_system_prompt'  => 'wp_kses_post',
      'diana_temperature'    => 'floatval',
      'diana_max_tokens'     => 'intval',
      'diana_stop_sequences' => 'sanitize_text_field',
      'diana_assistant_name' => 'sanitize_text_field',
      'diana_avatar_url'     => 'esc_url_raw',
      'diana_greeting'       => 'wp_kses_post',
      'diana_suggestions'    => 'sanitize_text_field',
      'diana_pdfs'           => 'wp_kses_post',
    ];
    foreach ($fields as $name => $cb) {
      register_setting('diana_chat_settings', $name, ['sanitize_callback' => $cb]);
    }

    add_settings_section('diana_chat_api', 'API', null, 'diana-chat');
    add_settings_field('diana_openai_api_key', 'API Key', function () {
      $v = esc_attr(get_option('diana_openai_api_key', ''));
      echo '<input type="text" name="diana_openai_api_key" value="'.$v.'" style="width:420px">';
    }, 'diana-chat', 'diana_chat_api');
    add_settings_field('diana_base_url', 'Base URL', function () {
      $v = esc_attr(get_option('diana_base_url', 'https://api.openai.com'));
      echo '<input type="url" name="diana_base_url" value="'.$v.'" style="width:420px">';
    }, 'diana-chat', 'diana_chat_api');

    add_settings_section('diana_chat_model', 'Modell', null, 'diana-chat');
    add_settings_field('diana_model', 'Model', function () {
      $v = esc_attr(get_option('diana_model', 'gpt-5'));
      echo '<input type="text" name="diana_model" value="'.$v.'" style="width:240px">
            <p class="description">Für GPT-5 wird automatisch die Responses API genutzt.</p>';
    }, 'diana-chat', 'diana_chat_model');
    add_settings_field('diana_temperature', 'Temperatur', function () {
      $v = esc_attr(get_option('diana_temperature', ''));
      echo '<input type="number" step="0.01" min="0" max="2" name="diana_temperature" value="'.$v.'" style="width:120px">
            <p class="description">Bei gpt-5 wird temperature nicht gesendet.</p>';
    }, 'diana-chat', 'diana_chat_model');
    add_settings_field('diana_max_tokens', 'Max Tokens', function () {
      $v = esc_attr(get_option('diana_max_tokens', '1000'));
      echo '<input type="number" min="1" name="diana_max_tokens" value="'.$v.'" style="width:120px">
            <p class="description">Responses nutzt max_output_tokens.</p>';
    }, 'diana-chat', 'diana_chat_model');
    add_settings_field('diana_stop_sequences', 'Stop Sequenzen', function () {
      $v = esc_attr(get_option('diana_stop_sequences', ''));
      echo '<input type="text" name="diana_stop_sequences" value="'.$v.'" style="width:100%;max-width:780px" placeholder="END,||">';
    }, 'diana-chat', 'diana_chat_model');

    add_settings_section('diana_chat_prompt', 'System Prompt', null, 'diana-chat');
    add_settings_field('diana_system_prompt', 'Prompt', function () {
      $v = esc_textarea(get_option('diana_system_prompt', 'Du bist Diana, eine ruhige Co Moderatorin. Antworte kurz und klar.'));
      echo '<textarea name="diana_system_prompt" rows="8" style="width:100%;max-width:780px">'.$v.'</textarea>';
    }, 'diana-chat', 'diana_chat_prompt');

    add_settings_section('diana_chat_ui', 'UI', null, 'diana-chat');
    add_settings_field('diana_assistant_name', 'Name im UI', function () {
      $v = esc_attr(get_option('diana_assistant_name', 'DiANA'));
      echo '<input type="text" name="diana_assistant_name" value="'.$v.'" style="width:240px">';
    }, 'diana-chat', 'diana_chat_ui');
    add_settings_field('diana_avatar_url', 'Avatar URL', function () {
      $v = esc_attr(get_option('diana_avatar_url', ''));
      echo '<input type="url" name="diana_avatar_url" value="'.$v.'" style="width:420px" placeholder="https://.../avatar.png">';
    }, 'diana-chat', 'diana_chat_ui');
    add_settings_field('diana_greeting', 'Begrüßung', function () {
      $v = esc_textarea(get_option('diana_greeting', 'Hallo, ich bin DiANA. Wie kann ich helfen?'));
      echo '<textarea name="diana_greeting" rows="3" style="width:100%;max-width:780px">'.$v.'</textarea>';
    }, 'diana-chat', 'diana_chat_ui');
    add_settings_field('diana_suggestions', 'Prompt Buttons', function () {
      $v = esc_attr(get_option('diana_suggestions', 'Was kannst du,Moderier die Diskussion,Erstelle eine Agenda,Erkläre ein Fachbegriff,Erzeuge eine Kurz-Zusammenfassung'));
      echo '<input type="text" name="diana_suggestions" value="'.$v.'" style="width:100%;max-width:780px">';
    }, 'diana-chat', 'diana_chat_ui');

    add_settings_section('diana_chat_pdfs', 'PDF-Guides', function () {
      echo '<p>Eine Zeile pro Regel: <code>Regex | Titel | https://.../leitfaden.pdf | optionales Thumbnail</code></p>';
    }, 'diana-chat');
    add_settings_field('diana_pdfs', 'Regeln', function () {
      $def = "/*Moderationszyklus|Agenda|Methoden|Check-in|Check-out*/i | Methoden-Sammlung | https://example.com/Methoden-alle.pdf";
      $v = esc_textarea(get_option('diana_pdfs', $def));
      echo '<textarea name="diana_pdfs" rows="6" style="width:100%;max-width:780px">'.$v.'</textarea>';
    }, 'diana-chat', 'diana_chat_pdfs');
  }

  public static function page(){ ?>
  <div class="wrap">
    <h1>Diana Chat</h1>
    <form method="post" action="options.php">
      <?php settings_fields('diana_chat_settings'); do_settings_sections('diana-chat'); submit_button(); ?>
    </form>
    <p>Shortcode: <code>[diana_chat]</code></p>
  </div>
  <?php }
}
Diana_Chat_Settings::init();
