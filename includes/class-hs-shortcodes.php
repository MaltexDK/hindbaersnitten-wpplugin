<?php
if (!defined('ABSPATH')) exit;

class HSNitte_Shortcodes {

    public static function register() {
        add_shortcode('snitter_reviews', [__CLASS__, 'reviews']);
        add_shortcode('snitter_review',  [__CLASS__, 'review']);
        add_shortcode('snitter_latest',  [__CLASS__, 'latest']);
        add_shortcode('snitter_reviewers', [__CLASS__, 'reviewers']);
    }

    private static function enqueue_front() {
        if (!defined('HSNITTE_PLUGIN_VER')) define('HSNITTE_PLUGIN_VER', '1.0.0');
        wp_enqueue_script('hsnitte-latest', HSNITTE_PLUGIN_URL.'assets/js/hs-latest.js', [], HSNITTE_PLUGIN_VER, true);
        wp_enqueue_style('hsnitte-css', HSNITTE_PLUGIN_URL.'assets/css/hs-frontend.css', [], HSNITTE_PLUGIN_VER);
    }

    private static function get_field($row, array $keys, $default = null) {
        foreach ($keys as $k) {
            if (isset($row[$k]) && $row[$k] !== '' && $row[$k] !== null) return $row[$k];
        }
        return $default;
    }

    /** Parse API dates explicitly, including legacy Danish date formats. */
    private static function date_timestamp(string $value) {
        foreach (['!Y-m-d', '!Y-m-d H:i:s', '!Y-m-d\TH:i:sP', '!d-m-Y', '!d/m/Y', '!d.m.Y'] as $format) {
            $date = DateTimeImmutable::createFromFormat($format, trim($value), new DateTimeZone('UTC'));
            $errors = DateTimeImmutable::getLastErrors();
            if ($date !== false && ($errors === false || (!$errors['warning_count'] && !$errors['error_count']))) {
                return $date->getTimestamp();
            }
        }
        return null;
    }

    private static function krymmel($n) {
        $n = max(0, min(6, (int)$n));
        $out = '<span class="hs-krymmel" role="img" aria-label="'.esc_attr($n.' af 6 krymmel').'">';
        for ($i=0; $i<$n; $i++) $out .= '<i class="krym"></i>';
        for ($i=$n; $i<6; $i++) $out .= '<i class="krym off"></i>';
        return $out.'</span>';
    }

    public static function reviews($atts = []) {
        self::enqueue_front();

        $atts = shortcode_atts([
            'sort'   => 'date',
            'dir'    => 'desc',
            'per_page' => 500,
            'limit'  => null,   // use API default when null
            'page'   => 1,
            'q'      => '',
            'status' => 'published',
            'author_id' => 0,
        ], $atts, 'snitter_reviews');

        if (!class_exists('HSNitte_Api_Client')) {
            return '<div class="hs-error">API-klienten mangler (HSNitte_Api_Client).</div>';
        }

        // Map params til API-klienten
        $query = [
            'sort'     => $atts['sort'],
            'dir'      => $atts['dir'],
            'per_page' => $atts['limit'] !== null ? (int)$atts['limit'] : (int)$atts['per_page'],
            'page'     => (int)$atts['page'],
            'q'        => $atts['q'],
            'status'   => $atts['status'],
        ];
        if ((int)$atts['author_id'] > 0) $query['author_id'] = (int)$atts['author_id'];
        $resp = HSNitte_Api_Client::reviews($query);

        if (is_wp_error($resp)) {
            return '<div class="hs-error">'.esc_html($resp->get_error_message()).'</div>';
        }

        // API v1 wraps rows in data; retain support for older, flat responses.
        $items = is_array($resp) ? ($resp['data'] ?? $resp) : [];
        if (empty($items)) {
            return '<div class="hs-empty">Ingen anmeldelser fundet.</div>';
        }

        wp_enqueue_script('hsnitte-list', HSNITTE_PLUGIN_URL.'assets/js/hs-list.js', ['hsnitte-latest'], HSNITTE_PLUGIN_VER, true);

        ob_start();
        ?>
        <div class="hs-list-wrap" data-detail-url="<?php echo esc_url(rest_url('hindbaersnitte/v1/reviews/')); ?>">
            <table class="hs-table hs-reviews">
                <thead>
                    <tr>
                        <?php foreach (['title' => 'Overskrift', 'bakery' => 'Bageri / sted', 'rating' => 'Karakter', 'date' => 'Dato'] as $key => $label): ?>
                        <th scope="col" aria-sort="none"><button type="button" class="hs-sort" data-key="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?> <span class="caret" aria-hidden="true">↕</span></button></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $row):
                    // Robust udtræk af felter (bagudkompatibel med tidligere API-formater)
                    $id = (int) self::get_field($row, ['id','review_id','post_id','wp_id','ID'], 0);
                    $title = (string) self::get_field($row, ['title','overskrift','headline'], '');
                    $rating = (int) self::get_field($row, ['rating','score','krymmel'], 0);
                    $bakery = (string) self::get_field($row, ['bakery_name','bageri','bageri_navn'], '');
                    $city = (string) self::get_field($row, ['bakery_city','by','city'], '');
                    $date_raw = (string) self::get_field($row, ['review_date','date','published_at','created_at'], '');
                    $date_timestamp = self::date_timestamp($date_raw);
                    $date = $date_timestamp !== null ? date_i18n(get_option('date_format'), $date_timestamp) : '';
                    if ($title === '') $title = ($id > 0 ? 'Anmeldelse #'.$id : __('(uden titel)','hindbaersnitte'));
                ?>
                    <tr class="hs-row" data-review-id="<?php echo esc_attr($id); ?>" data-title="<?php echo esc_attr($title); ?>" data-bakery="<?php echo esc_attr(trim($bakery.' '.$city)); ?>" data-rating="<?php echo esc_attr($rating); ?>" data-date="<?php echo esc_attr($date_raw); ?>" data-date-timestamp="<?php echo esc_attr($date_timestamp ?? ''); ?>">
                        <td class="hs-col hs-title">
                            <a href="#" class="hs-open" data-id="<?php echo esc_attr($id); ?>">
                                <?php echo esc_html($title); ?>
                            </a>
                        </td>
                        <td class="hs-col hs-bakery"><?php echo esc_html(trim($bakery.($city ? ' • '.$city : ''))); ?></td>
                        <td class="hs-col hs-rating"><?php echo self::krymmel($rating); ?></td>
                        <td class="hs-col hs-date"><?php echo esc_html($date); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if ((int)$atts['author_id'] > 0 && (int)$query['page'] * (int)$query['per_page'] < (int)($resp['meta']['total'] ?? 0)): ?>
                <button type="button" class="hs-reviews-more" data-url="<?php echo esc_url(add_query_arg('page', (int)$query['page']+1, rest_url('hindbaersnitte/v1/reviewers/'.(int)$atts['author_id'].'/reviews'))); ?>">Vis flere anmeldelser</button>
            <?php endif; ?>
        </div>


        <?php
        return ob_get_clean();
    }

