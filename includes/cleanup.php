<?php
if (!defined('ABSPATH')) exit;

add_action('diana_daily_cleanup', function () {
  global $wpdb;

  // 1) Transients mit Präfix 'diana_rl_' bereinigen
  // Optionen-Tabelle kann je nach Prefix heißen:
  $table = $wpdb->options;

  // Timeout-Einträge weg:
  $wpdb->query(
    $wpdb->prepare(
      "DELETE FROM $table WHERE option_name LIKE %s",
      $wpdb->esc_like('_transient_timeout_diana_rl_') . '%'
    )
  );
  // Wert-Einträge weg:
  $wpdb->query(
    $wpdb->prepare(
      "DELETE FROM $table WHERE option_name LIKE %s",
      $wpdb->esc_like('_transient_diana_rl_') . '%'
    )
  );

  // 2) Optional: WordPress-Debug-Log rotieren oder leeren
  // Nur wenn du debug.log nutzt und Rotationen möchtest:
  $debug_log = WP_CONTENT_DIR . '/debug.log';
  if (file_exists($debug_log)) {
    // Wenn älter als 30 Tage, löschen
    $ageDays = (time() - filemtime($debug_log)) / 86400;
    if ($ageDays > 30) @unlink($debug_log);
  }
});
