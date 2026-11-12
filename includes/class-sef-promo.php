<?php
/**
 * Σύνδεσμος-προβολή προς τη σελίδα των εντύπων (0.8.0):
 * [school_eforms_link] για την αρχική σελίδα, ένα sidebar ή ένα widget «Shortcode».
 *
 * Χρώματα από το θέμα: φόντο = δεύτερο χρώμα (Flatsome «Secondary»), έμφαση = κύριο (Flatsome «Primary»).
 * Η αντίθεση κειμένου διορθώνεται στη σελίδα (μικρό ενσωματωμένο script, χωρίς εξωτερικά αρχεία).
 * Responsive με container query: οριζόντια διάταξη σε φαρδύ χώρο, κάθετη σε sidebar/κινητό.
 */
defined( 'ABSPATH' ) || exit;

class SEF_Promo {

	/** Το στυλ και το script γράφονται μία φορά ανά σελίδα. */
	private static $printed = false;

	public static function init() {
		add_shortcode( 'school_eforms_link', array( __CLASS__, 'shortcode' ) );
		add_action( 'widgets_init', array( __CLASS__, 'register_widget' ) );
		add_action( 'save_post', array( __CLASS__, 'forget_page' ) );
		add_action( 'trashed_post', array( __CLASS__, 'forget_page' ) );
		add_action( 'deleted_post', array( __CLASS__, 'forget_page' ) );
	}

	public static function register_widget() {
		if ( class_exists( 'WP_Widget' ) ) {
			require_once SEF_DIR . 'includes/class-sef-promo-widget.php';
			register_widget( 'SEF_Promo_Widget' );
		}
	}

	public static function forget_page() {
		delete_transient( 'sef_forms_page' );
	}

	/** Η διεύθυνση της σελίδας με το [school_eforms]: από τη ρύθμιση ή αυτόματη αναζήτηση. */
	public static function forms_url() {
		$s  = SEF_Settings::get();
		$id = (int) $s['forms_page'];
		if ( ! $id ) {
			$id = (int) get_transient( 'sef_forms_page' );
			if ( ! $id ) {
				$found = get_posts(
					array(
						'post_type'   => array( 'page', 'post' ),
						'post_status' => 'publish',
						's'           => '[school_eforms',
						'numberposts' => 20,
						'orderby'     => 'date',
						'order'       => 'ASC',
					)
				);
				foreach ( (array) $found as $p ) {
					if ( has_shortcode( (string) $p->post_content, 'school_eforms' ) ) {
						$id = (int) $p->ID;
						break;
					}
				}
				set_transient( 'sef_forms_page', $id ? $id : -1, 12 * HOUR_IN_SECONDS );
			}
		}
		return $id > 0 ? (string) get_permalink( $id ) : '';
	}

	private static function hex( $v ) {
		$v = trim( (string) $v );
		if ( '' !== $v && '#' !== $v[0] ) {
			$v = '#' . $v;
		}
		return sanitize_hex_color( $v ) ? $v : '';
	}

	/** Κείμενο κουμπιού χωρίς βελάκι/σύμβολο στο τέλος (›, », >, →, ➜ κ.λπ.)· κενό → «Είσοδος». */
	public static function clean_button( $text ) {
		$t = trim( preg_replace( '/[\s\x{00A0}]*(?:[›»>→➜➔⟶▸▶︎❯]|&gt;|&raquo;|&rsaquo;)+[\s\x{00A0}]*$/u', '', (string) $text ) );
		return '' === $t ? 'Είσοδος' : $t;
	}

