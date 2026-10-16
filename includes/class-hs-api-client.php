<?php
if (!defined('ABSPATH')) exit;

class HSNitte_Api_Client {

    private static function base() : string {
        return rtrim(get_option(HSNITTE_OPTION_API_BASE, ''), '/');
    }

    private static function get(string $path, array $query = [], int $ttl = 60) {
        $base = self::base();
        if ($base === '') return new WP_Error('hsnitte_no_base', __('API-base-URL er ikke sat i indstillingerne.', 'hindbaersnitte'));

        $url = $base . '/' . ltrim($path, '/');
        if ($query) $url = add_query_arg($query, $url);

        // simpelt cache via transient
        $key = 'hsnitte_' . md5($url);
        $cached = get_transient($key);
        if ($cached !== false) return $cached;

        $resp = wp_remote_get($url, ['timeout' => 12]);
        if (is_wp_error($resp)) return $resp;

        $code = wp_remote_retrieve_response_code($resp);
        if ($code !== 200) return new WP_Error('hsnitte_http', 'HTTP '.$code.' fra API', ['url'=>$url]);

        $body = wp_remote_retrieve_body($resp);
        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('hsnitte_json', 'Ugyldigt JSON fra API', ['body'=>$body]);
        }

        set_transient($key, $data, $ttl);
        return $data;
    }

    /** Liste over reviews (published) */
    public static function reviews(array $args = []) {
        $defaults = ['sort'=>'date', 'dir'=>'desc', 'page'=>1, 'per_page'=>500];
        $q = array_merge($defaults, $args);
        return self::get('reviews', $q, 60);
    }

    /** Én review */
    public static function review(int $id) {
        return self::get('reviews/'.$id, [], 120);
    }

    /** Nyeste */
    public static function latest() {
        return self::get('reviews/latest', [], 60);
    }
}
?>