<?php
if (!defined('ABSPATH')) exit;

add_action('init', function () {

    // Editor-script + -style
    wp_register_script(
        'hsnitte-blocks',
        HSNITTE_PLUGIN_URL.'assets/js/blocks.js',
        ['wp-blocks','wp-element','wp-i18n','wp-components','wp-block-editor','wp-server-side-render'],
        HSNITTE_PLUGIN_VER,
        true
    );
    wp_register_style(
        'hsnitte-blocks',
        HSNITTE_PLUGIN_URL.'assets/css/blocks.css',
        [],
        HSNITTE_PLUGIN_VER
    );

    // Lokaliser API-base til editor
    wp_localize_script('hsnitte-blocks', 'HSNITTE_API', [
        'base' => rtrim(get_option(HSNITTE_OPTION_API_BASE, ''), '/'),
        'settingsUrl' => admin_url('options-general.php?page=hsnitte-api'),
    ]);

    wp_register_style('hsnitte-css', HSNITTE_PLUGIN_URL.'assets/css/hs-frontend.css', [], HSNITTE_PLUGIN_VER);

    // Shared styles keep the editor and published reviews consistent.
    $common = [
        'editor_script' => 'hsnitte-blocks',
        'editor_style'  => ['hsnitte-blocks', 'hsnitte-css'],
        'style'         => 'hsnitte-css',
    ];

    // 1) Listeblok
    register_block_type('hindbaersnitte/reviews', array_merge($common, [
        'attributes' => [
            'sort'     => ['type'=>'string','default'=>'date'],
            'dir'      => ['type'=>'string','default'=>'desc'],
            'per_page' => ['type'=>'number','default'=>500],
            'q'        => ['type'=>'string','default'=>''],
        ],
        'render_callback' => function($atts){
            // brug shortcode-renderen for identisk markup
            return HSNitte_Shortcodes::reviews([
                'sort'     => $atts['sort']     ?? 'date',
                'dir'      => $atts['dir']      ?? 'desc',
                'per_page' => $atts['per_page'] ?? 500,
                'q'        => $atts['q']        ?? '',
            ]);
        },
    ]));

    // 2) Enkelt anmeldelse
    register_block_type('hindbaersnitte/review', array_merge($common, [
        'attributes' => [
            'id' => ['type'=>'number','default'=>0],
        ],
        'render_callback' => function($atts){
            $id = (int)($atts['id'] ?? 0);
            if ($id <= 0) return '<div class="hs-error">'.esc_html__('Vælg et gyldigt ID i blokindstillingerne.', 'hindbaersnitte').'</div>';
            return HSNitte_Shortcodes::review(['id'=>$id]);
        },
    ]));

    register_block_type('hindbaersnitte/reviewers', array_merge($common, [
        'api_version'=>2,
        'supports'=>['align'=>['wide','full']],
        'render_callback'=>function () {
            return '<div '.get_block_wrapper_attributes().'>'.HSNitte_Shortcodes::reviewers().'</div>';
        },
    ]));

    // 3) Nyeste anmeldelse
    register_block_type('hindbaersnitte/latest', array_merge($common, [
        'api_version' => 2,
        'supports' => ['align' => ['wide', 'full']],
        'render_callback' => function(){
            return '<div '.get_block_wrapper_attributes().'>'.HSNitte_Shortcodes::latest().'</div>';
        },
    ]));
});
?>