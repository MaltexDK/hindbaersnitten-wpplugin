<?php
if (!defined('ABSPATH')) exit;

class HSNitte_Shortcodes {

    public static function register() {
        add_shortcode('snitter_reviews', [__CLASS__, 'reviews']);
        add_shortcode('snitter_review',  [__CLASS__, 'review']);
        add_shortcode('snitter_latest',  [__CLASS__, 'latest']);
    }

    private static function enqueue_front() {
        if (!defined('HSNITTE_PLUGIN_VER')) define('HSNITTE_PLUGIN_VER', '1.0.0');
        // No external assets needed; JS and CSS are inline for max compatibility.
    }

    private static function get_field($row, array $keys, $default = null) {
        foreach ($keys as $k) {
            if (isset($row[$k]) && $row[$k] !== '' && $row[$k] !== null) return $row[$k];
        }
        return $default;
    }

    private static function krymmel($n) {
        $n = max(0, min(6, (int)$n));
        $out = '<span class="hs-krymmel">';
        for ($i=0; $i<$n; $i++) $out .= '<i class="krym"></i>';
        for ($i=$n; $i<6; $i++) $out .= '<i class="krym off"></i>';
        return $out.'</span>';
    }

    public static function reviews($atts = []) {
        self::enqueue_front();

        $atts = shortcode_atts([
            'sort'   => 'date_desc',
            'dir'    => 'desc',
            'limit'  => null,   // use API default when null
            'page'   => 1,
            'q'      => '',
            'status' => 'published',
        ], $atts, 'snitter_reviews');

        if (!class_exists('HSNitte_Api_Client')) {
            return '<div class="hs-error">API-klienten mangler (HSNitte_Api_Client).</div>';
        }

        // Map params til API-klienten
        $query = [
            'sort'     => $atts['sort'],
            'dir'      => $atts['dir'],
            'per_page' => $atts['limit'] !== null ? (int)$atts['limit'] : null,
            'page'     => (int)$atts['page'],
            'q'        => $atts['q'],
            'status'   => $atts['status'],
        ];
        $resp = HSNitte_Api_Client::reviews($query);

        if (is_wp_error($resp)) {
            return '<div class="hs-error">'.esc_html($resp->get_error_message()).'</div>';
        }

        $items = is_array($resp) ? $resp : [];
        if (empty($items)) {
            return '<div class="hs-empty">Ingen anmeldelser fundet.</div>';
        }

        // find API-base for JS
        $api_base = rtrim(get_option(HSNITTE_OPTION_API_BASE, ''), '/');

        ob_start();
        ?>
        <div class="hs-list-wrap" data-api-base="<?php echo esc_attr($api_base); ?>">
            <table class="hs-table hs-reviews">
                <tbody>
                <?php foreach ($items as $row):
                    // Robust udtræk af felter (bagudkompatibel med tidligere API-formater)
                    $id = (int) self::get_field($row, ['id','review_id','post_id','wp_id','ID'], 0);
                    $title = (string) self::get_field($row, ['title','overskrift','headline'], '');
                    $rating = (int) self::get_field($row, ['rating','score','krymmel'], 0);
                    $bakery = (string) self::get_field($row, ['bakery_name','bageri','bageri_navn'], '');
                    $city = (string) self::get_field($row, ['bakery_city','by','city'], '');
                    $date_raw = (string) self::get_field($row, ['date','published_at','created_at'], '');
                    $date = $date_raw ? date_i18n(get_option('date_format'), strtotime($date_raw)) : '';
                    if ($title === '') $title = ($id > 0 ? 'Anmeldelse #'.$id : __('(uden titel)','hindbaersnitte'));
                ?>
                    <tr class="hs-row" data-review-id="<?php echo esc_attr($id); ?>">
                        <td class="hs-col hs-title">
                            <a href="#" class="hs-open" data-id="<?php echo esc_attr($id); ?>">
                                <?php echo esc_html($title); ?>
                            </a>
                            <div class="hs-sub"><?php echo esc_html(trim($bakery.($city ? ' • '.$city : ''))); ?></div>
                        </td>
                        <td class="hs-col hs-rating"><?php echo self::krymmel($rating); ?></td>
                        <td class="hs-col hs-date"><?php echo esc_html($date); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <script>
        (function(){
            if (window.__hsnitteAccordionBound) return;
            window.__hsnitteAccordionBound = true;

            function closeAll(openRow){
                document.querySelectorAll('tr.hs-detail-row').forEach(function(r){
                    if (!openRow || r !== openRow) r.remove();
                });
                document.querySelectorAll('tr.hs-row.is-open').forEach(function(r){
                    if (!openRow || r !== openRow) r.classList.remove('is-open');
                });
            }

            document.addEventListener('click', async function(e){
                var a = e.target.closest && e.target.closest('a.hs-open');
                if(!a) return;
                e.preventDefault();

                var tr = a.closest('tr.hs-row');
                var id = parseInt(a.getAttribute('data-id')||'0',10);
                var wrap = tr && tr.closest('.hs-list-wrap');
                var base = wrap ? (wrap.dataset.apiBase||'').replace(/\/+$/,'') : '';

                if(!tr) return;

                // Toggle same row
                if (tr.classList.contains('is-open')) { closeAll(); return; }
                closeAll(tr);

                var detail = document.createElement('tr');
                detail.className = 'hs-detail-row';
                var colspan = tr.children.length;
                detail.innerHTML = '<td colspan="'+colspan+'"><div class="hs-card hs-detail"><div class="hs-detail-body">Henter…</div></div></td>';
                tr.insertAdjacentElement('afterend', detail);
                tr.classList.add('is-open');

                if (!id) {
                    detail.querySelector('.hs-detail-body').innerHTML = '<div class="hs-error">Kunne ikke finde ID for denne anmeldelse.</div>';
                    return;
                }
                if (!base) {
                    detail.querySelector('.hs-detail-body').innerHTML = '<div class="hs-error">API-base-URL er ikke sat i indstillingerne.</div>';
                    return;
                }

                try {
                    var res = await fetch(base + '/reviews/' + id);
                    if(!res.ok) throw new Error('API-fejl ('+res.status+')');
                    var data = await res.json();

                    var html = '';
                    html += '<div class="hs-detail-head">';
                    html += '<h3>'+ (data.title || ('#'+id)) +'</h3>';
                    if (data.rating != null) {
                        html += '<div class="hs-rating">';
                        var n = Math.max(0, Math.min(6, parseInt(data.rating, 10) || 0));
                        for (var i=0;i<n;i++){ html += '<i class="krym"></i>'; }
                        for (var j=n;j<6;j++){ html += '<i class="krym off"></i>'; }
                        html += '</div>';
                    }
                    if (data.bakery_name || data.bakery_address || data.bakery_postcode || data.bakery_city) {
                        var addr = [data.bakery_address, [data.bakery_postcode, data.bakery_city].filter(Boolean).join(" ")].filter(Boolean).join(" • ");
                        var byline = [data.bakery_name, addr].filter(Boolean).join(" — ");
                        html += '<div class="hs-byline">'+ byline +'</div>';
                    }
                    html += '</div>';
                    html += '<div class="hs-detail-body">';
                    if (data.body_html) { html += data.body_html; }
                    else if (data.body) { html += '<p>'+ String(data.body).replace(/</g,'&lt;') +'</p>'; }
                    html += '</div>';
                    if (data.image_url) {
                        var alt = data.image_alt || '';
                        html += '<div class="hs-detail-image"><img loading="lazy" src="'+ data.image_url +'" alt="'+ alt.replace(/"/g,'&quot;') +'"></div>';
                    }
                    detail.querySelector('.hs-detail-body').parentNode.innerHTML = html;
                } catch (err) {
                    detail.querySelector('.hs-detail-body').innerHTML = '<div class="hs-error">'+ (err && err.message ? err.message : 'Kunne ikke hente anmeldelsen') +'</div>';
                }
            }, true);
        })();
        </script>
        <style>
            .hs-table { width:100%; border-collapse:collapse; }
            .hs-table .hs-col { padding: .5rem .4rem; vertical-align: top; }
            .hs-row.is-open { background: rgba(0,0,0,.03); }
            .hs-detail-row > td { padding: 0; }
            .hs-card.hs-detail { padding: .75rem .9rem; border-top: 1px solid rgba(0,0,0,.08); }
            .hs-detail-head h3 { margin: 0 0 .25rem; }
            .hs-byline { opacity:.8; margin:.25rem 0 .5rem; font-size:.9em; }
            .hs-krymmel .krym, .hs-rating .krym { display:inline-block; width: .7em; height: .7em; margin-right: .15em; border-radius:50%; background: currentColor; opacity:.9; }
            .hs-krymmel .krym.off, .hs-rating .krym.off { opacity:.2; }
            .hs-error { color: #b00020; }
        </style>
        <?php
        return ob_get_clean();
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

        $rev = HSNitte_Api_Client::latest();
        if (is_wp_error($rev)) {
            return '<div class="hs-error">'.esc_html($rev->get_error_message()).'</div>';
        }
        if (!$rev) return '<div class="hs-empty">Ingen anmeldelser endnu.</div>';

        return self::render_detail($rev, true);
    }

    private static function render_detail(array $rev, bool $wrap = true) {
        $title  = $rev['title'] ?? ($rev['overskrift'] ?? '');
        $rating = (int)($rev['rating'] ?? ($rev['score'] ?? ($rev['krymmel'] ?? 0)));
        $body   = $rev['body_html'] ?? '';

        if (!$body && !empty($rev['body'])) {
            $body = '<p>'.esc_html($rev['body']).'</p>';
        }

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
        <div class="hs-detail-body"><?php echo $body; ?></div>
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
