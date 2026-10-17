<?php
// Deterministic API fixtures. Used only by tests; excluded from release ZIPs.
$GLOBALS['hs_reviewers_scenario'] = 'normal';
$GLOBALS['hs_reviewers_urls'] = [];
function hs_reviewers_test_base() { return $GLOBALS['hs_reviewers_base'] ?? 'https://example.test/hs-reviewers-fixture'; }
function hs_reviewers_test_http($pre, $args, $url) {
    if (!str_starts_with($url, hs_reviewers_test_base())) return $pre;
    $GLOBALS['hs_reviewers_urls'][] = $url;
    $scenario = $GLOBALS['hs_reviewers_scenario'];
    if ($scenario === 'error') return new WP_Error('test_error','Test API error');
    $path = parse_url($url, PHP_URL_PATH);
    parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $query);
    if (str_ends_with($path, '/reviewers')) {
        $data = ['data'=>$scenario === 'empty' ? [] : [
            ['id'=>7,'handle'=>'Snittefan & ven','presentation_html'=>'<p>Jeg elsker <strong>snitter</strong>.</p><ul><li>Sprød bund</li><li>Syrlig marmelade</li></ul><p><a href="https://example.test/profil">Min side</a></p>','review_count'=>101],
            ['id'=>8,'handle'=>'Glasurmesteren','review_count'=>1],
        ]];
    } elseif (preg_match('~/reviews/(\d+)$~', $path, $matches)) {
        $data = ['id'=>(int)$matches[1], 'title'=>'En formateret anmeldelse', 'rating'=>5,
            'bakery_name'=>'Bageren', 'bakery_city'=>'Aarhus', 'body'=>'<p>Første <strong>afsnit</strong><br>En ny linje.</p><div>Andet afsnit med <em>kursiv</em>, <u>understregning</u> og <b>fed</b>.</div><h3>Lille overskrift</h3><blockquote>Et citat</blockquote><ul><li>Punkt et</li></ul><ol><li>Punkt to</li></ol><p><a href="https://example.test/link" title="Testlink" target="_blank" rel="noopener">Et link</a> <a href="mailto:test@example.test">Mail</a></p><script>window.hsnitteXss=1</script><img src="x" onerror="window.hsnitteXss=2"><a href="javascript:alert(1)">Usikkert link</a>',
            'author'=>'Snittefan & ven', 'review_date'=>'2026-10-16',
            'image_url'=>'https://example.test/snitte.jpg', 'image_alt'=>'Hindbærsnitte'];
        if ($scenario === 'html-field') $data['body_html'] = '<p><strong>Prioriteret HTML</strong><br>Ny linje</p>';
        if ($scenario === 'plain') $data['body'] = "Almindelig tekst\n\nNyt afsnit";
        if ($scenario === 'missing-credit') { unset($data['author']); $data['review_date']='invalid'; }
    } else {
        $author = (int)($query['author_id'] ?? 7);
        $total = $author === 7 ? 101 : 1;
        $page = max(1,(int)($query['page'] ?? 1)); $per = (int)($query['per_page'] ?? 100);
        $rows = [];
        for ($i=($page-1)*$per; $i<min($total,$page*$per); $i++) $rows[]=[
            'id'=>$author*1000+$i, 'title'=>($author===7?'Snittefans':'Glasurmesterens').' anmeldelse '.($i+1),
            'rating'=>($i%7), 'bakery_name'=>'Bager '.($i+1), 'bakery_city'=>'Aarhus', 'review_date'=>$i%2?'2026-01-02':'2025-12-31', 'author_id'=>$author];
        $data = ['data'=>$rows,'meta'=>['total'=>$total,'page'=>$page,'per_page'=>$per]];
    }
    return ['response'=>['code'=>200,'message'=>'OK'],'headers'=>[],'cookies'=>[],'body'=>wp_json_encode($data)];
}
function hs_reviewers_test_bind() {
    add_filter('pre_option_hsnitte_api_base','hs_reviewers_test_base');
    add_filter('pre_http_request','hs_reviewers_test_http',10,3);
}
function hs_reviewers_test_clear() {
    foreach ($GLOBALS['hs_reviewers_urls'] as $url) delete_transient('hsnitte_'.md5($url));
}
