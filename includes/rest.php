<?php
if (!defined('ABSPATH')) exit;

// Public read-only views: the API client only reads published reviews.
// Every popup uses the same PHP renderer as standalone reviews and latest cards.
add_action('rest_api_init', function () {
    register_rest_route('hindbaersnitte/v1', '/reviews/(?P<id>[1-9][0-9]*)', [
        'methods'=>'GET', 'permission_callback'=>'__return_true',
        'callback'=>function ($request) {
            $review = HSNitte_Api_Client::review((int)$request['id']);
            if (is_wp_error($review)) return new WP_Error('hsnitte_review', 'Kunne ikke hente anmeldelsen. Prøv igen.', ['status'=>502]);
            if (!is_array($review) || !$review) return new WP_Error('hsnitte_review', 'Anmeldelsen findes ikke.', ['status'=>404]);
            return ['html'=>HSNitte_Shortcodes::render_detail($review, true)];
        },
    ]);
    register_rest_route('hindbaersnitte/v1', '/reviewers/(?P<id>[1-9][0-9]*)/reviews', [
        'methods'=>'GET', 'permission_callback'=>'__return_true',
        'args'=>['page'=>['type'=>'integer', 'default'=>1, 'minimum'=>1, 'maximum'=>1000000, 'sanitize_callback'=>'absint']],
        'callback'=>function ($request) {
            $html = HSNitte_Shortcodes::reviews(['author_id'=>(int)$request['id'], 'per_page'=>100, 'page'=>(int)$request['page']]);
            if (str_contains($html, 'class="hs-error"')) return new WP_Error('hsnitte_reviews', 'Kunne ikke hente anmeldelserne. Prøv igen.', ['status'=>502]);
            return ['html'=>$html];
        },
    ]);
});