	public static function shortcode( $attrs ) {
		$s = SEF_Settings::get();
		$a = shortcode_atts(
			array(
				'title'     => 'Ηλεκτρονικά έντυπα και αιτήσεις',
				'text'      => 'Αιτήσεις και δηλώσεις γονέων, άδειες εκπαιδευτικών και υπεύθυνες δηλώσεις, από το σπίτι.',
				'kicker'    => 'Νέα υπηρεσία του σχολείου',
				'button'    => 'Είσοδος',
				'url'       => '',
				'sections'  => 'no',   // yes = και σύνδεσμοι «Γονείς & μαθητές» / «Εκπαιδευτικοί»
				'season'    => 'no',   // yes = «Αυτή την περίοδο: …» (εποχικά έντυπα που είναι ανοιχτά)
				'heading'   => 'h2',   // h2 | h3 | h4 | p (στο sidebar συνήθως h3)
				'bg'        => '',     // δικό σας φόντο (hex)· αλλιώς από το θέμα
				'highlight' => '',     // δικό σας χρώμα έμφασης (hex)· αλλιώς από το θέμα
			),
			$attrs,
			'school_eforms_link'
		);

		// Βελάκι ή σύμβολο στο τέλος του κειμένου του κουμπιού (π.χ. «Είσοδος ›») δεν χρειάζεται: το βελάκι
		// μπαίνει διακοσμητικά και δεν πρέπει να το διαβάζουν οι αναγνώστες οθόνης (0.9.7).
		$a['button'] = self::clean_button( $a['button'] );

		$url = '#' === trim( $a['url'] ) ? '#' : ( '' !== trim( $a['url'] ) ? esc_url_raw( trim( $a['url'] ) ) : self::forms_url() ); // «#» = προεπισκόπηση διαχείρισης
		if ( '' === $url ) {
			// Χωρίς σελίδα εντύπων δεν υπάρχει πού να οδηγήσει: τίποτα για τους επισκέπτες, οδηγία για τον διαχειριστή.
			return current_user_can( 'manage_options' ) ? '<p class="sef-promo-missing">School e-Forms: δεν βρέθηκε σελίδα με το <code>[school_eforms]</code>. Ορίστε τη στις Ρυθμίσεις → Ηλεκτρονικά έντυπα → Εμφάνιση, ή γράψτε <code>url="…"</code>.</p>' : '';
		}
		$base = strtok( $url, '#' );

		// Χρώματα: φόντο / έμφαση.
		$bg = self::hex( $a['bg'] );
		$hl = self::hex( $a['highlight'] );
		if ( '' === $bg ) {
			$bg = 'custom' === $s['color_source'] ? $s['accent'] : 'var(--fs-color-secondary, var(--wp--preset--color--secondary, ' . $s['accent'] . '))';
		}
		if ( '' === $hl ) {
			$hl = 'var(--fs-color-primary, var(--wp--preset--color--primary, #d4a72c))';
		}
		$style = '--sef-p-bg:' . $bg . ';--sef-p-hl:' . $hl . ';';

		$tag = in_array( $a['heading'], array( 'h2', 'h3', 'h4', 'p' ), true ) ? $a['heading'] : 'h2';
		$uid = wp_unique_id( 'sef-promo-' );

		// Ενότητες και εποχικά (μόνο όσα είναι διαθέσιμα τώρα, για τη βαθμίδα του σχολείου).
		$links = array();
		$now   = array();
		if ( 'yes' === $a['sections'] || 'yes' === $a['season'] ) {
			$forms = SEF_Forms::available();
			if ( 'yes' === $a['sections'] && 'list' !== $s['home_layout'] ) {
				foreach ( SEF_Forms::sections() as $sid => $sec ) {
					foreach ( $forms as $f ) {
						if ( in_array( $f['category'], $sec['cats'], true ) ) {
							$links[ $sid ] = $sec['title'];
							break;
						}
					}
				}
			}
			if ( 'yes' === $a['season'] ) {
				foreach ( $forms as $id => $f ) {
					if ( ! empty( $f['seasonal'] ) && count( $now ) < 3 ) {
						$now[ $id ] = $f['label'];
					}
				}
			}
		}

		ob_start();
		self::print_assets();
		?>
		<section class="sef-promo" data-sef-promo aria-labelledby="<?php echo esc_attr( $uid ); ?>" style="<?php echo esc_attr( $style ); ?>">
			<div class="sef-promo__in">
				<span class="sef-promo__deco" aria-hidden="true"></span>
				<span class="sef-promo__icon" aria-hidden="true">
					<svg viewBox="0 0 24 24" width="38" height="38" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h4"/><path d="M14 3v5h5"/><path d="M14 3l5 5v3"/><path d="M8.5 13.5c1-1.4 2-1.4 2.6 0s1.6 1.4 2.4 0"/><path d="M19.4 13.6a1.4 1.4 0 0 1 2 2L16 21l-2.6.6.6-2.6z"/></svg>
				</span>
				<div class="sef-promo__body">
					<?php if ( '' !== trim( $a['kicker'] ) ) : ?><p class="sef-promo__kicker"><?php echo esc_html( $a['kicker'] ); ?></p><?php endif; ?>
					<<?php echo $tag; // phpcs:ignore ?> class="sef-promo__title" id="<?php echo esc_attr( $uid ); ?>"><?php echo esc_html( $a['title'] ); ?></<?php echo $tag; // phpcs:ignore ?>>
					<?php if ( '' !== trim( $a['text'] ) ) : ?><p class="sef-promo__text"><?php echo esc_html( $a['text'] ); ?></p><?php endif; ?>
					<?php if ( $links ) : ?>
						<p class="sef-promo__links">
							<?php foreach ( $links as $sid => $label ) : ?>
								<a class="sef-promo__chip" href="<?php echo esc_url( $base . '#sef/' . $sid ); ?>"><?php echo esc_html( $label ); ?></a>
							<?php endforeach; ?>
						</p>
					<?php endif; ?>
					<?php if ( $now ) : ?>
						<p class="sef-promo__now"><b>Αυτή την περίοδο:</b>
							<?php
							$parts = array();
							foreach ( $now as $id => $label ) {
								$parts[] = '<a class="sef-promo__now-link" href="' . esc_url( $base . '#sef/' . $id ) . '">' . esc_html( $label ) . '</a>';
							}
							echo implode( ' · ', $parts ); // phpcs:ignore
							?>
						</p>
					<?php endif; ?>
				</div>
				<a class="sef-promo__btn" href="<?php echo esc_url( $url ); ?>" aria-label="<?php echo esc_attr( $a['button'] . ' — ' . $a['title'] ); ?>"><?php echo esc_html( $a['button'] ); ?><span aria-hidden="true" class="sef-promo__arrow"></span></a>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}

