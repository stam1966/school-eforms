<?php
/**
 * Βιβλιοθήκη εντύπων. Κάθε έντυπο είναι φάκελος στο forms/<id>/ με:
 *   form.php      → επιστρέφει πίνακα με τα στοιχεία του εντύπου
 *   template.php  → το «χαρτί» Α4 με τα πεδία συμπλήρωσης
 * Άλλο plugin/θέμα μπορεί να προσθέσει έντυπα με το φίλτρο `sef_forms`.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SEF_Forms {
	private static $forms = null;

	/** Παλιά αναγνωριστικά της 0.1.x που υποστηρίζονται ακόμη στο shortcode. */
	/** Ενσωματωμένα έντυπα που καταργήθηκαν· αγνοούνται αν ο φάκελός τους έμεινε από παλιά έκδοση. */
	const REMOVED = array( 'sick_leave' );

	const ALIASES = array(
		'parent'      => 'parent_request',
		'leave'       => 'teacher_leave',
		'sick_leave'  => 'teacher_leave', // 0.1.x–0.3.3: η αναρρωτική είναι πλέον είδος της ενιαίας αίτησης
		'declaration' => 'gov_declaration',
	);

	/**
	 * Μορφές εντύπου:
	 *   sheet    → ηλεκτρονική συμπλήρωση πάνω στο υπόδειγμα (+ κενό έντυπο για εκτύπωση)
	 *   external → έκδοση σε υπηρεσία gov.gr και αποστολή στο σχολείο (π.χ. υπεύθυνη δήλωση)
	 *   link     → απλή παραπομπή σε υπηρεσία (π.χ. βεβαίωση φοίτησης)
	 *   download → μόνο για εκτύπωση: αρχείο PDF/Word από τα Πολυμέσα
	 */
	public static function kinds() {
		return array(
			'sheet'    => 'Ηλεκτρονικό έντυπο',
			'external' => 'Έκδοση στο gov.gr και αποστολή',
			'link'     => 'Παραπομπή σε υπηρεσία',
			'download' => 'Μόνο για εκτύπωση',
		);
	}

	public static function categories() {
		return array(
			'parents'      => array( 'title' => 'Γονείς & κηδεμόνες', 'desc' => 'Αιτήσεις και δηλώσεις προς το σχολείο' ),
			'students'     => array( 'title' => 'Μαθητές', 'desc' => 'Έντυπα και υπηρεσίες για μαθητές' ),
			'teachers'     => array( 'title' => 'Εκπαιδευτικοί', 'desc' => 'Άδειες και υπηρεσιακές αιτήσεις' ),
			'declarations' => array( 'title' => 'Υπεύθυνες δηλώσεις', 'desc' => 'Έκδοση απευθείας από το gov.gr' ),
		);
	}

	/**
	 * Ενότητες της κεντρικής σελίδας (0.7.0). Κάθε ενότητα ανοίγει σε δική της οθόνη (#sef/<id>)
	 * με τα έντυπα των κατηγοριών της και τις «Χρήσιμες υπηρεσίες» με «Για» = for.
	 * Οι Υπεύθυνες δηλώσεις και οι υπηρεσίες «για όλους» μένουν στην κεντρική οθόνη.
	 */
	public static function sections() {
		return array(
			'parents'  => array(
				'title' => 'Γονείς & μαθητές',
				'desc'  => 'Αιτήσεις και δηλώσεις προς το σχολείο, εγγραφές και υπηρεσίες για μαθητές',
				'cats'  => array( 'parents', 'students' ),
				'for'   => 'parents',
				'back'  => 'Γονείς & μαθητές',
			),
			'teachers' => array(
				'title' => 'Εκπαιδευτικοί',
				'desc'  => 'Άδειες, υπηρεσιακές αιτήσεις και πλατφόρμες για εκπαιδευτικούς',
				'cats'  => array( 'teachers' ),
				'for'   => 'teachers',
				'back'  => 'Εκπαιδευτικοί',
			),
		);
	}

	/** Η ενότητα στην οποία ανήκει μια κατηγορία (ή '' για όσα μένουν στην κεντρική οθόνη). */
	public static function section_of( $category ) {
		foreach ( self::sections() as $sid => $sec ) {
			if ( in_array( $category, $sec['cats'], true ) ) {
				return $sid;
			}
		}
		return '';
	}

	/** Ταιριάζει το έντυπο στο σχολείο (βαθμίδα + τύπος, ή έντυπο Μουσικού σε Δημοτικό). */
	public static function fits( $def ) {
		if ( SEF_Settings::level_matches( $def['levels'] ) && SEF_Settings::kind_matches( $def['school_kinds'] ) ) {
			return true;
		}
		return self::to_music( $def );
	}

	/**
	 * Έντυπο του Μουσικού σε ιστοσελίδα Δημοτικού (0.9.1): απευθύνεται στο Μουσικό σχολείο της περιοχής
	 * και ο γονέας το στέλνει εκεί με email ή το τυπώνει — ποτέ αποστολή από την ιστοσελίδα (θα πήγαινε στο Δημοτικό).
	 */
	public static function to_music( $def ) {
		return ! empty( $def['primary_to_music'] ) && 'primary' === SEF_Settings::level()
			&& ! ( SEF_Settings::level_matches( $def['levels'] ) && SEF_Settings::kind_matches( $def['school_kinds'] ) )
			&& '' !== trim( (string) SEF_Settings::get()['music_school_name'] );
	}

	public static function modes() {
		return array(
			'auto'   => 'Αυτόματα (ακολουθεί τη γενική ρύθμιση)',
			'email'  => 'Βεβαίωση gov.gr → ο χρήστης στέλνει email',
			'upload' => 'Βεβαίωση gov.gr → αποστολή από την ιστοσελίδα',
			'print'  => 'Εκτύπωση, ιδιόχειρη υπογραφή, κατάθεση',
		);
	}

	/** Φάκελος πρόσθετων/προσαρμοσμένων εντύπων — εκτός plugin, ώστε να μη σβήνεται στις ενημερώσεις. */
	public static function custom_dir() {
		return trailingslashit( apply_filters( 'sef_custom_forms_dir', WP_CONTENT_DIR . '/school-eforms/forms' ) );
	}

	public static function custom_url() {
		return trailingslashit( apply_filters( 'sef_custom_forms_url', content_url( 'school-eforms/forms' ) ) );
	}

	private static function load_dir( $dir, $url, $source ) {
		$out = array();
		foreach ( (array) glob( $dir . '*/form.php' ) as $file ) {
			$def = include $file;
			if ( ! is_array( $def ) || empty( $def['id'] ) ) {
				continue;
			}
			$def['id']     = sanitize_key( $def['id'] );
			if ( 'builtin' === $source && in_array( $def['id'], self::REMOVED, true ) ) {
				continue; // φάκελος που έμεινε από παλιά έκδοση (π.χ. αντιγραφή πάνω από την υπάρχουσα στο WAMP)
			}
			$def['dir']    = dirname( $file );
			$def['url']    = $url . basename( dirname( $file ) ) . '/';
			$def['source'] = $source;
			$out[ $def['id'] ] = $def;
		}
		return $out;
	}

	public static function all() {
		if ( null !== self::$forms ) {
			return self::$forms;
		}
		$forms = self::load_dir( SEF_DIR . 'forms/', SEF_URL . 'forms/', 'builtin' );
		$dir   = self::custom_dir();
		if ( is_dir( $dir ) ) {
			foreach ( self::load_dir( $dir, self::custom_url(), 'custom' ) as $id => $def ) {
				if ( isset( $forms[ $id ] ) ) {
					$def['source'] = 'override'; // ίδιο id → αντικαθιστά το ενσωματωμένο
					$base          = self::read_base( $def['dir'] );
					$def['base_version'] = $base ? $base['version'] : '';
					// Παλιό αντίγραφο: το ενσωματωμένο άλλαξε μετά την αντιγραφή.
					$def['outdated'] = $base && $base['hash'] !== self::builtin_hash( $id );
					// Σύγκρουση: πρόσθετο έντυπο με κωδικό που απέκτησε (νέο) ενσωματωμένο.
					$def['conflict'] = ! $base;
					$def           = array_merge( $forms[ $id ], $def );
				} elseif ( ! isset( $def['order'] ) ) {
					$def['order'] = 300;
				}
				if ( ! isset( $def['default_enabled'] ) && 'custom' === $def['source'] ) {
					$def['default_enabled'] = false;
				}
				$forms[ $id ] = $def;
			}
		}
		// Απευθείας από τη βάση (όχι SEF_Settings::get(), για να μη γίνει αναδρομή στη μετάπτωση).
		$raw  = get_option( SEF_Settings::OPTION, array() );
		$docs = is_array( $raw ) && ! empty( $raw['docs'] ) && is_array( $raw['docs'] ) ? $raw['docs'] : array();
		foreach ( $docs as $i => $doc ) {
			if ( ! is_array( $doc ) || empty( $doc['id'] ) || empty( $doc['title'] ) ) {
				continue;
			}
			$doc = array_merge( array( 'desc' => '', 'group' => 'parents', 'level' => 'all', 'kind' => 'download', 'url' => '', 'files' => array(), 'enabled' => '1' ), $doc );
			$forms[ $doc['id'] ] = array(
				'id'              => $doc['id'],
				'label'           => $doc['title'],
				'desc'            => $doc['desc'],
				'category'        => $doc['group'],
				'levels'          => array( $doc['level'] ),
				'kind'            => $doc['kind'],
				'icon'            => 'link' === $doc['kind'] ? 'link' : 'print',
				'order'           => 400 + $i,
				'default_enabled' => '1' === $doc['enabled'],
				'link_url'        => $doc['url'],
				'files'           => $doc['files'],
				'source'          => 'admin',
			);
		}
		foreach ( $forms as $id => $def ) {
			$forms[ $id ] = self::normalize( $def );
		}
		$forms = apply_filters( 'sef_forms', $forms );
		uasort(
			$forms,
			function ( $a, $b ) {
				return $a['order'] <=> $b['order'];
			}
		);
		self::$forms = $forms;
		return $forms;
	}

	public static function flush() {
		self::$forms = null;
	}

	/** Αποτύπωμα των αρχείων ενός ενσωματωμένου εντύπου (αλλάζει όταν αλλάξει το έντυπο). */
	public static function builtin_hash( $id ) {
		$dir = SEF_DIR . 'forms/' . sanitize_key( $id ) . '/';
		$h   = '';
		foreach ( array( 'form.php', 'template.php' ) as $f ) {
			$h .= is_readable( $dir . $f ) ? md5_file( $dir . $f ) : '';
		}
		return md5( $h );
	}

	private static function read_base( $dir ) {
		$f = $dir . '/sef-base.json';
		if ( ! is_readable( $f ) ) {
			return null;
		}
		$j = json_decode( (string) file_get_contents( $f ), true ); // phpcs:ignore
		return is_array( $j ) && isset( $j['hash'], $j['version'] ) ? $j : null;
	}

	/** Φάκελος αρχείου (αρχειοθετημένα προσαρμοσμένα έντυπα· δεν φορτώνονται). */
	public static function archive_dir() {
		return trailingslashit( dirname( untrailingslashit( self::custom_dir() ) ) ) . 'archive/';
	}

	/** Προσαρμοσμένα έντυπα που χρειάζονται προσοχή μετά από αναβάθμιση. */
	public static function attention() {
		$out = array();
		foreach ( self::all() as $id => $f ) {
			if ( 'override' === $f['source'] && ( ! empty( $f['outdated'] ) || ! empty( $f['conflict'] ) ) ) {
				$out[ $id ] = $f;
			}
		}
		return $out;
	}

	/**
	 * Επαναφορά στο ενσωματωμένο: ο φάκελος του προσαρμοσμένου ΔΕΝ διαγράφεται,
	 * μετακινείται στο archive/ (με ημερομηνία), ώστε να μη χαθεί η δουλειά.
	 * $id = 'all' → όλα τα προσαρμοσμένα (όχι τα νέα πρόσθετα έντυπα).
	 */
	public static function restore_builtin( $id ) {
		$ids = 'all' === $id ? array_keys( array_filter( self::all(), function ( $f ) {
			return 'override' === $f['source'];
		} ) ) : array( sanitize_key( $id ) );
		$moved = array();
		foreach ( $ids as $one ) {
			$src = self::custom_dir() . $one;
			if ( ! is_dir( SEF_DIR . 'forms/' . $one ) || ! is_dir( $src ) ) {
				continue;
			}
			if ( ! wp_mkdir_p( self::archive_dir() ) ) {
				return new WP_Error( 'sef_mkdir', 'Δεν ήταν δυνατή η δημιουργία του φακέλου αρχείου.' );
			}
			$dst = self::archive_dir() . $one . '-' . wp_date( 'Ymd-His' );
			if ( ! @rename( $src, $dst ) ) { // phpcs:ignore
				return new WP_Error( 'sef_move', 'Η μετακίνηση του φακέλου «' . $one . '» απέτυχε (δικαιώματα αρχείων).' );
			}
			$moved[] = $one;
		}
		if ( ! file_exists( self::archive_dir() . 'index.php' ) && is_dir( self::archive_dir() ) ) {
			file_put_contents( self::archive_dir() . 'index.php', "<?php // Silence is golden.\n" ); // phpcs:ignore
		}
		self::flush();
		return $moved;
	}

	/**
	 * Αντιγράφει ένα ενσωματωμένο έντυπο (ή το παράδειγμα) στον φάκελο
	 * πρόσθετων εντύπων, για επεξεργασία. Επιστρέφει τη διαδρομή ή WP_Error.
	 */
	public static function copy_to_custom( $id ) {
		$id = sanitize_key( $id );
		if ( 'example' === $id ) {
			$src  = SEF_DIR . 'examples/sx_paradeigma';
			$name = 'sx_paradeigma';
		} else {
			$src  = SEF_DIR . 'forms/' . $id;
			$name = $id;
		}
		if ( ! is_dir( $src ) ) {
			return new WP_Error( 'sef_src', 'Δεν βρέθηκε το έντυπο προέλευσης.' );
		}
		$dst = self::custom_dir() . $name;
		if ( file_exists( $dst ) ) {
			return new WP_Error( 'sef_exists', 'Υπάρχει ήδη φάκελος «' . $name . '» στα πρόσθετα έντυπα. Δεν έγινε αντικατάσταση.' );
		}
		if ( ! wp_mkdir_p( $dst ) ) {
			return new WP_Error( 'sef_mkdir', 'Δεν ήταν δυνατή η δημιουργία φακέλου (δικαιώματα εγγραφής στο wp-content).' );
		}
		// Ο φάκελος δεν πρέπει να εμφανίζει λίστα αρχείων.
		$root = self::custom_dir();
		if ( ! file_exists( $root . 'index.php' ) ) {
			file_put_contents( $root . 'index.php', "<?php // Silence is golden.\n" ); // phpcs:ignore
		}
		foreach ( (array) glob( $src . '/*' ) as $file ) {
			if ( is_file( $file ) && ! copy( $file, $dst . '/' . basename( $file ) ) ) {
				return new WP_Error( 'sef_copy', 'Η αντιγραφή απέτυχε.' );
			}
		}
		if ( 'example' !== $id ) {
			// Σημείωση προέλευσης: από ποια έκδοση αντιγράφηκε, για ειδοποίηση σε αναβάθμιση.
			file_put_contents( // phpcs:ignore
				$dst . '/sef-base.json',
				wp_json_encode(
					array(
						'based_on' => $id,
						'version'  => SEF_VERSION,
						'hash'     => self::builtin_hash( $id ),
						'copied'   => wp_date( 'Y-m-d H:i' ),
					),
					JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
				)
			);
		}
		self::flush();
		return $dst;
	}

	private static function normalize( $def ) {
		$def = array_merge(
			array(
				'label'           => $def['id'],
				'desc'            => '',
				'detail'          => '',
				'category'        => 'parents',
				'levels'          => array( 'all' ),
				'school_kinds'    => array(),     // κενό = όλοι οι τύποι σχολείου
				'primary_to_music' => false,      // και στο Δημοτικό, με αποδέκτη το Μουσικό σχολείο της περιοχής
				'kind'            => 'sheet',
				'stamp'           => false,
				'health_by'       => '',          // πεδίο επιλογής: είδος με data-health=1 → αποστολή μόνο με email       // αίτηση: κενό ~2 εκ. επάνω για τη σφραγίδα πρωτοκόλλου
				'gov'             => 'required',  // required | optional | none
				'link_url'        => '',
				'files'           => array(),
				'icon'            => 'doc',
				'minutes'         => 5,
				'order'           => 50,
				'season'          => array( '', '' ), // προεπιλεγμένη περίοδος εμφάνισης «ηη/μμ»
				'default_enabled' => true,
				'modes'           => array( 'email', 'upload', 'print' ),
				'default_mode'    => 'email',
				'subject'         => '',
				'filename'        => 'Έντυπο',
				'file_slug'       => 'entypo',
				'doc_title'       => '',
				'hint'            => '',
				'notice'          => '',
				'gov_note'        => '',
				'paper_note'      => '',  // εναλλακτική: κατάθεση αυτοπροσώπως (χωρίς gov.gr)  // ρητή υπενθύμιση gov.gr (βήματα 3–4) και αυστηρός έλεγχος αρχείου
				'send_note'       => '',
				'template'        => '',
				'source'          => 'builtin',
				'url'             => '',
				'outdated'        => false,
				'conflict'        => false,
				'base_version'    => '',
			),
			$def
		);
		if ( '' === $def['doc_title'] ) {
			$def['doc_title'] = $def['label'];
		}
		if ( '' === $def['template'] && ! empty( $def['dir'] ) ) {
			$def['template'] = $def['dir'] . '/template.php';
		}
		if ( 'external' === $def['kind'] ) {
			$def['modes'] = array_values( array_diff( $def['modes'], array( 'print' ) ) );
		}
		if ( in_array( $def['kind'], array( 'link', 'download' ), true ) ) {
			$def['modes'] = array( 'print' ); // δεν υπάρχει βήμα αποστολής
			$def['gov']   = 'none';
		}
		if ( ! in_array( $def['default_mode'], $def['modes'], true ) ) {
			$def['default_mode'] = reset( $def['modes'] );
		}
		return $def;
	}

	public static function get( $id ) {
		$all = self::all();
		$id  = isset( self::ALIASES[ $id ] ) ? self::ALIASES[ $id ] : $id;
		return isset( $all[ $id ] ) ? $all[ $id ] : null;
	}

	public static function resolve_id( $id ) {
		return isset( self::ALIASES[ $id ] ) ? self::ALIASES[ $id ] : $id;
	}

	/** Έντυπα που εμφανίζονται: ενεργά και για τη βαθμίδα του σχολείου. */
	public static function available( $category = '', $only = '' ) {
		$s   = SEF_Settings::get();
		$out = array();
		$only = $only ? self::resolve_id( $only ) : '';
		foreach ( self::all() as $id => $def ) {
			$conf = SEF_Settings::form( $id, $def );
			if ( 'admin' === $def['source'] ) {
				$conf['enabled'] = ! empty( $def['default_enabled'] ); // ενεργοποίηση από τη λίστα «Έγγραφα»
			}
			$fits = self::fits( $def );
			// Έντυπο άλλης βαθμίδας/τύπου: εμφανίζεται μόνο αν ο διαχειριστής το ζητήσει ρητά.
			if ( ! $conf['enabled'] || ( ! $fits && ! $conf['other_level'] ) ) {
				continue;
			}
			// Εποχικό έντυπο εκτός περιόδου εμφάνισης: δεν φαίνεται (ούτε με σύνδεσμο ή shortcode).
			if ( ! SEF_Settings::season_ok( $conf ) ) {
				continue;
			}
			if ( $category && $category !== $def['category'] ) {
				continue;
			}
			if ( $only && $only !== $id ) {
				continue;
			}
			$music = self::to_music( $def );
			if ( $music ) {
				$def['modes'] = array_values( array_intersect( $def['modes'], array( 'email', 'print' ) ) );
				if ( 'upload' === $conf['mode'] || 'auto' === $conf['mode'] ) {
					$conf['mode'] = 'email';
				}
			}
			$eff  = SEF_Settings::effective_mode( $def, $conf['mode'] );
			$mode = $eff['mode'];
			if ( $music ) {
				$memail = SEF_Settings::get()['music_school_email'];
				$mode   = is_email( $memail ) && 'print' !== $conf['mode'] ? 'email' : 'print';
				$def['to_music'] = SEF_Settings::get()['music_school_name'];
				$def['to_email'] = is_email( $memail ) ? $memail : '';
				$def['desc']    .= ' Απευθύνεται στο ' . rtrim( $def['to_music'], '.' ) . '.';
			}
			$def['mode'] = in_array( $def['kind'], array( 'link', 'download' ), true ) ? 'print' : $mode;
			$def['gov']  = 'print' === $def['mode'] || in_array( $def['kind'], array( 'link', 'download' ), true ) ? 'none' : $conf['gov'];
			if ( 'external' === $def['kind'] ) {
				$def['gov'] = 'required'; // η ίδια η υπηρεσία gov.gr εκδίδει το έγγραφο
			}
			$def['file'] = $conf['file'];
			// Εποχικό: έχει περίοδο εμφάνισης (άρα αυτή τη στιγμή είναι μέσα σε αυτήν).
			$def['seasonal'] = '' !== trim( ( $conf['from'] ?? '' ) . ( $conf['to'] ?? '' ) . ( $conf['from2'] ?? '' ) . ( $conf['to2'] ?? '' ) );
			$out[ $id ]  = $def;
		}
		return $out;
	}
}
