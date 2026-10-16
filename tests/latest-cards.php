<?php
/** Run with PHP CLI and the path to WordPress wp-load.php. No production data changes. */
require $argv[1];
$base = 'https://example.test/hs-cards-' . uniqid();
$count = 3; $scenario = 'normal'; $urls = [];
$base_filter = function () use ($base) { return $base; };
$http_filter = function ($pre, $args, $url) use ($base, &$count, &$scenario, &$urls) {
    if (strpos($url, $base) !== 0) return $pre;
    $urls[] = $url;
    if ($scenario === 'list-error' && str_contains($url, '/reviews?')) return new WP_Error('test', 'Test API-fejl');
    if (preg_match('~/reviews/(\d+)$~', $url, $match)) {
        if ($scenario === 'detail-error') return new WP_Error('test', 'Test detaljefejl');
        $id = (int)$match[1];
        $data = ['id'=>$id, 'title'=>'Anmeldelse '.$id, 'rating'=>$id % 6,
            'bakery_name'=>'Bageren '.$id, 'bakery_city'=>'København', 'bakery_address'=>'Testvej '.$id,
            'body'=>str_repeat('En sprød hindbærsnitte med syrlig marmelade. ', 12).'SLUT PÅ HELE ANMELDELSEN',
            'image_url'=>$id===43 ? null : 'https://example.test/snitte-'.$id.'.jpg', 'image_alt'=>'En snitte', 'review_date'=>'2026-10-16'];
    } else {
        $rows=[];
        for ($i=0; $i<$count; $i++) $rows[]=['id'=>42+$i, 'title'=>'Anmeldelse '.(42+$i)];
        $data=['data'=>$rows,'meta'=>['total'=>$count]];
    }
    return ['response'=>['code'=>200,'message'=>'OK'],'headers'=>[],'cookies'=>[],'body'=>wp_json_encode($data)];
};
function cards_verify($ok,$message) { if (!$ok) throw new RuntimeException($message); }
function cards_clear_cache($urls) { foreach ($urls as $url) delete_transient('hsnitte_'.md5($url)); }
add_filter('pre_option_hsnitte_api_base',$base_filter);
add_filter('pre_http_request',$http_filter,10,3);
try {
    foreach ([1,2,3] as $count) {
        cards_clear_cache($urls); $urls=[];
        $html=do_shortcode('[snitter_latest]');
        cards_verify(substr_count($html,'class="hs-latest-item"')===$count,'Wrong card count: '.$count);
        cards_verify(str_contains(implode(' ', $urls),'per_page=3') && str_contains(implode(' ', $urls),'sort=date') && str_contains(implode(' ', $urls),'dir=desc'),'Latest query ordering or limit incorrect');
        $doc=new DOMDocument(); @$doc->loadHTML('<?xml encoding="utf-8" ?>'.$html); $xpath=new DOMXPath($doc);
        $summaries=$xpath->query('//summary');
        foreach ($summaries as $summary) {
            $excerpt=$xpath->query('.//span[@class="hs-latest-excerpt"]',$summary)->item(0);
            cards_verify($excerpt && mb_strlen($excerpt->textContent)<=181,'Excerpt not truncated');
            cards_verify(!str_contains($summary->textContent,'SLUT PÅ HELE ANMELDELSEN'),'Full body exposed on card');
        }
        cards_verify(substr_count($html,'SLUT PÅ HELE ANMELDELSEN')===$count,'Full details missing');
        cards_verify(wp_script_is('hsnitte-latest','enqueued'),'Popup script missing');
        cards_verify(str_contains($html,'Testvej 42'),'Full address missing');
        cards_verify($xpath->query('//summary//img')->length===($count===1?1:$count-1),'Optional thumbnails incorrect');
        $block=render_block(['blockName'=>'hindbaersnitte/latest','attrs'=>['align'=>'wide'],'innerContent'=>[]]);
        cards_verify(str_contains($block,'alignwide') && str_contains($block,$html),'Gutenberg output or wide alignment incorrect');
        $before=count($urls); do_shortcode('[snitter_latest]');
        cards_verify(count($urls)===$before,'Latest requests not cached');
    }
    $count=0;cards_clear_cache($urls);
    cards_verify(str_contains(do_shortcode('[snitter_latest]'),'Ingen anmeldelser endnu.'),'Empty state missing');
    $count=3;
    foreach (['list-error','detail-error'] as $scenario) {
        cards_clear_cache($urls);
        cards_verify(str_contains(do_shortcode('[snitter_latest]'),'hs-error'),'API failure not handled: '.$scenario);
    }
    echo "PASS: 1–3 latest cards, truncated excerpts, full details, optional images, Gutenberg wide alignment, caching, empty state and API failures\n";
} finally {
    cards_clear_cache($urls);
    remove_filter('pre_option_hsnitte_api_base',$base_filter);
    remove_filter('pre_http_request',$http_filter,10);
}
