<?php
/**
 * Ενημερώσεις από το GitHub (0.9.6).
 *
 * Το WordPress ελέγχει για ενημερώσεις πρόσθετων περίπου δύο φορές την ημέρα. Τότε το πρόσθετο διαβάζει
 * το μικρό αρχείο update.json του repository stam1966/school-eforms και, αν η έκδοση είναι νεότερη,
 * η ενημέρωση εμφανίζεται στον πίνακα του WordPress όπως σε κάθε άλλο πρόσθετο (ενημέρωση με ένα κλικ,
 * «Λεπτομέρειες έκδοσης» με τη λίστα αλλαγών, αυτόματες ενημερώσεις αν τις ενεργοποιήσει ο διαχειριστής).
 *
 * Η σύνδεση γίνεται από τον διακομιστή του σχολείου προς το GitHub — ποτέ από τον browser των επισκεπτών —
 * και δεν στέλνει κανένα στοιχείο του σχολείου (ουδέτερο User-Agent, χωρίς διεύθυνση ιστοσελίδας).
 * Απενεργοποιείται από τις ρυθμίσεις («Έλεγχος για ενημερώσεις»).
 *
 * Μορφή update.json: {"version":"0.9.6","download":"https://…zip","requires":"6.0","requires_php":"7.4",
 * "tested":"6.8","date":"2026-11-12","changelog":"γραμμές κειμένου· «- » για κουκκίδες"}
 */

defined( 'ABSPATH' ) || exit;

class SEF_Updater {

	const SLUG      = 'school-eforms';
	const REPO      = 'https://github.com/stam1966/school-eforms';
	const INFO_URL  = 'https://raw.githubusercontent.com/stam1966/school-eforms/main/update.json';
	const CACHE     = 'sef_update_info';
	const CACHE_TTL = 43200; // 12 ώρες

