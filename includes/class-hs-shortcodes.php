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
        $resp = HSNitte_Api_Client::reviews($query);

        if (is_wp_error($resp)) {
            return '<div class="hs-error">'.esc_html($resp->get_error_message()).'</div>';
        }

        // API v1 wraps rows in data; retain support for older, flat responses.
        $items = is_array($resp) ? ($resp['data'] ?? $resp) : [];
        if (empty($items)) {
            return '<div class="hs-empty">Ingen anmeldelser fundet.</div>';
        }

        // find API-base for JS
        $api_base = rtrim(get_option(HSNITTE_OPTION_API_BASE, ''), '/');

        ob_start();
        ?>
        <div class="hs-list-wrap" data-api-base="<?php echo esc_attr($api_base); ?>">
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
        </div>
        <script>
        (function(){
            if (window.__hsnitteAccordionBound) return;
            window.__hsnitteAccordionBound = true;

            function escapeHTML(value) {
                return String(value == null ? '' : value).replace(/[&<>"']/g, function(c){
                    return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c];
                });
            }
            function closeAll(openRow){
                document.querySelectorAll('tr.hs-detail-row').forEach(function(r){
                    if (!openRow || r !== openRow) r.remove();
                });
                document.querySelectorAll('tr.hs-row.is-open').forEach(function(r){
                    if (!openRow || r !== openRow) r.classList.remove('is-open');
                });
            }

            document.addEventListener('click', async function(e){
                var sort = e.target.closest && e.target.closest('.hs-sort');
                if (sort && sort.closest('.hs-list-wrap')) {
                    var table = sort.closest('table');
                    var body = table.querySelector('tbody');
                    var th = sort.closest('th');
                    var direction = th.getAttribute('aria-sort') === 'ascending' ? 'descending' : 'ascending';
                    var key = sort.dataset.key;
                    body.querySelectorAll('.hs-detail-row').forEach(function(row){ row.remove(); });
                    var rows = Array.from(body.querySelectorAll('.hs-row'));
                    rows.forEach(function(row){ row.classList.remove('is-open'); });
                    rows.sort(function(a, b){
                        var left = key === 'date' ? a.dataset.dateTimestamp : (a.dataset[key] || '');
                        var right = key === 'date' ? b.dataset.dateTimestamp : (b.dataset[key] || '');
                        // Missing dates stay last in either direction.
                        if (key === 'date' && (left === '' || right === '')) {
                            return left === right ? 0 : left === '' ? 1 : -1;
                        }
                        var result = key === 'rating' ? Number(left) - Number(right)
                            : key === 'date' ? Number(left) - Number(right)
                            : left.localeCompare(right, 'da', {numeric:true, sensitivity:'base'});
                        return direction === 'ascending' ? result : -result;
                    });
                    rows.forEach(function(row){ body.appendChild(row); });
                    table.querySelectorAll('th[aria-sort]').forEach(function(header){
                        header.setAttribute('aria-sort', 'none');
                        header.querySelector('.caret').textContent = '↕';
                    });
                    th.setAttribute('aria-sort', direction);
                    sort.querySelector('.caret').textContent = direction === 'ascending' ? '▲' : '▼';
                    return;
                }
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
                    html += '<h3>'+ escapeHTML(data.title || ('#'+id)) +'</h3>';
                    if (data.rating != null) {
                        html += '<div class="hs-rating"><span class="hs-krymmel">';
                        var n = Math.max(0, Math.min(6, parseInt(data.rating, 10) || 0));
                        for (var i=0;i<n;i++){ html += '<i class="krym"></i>'; }
                        for (var j=n;j<6;j++){ html += '<i class="krym off"></i>'; }
                        html += '</span></div>';
                    }
                    if (data.bakery_name || data.bakery_address || data.bakery_postcode || data.bakery_city) {
                        var addr = [data.bakery_address, [data.bakery_postcode, data.bakery_city].filter(Boolean).join(" ")].filter(Boolean).join(" • ");
                        var byline = [data.bakery_name, addr].filter(Boolean).join(" — ");
                        html += '<div class="hs-byline">'+ escapeHTML(byline) +'</div>';
                    }
                    html += '</div>';
                    html += '<div class="hs-detail-body">';
                    if (data.body_html) {
                        var parsed = new DOMParser().parseFromString(data.body_html, 'text/html');
                        html += '<p>'+escapeHTML(parsed.body.textContent)+'</p>';
                    }
                    else if (data.body) { html += '<p>'+ escapeHTML(data.body).replace(/\n/g, '<br>') +'</p>'; }
                    html += '</div>';
                    if (data.image_url && /^https?:\/\//i.test(data.image_url)) {
                        var alt = data.image_alt || '';
                        html += '<div class="hs-detail-image"><img loading="lazy" src="'+ escapeHTML(data.image_url) +'" alt="'+ escapeHTML(alt) +'">';
                        if (alt) html += '<div class="hs-image-caption">'+escapeHTML(alt)+'</div>';
                        html += '</div>';
                    }
                    detail.querySelector('.hs-detail-body').parentNode.innerHTML = html;
                } catch (err) {
                    detail.querySelector('.hs-detail-body').innerHTML = '<div class="hs-error">'+ escapeHTML(err && err.message ? err.message : 'Kunne ikke hente anmeldelsen') +'</div>';
                }
            }, true);
        })();
        </script>

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
            $body = wpautop(esc_html($rev['body']));
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
        <div class="hs-detail-body"><?php echo wp_kses_post($body); ?></div>
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
