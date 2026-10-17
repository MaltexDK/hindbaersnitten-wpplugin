<?php
/** Run with local WordPress wp-load.php. No database content changes. */
require $argv[1];
require __DIR__.'/support/reviewers-api.php';
function reviewers_verify($ok,$message) { if (!$ok) throw new RuntimeException($message); }
$GLOBALS['hs_reviewers_base'] = 'https://example.test/hs-reviewers-'.uniqid();
hs_reviewers_test_bind();hs_reviewers_test_clear();
try {
    $html = do_shortcode('[snitter_reviewers]');
    reviewers_verify(substr_count($html,'class="hs-reviewer"')===2, 'Reviewer list missing');
    reviewers_verify(str_contains($html,'Snittefan &amp; ven') && str_contains($html,'101 anmeldelser'), 'Escaped handles or counts missing');
    reviewers_verify(substr_count($html,'class="hs-reviewer-presentation"')===1, 'Empty presentation must be omitted');
    reviewers_verify(str_contains($html,'<strong>snitter</strong>') && str_contains($html,'<li>Sprød bund</li>'), 'Presentation formatting missing');
    reviewers_verify(strpos($html,'hs-reviewer-presentation') < strpos($html,'hs-reviewer-reviews'), 'Presentation must precede reviews');
    $block = render_block(['blockName'=>'hindbaersnitte/reviewers','attrs'=>['align'=>'wide'],'innerContent'=>[]]);
    reviewers_verify(str_contains($block,'alignwide') && str_contains($block,$html), 'Gutenberg renderer differs');
    reviewers_verify(wp_script_is('hsnitte-reviewers','enqueued') && wp_script_is('hsnitte-list','enqueued'), 'Accordion/popup scripts missing');
    $request = new WP_REST_Request('GET','/hindbaersnitte/v1/reviewers/7/reviews');
    $result = rest_do_request($request);
    reviewers_verify($result->get_status()===200,'Reviewer reviews REST failed');
    $list = $result->get_data()['html'];
    reviewers_verify(substr_count($list,'class="hs-row"')===100 && str_contains($list,'hs-reviews-more'), 'First reviewer page or pagination missing');
    reviewers_verify(str_contains(implode(' ', $GLOBALS['hs_reviewers_urls']),'author_id=7'), 'Exact author filter missing');
    $request->set_param('page',2);$last=rest_do_request($request)->get_data()['html'];
    reviewers_verify(substr_count($last,'class="hs-row"')===1 && !str_contains($last,'hs-reviews-more'), 'Final reviewer page incorrect');
    $detail = rest_do_request(new WP_REST_Request('GET','/hindbaersnitte/v1/reviews/7000'))->get_data()['html'];
    $single = do_shortcode('[snitter_review id="7000"]');
    reviewers_verify($single===$detail, 'List popup and standalone markup differ');
    foreach (['<strong>afsnit</strong>','<br','<em>kursiv</em>','<u>understregning</u>','<blockquote>','<ul>','<ol>','href="https://example.test/link"','mailto:test@example.test'] as $expected) reviewers_verify(str_contains($detail,$expected),'Formatting lost: '.$expected);
    reviewers_verify(!preg_match('/<script|onerror=|href="javascript:/i',$detail),'Unsafe HTML rendered');
    reviewers_verify(!str_contains($detail,'&lt;p&gt;'),'HTML escaped as text');
    $credit = strpos($detail,'Anmeldt af Snittefan &amp; ven den ');
    reviewers_verify($credit!==false && strpos($detail,'hs-detail-body')<$credit && $credit<strpos($detail,'hs-detail-image'),'Credit placement incorrect');
    reviewers_verify(str_contains($detail,date_i18n(get_option('date_format'),strtotime('2026-10-16 UTC'))),'Localized date missing');
    foreach (['html-field','plain','missing-credit'] as $scenario) {
        $GLOBALS['hs_reviewers_scenario']=$scenario;hs_reviewers_test_clear();
        $detail=do_shortcode('[snitter_review id="7000"]');
        if ($scenario==='html-field') reviewers_verify(str_contains($detail,'<strong>Prioriteret HTML</strong>'),'body_html ignored');
        if ($scenario==='plain') reviewers_verify(str_contains($detail,"<p>Nyt afsnit</p>"),'Plain-text paragraphs broken');
        if ($scenario==='missing-credit') reviewers_verify(!str_contains($detail,'hs-review-credit'),'Empty/invalid credit displayed');
    }
    foreach (['empty','error'] as $scenario) {
        $GLOBALS['hs_reviewers_scenario']=$scenario;hs_reviewers_test_clear();
        reviewers_verify(str_contains(do_shortcode('[snitter_reviewers]'),$scenario==='empty'?'hs-empty':'hs-error'),'Reviewer state missing: '.$scenario);
    }
    reviewers_verify(rest_do_request(new WP_REST_Request('GET','/hindbaersnitte/v1/reviews/7000'))->get_status()===502,'Detail failure not handled');
    echo "PASS: formatted/sanitized HTML, body/body_html/plain text, credit/date placement, public REST, reviewer block/shortcode, exact filter, 100+1 pagination, empty/error states\n";
} finally {
    hs_reviewers_test_clear();
    remove_filter('pre_option_hsnitte_api_base','hs_reviewers_test_base');
    remove_filter('pre_http_request','hs_reviewers_test_http',10);
}