	public static function init() {
		add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'inject' ) );
		add_filter( 'plugins_api', array( __CLASS__, 'details' ), 20, 3 );
		add_filter( 'upgrader_source_selection', array( __CLASS__, 'fix_folder' ), 10, 4 );
		add_action( 'upgrader_process_complete', array( __CLASS__, 'clear' ), 10, 0 );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'row_meta' ), 10, 2 );
	}

	public static function enabled() {
		return '0' !== (string) SEF_Settings::get()['update_check'];
	}

	public static function basename() {
		return plugin_basename( SEF_FILE );
	}

	/** Διεύθυνση του update.json (αλλάζει μόνο για δοκιμές, με το φίλτρο sef_update_info_url). */
	private static function info_url() {
		return (string) apply_filters( 'sef_update_info_url', self::INFO_URL );
	}

	/**
	 * Τα στοιχεία της τελευταίας έκδοσης (με προσωρινή μνήμη 12 ωρών· αποτυχία → 1 ώρα, για να μη
	 * βαραίνει ο πίνακας όταν το GitHub δεν απαντά). $force: «Έλεγχος ξανά» στη σελίδα Ενημερώσεων.
	 */
	public static function info( $force = false ) {
		if ( ! self::enabled() ) {
			return null;
		}
		$c = get_site_transient( self::CACHE );
		if ( ! $force && is_array( $c ) ) {
			return empty( $c['version'] ) ? null : $c;
		}
		$r = wp_remote_get(
			self::info_url(),
			array(
				'timeout'    => 8,
				'user-agent' => 'School-eForms-Updater/' . SEF_VERSION, // χωρίς τη διεύθυνση του σχολείου
				'headers'    => array( 'Accept' => 'application/json' ),
			)
		);
		$data = null;
		if ( ! is_wp_error( $r ) && 200 === (int) wp_remote_retrieve_response_code( $r ) ) {
			$data = self::parse( wp_remote_retrieve_body( $r ) );
		}
		set_site_transient( self::CACHE, $data ? $data : array( 'version' => '' ), $data ? self::CACHE_TTL : HOUR_IN_SECONDS );
		return $data;
	}

	/** Έλεγχος και καθαρισμός του update.json· μόνο λήψεις από το GitHub. */
	public static function parse( $body ) {
		$j = json_decode( (string) $body, true );
		if ( ! is_array( $j ) || empty( $j['version'] ) || empty( $j['download'] ) ) {
			return null;
		}
		$v = (string) $j['version'];
		if ( ! preg_match( '/^\d+\.\d+(\.\d+)?$/', $v ) ) {
			return null;
		}
		$dl   = esc_url_raw( (string) $j['download'] );
		$host = wp_parse_url( $dl, PHP_URL_HOST );
		if ( 'https' !== wp_parse_url( $dl, PHP_URL_SCHEME ) || ! in_array( $host, array( 'github.com', 'codeload.github.com', 'objects.githubusercontent.com', 'raw.githubusercontent.com' ), true ) ) {
			return null;
		}
		return array(
			'version'      => $v,
			'download'     => $dl,
			'requires'     => isset( $j['requires'] ) ? sanitize_text_field( $j['requires'] ) : '6.0',
			'requires_php' => isset( $j['requires_php'] ) ? sanitize_text_field( $j['requires_php'] ) : '7.4',
			'tested'       => isset( $j['tested'] ) ? sanitize_text_field( $j['tested'] ) : '',
			'date'         => isset( $j['date'] ) ? sanitize_text_field( $j['date'] ) : '',
			'changelog'    => isset( $j['changelog'] ) ? mb_substr( (string) $j['changelog'], 0, 20000 ) : '',
		);
	}

	/** Προσθέτει την ενημέρωση στη λίστα του WordPress, όταν υπάρχει νεότερη έκδοση. */
	public static function inject( $transient ) {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}
		$force = is_admin() && isset( $_GET['force-check'] ); // phpcs:ignore WordPress.Security.NonceVerification -- μόνο ανάγνωση
		$info  = self::info( $force );
		$base  = self::basename();
		$item  = (object) array(
			'id'           => self::REPO,
			'slug'         => self::SLUG,
			'plugin'       => $base,
			'new_version'  => $info ? $info['version'] : SEF_VERSION,
			'url'          => self::REPO,
			'package'      => $info ? $info['download'] : '',
			'requires'     => $info ? $info['requires'] : '',
			'requires_php' => $info ? $info['requires_php'] : '',
			'tested'       => $info ? $info['tested'] : '',
			'icons'        => array(),
			'banners'      => array(),
		);
		if ( $info && version_compare( $info['version'], SEF_VERSION, '>' ) ) {
			if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
				$transient->response = array();
			}
			$transient->response[ $base ] = $item;
		} else {
			// Χωρίς ενημέρωση: δηλώνεται ως «ενημερωμένο», ώστε να λειτουργεί και η επιλογή αυτόματων ενημερώσεων.
			if ( ! isset( $transient->no_update ) || ! is_array( $transient->no_update ) ) {
				$transient->no_update = array();
			}
			$item->new_version             = SEF_VERSION;
			$transient->no_update[ $base ] = $item;
			if ( isset( $transient->response[ $base ] ) ) {
				unset( $transient->response[ $base ] );
			}
		}
		return $transient;
	}

	/** «Λεπτομέρειες έκδοσης»: παράθυρο με τη λίστα αλλαγών. */
	public static function details( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || ! isset( $args->slug ) || self::SLUG !== $args->slug ) {
			return $result;
		}
		$info = self::info();
		$ver  = $info ? $info['version'] : SEF_VERSION;
		return (object) array(
			'name'          => 'School e-Forms',
			'slug'          => self::SLUG,
			'version'       => $ver,
			'author'        => 'School e-Forms Project',
			'homepage'      => self::REPO,
			'requires'      => $info ? $info['requires'] : '6.0',
			'requires_php'  => $info ? $info['requires_php'] : '7.4',
			'tested'        => $info ? $info['tested'] : '',
			'last_updated'  => $info ? $info['date'] : '',
			'download_link' => $info ? $info['download'] : '',
			'sections'      => array(
				'description' => '<p>Ηλεκτρονικά έντυπα σχολείου: συμπλήρωση πάνω στο επίσημο υπόδειγμα, PDF με πραγματικό ελληνικό κείμενο, ψηφιακή βεβαίωση μέσω gov.gr και αποστολή στο σχολείο. Χωρίς αποθήκευση προσωπικών δεδομένων.</p>',
				'changelog'   => self::changelog_html( $info ? $info['changelog'] : '' ),
			),
		);
	}

	/** Απλό κείμενο → HTML: «- » κουκκίδες, «## » επικεφαλίδες, υπόλοιπα παράγραφοι. */
	public static function changelog_html( $text ) {
		if ( '' === trim( (string) $text ) ) {
			return '<p>Δείτε το <a href="' . esc_url( self::REPO ) . '">repository</a>.</p>';
		}
		$html = '';
		$list = false;
		foreach ( preg_split( '/\r\n|\r|\n/', (string) $text ) as $line ) {
			$line = trim( $line );
			if ( preg_match( '/^[-•*]\s+(.*)$/u', $line, $m ) ) {
				$html .= ( $list ? '' : '<ul>' ) . '<li>' . esc_html( $m[1] ) . '</li>';
				$list  = true;
				continue;
			}
			if ( $list ) {
				$html .= '</ul>';
				$list  = false;
			}
			if ( '' === $line ) {
				continue;
			}
			$html .= preg_match( '/^#+\s*(.*)$/u', $line, $m ) ? '<h4>' . esc_html( $m[1] ) . '</h4>' : '<p>' . esc_html( $line ) . '</p>';
		}
		return $html . ( $list ? '</ul>' : '' );
	}

	/**
	 * Το zip του GitHub αποσυμπιέζεται σε φάκελο «school-eforms-<κωδικός>»· μετονομάζεται σε «school-eforms»,
	 * ώστε η ενημέρωση να αντικαθιστά το ίδιο πρόσθετο (και να μένει ενεργό).
	 */
	public static function fix_folder( $source, $remote_source, $upgrader, $hook_extra = array() ) {
		global $wp_filesystem;
		if ( empty( $hook_extra['plugin'] ) || self::basename() !== $hook_extra['plugin'] || is_wp_error( $source ) ) {
			return $source;
		}
		$want = trailingslashit( $remote_source ) . self::SLUG . '/';
		if ( untrailingslashit( $source ) === untrailingslashit( $want ) ) {
			return $source;
		}
		if ( $wp_filesystem && $wp_filesystem->move( untrailingslashit( $source ), untrailingslashit( $want ), true ) ) {
			return $want;
		}
		return new WP_Error( 'sef_update_folder', 'Δεν ήταν δυνατή η μετονομασία του φακέλου της ενημέρωσης.' );
	}

	public static function clear() {
		delete_site_transient( self::CACHE );
	}

	/** Σύνδεσμοι κάτω από το πρόσθετο στη λίστα «Πρόσθετα». */
	public static function row_meta( $links, $file ) {
		if ( self::basename() === $file ) {
			$links[] = '<a href="' . esc_url( self::REPO ) . '" target="_blank" rel="noopener noreferrer">GitHub</a>';
			if ( ! self::enabled() ) {
				$links[] = '<span>Ο έλεγχος ενημερώσεων είναι απενεργοποιημένος</span>';
			}
		}
		return $links;
	}
}
