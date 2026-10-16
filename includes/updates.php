<?php
/** GitHub updates via bundled Plugin Update Checker 5.6 (MIT). */

if (!defined('ABSPATH')) {
    exit;
}

require_once dirname(__DIR__) . '/lib/plugin-update-checker/plugin-update-checker.php';

$hsnitte_plugin_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
    'https://github.com/MaltexDK/hindbaersnitten-wpplugin/',
    HSNITTE_PLUGIN_FILE,
    'hindbaersnitten-wpplugin'
);

// Only published stable releases with our installable asset are eligible.
// In particular, do not fall back to tags or development branches.
$hsnitte_plugin_checker->setBranch('main');
$hsnitte_plugin_checker->getVcsApi()->setStrategyFilterName('hsnitte_plugin_release_strategies');
add_filter('hsnitte_plugin_release_strategies', function ($strategies) {
    return array_intersect_key($strategies, array('latest_release' => true));
});
$hsnitte_plugin_checker->getVcsApi()->enableReleaseAssets(
    '/^hindbaersnitten-wpplugin\.zip$/',
    \YahnisElsts\PluginUpdateChecker\v5p6\Vcs\Api::REQUIRE_RELEASE_ASSETS
);

// The theme checker can return header metadata without a usable release.
// Never show an update unless the packaged download is available.
$hsnitte_plugin_checker->addFilter('request_update_result', function ($update) {
    return !empty($update->download_url) ? $update : null;
});