    public static function reviewers($atts = []) {
        self::enqueue_front();
        wp_enqueue_script('hsnitte-list', HSNITTE_PLUGIN_URL.'assets/js/hs-list.js', ['hsnitte-latest'], HSNITTE_PLUGIN_VER, true);
        wp_enqueue_script('hsnitte-reviewers', HSNITTE_PLUGIN_URL.'assets/js/hs-reviewers.js', ['hsnitte-list'], HSNITTE_PLUGIN_VER, true);
        $response = HSNitte_Api_Client::reviewers();
        if (is_wp_error($response)) return '<div class="hs-error">Kunne ikke hente anmeldere. Kontrollér at anmeldersystemets API er opdateret.</div>';
        $reviewers = $response['data'] ?? [];
        if (!$reviewers) return '<p class="hs-empty">Ingen anmeldere med udgivne anmeldelser endnu.</p>';
        ob_start(); ?>
        <div class="hs-reviewers">
        <?php foreach ($reviewers as $reviewer): ?>
            <details class="hs-reviewer" data-url="<?php echo esc_url(rest_url('hindbaersnitte/v1/reviewers/'.(int)$reviewer['id'].'/reviews')); ?>">
                <summary><span><?php echo esc_html($reviewer['handle']); ?></span><span class="hs-reviewer-count"><?php echo (int)$reviewer['review_count'].((int)$reviewer['review_count'] === 1 ? ' anmeldelse' : ' anmeldelser'); ?></span></summary>
                <div class="hs-reviewer-reviews"><p role="status">Åbn anmelderen for at hente anmeldelser.</p></div>
            </details>
        <?php endforeach; ?>
        <noscript><p>Aktivér JavaScript for at hente anmeldernes anmeldelser og åbne dem i popup.</p></noscript>
        </div>
        <?php return ob_get_clean();
    }

    public static function review($atts = []) {
        self::enqueue_front();
        $atts = shortcode_atts(['id' => 0], $atts, 'snitter_review');
        $id = (int)$atts['id'];
        if ($id <= 0) return '<div class="hs-error">Ugyldigt ID.</div>';

        if (!class_exists('HSNitte_Api_Client')) {
            return '<div class="hs-error">API-klienten mangler (HSNitte_Api_Client).</div>';
        }

        $rev = HSNitte_Api_Client::review($id);
        if (is_wp_error($rev)) {
            return '<div class="hs-error">'.esc_html($rev->get_error_message()).'</div>';
        }
        if (!$rev) return '<div class="hs-empty">Anmeldelse ikke fundet.</div>';

        return self::render_detail($rev, true);
    }

