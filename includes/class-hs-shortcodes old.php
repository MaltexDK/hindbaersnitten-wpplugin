<?php
if (!defined('ABSPATH')) exit;

class HSNitte_Shortcodes {

    public static function register() {
        add_shortcode('snitter_reviews', [__CLASS__, 'reviews']);
        add_shortcode('snitter_review',  [__CLASS__, 'review']);
        add_shortcode('snitter_latest',  [__CLASS__, 'latest']);
    }

    private static function enqueue_front() {
        wp_enqueue_style('hsnitte-css', HSNITTE_PLUGIN_URL.'assets/css/hs-frontend.css', [], HSNITTE_PLUGIN_VER);
        wp_enqueue_script('hsnitte-js', HSNITTE_PLUGIN_URL.'assets/js/hs-frontend.js', [], HSNITTE_PLUGIN_VER, true);
        wp_localize_script('hsnitte-js', 'HSNITTE_API', [
            'base' => rtrim(get_option(HSNITTE_OPTION_API_BASE, ''), '/'),
        ]);
    }

    private static function krymmel($n){
        $n = max(0, min(6, (int)$n));
        $out = '<span class="hs-krymmel">';
        for ($i=0; $i<$n; $i++) $out .= '<i class="krym"></i>';
        for ($i=$n; $i<6; $i++) $out .= '<i class="krym off"></i>';
        return $out.'</span>';
    }

    
    public static function reviews($atts = []) {
    self::enqueue_front();
    // tillad blok/shortcode-attributter
    $atts = shortcode_atts([
        'sort'     => 'date',   // date|rating|title|author
        'dir'      => 'desc',   // asc|desc
        'per_page' => 500,
        'q'        => '',
    ], $atts, 'snitter_reviews');

    $api = HSNitte_Api_Client::reviews([
        'sort'     => $atts['sort'],
        'dir'      => $atts['dir'],
        'per_page' => (int)$atts['per_page'],
        'q'        => $atts['q'],
    ]);
    if (is_wp_error($api))  return '<div class="hs-error">'.esc_html($api->get_error_message()).'</div>';
    if (!is_array($api) || !isset($api['data'])) return '<div class="hs-error">Kunne ikke hente data.</div>';
    $rows = $api['data'];

    ob_start(); ?>
    <div class="hs-wrap" data-hs-view="list">
      <div class="hs-card">

        <table class="hs-table" data-hs-sortable="1">
          <thead>
            <tr>
              <th><button type="button" class="hs-sort" data-key="title"><?php esc_html_e('Overskrift','hindbaersnitte'); ?> <span class="caret">↕</span></button></th>
              <th><button type="button" class="hs-sort" data-key="rating" data-type="num"><?php esc_html_e('Krymmel','hindbaersnitte'); ?> <span class="caret">↕</span></button></th>
              <th><button type="button" class="hs-sort" data-key="bakery_name"><?php esc_html_e('Bageri','hindbaersnitte'); ?> <span class="caret">↕</span></button></th>
              <th><button type="button" class="hs-sort" data-key="review_date" data-type="date"><?php esc_html_e('Dato','hindbaersnitte'); ?> <span class="caret">↕</span></button></th>
              <th><button type="button" class="hs-sort" data-key="author"><?php esc_html_e('Anmelder','hindbaersnitte'); ?> <span class="caret">↕</span></button></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): $id=(int)$r['id']; ?>
              <tr class="hs-row"
                  data-id="<?php echo $id; ?>"
                  data-title="<?php echo esc_attr($r['title']); ?>"
                  data-rating="<?php echo (int)$r['rating']; ?>"
                  data-bakery_name="<?php echo esc_attr($r['bakery_name']); ?>"
                  data-review_date="<?php echo esc_attr($r['review_date']); ?>"
                  data-author="<?php echo esc_attr($r['author'] ?? ''); ?>">
                <td><a class="hs-open" href="#"><?php echo esc_html($r['title']); ?></a></td>
                <td><?php echo self::krymmel($r['rating']); ?></td>
                <td><?php echo esc_html($r['bakery_name']); ?></td>
                <td><?php echo esc_html(date_i18n('d/m/Y', strtotime($r['review_date']))); ?></td>
                <td><?php echo esc_html($r['author'] ?? ''); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="hs-card hs-detail" style="display:none"></div>
    </div>
    <?php
    return ob_get_clean();
		}

    public static function review($atts = []) {
        self::enqueue_front();
        $atts = shortcode_atts(['id'=>0], $atts, 'snitter_review');
        $id = (int)$atts['id'];
        if ($id <= 0) return '<div class="hs-error">'.esc_html__('Mangler id-parameter.', 'hindbaersnitte').'</div>';

        $r = HSNitte_Api_Client::review($id);
        if (is_wp_error($r)) return '<div class="hs-error">'.esc_html($r->get_error_message()).'</div>';
        if (isset($r['error'])) return '<div class="hs-error">'.esc_html($r['error']).'</div>';

        return self::render_detail($r, true);
    }

    public static function latest() {
        self::enqueue_front();
        $r = HSNitte_Api_Client::latest();
        if (is_wp_error($r)) return '<div class="hs-error">'.esc_html($r->get_error_message()).'</div>';
        if (isset($r['error'])) return '<div class="hs-error">'.esc_html($r['error']).'</div>';

        $id = (int)$r['id'];
        ob_start(); ?>
        <div class="hs-card hs-latest" data-id="<?php echo $id; ?>">
          <div class="hs-latest-head">
            <div class="hs-latest-title"><a class="hs-open" href="#"><?php echo esc_html($r['title']); ?></a></div>
            <div class="hs-latest-meta">
              <span class="hs-latest-bakery"><?php echo esc_html($r['bakery_name']); ?></span>
              <span class="sep">•</span>
              <?php echo self::krymmel($r['rating']); ?>
            </div>
          </div>
          <div class="hs-detail" style="display:none"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function render_detail(array $rev, bool $wrap=true) {
        $date = !empty($rev['review_date']) ? date_i18n('j/n Y', strtotime($rev['review_date'])) : '';
        $byline = $date && !empty($rev['author']) ? sprintf(__('Anmeldt den %s af %s', 'hindbaersnitte'), $date, $rev['author']) : '';
        $body = wp_kses_post($rev['body'] ?? '');

        ob_start();
        if ($wrap) echo '<div class="hs-card hs-detail">';
        ?>
        <div class="hs-detail-head">
          <h2><?php echo esc_html($rev['title']); ?></h2>
          <div><?php echo self::krymmel((int)$rev['rating']); ?></div>
          <?php if ($byline): ?><div class="hs-detail-meta"><?php echo esc_html($byline); ?></div><?php endif; ?>
        </div>
        <div class="hs-detail-bakery">
          <strong><?php echo esc_html($rev['bakery_name']); ?></strong><br>
          <?php echo esc_html($rev['bakery_address'].' • '.$rev['bakery_postcode'].' '.$rev['bakery_city']); ?>
        </div>
        <div class="hs-detail-body"><?php echo $body; ?></div>
        <?php if (!empty($rev['image_url'])): ?>
          <div class="hs-detail-image">
            <img src="<?php echo esc_url($rev['image_url']); ?>" alt="<?php echo esc_attr($rev['image_alt'] ?? ''); ?>">
            <?php if (!empty($rev['image_alt'])): ?><div class="cap"><?php echo esc_html($rev['image_alt']); ?></div><?php endif; ?>
          </div>
        <?php endif; ?>
        <?php
        if ($wrap) echo '</div>';
        return ob_get_clean();
    }
}
?>