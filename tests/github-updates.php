<?php
/** Run with: php tests/github-updates.php /path/to/wordpress/wp-load.php */
if (PHP_SAPI !== 'cli' || empty($argv[1])) {
    exit("Usage: php tests/github-updates.php /path/to/wp-load.php\n");
}
require $argv[1];

function hs_update_assert($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$cases = array(
    array('hsnitte_plugin_checker', 'hindbaersnitten-wpplugin', 'hindbaersnitten-wpplugin', '1.0.3', 'hindbaersnitte-reviews.php'),
    array('hsnitte_theme_checker', 'hindbaersnitten-wptheme', 'hindbaersnitten-child', '1.0.2', 'style.css'),
);
$scenario = 'stable';
$requests = array();
$mock = function ($pre, $args, $url) use (&$scenario, &$requests, $cases) {
    if (strpos($url, 'github') === false) {
        return $pre;
    }
    $requests[] = $url;
    foreach ($cases as $case) {
        list($variable, $repo, $slug, $version, $header) = $case;
        if (strpos($url, '/' . $repo . '/') === false) {
            continue;
        }
        $code = 200;
        $body = '';
        if (strpos($url, '/releases/latest') !== false) {
            $release = array(
                'tag_name' => 'v' . $version,
                'draft' => $scenario === 'draft',
                'prerelease' => $scenario === 'prerelease',
                'created_at' => '2026-10-16T12:00:00Z',
                'body' => 'Update test',
                'zipball_url' => 'https://example.test/unsafe-source.zip',
                'assets' => array(array(
                    'name' => $scenario === 'wrong-asset' ? 'wrong.zip' : $slug . '.zip',
                    'browser_download_url' => 'https://github.com/MaltexDK/' . $repo . '/releases/download/v' . $version . '/' . $slug . '.zip',
                    'download_count' => 0,
                )),
            );
            if ($scenario === 'missing') {
                $code = 404;
                $release = array('message' => 'Not Found');
            }
            if ($scenario === 'no-assets') {
                $release['assets'] = array();
            }
            $body = wp_json_encode($release);
        } elseif (strpos($url, '/' . $header) !== false) {
            $source = $header === 'style.css'
                ? WP_CONTENT_DIR . '/themes/hindbaersnitten-child/style.css'
                : WP_PLUGIN_DIR . '/hindbaersnitten-wpplugin/hindbaersnitte-reviews.php';
            $body = preg_replace('/Version:\s*[0-9.]+/', 'Version: ' . $version, file_get_contents($source));
        } else {
            throw new RuntimeException('Unexpected GitHub request: ' . $url);
        }
        return array('response' => array('code' => $code, 'message' => 'Mock'), 'headers' => array(), 'body' => $body, 'cookies' => array());
    }
    throw new RuntimeException('Unexpected repository: ' . $url);
};
add_filter('pre_http_request', $mock, 10, 3);
try {
    foreach ($cases as $case) {
        list($variable, $repo, $slug, $version) = $case;
        hs_update_assert(isset($GLOBALS[$variable]), 'Updater not loaded: ' . $variable);
        $checker = $GLOBALS[$variable];
        $api = $checker->getVcsApi();
        foreach (array('stable', 'draft', 'prerelease', 'no-assets', 'wrong-asset', 'missing') as $scenario) {
            $requests = array();
            $reference = $api->chooseReference('main');
            if ($scenario === 'stable') {
                hs_update_assert($reference && $reference->version === $version, 'Stable version not found');
                hs_update_assert(substr($reference->downloadUrl, -strlen($slug . '.zip')) === $slug . '.zip', 'Wrong asset selected');
                $update = $checker->requestUpdate();
                hs_update_assert($update && $update->version === $version, 'Update metadata incorrect');
                hs_update_assert($update->download_url === $reference->downloadUrl, 'Download URL incorrect');
            } else {
                hs_update_assert($reference === null, 'Unsafe source accepted: ' . $scenario);
                hs_update_assert(count($requests) === 1 && strpos($requests[0], '/releases/latest') !== false, 'Fell back to tag or branch');
                hs_update_assert($checker->requestUpdate() === null, 'Offered update without packaged release');
            }
            echo $slug . ': ' . $scenario . " OK\n";
        }
    }
} finally {
    remove_filter('pre_http_request', $mock, 10);
}