	/** Στυλ + script αντίθεσης, μία φορά, ενσωματωμένα (ώστε να δουλεύουν και σε sidebar/widget). */
	private static function print_assets() {
		if ( self::$printed ) {
			return;
		}
		self::$printed = true;
		?>
<style id="sef-promo-css">
.sef-promo{container-type:inline-size;margin:0 0 1.5em;font-size:16px;line-height:1.5}
.sef-promo *{box-sizing:border-box}
.sef-promo__in{--ink:#fff;--hl-text:var(--sef-p-hl);--hl-ink:#1b1b1b;position:relative;overflow:hidden;display:flex;align-items:center;gap:24px;padding:28px 32px;border-radius:16px;background:var(--sef-p-bg);color:var(--ink);box-shadow:0 10px 28px rgba(0,0,0,.14)}
.sef-promo__deco{position:absolute;right:-26px;top:50%;width:150px;height:150px;transform:translateY(-50%) rotate(-12deg);border-radius:30px;border:16px solid color-mix(in srgb,var(--sef-p-hl) 28%,transparent);pointer-events:none}
.sef-promo__deco::before{content:"";position:absolute;inset:-60px auto -60px -90px;width:36px;background:color-mix(in srgb,var(--ink) 8%,transparent);transform:rotate(12deg)}
.sef-promo__icon{position:relative;z-index:1;flex:none;width:72px;height:72px;border-radius:16px;background:#fff;color:var(--icon,var(--sef-p-bg));display:grid;place-items:center;box-shadow:0 4px 12px rgba(0,0,0,.12)}
.sef-promo__icon::after{content:"";position:absolute;top:10px;right:10px;width:11px;height:11px;border-radius:3px;background:var(--sef-p-hl)}
.sef-promo__body{position:relative;z-index:1;flex:1;min-width:0}
.sef-promo p.sef-promo__kicker{display:flex;align-items:center;gap:8px;margin:0 0 4px;font-size:14px;font-weight:700;color:var(--hl-text);letter-spacing:0;text-transform:none}
.sef-promo__kicker::before{content:"";width:10px;height:10px;border-radius:3px;background:var(--sef-p-hl);flex:none}
.sef-promo .sef-promo__title{margin:0 0 6px;padding:0;font-size:24px;line-height:1.25;font-weight:800;color:var(--ink);letter-spacing:0;text-transform:none;border:0}
.sef-promo p.sef-promo__text{margin:0;font-size:15.5px;color:var(--ink);opacity:.92}
.sef-promo p.sef-promo__links,.sef-promo p.sef-promo__now{margin:10px 0 0;font-size:14.5px;color:var(--ink)}
.sef-promo__links{display:flex;flex-wrap:wrap;gap:8px}
.sef-promo .sef-promo__links a.sef-promo__chip{display:inline-block;padding:4px 12px;border-radius:99px;border:1px solid color-mix(in srgb,var(--ink) 40%,transparent);color:var(--ink)!important;text-decoration:none!important;font-weight:600;background:transparent}
.sef-promo .sef-promo__links a.sef-promo__chip:hover{background:color-mix(in srgb,var(--ink) 12%,transparent)}
.sef-promo__now b{color:var(--hl-text)}
/* Ρητό χρώμα: γενικοί κανόνες θεμάτων (π.χ. #content p a:not([class])) έκαναν τον σύνδεσμο αόρατο πάνω στην κάρτα (0.9.10). */
.sef-promo .sef-promo__now a.sef-promo__now-link,.sef-promo .sef-promo__now a.sef-promo__now-link:visited,.sef-promo .sef-promo__now a.sef-promo__now-link:hover{color:var(--ink)!important;text-decoration:underline!important;text-decoration-color:currentColor!important;text-underline-offset:2px;background:transparent;font-weight:600}
.sef-promo a.sef-promo__btn{position:relative;z-index:1;flex:none;display:inline-flex;align-items:center;justify-content:center;gap:10px;min-height:52px;margin:0;padding:12px 30px;border-radius:99px;background:var(--sef-p-hl);color:var(--hl-ink);font-size:17px;font-weight:800;line-height:1.2;text-decoration:none;text-transform:none;letter-spacing:0;box-shadow:0 4px 12px rgba(0,0,0,.18);transition:transform .15s,box-shadow .15s}
.sef-promo a.sef-promo__btn:hover{transform:translateY(-1px);box-shadow:0 8px 18px rgba(0,0,0,.22)}
.sef-promo a:focus-visible{outline:3px solid #fff;outline-offset:3px;box-shadow:0 0 0 6px rgba(0,0,0,.55)}
.sef-promo__arrow{flex:none;width:.5em;height:.5em;border:solid currentColor;border-width:.16em .16em 0 0;transform:rotate(45deg);margin-left:-.1em}
.sef-sr-p{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
@container (max-width:620px){
.sef-promo__in{flex-direction:column;align-items:flex-start;gap:16px;padding:22px 20px}
.sef-promo__icon{width:56px;height:56px;border-radius:14px}
.sef-promo__icon svg{width:28px;height:28px}
.sef-promo .sef-promo__title{font-size:20px}
.sef-promo a.sef-promo__btn{align-self:stretch}
.sef-promo__deco{top:-34px;right:-46px;transform:rotate(-12deg);width:110px;height:110px;border-width:12px}
.sef-promo__deco::before{display:none}
}
@media (prefers-reduced-motion:reduce){.sef-promo a.sef-promo__btn{transition:none}.sef-promo a.sef-promo__btn:hover{transform:none}}
</style>
<script id="sef-promo-js">
(function () {
  function rgb(c, x) { x.fillStyle = '#000'; x.fillStyle = String(c || '').trim(); var v = x.fillStyle, m;
    if ((m = /^#([0-9a-f]{6})$/i.exec(v))) { var n = parseInt(m[1], 16); return [n >> 16 & 255, n >> 8 & 255, n & 255]; }
    if ((m = /rgba?\(([\d.]+),\s*([\d.]+),\s*([\d.]+)/.exec(v))) { return [+m[1], +m[2], +m[3]]; } return null; }
  function lum(c) { return c.map(function (v) { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); }).reduce(function (a, v, i) { return a + v * [0.2126, 0.7152, 0.0722][i]; }, 0); }
  function con(a, b) { var p = lum(a), q = lum(b); return (Math.max(p, q) + 0.05) / (Math.min(p, q) + 0.05); }
  function hex(c) { return '#' + c.map(function (v) { return ('0' + Math.round(v).toString(16)).slice(-2); }).join(''); }
  function fix(el) {
    var x = document.createElement('canvas').getContext('2d'); if (!x) { return; }
    var cs = getComputedStyle(el), bg = rgb(cs.getPropertyValue('--sef-p-bg'), x), hl = rgb(cs.getPropertyValue('--sef-p-hl'), x);
    if (!bg || !hl) { return; }
    var W = [255, 255, 255], D = [27, 27, 27];
    var ink = con(bg, W) >= con(bg, D) ? W : D;                  // κείμενο πάνω στο φόντο
    el.style.setProperty('--ink', hex(ink));
    el.style.setProperty('--icon', con(bg, W) >= 3 ? hex(bg) : hex(D)); // εικονίδιο στο λευκό πλακίδιο
    el.style.setProperty('--hl-ink', hex(con(hl, W) >= con(hl, D) ? W : D)); // γράμματα του κουμπιού
    var t = hl.slice(), k = 0;                                     // χρώμα έμφασης ως κείμενο: ως 4,5:1
    while (con(t, bg) < 4.5 && k < 20) { k++; t = hl.map(function (v, i) { return v + (ink[i] - v) * k * 0.05; }); }
    el.style.setProperty('--hl-text', hex(t));
  }
  function run() { Array.prototype.forEach.call(document.querySelectorAll('[data-sef-promo] .sef-promo__in'), function (el) { try { fix(el); } catch (e) {} }); }
  if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', run); } else { run(); }
})();
</script>
		<?php
	}
}
