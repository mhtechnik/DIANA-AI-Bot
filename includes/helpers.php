<?php
if (!defined('ABSPATH')) exit;

function diana_rl_key($suffix){
  $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
  return 'diana_rl_' . md5($ip . $suffix);
}

function diana_check_rate_limit(){
  $now=time(); $k10=diana_rl_key('10s'); $c10=(int)get_transient($k10);
  if($c10 >= DIANA_BURST_10S) return new WP_Error('too_many','Zu viele Anfragen. Bitte kurz warten.',['status'=>429]);
  set_transient($k10,$c10+1,10);

  $kh=diana_rl_key('hour'); $ch=(int)get_transient($kh);
  if($ch >= DIANA_HOURLY) return new WP_Error('too_many','Limit pro Stunde erreicht.',['status'=>429]);
  $ttl=get_option('_transient_timeout_'.$kh);
  if(!$ttl || $ttl < $now) set_transient($kh,1,HOUR_IN_SECONDS); else set_transient($kh,$ch+1,$ttl - $now);
  return true;
}

function diana_check_origin(){
  $expect = wp_parse_url(home_url(), PHP_URL_HOST);
  $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
  $refer  = $_SERVER['HTTP_REFERER'] ?? '';
  $got=''; if($origin) $got=wp_parse_url($origin, PHP_URL_HOST); elseif($refer) $got=wp_parse_url($refer, PHP_URL_HOST);
  if ($expect && $got && strtolower($expect)!==strtolower($got)) return new WP_Error('bad_origin','Ungültige Herkunft',['status'=>403]);
  return true;
}
