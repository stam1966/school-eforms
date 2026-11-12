<?php
/**
 * Δημόσια εμφάνιση: shortcode [school_eforms], φόρτωση αρχείων, σήμανση.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SEF_Frontend {

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
		add_shortcode( 'school_eforms', array( __CLASS__, 'shortcode' ) );
	}

	public static function register_assets() {
		$v = SEF_VERSION;
		wp_register_style( 'sef-sheet', SEF_URL . 'assets/css/sheet.css', array(), $v );
		wp_register_style( 'sef-app', SEF_URL . 'assets/css/eforms.css', array( 'sef-sheet' ), $v );
		wp_register_script( 'sef-fontmeta', SEF_URL . 'assets/js/sef-fontmeta.js', array(), $v, true );
		wp_register_script( 'sef-pdf', SEF_URL . 'assets/js/sef-pdf.js', array( 'sef-fontmeta' ), $v, true );
		wp_register_script( 'sef-app', SEF_URL . 'assets/js/eforms.js', array( 'sef-pdf' ), $v, true );

		// Φόρτωση στο <head> μόνο όπου υπάρχει το shortcode (χωρίς «αναλαμπή» χωρίς στυλ).
		if ( is_singular() ) {
			$post = get_post();
			if ( $post && has_shortcode( (string) $post->post_content, 'school_eforms' ) ) {
				wp_enqueue_style( 'sef-app' );
			}
		}
	}

	private static function icon( $key ) {
		$p = array(
			'doc'       => '<path d="M6 2h9l4 4v16H6z"/><path d="M15 2v5h4M9 12h7M9 16h5"/>',
			'pen'       => '<path d="M4 20h4L19 9l-4-4L4 16z"/><path d="m13.5 6.5 4 4"/>',
			'calendar'  => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/><path d="m9 15 2 2 4-4"/>',
			'book'      => '<path d="M4 5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2z"/><path d="M4 21V5M9 8h6"/>',
			'users'     => '<circle cx="9" cy="8" r="3"/><path d="M3 20v-1.5C3 15.5 5.5 14 9 14s6 1.5 6 4.5V20"/><path d="M16 5a3 3 0 0 1 0 6m2 3c2 .5 3 1.8 3 4v2"/>',
			'clock'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
			'flag'      => '<path d="M5 21V4M5 4h11l-2 4 2 4H5"/>',
			'briefcase' => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M3 13h18"/>',
			'heart'     => '<path d="M12 20s-7-4.5-7-10a4 4 0 0 1 7-2.5A4 4 0 0 1 19 10c0 5.5-7 10-7 10z"/><path d="M9 11h6M12 8v6"/>',
			'shield'    => '<path d="M12 3 4 6v6c0 4.5 3.4 8 8 9 4.6-1 8-4.5 8-9V6z"/><path d="m9 12 2 2 4-4"/>',
			'parents'   => '<circle cx="9" cy="7.5" r="3"/><path d="M3 20v-2.5C3 14.5 5.5 13 9 13s6 1.5 6 4.5V20"/><path d="M17 5a3 3 0 0 1 0 6m1 3c2 .5 3 1.8 3 4v2"/>',
			'teachers'  => '<path d="m2 9 10-5 10 5-10 5z"/><path d="M6 11v5c3.5 3 8.5 3 12 0v-5M22 9v7"/>',
			'declarations' => '<path d="M6 2h9l4 4v16H6z"/><path d="M15 2v5h4M9 12h7"/><path d="m10 17 2 2 4-4"/>',
			'print'     => '<path d="M7 8V3h10v5"/><rect x="3" y="8" width="18" height="9" rx="2"/><path d="M7 14h10v7H7z"/>',
			'students'  => '<path d="M12 3 2 8l10 5 10-5z"/><path d="M6 10.5V15c3.5 2.5 8.5 2.5 12 0v-4.5"/>',
			'link'      => '<path d="M14 4h6v6M20 4l-9 9"/><path d="M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>',
		);
		$path = isset( $p[ $key ] ) ? $p[ $key ] : $p['doc'];
		return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $path . '</svg>';
	}

	private static function steps_for( $form ) {
		if ( 'external' === $form['kind'] ) {
			return array( 'Έκδοση στο gov.gr', 'Αποστολή στο σχολείο' );
		}
		if ( 'print' === $form['mode'] ) {
			return array( 'Συμπλήρωση', 'Λήψη PDF / εκτύπωση', 'Υπογραφή & κατάθεση' );
		}
		if ( 'none' === $form['gov'] ) {
			return array( 'Συμπλήρωση', 'Λήψη PDF', 'Αποστολή στο σχολείο' );
		}
		return array( 'Συμπλήρωση', 'Λήψη PDF', 'optional' === $form['gov'] ? 'Βεβαίωση gov.gr (προαιρετική)' : 'Βεβαίωση στο gov.gr', 'Αποστολή στο σχολείο' );
	}

	/** Αρχείο από τα Πολυμέσα: url, όνομα, τύπος (PDF/Word/…), μέγεθος. */
	private static function media_file( $id ) {
		$url = $id ? wp_get_attachment_url( (int) $id ) : '';
		if ( ! $url ) {
			return null;
		}
		$path = get_attached_file( (int) $id );
		$ext  = strtolower( pathinfo( $path ? $path : $url, PATHINFO_EXTENSION ) );
		$type = array( 'pdf' => 'PDF', 'doc' => 'Word', 'docx' => 'Word', 'odt' => 'ODT', 'xls' => 'Excel', 'xlsx' => 'Excel', 'rtf' => 'RTF' );
		return array(
			'url'  => $url,
			'name' => wp_basename( $path ? $path : $url ),
			'type' => isset( $type[ $ext ] ) ? $type[ $ext ] : strtoupper( $ext ),
			'size' => $path && file_exists( $path ) ? size_format( filesize( $path ) ) : '',
		);
	}

	/** Σύντομη περιγραφή κάτω από τον τίτλο της κάρτας. */
	private static function card_meta( $f ) {
		if ( 'link' === $f['kind'] ) {
			$host = wp_parse_url( $f['link_url'], PHP_URL_HOST );
			return 'Παραπομπή σε υπηρεσία' . ( $host ? ' · ' . preg_replace( '/^www\./', '', $host ) : '' );
		}
		if ( 'download' === $f['kind'] ) {
			$types = array();
			foreach ( $f['files'] as $fid ) {
				$m = self::media_file( $fid );
				if ( $m ) {
					$types[ $m['type'] ] = true;
				}
			}
			return 'Για εκτύπωση' . ( $types ? ' · ' . implode( ' / ', array_keys( $types ) ) : '' );
		}
		$meta = array();
		if ( 'external' !== $f['kind'] ) {
			$meta[] = '≈ ' . (int) $f['minutes'] . ' λεπτά';
		}
		if ( 'print' === $f['mode'] ) {
			$meta[] = ! empty( $f['to_music'] ) ? 'Κατάθεση στο Μουσικό σχολείο' : 'Κατάθεση στο σχολείο';
		} else {
			$meta[] = ( 'required' === $f['gov'] ? 'Taxisnet · ' : '' ) . ( 'upload' === $f['mode'] ? ( ! empty( $f['health_by'] ) ? 'αποστολή από εδώ (για λόγους υγείας με email)' : 'αποστολή από εδώ' ) : 'αποστολή με email' );
		}
		return implode( ' · ', $meta );
	}

	public static function shortcode( $attrs ) {
		$atts = shortcode_atts(
			array(
				'group'      => '',
				'category'   => '', // παλιό όνομα του group
				'form'       => '',
				'show_intro' => 'yes',
				'show_steps' => 'yes',
				'search'     => 'auto',
				'layout'     => '',    // sections | list (κενό = η ρύθμιση «Εμφάνιση → Αρχική σελίδα εντύπων»)
			),
			$attrs,
			'school_eforms'
		);
		$cat        = sanitize_key( $atts['group'] ? $atts['group'] : $atts['category'] );
		$only       = sanitize_key( $atts['form'] );
		$show_intro = ! in_array( strtolower( (string) $atts['show_intro'] ), array( 'no', 'false', '0', 'όχι' ), true );
		$show_steps = ! in_array( strtolower( (string) $atts['show_steps'] ), array( 'no', 'false', '0', 'όχι' ), true );
		$s      = SEF_Settings::get();
		$forms = SEF_Forms::available( $cat, $only );

		wp_enqueue_style( 'sef-app' );
		wp_enqueue_script( 'sef-app' );

		$uid    = 'sef-' . wp_unique_id();
		$cats   = SEF_Forms::categories();
		$single = '' !== $only && 1 === count( $forms ) && 'link' !== reset( $forms )['kind'];
		$search = 'no' !== $atts['search'] && ( 'yes' === $atts['search'] || count( $forms ) >= 6 );
		$logo   = $s['logo_id'] ? wp_get_attachment_image_url( (int) $s['logo_id'], 'medium' ) : '';

		$config = array(
			'id'        => $uid,
			'ajax'      => admin_url( 'admin-ajax.php' ),
			'fonts'     => array(
				'regular' => SEF_URL . 'assets/fonts/sef-doc-regular.ttf',
				'bold'    => SEF_URL . 'assets/fonts/sef-doc-bold.ttf',
			),
			'sheetCss'  => add_query_arg( 'ver', SEF_VERSION, SEF_URL . 'assets/css/sheet.css' ),
			'guideBase' => SEF_URL . 'assets/guide/',
			'govCert'   => $s['gov_cert_url'],
			'govYd'     => $s['gov_yd_url'],
			'email'     => is_email( $s['school_email'] ) ? $s['school_email'] : '',
			'maxBytes'  => SEF_Settings::max_upload_bytes(),
			// Τοπικές αργίες για τον υπολογισμό ημερών άδειας σε εργάσιμες ([ηη, μμ, εεεε|0]).
			'holidays'  => array_map( function ( $h ) { return array( $h['d'], $h['m'], $h['y'] ); }, SEF_Settings::local_holidays() ),
			'single'    => $single,
			'school'    => $s['school_name'],
			'forms'     => array(),
		);
		foreach ( $forms as $id => $f ) {
			$config['forms'][ $id ] = array(
				'label'    => $f['label'],
				'mode'     => $f['mode'],
				'kind'     => $f['kind'],
				'subject'  => $f['subject'],
				'filename' => $f['filename'],
				'title'    => $f['doc_title'],
				'gov'      => $f['gov'],
				'govStrict' => 'required' === $f['gov'] && '' !== $f['gov_note'],
				'email'    => isset( $f['to_email'] ) ? $f['to_email'] : '',   // έντυπο Μουσικού σε Δημοτικό: email του Μουσικού
				'healthBy' => 'upload' === $f['mode'] ? $f['health_by'] : '',   // είδος «για λόγους υγείας» → μόνο email
			);
		}

		// Διάταξη: κεντρική σελίδα με ενότητες (0.7.0) ή όλα σε μία λίστα.
		$layout     = in_array( $atts['layout'], array( 'list', 'sections' ), true ) ? $atts['layout'] : $s['home_layout'];
		$hub        = ! $single && '' === $cat && '' === $only && 'list' !== $layout;
		$show_links = '1' === $s['show_links'];
		$secs       = array();
		$common_forms = array();
		$common_links = array();
		if ( $hub ) {
			foreach ( SEF_Forms::sections() as $sid => $sec ) {
				$sf = array();
				foreach ( $forms as $id => $f ) {
					if ( in_array( $f['category'], $sec['cats'], true ) ) {
						$sf[ $id ] = $f;
					}
				}
				$sl = $show_links ? self::without_cards( SEF_Settings::links( null, array( $sec['for'] ) ), $sf ) : array();
				if ( $sf || $sl ) {
					$secs[ $sid ] = $sec + array( 'forms' => $sf, 'links' => $sl );
				}
			}
			foreach ( $forms as $id => $f ) {
				if ( '' === SEF_Forms::section_of( $f['category'] ) ) {
					$common_forms[ $id ] = $f;
				}
			}
			$common_links = $show_links ? self::without_cards( SEF_Settings::links( null, array( 'all' ) ), $common_forms ) : array();
			// Με μία μόνο ενότητα (π.χ. μόνο έντυπα γονέων) οι κάρτες ενοτήτων δεν βοηθούν: όλα σε μία λίστα.
			if ( count( $secs ) < 2 ) {
				$hub = false;
			}
		}
		self::$hub = $hub ? $secs : array();
		// «Όλα σε μία λίστα» ή shortcode με group=…: οι υπηρεσίες της ομάδας (και όσες είναι για όλους).
		$flat_links = array();
		if ( ! $hub && $show_links ) {
			if ( '' === $cat ) {
				$flat_links = SEF_Settings::links();
			} else {
				$sid        = SEF_Forms::section_of( $cat );
				$sections   = SEF_Forms::sections();
				$flat_links = SEF_Settings::links( null, $sid ? array( $sections[ $sid ]['for'], 'all' ) : array( 'all' ) );
			}
			$flat_links = self::without_cards( $flat_links, $forms );
		}

		$intro   = self::intro( $forms );
		$uploads = false;
		foreach ( $forms as $f ) {
			if ( 'upload' === $f['mode'] ) {
				$uploads = true;
			}
		}

		// Χρώμα: από το θέμα (Flatsome «Secondary color» → --fs-color-secondary) ή το δικό μας.
		// Η αντίθεση κειμένου (4,5:1) διορθώνεται στη σελίδα (eforms.js · fixContrast).
		$style = 'custom' === $s['color_source'] ? '--sef-accent:' . $s['accent'] . ';' : '--sef-accent:var(--fs-color-secondary, ' . $s['accent'] . ');';
		ob_start();
		?>
		<div class="sef-app" id="<?php echo esc_attr( $uid ); ?>" data-sef data-sef-theme="<?php echo esc_attr( $s['theme'] ); ?>" style="<?php echo esc_attr( $style ); ?>">
			<?php if ( ! $forms ) : ?>
				<div class="sef-empty">Δεν υπάρχουν διαθέσιμα έντυπα σε αυτή την ενότητα.</div>
			<?php else : ?>

			<?php if ( ! $single ) : ?>
			<div class="sef-view sef-home<?php echo $hub ? ' sef-home-hub' : ''; ?>" data-sef-view="home">
				<?php if ( $show_intro ) : ?>
				<header class="sef-hero">
					<?php if ( $logo ) : ?><img class="sef-logo" src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( $s['school_name'] ); ?>"><?php endif; ?>
					<p class="sef-kicker"><?php echo esc_html( $s['school_name'] ); ?></p>
					<h2 class="sef-h1">Ηλεκτρονικά έντυπα</h2>
					<p class="sef-lead"><?php echo esc_html( $intro['lead'] ); ?></p>
					<?php if ( $show_steps && $intro['steps'] ) : ?>
					<ol class="sef-how" aria-label="Πώς λειτουργεί">
						<?php foreach ( $intro['steps'] as $n => $st ) : ?>
						<li><b><?php echo (int) ( $n + 1 ); ?></b><span><?php echo esc_html( $st[0] ); ?><small><?php echo esc_html( $st[1] ); ?></small></span></li>
						<?php endforeach; ?>
					</ol>
					<?php endif; ?>
				</header>
				<?php endif; ?>

				<?php if ( $search ) : ?>
					<div class="sef-search">
						<label class="sef-sr" for="<?php echo esc_attr( $uid ); ?>-q"><?php echo $hub ? 'Αναζήτηση σε όλα τα έντυπα' : 'Αναζήτηση εντύπου'; ?></label>
						<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
						<input type="search" id="<?php echo esc_attr( $uid ); ?>-q" data-sef-search placeholder="Αναζήτηση, π.χ. απουσίες, άδεια, βεβαίωση" autocomplete="off">
					</div>
					<p class="sef-noresults" data-sef-noresults hidden role="status">Δεν βρέθηκε έντυπο. Δοκιμάστε άλλη λέξη.</p>
				<?php endif; ?>

				<?php if ( $hub ) : ?>
					<div class="sef-hub" data-sef-hub>
						<nav class="sef-tiles" aria-label="Ενότητες">
							<?php foreach ( $secs as $sid => $sec ) : ?>
								<?php
								$n_forms = count( $sec['forms'] );
								$n_links = count( $sec['links'] );
								$now     = array();
								foreach ( $sec['forms'] as $f ) {
									if ( ! empty( $f['seasonal'] ) ) {
										$now[] = $f['label'];
									}
								}
								$meta = array();
								if ( $n_forms ) {
									$meta[] = $n_forms . ( 1 === $n_forms ? ' έντυπο' : ' έντυπα' );
								}
								if ( $n_links ) {
									$meta[] = $n_links . ( 1 === $n_links ? ' υπηρεσία' : ' υπηρεσίες' );
								}
								?>
								<a class="sef-tile sef-cat-<?php echo esc_attr( $sid ); ?>" href="#sef/<?php echo esc_attr( $sid ); ?>" data-sef-open="<?php echo esc_attr( $sid ); ?>">
									<span class="sef-tile-icon"><?php echo self::icon( $sid ); // phpcs:ignore ?></span>
									<span class="sef-tile-body">
										<strong><?php echo esc_html( $sec['title'] ); ?></strong>
										<span class="sef-card-desc"><?php echo esc_html( $sec['desc'] ); ?></span>
										<span class="sef-meta"><?php echo esc_html( implode( ' · ', $meta ) ); ?></span>
										<?php if ( $now ) : ?><span class="sef-tile-now"><b>Αυτή την περίοδο:</b> <?php echo esc_html( implode( ' · ', array_slice( $now, 0, 3 ) ) ); ?></span><?php endif; ?>
									</span>
									<span class="sef-card-go" aria-hidden="true">→</span>
								</a>
							<?php endforeach; ?>
						</nav>

						<section class="sef-common" aria-labelledby="<?php echo esc_attr( $uid ); ?>-common">
							<h3 id="<?php echo esc_attr( $uid ); ?>-common">Για όλους</h3>
							<?php if ( $common_forms ) : ?>
								<div class="sef-cat sef-cat-declarations" data-sef-grp>
									<div class="sef-cards">
										<?php foreach ( $common_forms as $id => $f ) { self::render_card( $id, $f ); } ?>
									</div>
								</div>
							<?php endif; ?>
							<button type="button" class="sef-guide-cta sef-guide-cta-home" data-sef-guide>
								<span class="sef-play" aria-hidden="true">▶</span>
								<span><b>Πώς γίνεται η ψηφιακή βεβαίωση εγγράφου στο gov.gr</b><small>Οδηγός βήμα-βήμα με εικόνες · 1½ λεπτό</small></span>
							</button>
							<?php if ( $common_links ) { self::render_links( $uid . '-links-all', $common_links, 'Χρήσιμες υπηρεσίες', 'h4' ); } ?>
						</section>
					</div>
					<div class="sef-results" data-sef-results hidden>
						<?php self::render_cat_list( $forms, $cats ); ?>
					</div>
				<?php else : ?>
					<?php
					$by_cat = array();
					foreach ( $forms as $id => $f ) {
						$by_cat[ $f['category'] ][ $id ] = $f;
					}
					if ( count( $by_cat ) > 1 ) :
						?>
						<div class="sef-chips" role="group" aria-label="Φίλτρο κατηγορίας">
							<button type="button" class="sef-chip" data-sef-filter="" aria-pressed="true">Όλα</button>
							<?php foreach ( $cats as $cid => $c ) : if ( empty( $by_cat[ $cid ] ) ) { continue; } ?>
								<button type="button" class="sef-chip" data-sef-filter="<?php echo esc_attr( $cid ); ?>" aria-pressed="false"><?php echo esc_html( $c['title'] ); ?> <span class="sef-chip-n"><?php echo (int) count( $by_cat[ $cid ] ); ?></span></button>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					<?php self::render_cat_list( $forms, $cats ); ?>
					<?php if ( $flat_links ) : ?>
						<section class="sef-links-wrap">
							<?php self::render_links( $uid . '-links', $flat_links, 'Χρήσιμες υπηρεσίες', 'h3' ); ?>
						</section>
					<?php endif; ?>
				<?php endif; ?>
			</div>
			<?php
			if ( $hub ) {
				foreach ( $secs as $sid => $sec ) {
					self::render_section( $uid, $sid, $sec, $s );
				}
			}
			?>
			<?php endif; ?>

			<?php
			foreach ( $forms as $id => $f ) {
				if ( 'download' === $f['kind'] ) {
					self::render_download( $uid, $id, $f, $single );
				} elseif ( 'link' !== $f['kind'] ) {
					self::render_form( $uid, $id, $f, $s, $single );
				}
			}
			?>

			<?php self::render_guide( $uid ); ?>
			<?php endif; ?>

			<p class="sef-privacy">
				<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
				<?php if ( $uploads ) : ?>Ό,τι γράφετε μένει στη συσκευή σας: η σελίδα δεν αποθηκεύει τα στοιχεία των εντύπων. Μόνο αν επιλέξετε «αποστολή από την ιστοσελίδα», το PDF στέλνεται στο email του σχολείου και δεν αποθηκεύεται στην ιστοσελίδα.<?php else : ?>Ό,τι γράφετε μένει στη συσκευή σας: η σελίδα δεν αποθηκεύει και δεν στέλνει τα στοιχεία των εντύπων.<?php endif; ?><?php if ( $s['privacy_url'] ) : ?> <a href="<?php echo esc_url( $s['privacy_url'] ); ?>" target="_blank" rel="noopener noreferrer">Προστασία δεδομένων</a><?php endif; ?>
			</p>
			<script type="application/json" class="sef-config"><?php echo wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE ); ?></script>
		</div>
		<?php
		return ob_get_clean();
	}

	/** Ενότητες της τρέχουσας κεντρικής σελίδας (κενό = διάταξη «όλα σε μία λίστα»). */
	private static $hub = array();

	/** Υπηρεσίες χωρίς όσες υπάρχουν ήδη ως κάρτα-παραπομπή στην ίδια οθόνη (π.χ. «Βεβαίωση φοίτησης»). */
	private static function without_cards( $links, $forms ) {
		$urls = array();
		foreach ( $forms as $f ) {
			if ( 'link' === $f['kind'] && ! empty( $f['link_url'] ) ) {
				$urls[] = untrailingslashit( strtolower( $f['link_url'] ) );
			}
		}
		if ( ! $urls ) {
			return $links;
		}
		return array_values(
			array_filter(
				$links,
				function ( $l ) use ( $urls ) {
					return ! in_array( untrailingslashit( strtolower( $l['url'] ) ), $urls, true );
				}
			)
		);
	}

	/** «←» στην κορυφή ενός εντύπου: στην ενότητά του (κεντρική σελίδα) ή στη λίστα. */
	private static function back_link( $f ) {
		if ( self::$hub ) {
			$sid = SEF_Forms::section_of( $f['category'] );
			if ( $sid && isset( self::$hub[ $sid ] ) ) {
				echo '<a class="sef-back" href="#sef/' . esc_attr( $sid ) . '" data-sef-open="' . esc_attr( $sid ) . '"><span aria-hidden="true">←</span> ' . esc_html( self::$hub[ $sid ]['back'] ) . '</a>';
				return;
			}
			echo '<a class="sef-back" href="#sef" data-sef-home><span aria-hidden="true">←</span> Αρχική σελίδα εντύπων</a>';
			return;
		}
		echo '<a class="sef-back" href="#sef" data-sef-home><span aria-hidden="true">←</span> Όλα τα έντυπα</a>';
	}

	/** Κάρτα εντύπου (ή παραπομπής). */
	private static function render_card( $id, $f ) {
		if ( 'link' === $f['kind'] ) : ?>
			<a class="sef-card sef-card-link" href="<?php echo esc_url( $f['link_url'] ); ?>" target="_blank" rel="noopener noreferrer" data-sef-card>
		<?php else : ?>
			<a class="sef-card" href="#sef/<?php echo esc_attr( $id ); ?>" data-sef-open="<?php echo esc_attr( $id ); ?>" data-sef-card>
		<?php endif; ?>
				<span class="sef-card-icon"><?php echo self::icon( $f['icon'] ); // phpcs:ignore ?></span>
				<span class="sef-card-body">
					<strong><?php echo esc_html( $f['label'] ); ?></strong>
					<span class="sef-card-desc"><?php echo esc_html( $f['desc'] ); ?></span>
					<span class="sef-meta"><?php echo esc_html( self::card_meta( $f ) ); ?></span>
				</span>
				<span class="sef-card-go" aria-hidden="true"><?php echo 'link' === $f['kind'] ? '↗' : '→'; ?></span>
				<?php if ( 'link' === $f['kind'] ) : ?><span class="sef-sr"> (ανοίγει σε νέα καρτέλα)</span><?php endif; ?>
			</a>
		<?php
	}

	/** Όλα τα έντυπα ανά κατηγορία (διάταξη «λίστα» και αποτελέσματα αναζήτησης της κεντρικής σελίδας). */
	private static function render_cat_list( $forms, $cats ) {
		$by_cat = array();
		foreach ( $forms as $id => $f ) {
			$by_cat[ $f['category'] ][ $id ] = $f;
		}
		foreach ( $cats as $cid => $c ) :
			if ( empty( $by_cat[ $cid ] ) ) {
				continue;
			}
			?>
			<section class="sef-cat sef-cat-<?php echo esc_attr( $cid ); ?>" data-sef-cat="<?php echo esc_attr( $cid ); ?>">
				<div class="sef-cat-head">
					<span class="sef-cat-icon"><?php echo self::icon( $cid ); // phpcs:ignore ?></span>
					<div><h3><?php echo esc_html( $c['title'] ); ?></h3><p><?php echo esc_html( $c['desc'] ); ?></p></div>
				</div>
				<div class="sef-cards">
					<?php foreach ( $by_cat[ $cid ] as $id => $f ) { self::render_card( $id, $f ); } ?>
				</div>
			</section>
			<?php
		endforeach;
	}

	/** «Χρήσιμες υπηρεσίες»: επίσημες υπηρεσίες, ανοίγουν σε νέα καρτέλα. */
	private static function render_links( $hid, $links, $title, $tag ) {
		$tag = in_array( $tag, array( 'h3', 'h4' ), true ) ? $tag : 'h3';
		?>
		<div class="sef-links" role="region" aria-labelledby="<?php echo esc_attr( $hid ); ?>">
			<<?php echo $tag; // phpcs:ignore ?> id="<?php echo esc_attr( $hid ); ?>"><?php echo esc_html( $title ); ?></<?php echo $tag; // phpcs:ignore ?>>
			<p class="sef-muted">Δεν είναι έντυπα του σχολείου· είναι επίσημες υπηρεσίες και ανοίγουν σε νέα καρτέλα.</p>
			<div class="sef-link-grid">
				<?php foreach ( $links as $l ) : ?>
					<a class="sef-link" href="<?php echo esc_url( $l['url'] ); ?>" target="_blank" rel="noopener noreferrer">
						<span class="sef-link-icon"><?php echo self::icon( 'link' ); // phpcs:ignore ?></span>
						<span><strong><?php echo esc_html( $l['title'] ); ?></strong><?php if ( $l['desc'] ) : ?><small><?php echo esc_html( $l['desc'] ); ?></small><?php endif; ?><span class="sef-sr"> (ανοίγει σε νέα καρτέλα)</span></span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/** Οθόνη ενότητας (#sef/parents, #sef/teachers): έντυπα της περιόδου, έντυπα, για εκτύπωση, υπηρεσίες. */
	private static function render_section( $uid, $sid, $sec, $s ) {
		$groups = array(
			'now'   => array( 'Αυτή την περίοδο', 'calendar', array() ),
			'forms' => array( 'Έντυπα και αιτήσεις', 'doc', array() ),
			'print' => array( 'Για εκτύπωση', 'print', array() ),
		);
		foreach ( $sec['forms'] as $id => $f ) {
			$g = ! empty( $f['seasonal'] ) ? 'now' : ( 'download' === $f['kind'] ? 'print' : 'forms' );
			$groups[ $g ][2][ $id ] = $f;
		}
		?>
		<div class="sef-view sef-section-view" data-sef-view="<?php echo esc_attr( $sid ); ?>" hidden>
			<div class="sef-topbar">
				<a class="sef-back" href="#sef" data-sef-home><span aria-hidden="true">←</span> Όλες οι ενότητες</a>
				<div class="sef-topbar-title">
					<span class="sef-kicker"><?php echo esc_html( $s['school_name'] ); ?></span>
					<h2 class="sef-h2" tabindex="-1"><?php echo esc_html( $sec['title'] ); ?></h2>
				</div>
			</div>
			<p class="sef-section-desc"><?php echo esc_html( $sec['desc'] ); ?></p>
			<?php foreach ( $groups as $gid => $g ) : if ( ! $g[2] ) { continue; } ?>
				<section class="sef-cat sef-cat-<?php echo esc_attr( $sid ); ?> sef-grp-<?php echo esc_attr( $gid ); ?>" data-sef-grp="<?php echo esc_attr( $gid ); ?>">
					<div class="sef-cat-head">
						<span class="sef-cat-icon"><?php echo self::icon( $g[1] ); // phpcs:ignore ?></span>
						<div><h3><?php echo esc_html( $g[0] ); ?></h3><?php if ( 'now' === $gid ) : ?><p>Εμφανίζονται μόνο όσο διαρκεί η περίοδος υποβολής τους.</p><?php endif; ?></div>
					</div>
					<div class="sef-cards">
						<?php foreach ( $g[2] as $id => $f ) { self::render_card( $id, $f ); } ?>
					</div>
				</section>
			<?php endforeach; ?>
			<?php if ( $sec['links'] ) { self::render_links( $uid . '-links-' . $sid, $sec['links'], 'Χρήσιμες υπηρεσίες', 'h3' ); } ?>
		</div>
		<?php
	}

	/** Ρητή, εμφανής υπενθύμιση ότι γίνεται δεκτό μόνο το αρχείο από το gov.gr. */
	private static function gov_note( $f ) {
		if ( 'required' !== $f['gov'] || '' === $f['gov_note'] ) {
			return;
		}
		echo '<p class="sef-note sef-note-strong"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3 2 21h20L12 3z"/><path d="M12 10v5m0 3v.5"/></svg><span>' . esc_html( $f['gov_note'] ) . '</span></p>';
		if ( '' !== $f['paper_note'] ) {
			echo '<p class="sef-note sef-note-paper">' . esc_html( $f['paper_note'] ) . '</p>';
		}
	}

	private static function step_head( $n, $title, $state = '' ) {
		echo '<h3 class="sef-step-title"><span class="sef-num" aria-hidden="true">' . (int) $n . '</span><span>' . esc_html( $title ) . '</span></h3>';
	}

	private static function render_form( $uid, $id, $f, $s, $single ) {
		$px     = $uid . '-' . $id;
		$steps  = self::steps_for( $f );
		$cats   = SEF_Forms::categories();
		$n      = 0;
		?>
		<div class="sef-view sef-form-view" data-sef-view="<?php echo esc_attr( $id ); ?>" data-sef-mode="<?php echo esc_attr( $f['mode'] ); ?>" data-sef-kind="<?php echo esc_attr( $f['kind'] ); ?>" data-sef-gov-mode="<?php echo esc_attr( $f['gov'] ); ?>"<?php echo $single ? '' : ' hidden'; ?>>
			<div class="sef-topbar">
				<?php if ( ! $single ) { self::back_link( $f ); } ?>
				<div class="sef-topbar-title">
					<span class="sef-kicker"><?php echo esc_html( $cats[ $f['category'] ]['title'] ?? '' ); ?></span>
					<h2 class="sef-h2" tabindex="-1"><?php echo esc_html( $f['label'] ); ?></h2>
				</div>
			</div>

			<ol class="sef-stepper" aria-label="Βήματα">
				<?php foreach ( $steps as $i => $label ) : ?>
					<li data-sef-stepper="<?php echo (int) ( $i + 1 ); ?>" data-state="<?php echo 0 === $i ? 'current' : 'todo'; ?>">
						<a href="#<?php echo esc_attr( $px . '-s' . ( $i + 1 ) ); ?>"><b class="sef-dot"><?php echo (int) ( $i + 1 ); ?></b><span><?php echo esc_html( $label ); ?></span></a>
					</li>
				<?php endforeach; ?>
			</ol>

			<?php if ( 'external' === $f['kind'] ) : ?>
				<section class="sef-step" id="<?php echo esc_attr( $px . '-s1' ); ?>" data-sef-step="1">
					<?php self::step_head( ++$n, 'Εκδώστε τη δήλωση στο gov.gr' ); ?>
					<p class="sef-p">Η υπεύθυνη δήλωση με δικό σας κείμενο εκδίδεται <b>απευθείας</b> από την επίσημη υπηρεσία, με κωδικούς Taxisnet. Στο πεδίο του αποδέκτη γράψτε: <b><?php echo esc_html( $s['school_to'] ); ?></b>.</p>
					<p class="sef-p">Στο τέλος πατήστε «Αποθήκευση» για να κατεβάσετε το PDF και επιστρέψτε εδώ.</p>
					<div class="sef-actions"><a class="sef-btn sef-btn-gov" href="<?php echo esc_url( $s['gov_yd_url'] ); ?>" target="_blank" rel="noopener noreferrer" data-sef-gov>Άνοιγμα υπηρεσίας στο gov.gr <span aria-hidden="true">↗</span></a></div>
				</section>
				<?php self::render_send( $px, $id, $f, $s, ++$n ); ?>
			<?php else : ?>
				<section class="sef-step sef-step-fill" id="<?php echo esc_attr( $px . '-s1' ); ?>" data-sef-step="1">
					<div class="sef-step-row">
						<?php self::step_head( ++$n, 'Συμπληρώστε το έντυπο' ); ?>
						<button type="button" class="sef-btn sef-btn-ghost sef-btn-sm" data-sef-clear>Καθαρισμός</button>
					</div>
					<?php if ( $f['hint'] ) : ?><p class="sef-p"><?php echo esc_html( $f['hint'] ); ?></p><?php endif; ?>
					<p class="sef-p sef-muted">Τα πεδία με γαλάζιο φόντο συμπληρώνονται. Τα υποχρεωτικά επισημαίνονται αν μείνουν κενά.</p>
					<div class="sef-paper-alt">
						<span>Προτιμάτε χαρτί;</span>
						<button type="button" class="sef-btn sef-btn-ghost sef-btn-sm" data-sef-blank>Κενό έντυπο για εκτύπωση (PDF)</button>
						<?php $blank_file = self::media_file( $f['file'] ); ?>
						<?php if ( $blank_file ) : ?><a class="sef-btn sef-btn-ghost sef-btn-sm" href="<?php echo esc_url( $blank_file['url'] ); ?>" download><?php echo esc_html( 'Κενό έντυπο (' . $blank_file['type'] . ')' ); ?></a><?php endif; ?>
						<small class="sef-paper-tip">Για κατάθεση ιδιοχείρως δεν χρειάζεται να το γράψετε με το χέρι: συμπληρώστε το εδώ και πατήστε «Λήψη PDF» ή «Εκτύπωση» στο βήμα 2, υπογράψτε το και καταθέστε το.</small>
					</div>
					<p class="sef-status" data-sef-blank-status role="status" aria-live="polite"></p>
				</section>
				<div class="sef-paper">
					<form class="sef-sheet<?php echo ! empty( $f['stamp'] ) ? ' sef-stamp' : ''; ?>" data-sef-sheet autocomplete="on" novalidate onsubmit="return false">
						<?php echo SEF_Template::render( $f, $px ); // phpcs:ignore WordPress.Security.EscapeOutput -- τα πρότυπα κάνουν escaping. ?>
					</form>
				</div>

				<section class="sef-step" id="<?php echo esc_attr( $px . '-s2' ); ?>" data-sef-step="2">
					<?php self::step_head( ++$n, 'print' === $f['mode'] ? 'Κατεβάστε ή εκτυπώστε το έντυπο' : 'Κατεβάστε το PDF' ); ?>
					<p class="sef-p">Το PDF αποθηκεύεται στις «Λήψεις» του υπολογιστή ή του κινητού σας. Περιέχει πραγματικό κείμενο, σε μορφή Α4.</p>
					<div class="sef-missing" data-sef-missing hidden role="alert"></div>
					<div class="sef-actions">
						<button type="button" class="sef-btn sef-btn-primary sef-btn-lg" data-sef-pdf>
							<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3v12m0 0-4-4m4 4 4-4M5 21h14"/></svg>
							Λήψη PDF
						</button>
						<button type="button" class="sef-btn sef-btn-ghost" data-sef-print>Εκτύπωση</button>
					</div>
					<p class="sef-status" data-sef-pdf-status role="status" aria-live="polite"></p>
				</section>

				<?php if ( 'print' === $f['mode'] ) : ?>
					<section class="sef-step" id="<?php echo esc_attr( $px . '-s3' ); ?>" data-sef-step="3">
						<?php self::step_head( ++$n, 'Υπογράψτε και καταθέστε το' ); ?>
						<?php if ( ! empty( $f['to_music'] ) ) : ?>
						<p class="sef-p">Εκτυπώστε το έντυπο, υπογράψτε το ιδιόχειρα και καταθέστε το στο <b><?php echo esc_html( $f['to_music'] ); ?></b>.</p>
						<?php else : ?>
						<p class="sef-p">Εκτυπώστε το έντυπο, υπογράψτε το ιδιόχειρα και καταθέστε το στο σχολείο<?php echo $s['school_address'] ? ' (' . esc_html( $s['school_address'] ) . ')' : ''; ?>.</p>
						<?php endif; ?>
						<?php if ( $f['send_note'] ) : ?><p class="sef-note"><?php echo esc_html( $f['send_note'] ); ?></p><?php endif; ?>
						<label class="sef-confirm"><input type="checkbox" data-sef-done-check="3"> <span>Το κατέθεσα / θα το καταθέσω στο σχολείο.</span></label>
					</section>
				<?php elseif ( 'none' === $f['gov'] ) : ?>
					<?php self::render_send( $px, $id, $f, $s, ++$n ); ?>
				<?php else : ?>
					<section class="sef-step" id="<?php echo esc_attr( $px . '-s3' ); ?>" data-sef-step="3">
						<?php self::step_head( ++$n, 'optional' === $f['gov'] ? 'Βεβαιώστε το στο gov.gr (προαιρετικά)' : 'Βεβαιώστε το ψηφιακά στο gov.gr' ); ?>
						<?php if ( 'optional' === $f['gov'] ) : ?><p class="sef-note">Το σχολείο δέχεται αυτό το έντυπο και χωρίς ψηφιακή βεβαίωση. Με τη βεβαίωση όμως αποδεικνύεται ότι το στείλατε εσείς.</p><?php endif; ?>
						<p class="sef-p">Στην υπηρεσία «Ψηφιακή βεβαίωση εγγράφου»: συνδέεστε με κωδικούς Taxisnet, ανεβάζετε το PDF, γράφετε τον κωδικό που έρχεται με SMS και <b>κατεβάζετε το βεβαιωμένο αρχείο</b>.</p>
						<button type="button" class="sef-guide-cta" data-sef-guide>
							<span class="sef-play" aria-hidden="true">▶</span>
							<span><b>Δείτε πώς γίνεται</b><small>Οδηγός βήμα-βήμα με εικόνες από το gov.gr · 1½ λεπτό</small></span>
						</button>
						<div class="sef-actions"><a class="sef-btn sef-btn-gov sef-btn-lg" href="<?php echo esc_url( $s['gov_cert_url'] ); ?>" target="_blank" rel="noopener noreferrer" data-sef-gov>Άνοιγμα gov.gr <span aria-hidden="true">↗</span></a></div>
						<?php if ( 'required' === $f['gov'] && '' === $f['gov_note'] ) : ?><p class="sef-note">Δεν γίνεται δεκτή υπογραφή με το χέρι ή σκαναρισμένο χαρτί· μόνο το αρχείο που δίνει το gov.gr.</p><?php endif; ?>
						<?php self::gov_note( $f ); ?>
					</section>
					<?php self::render_send( $px, $id, $f, $s, ++$n ); ?>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/** Έντυπο «μόνο για εκτύπωση»: περιγραφή και αρχεία από τα Πολυμέσα. */
	private static function render_download( $uid, $id, $f, $single ) {
		$cats = SEF_Forms::categories();
		?>
		<div class="sef-view sef-form-view sef-download-view" data-sef-view="<?php echo esc_attr( $id ); ?>" data-sef-kind="download"<?php echo $single ? '' : ' hidden'; ?>>
			<div class="sef-topbar">
				<?php if ( ! $single ) { self::back_link( $f ); } ?>
				<div class="sef-topbar-title">
					<span class="sef-kicker"><?php echo esc_html( isset( $cats[ $f['category'] ] ) ? $cats[ $f['category'] ]['title'] : '' ); ?></span>
					<h2 class="sef-h2" tabindex="-1"><?php echo esc_html( $f['label'] ); ?></h2>
				</div>
			</div>
			<section class="sef-step" data-state="current">
				<h3 class="sef-step-title"><span class="sef-num" aria-hidden="true">↓</span><span>Κατεβάστε και εκτυπώστε το έντυπο</span></h3>
				<?php if ( $f['desc'] ) : ?><p class="sef-p"><?php echo esc_html( $f['desc'] ); ?></p><?php endif; ?>
				<ul class="sef-files">
					<?php
					foreach ( $f['files'] as $fid ) :
						$m = self::media_file( $fid );
						if ( ! $m ) {
							continue;
						}
						?>
						<li><a class="sef-file" href="<?php echo esc_url( $m['url'] ); ?>" download>
							<span class="sef-file-ico sef-file-<?php echo esc_attr( strtolower( $m['type'] ) ); ?>" aria-hidden="true"><?php echo esc_html( $m['type'] ); ?></span>
							<span class="sef-file-txt"><b><?php echo esc_html( $m['name'] ); ?></b><small><?php echo esc_html( trim( $m['type'] . ( $m['size'] ? ' · ' . $m['size'] : '' ) ) ); ?></small></span>
							<span class="sef-file-go" aria-hidden="true">↓</span>
						</a></li>
					<?php endforeach; ?>
				</ul>
				<p class="sef-muted sef-small">Συμπληρώνεται με το χέρι και κατατίθεται στο σχολείο, εκτός αν το σχολείο ορίσει διαφορετικά.</p>
			</section>
		</div>
		<?php
	}

	/** Τελικό βήμα: αποστολή με email ή μέσω ιστοσελίδας, με έλεγχο αρχείου. */
	private static function render_send( $px, $id, $f, $s, $n ) {
		$sid  = $px . '-s' . $n;
		$gov  = $f['gov'];
		$what = 'required' === $gov ? 'το αρχείο που κατεβάσατε από το gov.gr' : ( 'optional' === $gov ? 'το PDF του εντύπου (ή το βεβαιωμένο από το gov.gr, αν το βεβαιώσατε)' : 'το PDF του εντύπου' );
		$to   = ! empty( $f['to_email'] ) ? $f['to_email'] : $s['school_email']; // έντυπο Μουσικού σε Δημοτικό → email του Μουσικού
		?>
		<section class="sef-step" id="<?php echo esc_attr( $sid ); ?>" data-sef-step="<?php echo (int) $n; ?>" data-sef-send>
			<?php self::step_head( $n, 'Στείλτε το στο σχολείο' ); ?>
			<?php self::gov_note( $f ); ?>

			<div class="sef-check" data-sef-checker>
				<p class="sef-p"><b>Έλεγχος αρχείου (<?php echo 'required' === $gov && $f['gov_note'] ? 'συνιστάται' : 'προαιρετικός'; ?>):</b> επιλέξτε <?php echo esc_html( $what ); ?> για να δείτε αν είναι το σωστό. Ο έλεγχος γίνεται μόνο στη συσκευή σας.</p>
				<label class="sef-drop">
					<input type="file" accept="application/pdf,.pdf" data-sef-check-file>
					<span class="sef-drop-icon" aria-hidden="true">PDF</span>
					<span class="sef-drop-text"><b>Επιλέξτε ή σύρετε εδώ το PDF</b><small data-sef-file-name>Δεν έχει επιλεγεί αρχείο</small></span>
				</label>
				<p class="sef-fcheck" data-sef-check-result hidden role="status" aria-live="polite"></p>
			</div>

			<?php if ( 'upload' === $f['mode'] ) : ?>
				<?php if ( $f['health_by'] ) : ?><div data-sef-send-up><?php endif; ?>
				<form class="sef-upload" data-sef-upload enctype="multipart/form-data" novalidate>
					<input type="hidden" name="sef_form" value="<?php echo esc_attr( $id ); ?>">
					<div class="sef-grid2">
						<label class="sef-field"><span>Ονοματεπώνυμο αποστολέα *</span><input type="text" name="name" maxlength="150" required autocomplete="name"></label>
						<label class="sef-field"><span>Email επικοινωνίας *</span><input type="email" name="email" maxlength="150" required autocomplete="email"></label>
						<label class="sef-field sef-span2"><span>Θέμα *</span><input type="text" name="subject" maxlength="200" required data-sef-subject-input></label>
						<?php if ( 'none' !== $gov ) : ?><label class="sef-field"><span>Κωδικός εγγράφου gov.gr <small>(προαιρετικά)</small></span><input type="text" name="code" maxlength="40" autocomplete="off" placeholder="από το κάτω μέρος του PDF"></label><?php endif; ?>
						<div class="sef-field"><span>Αρχείο *</span><p class="sef-muted sef-small" data-sef-upload-file>Επιλέξτε το αρχείο στον «Έλεγχο αρχείου» παραπάνω (έως <?php echo esc_html( size_format( SEF_Settings::max_upload_bytes() ) ); ?>).</p></div>
					</div>
					<div class="sef-challenge" data-sef-challenge>
						<label class="sef-field"><span data-sef-question>Ερώτηση ελέγχου: φόρτωση…</span><input type="number" name="sef_answer" inputmode="numeric" min="0" max="99" required></label>
						<input type="hidden" name="sef_cid" value="">
					</div>
					<div class="sef-hp" aria-hidden="true"><label>Μην συμπληρώνετε αυτό το πεδίο<input tabindex="-1" autocomplete="off" type="text" name="sef_website"></label></div>
					<label class="sef-confirm"><input type="checkbox" name="sef_verified" value="1" required> <span><?php if ( 'required' === $gov ) : ?>Επισυνάπτω το αρχείο που κατέβασα από το gov.gr <b>μετά</b> τη βεβαίωση, χωρίς αλλαγές.<?php else : ?>Επισυνάπτω το τελικό PDF του εντύπου, χωρίς αλλαγές.<?php endif; ?></span></label>
					<label class="sef-confirm"><input type="checkbox" name="sef_consent" value="1" required> <span>Διάβασα την <a href="<?php echo esc_url( $s['privacy_url'] ); ?>" target="_blank" rel="noopener noreferrer">ενημέρωση για τα προσωπικά δεδομένα</a> και κατανοώ ότι το έγγραφο θα σταλεί στο email του σχολείου.</span></label>
					<?php if ( $f['send_note'] ) : ?><p class="sef-note"><?php echo esc_html( $f['send_note'] ); ?></p><?php endif; ?>
					<p class="sef-note">Μη στέλνετε από εδώ ιατρικά ή άλλα ευαίσθητα δικαιολογητικά.</p>
					<div class="sef-actions"><button type="submit" class="sef-btn sef-btn-primary sef-btn-lg">Αποστολή στο σχολείο</button></div>
					<p class="sef-status" data-sef-send-status role="status" aria-live="polite"></p>
				</form>
				<?php if ( $f['health_by'] ) : ?></div>
				<div data-sef-send-mail hidden>
					<p class="sef-note sef-note-health">Για άδεια <b>για λόγους υγείας</b> η αίτηση δεν στέλνεται από την ιστοσελίδα: στείλτε την με email, όπως παρακάτω.</p>
					<?php self::mail_block( $f, $what, $to ); ?>
				</div>
				<?php endif; ?>
			<?php else : ?>
				<?php self::mail_block( $f, $what, $to ); ?>
			<?php endif; ?>
		</section>
		<?php
	}

	/** Οδηγίες αποστολής με email (διεύθυνση, θέμα, κουμπιά Gmail / Outlook / συσκευής). */
	private static function mail_block( $f, $what, $to ) {
		?>
				<p class="sef-p">Στείλτε με email <?php echo esc_html( $what ); ?>, <b>χωρίς καμία αλλαγή</b>, στη διεύθυνση<?php echo ! empty( $f['to_music'] ) ? ' του ' . esc_html( $f['to_music'] ) : ''; ?>:</p>
				<div class="sef-mailbox">
					<span class="sef-addr" data-sef-copy-src><?php echo esc_html( $to ); ?></span>
					<button type="button" class="sef-btn sef-btn-ghost sef-btn-sm" data-sef-copy="addr">Αντιγραφή</button>
				</div>
				<div class="sef-subject">
					<p class="sef-p">Στο <b>«Θέμα»</b> του email γράψτε:</p>
					<div class="sef-subject-box"><span data-sef-subject></span><button type="button" class="sef-btn sef-btn-ghost sef-btn-sm" data-sef-copy="subject">Αντιγραφή</button></div>
				</div>
				<?php if ( $f['send_note'] ) : ?><p class="sef-note"><?php echo esc_html( $f['send_note'] ); ?></p><?php endif; ?>
				<p class="sef-p"><b>Νέο μήνυμα με έτοιμα παραλήπτη και θέμα:</b></p>
				<div class="sef-actions sef-mail-actions">
					<a class="sef-btn sef-btn-ghost sef-mail-btn" href="https://mail.google.com/mail/" target="_blank" rel="noopener noreferrer" data-sef-compose="gmail"><span class="sef-mail-ico sef-mail-gmail" aria-hidden="true">G</span> Gmail <span aria-hidden="true">↗</span></a>
					<a class="sef-btn sef-btn-ghost sef-mail-btn" href="https://outlook.live.com/mail/" target="_blank" rel="noopener noreferrer" data-sef-compose="outlook"><span class="sef-mail-ico sef-mail-outlook" aria-hidden="true">O</span> Outlook / Hotmail <span aria-hidden="true">↗</span></a>
					<a class="sef-btn sef-btn-ghost sef-mail-btn" href="mailto:<?php echo esc_attr( $to ); ?>" data-sef-compose="mailto"><span class="sef-mail-ico" aria-hidden="true">✉</span> Πρόγραμμα email συσκευής</a>
				</div>
				<p class="sef-muted sef-small">Επισυνάψτε στο μήνυμα το αρχείο από το gov.gr (συνήθως στις «Λήψεις»). Αν δεν ανοίξει τίποτα, χρησιμοποιήστε τα κουμπιά «Αντιγραφή» και γράψτε το μήνυμα στο email σας. Για άλλη υπηρεσία email (π.χ. sch.gr, Yahoo) ισχύει το ίδιο.</p>
		<?php
	}

	private static function render_guide( $uid ) {
		?>
		<dialog class="sef-guide" data-sef-guide-dialog aria-labelledby="<?php echo esc_attr( $uid ); ?>-guide-t">
			<div class="sef-guide-head">
				<div><h2 id="<?php echo esc_attr( $uid ); ?>-guide-t">Πώς γίνεται η ψηφιακή βεβαίωση εγγράφου</h2><p>Ακολουθήστε τα ίδια βήματα στο gov.gr</p></div>
				<button type="button" class="sef-guide-x" data-sef-guide-close aria-label="Κλείσιμο">✕</button>
			</div>
			<div class="sef-vp">
				<div class="sef-vp-screen" data-vp-screen>
					<div class="sef-vp-stage" data-vp-stage><div class="sef-vp-layer" data-vp-layer>
						<img data-vp-shot alt="" width="1366" height="660">
						<div class="sef-vp-hl" data-vp-hl></div>
						<div class="sef-vp-cur" data-vp-cur><svg viewBox="0 0 24 24" width="30" height="30" aria-hidden="true"><path d="M4 2l15 11-7 1 4 8-3 1-4-8-5 5z" fill="#fff" stroke="#111" stroke-width="1.4" stroke-linejoin="round"/></svg><span class="sef-vp-rip" data-vp-rip></span></div>
					</div></div>
					<button type="button" class="sef-vp-big" data-vp-big><span aria-hidden="true">▶</span><em>Αναπαραγωγή</em></button>
				</div>
				<div class="sef-vp-cap" aria-live="polite"><b data-vp-n></b><span data-vp-t></span></div>
				<div class="sef-vp-ctrl">
					<button type="button" data-vp-prev aria-label="Προηγούμενο βήμα">⏮</button>
					<button type="button" data-vp-play aria-label="Αναπαραγωγή">▶</button>
					<button type="button" data-vp-next aria-label="Επόμενο βήμα">⏭</button>
					<div class="sef-vp-segs" data-vp-segs></div>
					<span class="sef-vp-time" data-vp-time>0:00</span>
				</div>
			</div>
		</dialog>
		<?php
	}

	/**
	 * Κείμενο εισαγωγής και βήματα, ανάλογα με τα έντυπα που εμφανίζονται.
	 * Χωρίς έντυπα συμπλήρωσης (μόνο λήψη/παραπομπή) δεν δείχνει βήματα·
	 * το gov.gr και η αποστολή εμφανίζονται μόνο αν ισχύουν για κάποιο έντυπο.
	 */
	private static function intro( $forms ) {
		$fill = 0;
		$gov  = 0;
		$send = 0;
		$pdf  = 0;
		foreach ( $forms as $f ) {
			if ( ! in_array( $f['kind'], array( 'sheet', 'external' ), true ) ) {
				continue;
			}
			$fill++;
			if ( 'sheet' === $f['kind'] ) {
				$pdf++;
			}
			if ( 'none' !== $f['gov'] ) {
				$gov++;
			}
			if ( 'print' !== $f['mode'] ) {
				$send++;
			}
		}
		if ( ! $fill ) {
			return array(
				'lead'  => 'Εδώ βρίσκετε τα έντυπα και τις υπηρεσίες του σχολείου: κατεβάζετε το έντυπο που χρειάζεστε ή μεταβαίνετε στην αντίστοιχη υπηρεσία.',
				'steps' => array(),
			);
		}
		$lead  = 'Συμπληρώνετε το έντυπο εδώ';
		$steps = array( array( 'Συμπλήρωση', 'πάνω στο έντυπο' ) );
		if ( $pdf ) {
			$steps[] = array( 'Λήψη PDF', 'με ένα κουμπί' );
		}
		if ( $gov ) {
			$lead   .= $gov === $fill ? ', το βεβαιώνετε ψηφιακά στο gov.gr' : ', το βεβαιώνετε ψηφιακά στο gov.gr όπου χρειάζεται';
			$steps[] = array( 'Βεβαίωση', $gov === $fill ? 'στο gov.gr' : 'στο gov.gr, όπου χρειάζεται' );
		}
		if ( $send ) {
			$lead   .= ' και το στέλνετε στο σχολείο';
			$steps[] = array( 'Αποστολή', 'στο σχολείο' );
		} else {
			$lead   .= ' και το εκτυπώνετε για να το παραδώσετε στο σχολείο';
			$steps[] = array( 'Εκτύπωση', 'και παράδοση στο σχολείο' );
		}
		return array(
			'lead'  => $lead . ' — από τον υπολογιστή ή το κινητό σας.',
			'steps' => $steps,
		);
	}
}
