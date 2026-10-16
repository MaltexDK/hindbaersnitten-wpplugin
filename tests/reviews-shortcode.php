<?php
// Run with PHP CLI; no WordPress installation is needed for this regression test.
define('ABSPATH', __DIR__);
define('HSNITTE_OPTION_API_BASE', 'hsnitte_api_base');
function add_action(...$args) {}
function shortcode_atts($defaults, $atts, $name) { return array_merge($defaults, $atts); }
function get_option($name, $default = '') { return $name === 'date_format' ? 'Y-m-d' : $default; }
function esc_attr($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function esc_html($value) { return esc_attr($value); }
function __($value, $domain = '') { return $value; }
function date_i18n($format, $timestamp) { return date($format, $timestamp); }
function is_wp_error($value) { return $value instanceof WP_Error; }
class WP_Error { public function get_error_message() { return 'Test API error'; } }
class HSNitte_Api_Client {
    public static $response;
    public static function reviews($args) { return self::$response; }
}
require __DIR__.'/../includes/class-hs-shortcodes.php';

function check($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
}
$row = ['id'=>42, 'title'=>'Test & snitte', 'rating'=>5, 'bakery_name'=>'Bageren', 'review_date'=>'2026-10-16'];
foreach ([['data'=>[$row], 'meta'=>['total'=>1]], [$row]] as $response) {
    HSNitte_Api_Client::$response = $response;
    $html = HSNitte_Shortcodes::reviews();
    check(substr_count($html, '<tr class="hs-row"') === 1, 'Expected exactly one review row');
    check(str_contains($html, 'data-review-id="42"'), 'Review ID missing');
    check(str_contains($html, 'Test &amp; snitte'), 'Escaped title missing');
    check(str_contains($html, '2026-10-16'), 'API review_date missing');
    check(!str_contains($html, '(uden titel)'), 'Metadata rendered as a review');
}
foreach ([['data'=>[], 'meta'=>['total'=>0]], []] as $response) {
    HSNitte_Api_Client::$response = $response;
    check(str_contains(HSNitte_Shortcodes::reviews(), 'Ingen anmeldelser fundet.'), 'Empty state missing');
}
HSNitte_Api_Client::$response = new WP_Error();
check(str_contains(HSNitte_Shortcodes::reviews(), 'Test API error'), 'API error missing');
echo "PASS: enveloped and legacy lists, review dates, empty responses and API errors\n";
