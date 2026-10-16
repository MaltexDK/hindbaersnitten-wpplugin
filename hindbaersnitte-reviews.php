<?php
/**
 * Plugin Name: Hindbærsnitte – Reviews via API
 * Description: Viser anmeldelser fra Hindbærsnitte-API'et. Brug Gutenberg-blokkene Snitter – Liste, Snitter – Anmeldelse, Snitter – Nyeste og Snitter – Anmeldere, eller shortcodes [snitter_reviews], [snitter_review id="42"] [snitter_latest] og [snitter_reviewers]. Sæt API-URL under Indstillinger → Hindbærsnitte API.
 * Version:     1.2.0
 * Author:      Maltex Gruppen
 * Author URI:  https://www.maltex.dk
 * License:     GPLv2 or later
 * Update URI:  https://github.com/MaltexDK/hindbaersnitten-wpplugin
 * Text Domain: hindbaersnitte
 */

if (!defined('ABSPATH')) exit;

define('HSNITTE_PLUGIN_VER', '1.2.0');
define('HSNITTE_PLUGIN_FILE', __FILE__);
define('HSNITTE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HSNITTE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HSNITTE_OPTION_API_BASE', 'hsnitte_api_base'); // e.g. https://site.dk/snitteanmeldelser/api

require_once HSNITTE_PLUGIN_DIR.'includes/updates.php';

require_once HSNITTE_PLUGIN_DIR.'includes/class-hs-api-client.php';
require_once HSNITTE_PLUGIN_DIR.'includes/class-hs-shortcodes.php';
require_once HSNITTE_PLUGIN_DIR.'includes/blocks.php';
require_once HSNITTE_PLUGIN_DIR.'includes/rest.php';

/** Settings page */
add_action('admin_menu', function () {
    add_options_page(
        __('Hindbærsnitte API', 'hindbaersnitte'),
        __('Hindbærsnitte API', 'hindbaersnitte'),
        'manage_options',
        'hsnitte-api',
        'hsnitte_render_settings_page'
    );
});

/** Register setting + validate by ping */
add_action('admin_init', function () {
    register_setting('hsnitte_group', HSNITTE_OPTION_API_BASE, [
        'type' => 'string',
        'sanitize_callback' => function ($val) {
            $val = trim($val);
            // fjern trailing slash
            $val = rtrim($val, "/ \t\n\r\0\x0B");
            if ($val === '') return '';
            // Ping API
            $url = $val.'/reviews?per_page=1';
            $resp = wp_remote_get($url, ['timeout' => 10]);
            if (is_wp_error($resp)) {
                add_settings_error('hsnitte_api', 'hsnitte_api_err', sprintf(__('Kunne ikke kontakte API’et: %s', 'hindbaersnitte'), $resp->get_error_message()), 'error');
                return get_option(HSNITTE_OPTION_API_BASE, '');
            }
            $code = wp_remote_retrieve_response_code($resp);
            if ($code !== 200) {
                add_settings_error('hsnitte_api', 'hsnitte_api_bad', sprintf(__('API svarede med HTTP %d – tjek URLen.', 'hindbaersnitte'), $code), 'error');
                return get_option(HSNITTE_OPTION_API_BASE, '');
            }
            add_settings_error('hsnitte_api', 'hsnitte_api_ok', __('Forbindelsen til API’et virker 👍', 'hindbaersnitte'), 'updated');
            return $val;
        },
        'default' => ''
    ]);
});

/** Settings page renderer */
function hsnitte_render_settings_page() {
    if (!current_user_can('manage_options')) return;
    ?>
    <div class="wrap">
      <h1><?php esc_html_e('Hindbærsnitte API', 'hindbaersnitte'); ?></h1>
      <?php settings_errors('hsnitte_api'); ?>
      <form method="post" action="options.php">
        <?php settings_fields('hsnitte_group'); ?>
        <table class="form-table" role="presentation">
          <tr>
            <th scope="row"><label for="hsnitte_api_base"><?php esc_html_e('API-base-URL', 'hindbaersnitte'); ?></label></th>
            <td>
              <input name="<?php echo esc_attr(HSNITTE_OPTION_API_BASE); ?>" id="hsnitte_api_base" type="text"
                     class="regular-text" placeholder="https://example.dk/snitteanmeldelser/api"
                     value="<?php echo esc_attr(get_option(HSNITTE_OPTION_API_BASE, '')); ?>">
              <p class="description"><?php esc_html_e('Uden trailing slash. Eksempel: https://domæne.dk/snitteanmeldelser/api', 'hindbaersnitte'); ?></p>
            </td>
          </tr>
        </table>
        <?php submit_button(__('Gem & tjek forbindelse', 'hindbaersnitte')); ?>
      </form>
    </div>
    <?php
}

/** Shortcodes */
add_action('init', function () {
    HSNitte_Shortcodes::register();
});
?>