    public static function latest() {
        self::enqueue_front();
        if (!class_exists('HSNitte_Api_Client')) {
            return '<div class="hs-error">API-klienten mangler (HSNitte_Api_Client).</div>';
        }
        $reviews = HSNitte_Api_Client::latest_reviews();
        if (is_wp_error($reviews)) return '<div class="hs-error">'.esc_html($reviews->get_error_message()).'</div>';
        if (!$reviews) return '<div class="hs-empty">Ingen anmeldelser endnu.</div>';

        ob_start();
        ?>
        <div class="hs-latest">
            <div class="hs-latest-grid">
            <?php foreach ($reviews as $review):
                $title = trim((string)($review['title'] ?? '')) ?: 'Anmeldelse #'.(int)($review['id'] ?? 0);
                $bakery = (string)($review['bakery_name'] ?? '');
                $city = (string)($review['bakery_city'] ?? '');
                $body = (string)($review['body_html'] ?? ($review['body'] ?? ''));
                $text = wp_strip_all_tags(str_ireplace(['</p>', '</div>', '<br>', '<br/>', '<br />'], ' ', $body));
                $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode($text, ENT_QUOTES, 'UTF-8')));
                $excerpt = wp_html_excerpt($text, 180, '…');
            ?>
                <details class="hs-latest-item">
                    <summary class="hs-latest-card" aria-label="<?php echo esc_attr('Læs anmeldelsen: '.$title); ?>">
                        <?php if (!empty($review['image_url'])): ?>
                        <span class="hs-latest-thumbnail"><img loading="lazy" decoding="async" src="<?php echo esc_url($review['image_url']); ?>" alt=""></span>
                        <?php endif; ?>
                        <span class="hs-latest-copy">
                            <strong class="hs-latest-title"><?php echo esc_html($title); ?></strong>
                            <span class="hs-latest-bakery"><?php echo esc_html(implode(' • ', array_filter([$bakery, $city]))); ?></span>
                            <?php echo self::krymmel((int)($review['rating'] ?? 0)); ?>
                            <span class="hs-latest-excerpt"><?php echo esc_html($excerpt); ?></span>
                            <span class="hs-latest-more">Læs anmeldelsen <span class="hs-latest-arrow" aria-hidden="true"></span></span>
                        </span>
                    </summary>
                    <div class="hs-latest-full"><?php echo self::render_detail($review, true); ?></div>
                </details>
            <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function render_detail(array $rev, bool $wrap = true) {
        $title  = $rev['title'] ?? ($rev['overskrift'] ?? '');
        $rating = (int)($rev['rating'] ?? ($rev['score'] ?? ($rev['krymmel'] ?? 0)));
        // API body can contain the editor's HTML even when body_html is absent.
        // WordPress' extensible post allowlist keeps formatting without executable HTML.
        $body = wpautop(wp_kses_post(!empty($rev['body_html']) ? $rev['body_html'] : ($rev['body'] ?? '')));
        $author = trim((string)($rev['author'] ?? ''));
        $timestamp = self::date_timestamp((string)($rev['review_date'] ?? ''));
        $date = $timestamp !== null ? date_i18n(get_option('date_format'), $timestamp) : '';
        $credit = $author !== '' ? 'Anmeldt af '.$author.($date !== '' ? ' den '.$date : '') : ($date !== '' ? 'Anmeldt den '.$date : '');

        ob_start();
        if ($wrap) echo '<div class="hs-card hs-detail">';
        ?>
        <div class="hs-detail-head">
            <?php if ($title): ?><h3><?php echo esc_html($title); ?></h3><?php endif; ?>
            <div class="hs-rating"><?php echo self::krymmel($rating); ?></div>
            <div class="hs-byline">
                <?php
                $parts = [];
                $bakery_name = $rev['bakery_name'] ?? ($rev['bageri'] ?? ($rev['bageri_navn'] ?? ''));
                if (!empty($bakery_name)) $parts[] = $bakery_name;
                $addr  = [];
                $addr_line = $rev['bakery_address'] ?? ($rev['adresse'] ?? '');
                if ($addr_line) $addr[] = $addr_line;
                $pcity = trim(($rev['bakery_postcode'] ?? ($rev['postnummer'] ?? '')).' '.($rev['bakery_city'] ?? ($rev['by'] ?? '')));
                if ($pcity) $addr[] = $pcity;
                if ($addr) $parts[] = implode(' • ', $addr);
                echo esc_html(implode(' — ', $parts));
                ?>
            </div>
        </div>
        <div class="hs-detail-body"><?php echo wp_kses_post($body); ?></div>
        <?php if ($credit !== ''): ?><p class="hs-review-credit"><?php echo esc_html($credit); ?></p><?php endif; ?>
        <?php if (!empty($rev['image_url'])): ?>
            <div class="hs-detail-image">
                <img loading="lazy" src="<?php echo esc_url($rev['image_url']); ?>" alt="<?php echo esc_attr($rev['image_alt'] ?? ''); ?>">
                <?php if (!empty($rev['image_alt'])): ?>
                    <div class="hs-image-caption"><?php echo esc_html($rev['image_alt']); ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php
        if ($wrap) echo '</div>';
        return ob_get_clean();
    }
}

add_action('init', ['HSNitte_Shortcodes', 'register']);
?>
