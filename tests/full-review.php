<?php
/** Run with PHP CLI and the path to WordPress wp-load.php. */
require $argv[1];
$base = 'https://example.test/hs-display-' . uniqid();
$urls = [];
$fixture = ['id'=>42, 'title'=>'En hel anmeldelse', 'rating'=>5, 'bakery_name'=>'Bageren', 'bakery_city'=>'København', 'bakery_address'=>'Testvej 1', 'body'=>"Første afsnit.\n\nHele anmeldelsens sidste afsnit.", 'image_url'=>'https://example.test/snitte.jpg', 'image_alt'=>'En snitte', 'review_date'=>'2026-10-16'];
$base_filter = function () use ($base) { return $base; };
$http_filter = function ($pre, $args, $url) use ($base, $fixture, &$urls) {
    if (strpos($url, $base) !== 0) return $pre;
    $urls[] = $url;
    if (str_ends_with($url, '/latest')) $data = ['id'=>42, 'title'=>$fixture['title'], 'bakery_name'=>'Bageren'];
    elseif (str_ends_with($url, '/42')) $data = $fixture;
    else $data = ['data'=>[$fixture], 'meta'=>['total'=>1]];
    return ['response'=>['code'=>200, 'message'=>'OK'], 'headers'=>[], 'cookies'=>[], 'body'=>wp_json_encode($data)];
};
function verify($ok, $message) { if (!$ok) throw new RuntimeException($message); }
add_filter('pre_option_hsnitte_api_base', $base_filter);
add_filter('pre_http_request', $http_filter, 10, 3);
try {
    $latest = do_shortcode('[snitter_latest]');
    foreach (['Hele anmeldelsens sidste afsnit.', 'snitte.jpg', 'En snitte', 'Testvej 1', '5 af 6 krymmel'] as $text) verify(str_contains($latest, $text), 'Missing latest detail: '.$text);
    verify(in_array($base.'/reviews/42', $urls, true), 'Latest must fetch full detail');
    verify(wp_style_is('hsnitte-css', 'enqueued'), 'Standalone latest CSS must be enqueued');
    verify(str_contains($latest, do_shortcode('[snitter_review id="42"]')), 'Popup and detail must share markup');
    verify(str_contains(render_block(['blockName'=>'hindbaersnitte/latest', 'attrs'=>[], 'innerContent'=>[]]), $latest), 'Gutenberg latest must use same renderer');
    $plugin = get_plugin_data(HSNITTE_PLUGIN_FILE, false, false);
    verify($plugin['AuthorURI'] === 'https://www.maltex.dk', 'Author link missing');
    $list = do_shortcode('[snitter_reviews limit="10"]');
    verify(substr_count($list, 'class="hs-sort"') === 4, 'Four sort headers required');
    verify(str_contains($list, 'data-rating="5"') && str_contains($list, 'data-date="2026-10-16"'), 'Raw sort values missing');
    verify(str_contains(implode(' ', $urls), 'per_page=10'), 'Shortcode limit ignored');
    echo "PASS: latest fetches full review; full text, rating, image and address; shared Gutenberg/detail markup; standalone CSS; sortable headers and limit\n";
} finally {
    foreach ($urls as $url) delete_transient('hsnitte_'.md5($url));
    remove_filter('pre_option_hsnitte_api_base', $base_filter);
    remove_filter('pre_http_request', $http_filter, 10);
}
