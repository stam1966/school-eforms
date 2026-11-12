<?php
/**
 * Ρυθμίσεις: προεπιλογές, ανάγνωση, καθαρισμός και μετάπτωση από 0.1.x.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SEF_Settings {
	const OPTION = 'sef_settings';

	/** Επίσημες σελίδες gov.gr (μπορούν να αλλάξουν από τις ρυθμίσεις). */
	const GOV_CERT_URL = 'https://www.gov.gr/ipiresies/polites-kai-kathemerinoteta/psephiaka-eggrapha-gov-gr/psephiake-bebaiose-eggraphou';
	const GOV_YD_URL   = 'https://www.gov.gr/ipiresies/polites-kai-kathemerinoteta/psephiaka-eggrapha-gov-gr/ekdose-upeuthunes-deloses';

	private static $cache = null;

	public static function defaults() {
		return array(
			'school_level'      => 'primary',   // primary | gymnasio | lykeio
			'school_kind'       => 'general',   // general | music | art | model | evening
			'level_check'       => '',          // '1' = η βαθμίδα μετατράπηκε αυτόματα και πρέπει να ελεγχθεί
			'principal'         => '',
			'sections'          => '',
			'school_name'       => get_bloginfo( 'name' ),
			'school_to'         => '',
			'school_gen'        => '',          // το όνομα σε γενική, π.χ. «του 22ου Δημοτικού Σχολείου Λαμίας»
			'school_city'       => '',
			'school_address'    => '',
			'school_phone'      => '',
			'school_email'      => '',
			'logo_id'           => 0,
			'privacy_url'       => '',
			'theme'             => 'light',
			'accent'            => '#0d5eaf',
			'color_source'      => 'theme',     // theme = χρώμα του θέματος (Flatsome: Secondary) · custom = το «Κύριο χρώμα»
			'gov_cert_url'      => self::GOV_CERT_URL,
			'gov_yd_url'        => self::GOV_YD_URL,
			'upload_enabled'    => '0',
			'max_mb'            => 5,
			'rate_limit'        => 10,
			'trust_proxy'       => '0',
			'sig_policy'        => 'warn',
			'show_links'        => '0',
			'links'             => self::default_links(),
			'links_v'           => 2,          // 0.7.0: «Για» σε κάθε υπηρεσία + υπηρεσίες εκπαιδευτικών
			'home_layout'       => 'sections', // sections = κεντρική σελίδα με ενότητες · list = όλα σε μία λίστα
			'forms_page'        => 0,          // 0.8.0: σελίδα με το [school_eforms] για το [school_eforms_link] (0 = αυτόματα)
			'school_year'       => '',
			'remedial_ops'      => '6063910',
			'remedial_subjects' => "Νεοελληνική Γλώσσα και Γραμματεία\nΑρχαία Ελληνική Γλώσσα και Γραμματεία\nΜαθηματικά\nΦυσική\nΧημεία\nΑγγλικά",
			'forms'             => array(),
			'docs'              => array(),
			'leave_types'       => self::default_leave_types(),
			'local_holidays'    => '',
			'absence_cases'     => self::default_absence_cases(),
			'request_topics'    => self::default_request_topics(),
			// Εποχικά έντυπα (βήμα 3): ζώνες Ολοήμερου και ξένες γλώσσες, μία ανά γραμμή.
			'allday_zones'      => "Πρόωρη υποδοχή (07:00 – 08:00)\nΟλοήμερο έως τις 15:00\nΟλοήμερο έως τις 16:00",
			'languages'         => "Γαλλικά\nΓερμανικά",
			'music_program'     => '',
			'music_school_name'  => '',           // Δημοτικό: το Μουσικό σχολείο της περιοχής (π.χ. «Μουσικό Γυμνάσιο Λαμίας με Λ.Τ.»)
			'music_school_email' => '',           // … και το email του (εκεί στέλνει ο γονέας)            // Μουσικό: όνομα εκπαιδευτικού προγράμματος για μαθητές Δημοτικού
			'music_topics'      => '',            // '1' = τα θέματα αιτήσεων αποθηκεύτηκαν ενώ το σχολείο ήταν Μουσικό
			'meals_time'        => '13.15-13.45',   // παραμονή μετά το πρωινό για το γεύμα (μαθητές εκτός Ολοήμερου)
			// Ώρες αποχώρησης (δήλωση αποχώρησης, Δημοτικό): «ώρα | περιγραφή», μία ανά γραμμή.
			'departure_times'   => "13:15 | μετά τη λήξη του υποχρεωτικού πρωινού προγράμματος\n15:00 | σύμφωνα με τη δηλωμένη ώρα αποχώρησης από το Προαιρετικό Ολοήμερο Πρόγραμμα\n16:00 | μετά τη λήξη του Προαιρετικού Ολοήμερου Προγράμματος",
			'migrated'          => '',
			'modes_v'           => 2,
			'leave_v'           => 5,
		);
	}

	/**
	 * Προεπιλεγμένες «Χρήσιμες υπηρεσίες». level: all | primary | secondary.
	 * Οι διευθύνσεις εγγραφών είναι αυτές που ανακοίνωσε το Υπουργείο για το 2026-2027.
	 */
	public static function default_links() {
		return array(
			// Για όλους (κεντρική σελίδα)
			array( 'title' => 'Υπεύθυνη δήλωση', 'url' => self::GOV_YD_URL, 'desc' => 'Με ελεύθερο κείμενο, απευθείας από το gov.gr.', 'level' => 'all', 'for' => 'all' ),
			array( 'title' => 'Ψηφιακή βεβαίωση εγγράφου', 'url' => self::GOV_CERT_URL, 'desc' => 'Ψηφιακή υπογραφή (γνήσιο υπογραφής) σε PDF.', 'level' => 'all', 'for' => 'all' ),
			array( 'title' => 'e-class - Ηλεκτρονική Σχολική Τάξη', 'url' => 'https://eclass.sch.gr/', 'desc' => 'Τα ψηφιακά μαθήματα και το υλικό των εκπαιδευτικών.', 'level' => 'all', 'for' => 'all' ),
			array( 'title' => 'Lessons', 'url' => 'https://lessons.sch.gr/', 'desc' => 'Προγραμματισμός ζωντανών μαθημάτων με BigBlueButton, με λογαριασμό ΠΣΔ.', 'level' => 'all', 'for' => 'all' ),
			array( 'title' => 'e-me.edu.gr', 'url' => 'https://e-me.edu.gr/', 'desc' => 'Ψηφιακή Εκπαιδευτική Πλατφόρμα e-me για μαθητές, μαθήτριες και εκπαιδευτικούς.', 'level' => 'all', 'for' => 'all' ),
			array( 'title' => 'Διαδραστικά σχολικά βιβλία', 'url' => 'https://evivlio.gov.gr/', 'desc' => 'Τα σχολικά βιβλία σε ψηφιακή μορφή.', 'level' => 'all', 'for' => 'all' ),
			// Γονείς & μαθητές
			array( 'title' => 'Εγγραφή στο Νηπιαγωγείο', 'url' => 'https://proti-eggrafi.services.gov.gr', 'desc' => 'Ηλεκτρονική εγγραφή με κωδικούς Taxisnet (κάθε Μάρτιο).', 'level' => 'primary', 'for' => 'parents' ),
			array( 'title' => 'Εγγραφή στην Α΄ Δημοτικού', 'url' => 'https://adimotikou.eservices.minedu.gov.gr', 'desc' => 'Ηλεκτρονική εγγραφή με κωδικούς Taxisnet (κάθε Μάρτιο).', 'level' => 'primary', 'for' => 'parents' ),
			array( 'title' => 'e-Parents', 'url' => 'https://eschools.minedu.gov.gr/login', 'desc' => 'Απουσίες και βαθμολογία του παιδιού σας, με κωδικούς Taxisnet.', 'level' => 'secondary', 'for' => 'parents' ),
			array( 'title' => 'e-Εγγραφές σε Λύκειο (ΓΕΛ / ΕΠΑΛ)', 'url' => 'https://e-eggrafes.minedu.gov.gr', 'desc' => 'Εγγραφή, ανανέωση εγγραφής και μετεγγραφή σε ΓΕΛ και ΕΠΑΛ.', 'level' => 'secondary', 'for' => 'parents' ),
			array( 'title' => 'Ψηφιακό Φροντιστήριο', 'url' => 'https://streaming.digitalschool.gov.gr/', 'desc' => 'Δωρεάν βιντεομαθήματα για μαθητές.', 'level' => 'secondary', 'for' => 'parents' ),
			array( 'title' => 'Βεβαίωση φοίτησης', 'url' => 'https://www.gov.gr/ipiresies/ekpaideuse/eggraphe-se-skholeio/attending-school', 'desc' => 'Την εκδίδετε μόνοι σας, χωρίς αίτηση στο σχολείο.', 'level' => 'all', 'for' => 'parents' ),
			// Εκπαιδευτικοί
			array( 'title' => 'ΟΠΣΥΔ', 'url' => 'https://opsyd.sch.gr/', 'desc' => 'Πληροφοριακό σύστημα του Υπουργείου Παιδείας όπου τηρείται ο ατομικός φάκελος κάθε υποψήφιου και υπηρετούντος εκπαιδευτικού.', 'level' => 'all', 'for' => 'teachers' ),
			array( 'title' => 'teachers.minedu.gov.gr', 'url' => 'https://teachers.minedu.gov.gr/', 'desc' => 'Online Σύστημα Διαχείρισης Μητρώου Εκπαιδευτικών και Υποβολής Αιτήσεων Μετάθεσης του Υπουργείου Παιδείας.', 'level' => 'all', 'for' => 'teachers' ),
			array( 'title' => 'Webex ΠΣΔ', 'url' => 'https://webex.sch.gr/', 'desc' => 'Σύνδεση στις ψηφιακές τάξεις Webex με λογαριασμό ΠΣΔ.', 'level' => 'all', 'for' => 'teachers' ),
		);
	}

	/** «Για ποιους» είναι μια υπηρεσία: σε ποια ενότητα της κεντρικής σελίδας εμφανίζεται. */
	public static function audiences() {
		return array(
			'all'      => 'Όλους (κεντρική σελίδα)',
			'parents'  => 'Γονείς & μαθητές',
			'teachers' => 'Εκπαιδευτικούς',
		);
	}

	/** Η λίστα υπηρεσιών της 0.2.1–0.6.x (χωρίς «Για»)· αν έμεινε αμετάβλητη, αντικαθίσταται με τη νέα. */
	private static function links_06_urls() {
		return array( 'https://proti-eggrafi.services.gov.gr', 'https://adimotikou.eservices.minedu.gov.gr', 'https://eschools.minedu.gov.gr/login', 'https://e-eggrafes.minedu.gov.gr', 'https://streaming.digitalschool.gov.gr/', 'https://www.gov.gr/ipiresies/ekpaideuse/eggraphe-se-skholeio/attending-school', self::GOV_YD_URL, self::GOV_CERT_URL, 'https://eclass.sch.gr/', 'https://evivlio.gov.gr/' );
	}

	/** Εκτίμηση «Για» για υπηρεσία χωρίς αυτό το πεδίο (λίστες που άλλαξε το σχολείο πριν την 0.7.0). */
	private static function guess_audience( $url ) {
		foreach ( self::default_links() as $d ) {
			if ( untrailingslashit( strtolower( $d['url'] ) ) === untrailingslashit( strtolower( (string) $url ) ) ) {
				return $d['for'];
			}
		}
		return 'all';
	}

	/**
	 * Είδη άδειας εκπαιδευτικού που χορηγεί ο/η Διευθυντής/ντρια (μόνο ονόματα, χωρίς διάρκειες).
	 * label: όπως μπαίνει στη φράση «Παρακαλώ να μου χορηγήσετε …» (αιτιατική)
	 * gen:   για το ΘΕΜΑ «Χορήγηση …» και το θέμα email «Αίτηση …» (γενική)
	 * health: η αίτηση γράφει σταθερά «για λόγους υγείας», χωρίς πεδίο παρατηρήσεων
	 * describe: υποχρεωτική περιγραφή στις Παρατηρήσεις
	 * docs: ονόματα δικαιολογητικών (ένα ανά γραμμή) — ποτέ πεδίο διάγνωσης
	 */
	public static function default_leave_types() {
		// Οι άδειες που χορηγεί ο/η Διευθυντής/ντρια (Δ/νση Π.Ε. Φθιώτιδας, 11-09-2025), με τη σειρά της.
		// Σημαίες: r = «για τους παρακάτω λόγους» (προαιρετικό κείμενο), h = «για λόγους υγείας» (χωρίς κείμενο),
		// d = υποχρεωτική περιγραφή. Χωρίς ένδειξη: ούτε λόγοι ούτε περιγραφή, όπως στα υποδείγματα.
		$t = array(
			array( 'regular', 'κανονική άδεια', 'κανονικής άδειας', 'r' ),
			array( 'sick', 'αναρρωτική άδεια με ιατρική βεβαίωση', 'αναρρωτικής άδειας', 'h', 'Ιατρική βεβαίωση' ),
			array( 'sick_decl', 'αναρρωτική άδεια με υπεύθυνη δήλωση', 'αναρρωτικής άδειας', 'h', 'Υπεύθυνη δήλωση' ),
			array( 'child_sick', 'άδεια ασθένειας τέκνου', 'άδειας ασθένειας τέκνου' ),
			array( 'exams', 'άδεια εξετάσεων / φοιτητική', 'άδειας εξετάσεων' ),
			array( 'child_school', 'άδεια παρακολούθησης σχολικής επίδοσης τέκνου', 'άδειας παρακολούθησης σχολικής επίδοσης τέκνου' ),
			array( 'blood', 'ειδική άδεια αιμοδοσίας / αιμοληψίας / λήψης αιμοπεταλίων', 'αιμοδοτικής άδειας', '', 'Βεβαίωση νοσηλευτικού ιδρύματος / συλλόγου' ),
			array( 'trial', 'ειδική άδεια για συμμετοχή σε δίκη', 'ειδικής άδειας για συμμετοχή σε δίκη' ),
			array( 'paternity', 'ειδική άδεια πατρότητας', 'ειδικής άδειας πατρότητας' ),
			array( 'marriage', 'ειδική άδεια γάμου', 'ειδικής άδειας γάμου' ),
			array( 'bereavement', 'ειδική άδεια θανάτου (συζύγου ή συγγενούς)', 'ειδικής άδειας (θανάτου)', '', "Ληξιαρχική πράξη θανάτου\nΥπεύθυνη δήλωση" ),
			array( 'gyn', 'άδεια για γυναικολογικό έλεγχο', 'άδειας ετήσιου γυναικολογικού ελέγχου', '', 'Ιατρική γνωμάτευση' ),
			array( 'serious_illness', 'ειδική άδεια νοσήματος', 'ειδικής άδειας νοσήματος', 'h' ),
			array( 'disability', 'ειδική άδεια αναπηρίας', 'ειδικής άδειας αναπηρίας' ),
			array( 'other', 'άλλη άδεια (βλ. παρακάτω)', 'άδειας', 'd' ),
		);
		$out = array();
		foreach ( $t as $r ) {
			$f     = isset( $r[3] ) ? $r[3] : '';
			$out[] = array(
				'id'       => $r[0],
				'label'    => $r[1],
				'gen'      => $r[2],
				'health'   => false !== strpos( $f, 'h' ) ? '1' : '0',
				'reasons'  => false !== strpos( $f, 'r' ) ? '1' : '0',
				'describe' => false !== strpos( $f, 'd' ) ? '1' : '0',
				'docs'     => isset( $r[4] ) ? $r[4] : '',
				'enabled'  => '1',
			);
		}
		return $out;
	}

	/**
	 * Περιπτώσεις δικαιολόγησης απουσιών (ενιαία πρόταση, Γ2). label: στη λίστα της οθόνης·
	 * text: η φράση που τυπώνεται στη δήλωση (χωρίς διάγνωση)· level: all | primary | secondary | gymnasio | lykeio.
	 */
	public static function default_absence_cases() {
		$jn = 'και παρακαλώ να μην προσμετρηθούν οι απουσίες του/της για τις ημέρες αυτές, σύμφωνα με ';
		$t  = array(
			array( 'parent', 'Με υπεύθυνη δήλωση γονέα/κηδεμόνα', 'και παρακαλώ να δικαιολογηθούν οι απουσίες του/της για τις ημέρες αυτές με την παρούσα υπεύθυνη δήλωση.', 'all', '' ),
			array( 'doctor', 'Με ιατρική βεβαίωση / γνωμάτευση', 'και παρακαλώ να δικαιολογηθούν οι απουσίες του/της για τις ημέρες αυτές με την ιατρική βεβαίωση που επισυνάπτεται.', 'all', 'Ιατρική βεβαίωση' ),
			array( 'nc_infection', 'Μη προσμέτρηση – λοίμωξη / γρίπη', $jn . 'την ιατρική βεβαίωση που επισυνάπτεται.', 'secondary', 'Ιατρική βεβαίωση' ),
			array( 'nc_therapy', 'Μη προσμέτρηση – συνεχιζόμενη θεραπεία', $jn . 'την ιατρική γνωμάτευση που επισυνάπτεται.', 'secondary', 'Ιατρική γνωμάτευση' ),
			array( 'nc_hospital', 'Μη προσμέτρηση – γνωμάτευση νοσοκομείου ή Κέντρου Υγείας', $jn . 'τη γνωμάτευση νοσοκομείου / Κέντρου Υγείας που επισυνάπτεται.', 'secondary', 'Γνωμάτευση νοσοκομείου / Κέντρου Υγείας' ),
			array( 'nc_kedasy', 'Μη προσμέτρηση – επίσκεψη στο ΚΕ.Δ.Α.Σ.Υ.', $jn . 'τη βεβαίωση του ΚΕ.Δ.Α.Σ.Υ. που επισυνάπτεται.', 'secondary', 'Βεβαίωση ΚΕ.Δ.Α.Σ.Υ.' ),
			array( 'nc_exams', 'Μη προσμέτρηση – συμμετοχή σε εξετάσεις', $jn . 'τη βεβαίωση συμμετοχής που επισυνάπτεται.', 'secondary', 'Βεβαίωση συμμετοχής' ),
		);
		$out = array();
		foreach ( $t as $r ) {
			$out[] = array( 'id' => $r[0], 'label' => $r[1], 'text' => $r[2], 'level' => $r[3], 'docs' => $r[4], 'enabled' => '1' );
		}
		return $out;
	}

	/**
	 * Έτοιμα θέματα της «Αίτησης προς το σχολείο» (βήμα 5): η επιλογή συμπληρώνει ΘΕΜΑ και κείμενο,
	 * που ο γονέας μπορεί μετά να διορθώσει. «……» = σημείο που συμπληρώνει ο γονέας.
	 */
	public static function default_request_topics() {
		$t = array(
			array( 'transfer', 'Μετεγγραφή', 'Μετεγγραφή μαθητή/μαθήτριας', 'να γίνει η μετεγγραφή του παιδιού μου στο …………………………………… (σχολείο), λόγω ……………………………………………… .' ),
			array( 'certificate', 'Πιστοποιητικό σπουδών / αντίγραφο τίτλου', 'Χορήγηση πιστοποιητικού σπουδών / αντιγράφου τίτλου σπουδών', 'να μου χορηγήσετε πιστοποιητικό σπουδών / αντίγραφο τίτλου σπουδών του παιδιού μου, για να το προσκομίσω ………………………………………… .' ),
			array( 'visit', 'Βεβαίωση επίσκεψης στο σχολείο', 'Χορήγηση βεβαίωσης επίσκεψης', 'να μου χορηγήσετε βεβαίωση ότι επισκέφθηκα το σχολείο την ……/……/………… για ενημέρωση σχετικά με την πρόοδο του παιδιού μου, για να την προσκομίσω στην υπηρεσία μου.' ),
			array( 'citizenship', 'Βεβαίωση φοίτησης για ιθαγένεια', 'Χορήγηση βεβαίωσης φοίτησης για την απόκτηση ιθαγένειας', 'να μου χορηγήσετε βεβαίωση φοίτησης του παιδιού μου, για να την προσκομίσω στην αρμόδια υπηρεσία για την απόκτηση της ελληνικής ιθαγένειας.' ),
		);
		$out = array();
		foreach ( $t as $r ) {
			$out[] = array( 'id' => $r[0], 'label' => $r[1], 'subject' => $r[2], 'text' => $r[3], 'enabled' => '1' );
		}
		return $out;
	}

	/** Θέματα που προστίθενται όταν το σχολείο είναι Μουσικό (0.9.0). */
	public static function music_request_topics() {
		return array(
			array(
				'id'      => 'music_transfer',
				'label'   => 'Μετεγγραφή λόγω εισαγωγής στο Μουσικό Σχολείο',
				'subject' => 'Αίτηση μετεγγραφής',
				'text'    => 'να εγκρίνετε τη μετεγγραφή του/της …………………………………………… από το ………… Γυμνάσιο ……………………………, λόγω εισαγωγής στο Μουσικό Σχολείο.',
				'enabled' => '1',
			),
		);
	}

	public static function request_topics( $all = false ) {
		$out = array();
		foreach ( (array) self::get()['request_topics'] as $t ) {
			if ( is_array( $t ) && ! empty( $t['id'] ) && ( $all || '1' === $t['enabled'] ) ) {
				$out[ $t['id'] ] = $t;
			}
		}
		return $out;
	}

	private static function sanitize_request_topics( $rows ) {
		$out  = array();
		$seen = array();
		foreach ( (array) $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$label = isset( $row['label'] ) ? mb_substr( sanitize_text_field( $row['label'] ), 0, 120 ) : '';
			if ( '' === $label ) {
				continue;
			}
			$id = isset( $row['id'] ) ? sanitize_key( $row['id'] ) : '';
			if ( '' === $id || isset( $seen[ $id ] ) ) {
				$id = 'r' . substr( md5( $label . count( $out ) ), 0, 8 );
			}
			$seen[ $id ] = true;
			$out[]       = array(
				'id'      => $id,
				'label'   => $label,
				'subject' => isset( $row['subject'] ) ? mb_substr( sanitize_text_field( $row['subject'] ), 0, 200 ) : '',
				'text'    => isset( $row['text'] ) ? mb_substr( sanitize_textarea_field( $row['text'] ), 0, 1500 ) : '',
				'enabled' => ! empty( $row['enabled'] ) ? '1' : '0',
			);
		}
		return $out;
	}

	/** Ενεργές περιπτώσεις απουσιών για τη βαθμίδα του σχολείου. */
	public static function absence_cases( $all = false ) {
		$out = array();
		foreach ( (array) self::get()['absence_cases'] as $c ) {
			if ( is_array( $c ) && ! empty( $c['id'] ) && ( $all || ( '1' === $c['enabled'] && self::level_matches( array( $c['level'] ) ) ) ) ) {
				$out[ $c['id'] ] = $c;
			}
		}
		return $out;
	}

	private static function sanitize_absence_cases( $rows ) {
		$out  = array();
		$seen = array();
		foreach ( (array) $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$label = isset( $row['label'] ) ? mb_substr( sanitize_text_field( $row['label'] ), 0, 120 ) : '';
			$text  = isset( $row['text'] ) ? mb_substr( sanitize_text_field( $row['text'] ), 0, 400 ) : '';
			if ( '' === $label ) {
				continue;
			}
			$id = isset( $row['id'] ) ? sanitize_key( $row['id'] ) : '';
			if ( '' === $id || isset( $seen[ $id ] ) ) {
				$id = 'c' . substr( md5( $label . count( $out ) ), 0, 8 );
			}
			$seen[ $id ] = true;
			$docs        = array();
			foreach ( preg_split( '/\r\n|\r|\n/', isset( $row['docs'] ) ? (string) $row['docs'] : '' ) as $d ) {
				$d = mb_substr( sanitize_text_field( $d ), 0, 80 );
				if ( '' !== $d ) {
					$docs[] = $d;
				}
			}
			$out[] = array(
				'id'      => $id,
				'label'   => $label,
				'text'    => '' !== $text ? $text : 'και παρακαλώ να δικαιολογηθούν οι απουσίες του/της για τις ημέρες αυτές.',
				'level'   => isset( $row['level'] ) && array_key_exists( $row['level'], self::levels() ) ? $row['level'] : 'all',
				'docs'    => implode( "\n", array_slice( $docs, 0, 6 ) ),
				'enabled' => ! empty( $row['enabled'] ) ? '1' : '0',
			);
		}
		return $out;
	}

	/** Η προεπιλεγμένη λίστα των 0.3.3–0.3.4 (για να αντικατασταθεί, αν δεν άλλαξε). */
	private static function is_old_default_leave_list( $rows ) {
		$old = array( 'regular', 'sick', 'hours', 'child_school', 'child_sick', 'exams', 'marriage', 'bereavement', 'paternity', 'mar', 'serious_illness', 'disability', 'guardian', 'blood', 'gyn', 'parental', 'special', 'other' );
		$ids = array();
		foreach ( (array) $rows as $r ) {
			if ( ! is_array( $r ) || '1' !== ( $r['enabled'] ?? '1' ) ) {
				return false;
			}
			$ids[] = $r['id'] ?? '';
			if ( 'sick' === ( $r['id'] ?? '' ) && "Υπεύθυνη δήλωση\nΙατρική βεβαίωση" !== ( $r['docs'] ?? '' ) ) {
				return false;
			}
			if ( 'sick' !== ( $r['id'] ?? '' ) && '' !== ( $r['docs'] ?? '' ) ) {
				return false;
			}
		}
		return $ids === $old;
	}

	/**
	 * Πρόταση για το όνομα σε γενική, από την επωνυμία: «22ο Δημοτικό Σχολείο Λαμίας» →
	 * «του 22ου Δημοτικού Σχολείου Λαμίας». Μόνο πρόταση στη διαχείριση· ο διαχειριστής την ελέγχει.
	 */
	public static function suggest_gen( $name ) {
		$name = trim( (string) $name );
		if ( '' === $name ) {
			return '';
		}
		$map = array(
			'Σχολείο' => 'Σχολείου', 'Δημοτικό' => 'Δημοτικού', 'Γυμνάσιο' => 'Γυμνασίου', 'Λύκειο' => 'Λυκείου',
			'Νηπιαγωγείο' => 'Νηπιαγωγείου', 'Γενικό' => 'Γενικού', 'Εσπερινό' => 'Εσπερινού', 'Μουσικό' => 'Μουσικού',
			'Καλλιτεχνικό' => 'Καλλιτεχνικού', 'Πρότυπο' => 'Προτύπου', 'Πειραματικό' => 'Πειραματικού', 'Ολοήμερο' => 'Ολοήμερου',
			'Ειδικό' => 'Ειδικού', 'Επαγγελματικό' => 'Επαγγελματικού', 'Εκκλησιαστικό' => 'Εκκλησιαστικού', 'Ενιαίο' => 'Ενιαίου',
			'ΓΕΛ' => 'ΓΕΛ', 'ΕΠΑΛ' => 'ΕΠΑΛ',
		);
		$out = array();
		foreach ( preg_split( '/\s+/u', $name ) as $w ) {
			if ( preg_match( '/^(\d+)(ο|ου)$/u', $w, $m ) ) {
				$out[] = $m[1] . 'ου';
			} elseif ( isset( $map[ $w ] ) ) {
				$out[] = $map[ $w ];
			} elseif ( isset( $map[ mb_convert_case( $w, MB_CASE_TITLE, 'UTF-8' ) ] ) ) {
				$out[] = $map[ mb_convert_case( $w, MB_CASE_TITLE, 'UTF-8' ) ];
			} else {
				$out[] = $w; // π.χ. τόπος «Λαμίας» (ήδη σε γενική)
			}
		}
		return 'του ' . implode( ' ', $out );
	}

	/** «5/6», «05-06», «5.6» → «05/06»· άκυρο → κενό. */
	public static function day_month( $v ) {
		if ( ! preg_match( '/^\s*(\d{1,2})\s*[\/.\-]\s*(\d{1,2})\s*$/', (string) $v, $m ) ) {
			return '';
		}
		$d = (int) $m[1];
		$n = (int) $m[2];
		return ( $d >= 1 && $d <= 31 && $n >= 1 && $n <= 12 ) ? sprintf( '%02d/%02d', $d, $n ) : '';
	}

	/** Είναι σήμερα μέσα στην περίοδο «ηη/μμ – ηη/μμ»; (και περίοδοι που περνούν την αλλαγή του έτους) */
	public static function in_season( $from, $to, $today = null ) {
		if ( '' === $from && '' === $to ) {
			return true;
		}
		$key   = function ( $dm ) { return (int) substr( $dm, 3, 2 ) * 100 + (int) substr( $dm, 0, 2 ); };
		$t     = null === $today ? (int) wp_date( 'n' ) * 100 + (int) wp_date( 'j' ) : $today;
		$f     = '' === $from ? 101 : $key( $from );
		$e     = '' === $to ? 1231 : $key( $to );
		return $f <= $e ? ( $t >= $f && $t <= $e ) : ( $t >= $f || $t <= $e );
	}

	/** Εμφανίζεται σήμερα το έντυπο; Χωρίς περιόδους = πάντα· αλλιώς μέσα σε μία από τις δύο. */
	public static function season_ok( $conf ) {
		$p1 = '' !== ( $conf['from'] ?? '' ) . ( $conf['to'] ?? '' );
		$p2 = '' !== ( $conf['from2'] ?? '' ) . ( $conf['to2'] ?? '' );
		if ( ! $p1 && ! $p2 ) {
			return true;
		}
		return ( $p1 && self::in_season( $conf['from'], $conf['to'] ) ) || ( $p2 && self::in_season( $conf['from2'], $conf['to2'] ) );
	}

	/** Ζώνες Ολοήμερου («ζώνη | περιγραφή») */
	public static function lines( $key ) {
		$out = array();
		foreach ( preg_split( '/\r\n|\r|\n/', (string) self::get()[ $key ] ) as $line ) {
			$line = trim( $line );
			if ( '' !== $line ) {
				$out[] = $line;
			}
		}
		return $out;
	}

	/** Ώρες αποχώρησης από τη ρύθμιση «ώρα | περιγραφή». */
	public static function departure_times() {
		$out = array();
		foreach ( preg_split( '/\r\n|\r|\n/', (string) self::get()['departure_times'] ) as $line ) {
			$p = array_map( 'trim', explode( '|', $line, 2 ) );
			if ( '' !== $p[0] ) {
				$out[] = array( 'time' => $p[0], 'text' => isset( $p[1] ) ? $p[1] : '' );
			}
		}
		return $out;
	}

	/** Ενεργά είδη άδειας, με τη σειρά της διαχείρισης. */
	public static function leave_types( $all = false ) {
		$s   = self::get();
		$out = array();
		foreach ( (array) $s['leave_types'] as $t ) {
			if ( is_array( $t ) && ! empty( $t['id'] ) && ( $all || '1' === $t['enabled'] ) ) {
				$t['work']        = in_array( $t['id'], self::workday_leaves(), true ) ? '1' : '0';
				$out[ $t['id'] ] = $t;
			}
		}
		return $out;
	}

	/**
	 * Είδη άδειας που μετρούν σε εργάσιμες ημέρες: χωρίς Σάββατο-Κυριακή, αργίες σχολικού έτους και
	 * τοπικές αργίες (οδηγία χρήστη 12-11-2026). Μη εργάσιμες: βλ. holiday() στο eforms.js (μαζί με 1η Μαΐου· η 30/1 είναι εργάσιμη). Τα υπόλοιπα (αναρρωτικές, «άλλη άδεια», όσα προσθέτει
	 * ο διαχειριστής) μετρούν ημερολογιακές ημέρες.
	 */
	public static function workday_leaves() {
		return array( 'regular', 'child_sick', 'exams', 'child_school', 'blood', 'trial', 'paternity', 'marriage', 'bereavement', 'gyn', 'serious_illness', 'disability' );
	}

	/**
	 * Τοπικές αργίες (π.χ. πολιούχος): μία ανά γραμμή, «ηη/μμ» (κάθε χρόνο) ή «ηη/μμ/εεεε», προαιρετικά με όνομα.
	 * Επιστρέφει [ ['d'=>ηη,'m'=>μμ,'y'=>εεεε|0,'label'=>…], … ].
	 */
	public static function local_holidays( $text = null ) {
		$text = null === $text ? (string) self::get()['local_holidays'] : (string) $text;
		$out  = array();
		foreach ( preg_split( '/\r\n|\r|\n/', $text ) as $line ) {
			if ( ! preg_match( '/^\s*(\d{1,2})\s*[\/.\-]\s*(\d{1,2})(?:\s*[\/.\-]\s*(\d{4}))?\s*[–—:\-]?\s*(.*)$/u', $line, $m ) ) {
				continue;
			}
			$d = (int) $m[1];
			$mo = (int) $m[2];
			$y = isset( $m[3] ) && '' !== $m[3] ? (int) $m[3] : 0;
			if ( ! checkdate( $mo, $d, $y ? $y : 2024 ) ) {
				continue;
			}
			$out[] = array( 'd' => $d, 'm' => $mo, 'y' => $y, 'label' => mb_substr( sanitize_text_field( $m[4] ), 0, 60 ) );
			if ( count( $out ) >= 20 ) {
				break;
			}
		}
		return $out;
	}

	private static function sanitize_leave_types( $rows ) {
		$out  = array();
		$seen = array();
		foreach ( (array) $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$label = isset( $row['label'] ) ? mb_substr( sanitize_text_field( $row['label'] ), 0, 120 ) : '';
			if ( '' === $label ) {
				continue; // κενή γραμμή = διαγραφή
			}
			$gen = isset( $row['gen'] ) ? mb_substr( sanitize_text_field( $row['gen'] ), 0, 120 ) : '';
			$id  = isset( $row['id'] ) ? sanitize_key( $row['id'] ) : '';
			if ( '' === $id || isset( $seen[ $id ] ) ) {
				$id = 't' . substr( md5( $label . count( $out ) ), 0, 8 );
			}
			$seen[ $id ] = true;
			$docs = array();
			foreach ( preg_split( '/\r\n|\r|\n/', isset( $row['docs'] ) ? (string) $row['docs'] : '' ) as $d ) {
				$d = mb_substr( sanitize_text_field( $d ), 0, 80 );
				if ( '' !== $d ) {
					$docs[] = $d;
				}
			}
			$out[] = array(
				'id'       => $id,
				'label'    => $label,
				'gen'      => '' !== $gen ? $gen : 'άδειας',
				'health'   => ! empty( $row['health'] ) ? '1' : '0',
				'reasons'  => ! empty( $row['reasons'] ) ? '1' : '0',
				'describe' => ! empty( $row['describe'] ) ? '1' : '0',
				'docs'     => implode( "\n", array_slice( $docs, 0, 8 ) ),
				'enabled'  => ! empty( $row['enabled'] ) ? '1' : '0',
			);
		}
		return $out;
	}

	/** Η λίστα της 0.2.0 (ελεύθερο κείμενο), για αναγνώριση κατά τη μετατροπή. */
	private static function legacy_default_links_text() {
		return implode(
			"\n",
			array(
				'Βεβαίωση φοίτησης | https://www.gov.gr/ipiresies/ekpaideuse/eggraphe-se-skholeio/attending-school | Την εκδίδετε μόνοι σας, χωρίς αίτηση στο σχολείο.',
				'Υπεύθυνη δήλωση | ' . self::GOV_YD_URL . ' | Με ελεύθερο κείμενο, απευθείας από το gov.gr.',
				'Ψηφιακή βεβαίωση εγγράφου | ' . self::GOV_CERT_URL . ' | Ψηφιακή υπογραφή (γνήσιο υπογραφής) σε PDF.',
				'e-Εγγραφές | https://e-eggrafes.minedu.gov.gr | Ηλεκτρονικές εγγραφές μαθητών.',
				'Ηλεκτρονική Σχολική Τάξη | https://eclass.sch.gr/ | Τα ψηφιακά μαθήματα και το υλικό των εκπαιδευτικών.',
				'Διαδραστικά σχολικά βιβλία | https://evivlio.gov.gr/ | Τα σχολικά βιβλία σε ψηφιακή μορφή.',
			)
		);
	}

	/** Μετατρέπει τη λίστα κειμένου της 0.2.0 («Τίτλος | URL | περιγραφή») σε γραμμές πίνακα. */
	private static function links_from_text( $text ) {
		if ( trim( str_replace( "\r", '', (string) $text ) ) === trim( self::legacy_default_links_text() ) ) {
			return self::default_links(); // αμετάβλητη προεπιλογή → νέα λίστα ανά βαθμίδα
		}
		$out = array();
		foreach ( preg_split( '/\r\n|\r|\n/', (string) $text ) as $line ) {
			$p = array_map( 'trim', explode( '|', $line ) );
			if ( count( $p ) >= 2 && '' !== $p[0] ) {
				$out[] = array( 'title' => $p[0], 'url' => $p[1], 'desc' => isset( $p[2] ) ? $p[2] : '', 'level' => 'all', 'for' => self::guess_audience( $p[1] ) );
			}
		}
		return $out;
	}

	public static function get() {
		if ( null !== self::$cache ) {
			return self::$cache;
		}
		$saved = get_option( self::OPTION, array() );
		$saved = is_array( $saved ) ? $saved : array();
		if ( empty( $saved['migrated'] ) && ! empty( $saved ) ) {
			$saved = self::migrate( $saved );
			update_option( self::OPTION, $saved );
		}
		if ( ! empty( $saved ) && empty( $saved['modes_v'] ) ) {
			// 0.2.0–0.2.1 αποθήκευαν «email» σε κάθε έντυπο ως προεπιλογή· γίνεται «Αυτόματα»,
			// ώστε ο γενικός διακόπτης «Αποστολή από την ιστοσελίδα» να ισχύει παντού.
			if ( isset( $saved['forms'] ) && is_array( $saved['forms'] ) ) {
				foreach ( $saved['forms'] as $fid => $fc ) {
					if ( is_array( $fc ) && isset( $fc['mode'] ) && 'email' === $fc['mode'] ) {
						$saved['forms'][ $fid ]['mode'] = 'auto';
					}
				}
			}
			$saved['modes_v'] = 2;
			update_option( self::OPTION, $saved );
		}
		if ( ! empty( $saved ) && empty( $saved['school_level'] ) ) {
			// Ως την 0.2.3: school_type = primary | secondary. Το «secondary» γίνεται Γυμνάσιο
			// και ζητείται έλεγχος, γιατί μπορεί να είναι Λύκειο.
			$old                   = isset( $saved['school_type'] ) ? $saved['school_type'] : 'primary';
			$saved['school_level'] = 'secondary' === $old ? 'gymnasio' : 'primary';
			$saved['level_check']  = 'secondary' === $old ? '1' : '';
			update_option( self::OPTION, $saved );
		}
		if ( ! empty( $saved ) && empty( $saved['color_v'] ) ) {
			// 0.5.0: χρώματα από το θέμα. Όποιος είχε ήδη αλλάξει το «Κύριο χρώμα» το κρατά.
			$saved['color_source'] = ! empty( $saved['accent'] ) && '#0d5eaf' !== strtolower( $saved['accent'] ) ? 'custom' : 'theme';
			$saved['color_v']      = 1;
			update_option( self::OPTION, $saved );
		}
		if ( ! empty( $saved ) && (int) ( $saved['leave_v'] ?? 0 ) < 1 ) {
			// 0.3.3: η «Αίτηση αναρρωτικής άδειας» έγινε είδος της ενιαίας «Αίτησης άδειας».
			// Ενεργό, τρόπος και gov.gr της παλιάς αναρρωτικής περνούν στην ενιαία αίτηση (η κάρτα της καταργείται στο leave_v 2).
			$forms = isset( $saved['forms'] ) && is_array( $saved['forms'] ) ? $saved['forms'] : array();
			$sick  = isset( $forms['sick_leave'] ) && is_array( $forms['sick_leave'] ) ? $forms['sick_leave'] : array();
			$tl    = isset( $forms['teacher_leave'] ) && is_array( $forms['teacher_leave'] ) ? $forms['teacher_leave'] : array();
			if ( $sick ) {
				if ( isset( $sick['enabled'] ) && '1' === $sick['enabled'] ) {
					$tl['enabled'] = '1';
				}
				$rank = array( 'none' => 0, 'optional' => 1, 'required' => 2 );
				$g1   = isset( $tl['gov'] ) && isset( $rank[ $tl['gov'] ] ) ? $tl['gov'] : 'required';
				$g2   = isset( $sick['gov'] ) && isset( $rank[ $sick['gov'] ] ) ? $sick['gov'] : 'required';
				$tl['gov'] = $rank[ $g2 ] > $rank[ $g1 ] ? $g2 : $g1; // κρατάμε την αυστηρότερη
				if ( ( empty( $tl['mode'] ) || 'auto' === $tl['mode'] ) && ! empty( $sick['mode'] ) && in_array( $sick['mode'], array( 'email', 'print' ), true ) ) {
					$tl['mode'] = $sick['mode'];
				}
				$forms['teacher_leave'] = $tl;
				$forms['sick_leave']    = array( 'enabled' => isset( $sick['enabled'] ) ? $sick['enabled'] : '0' );
				$saved['forms']         = $forms;
			}
			$saved['leave_v'] = 1;
			update_option( self::OPTION, $saved );
		}
		if ( ! empty( $saved ) && (int) $saved['leave_v'] < 2 ) {
			// 0.3.4: το ξεχωριστό έντυπο/κάρτα αναρρωτικής καταργήθηκε εντελώς·
			// η αναρρωτική λέγεται απλώς «αναρρωτική άδεια» (χωρίς «βραχυχρόνια»).
			unset( $saved['forms']['sick_leave'] );
			if ( ! empty( $saved['leave_types'] ) && is_array( $saved['leave_types'] ) ) {
				foreach ( $saved['leave_types'] as $k => $t ) {
					if ( is_array( $t ) && isset( $t['label'] ) && 'αναρρωτική άδεια (βραχυχρόνια)' === $t['label'] ) {
						$saved['leave_types'][ $k ]['label'] = 'αναρρωτική άδεια';
					}
				}
			}
			$saved['leave_v'] = 2;
			update_option( self::OPTION, $saved );
		}
		if ( ! empty( $saved ) && (int) $saved['leave_v'] < 3 ) {
			// 0.4.0: νέα λίστα κατά τη Δ/νση Π.Ε. Φθιώτιδας. Αντικαθιστά μόνο λίστα που έμεινε
			// όπως ήταν προεπιλεγμένη· λίστα που άλλαξε το σχολείο δεν αγγίζεται.
			if ( empty( $saved['leave_types'] ) || self::is_old_default_leave_list( $saved['leave_types'] ) ) {
				unset( $saved['leave_types'] );
			} else {
				foreach ( $saved['leave_types'] as $k => $t ) {
					if ( is_array( $t ) && ! isset( $t['reasons'] ) ) {
						// Παλιές λίστες: οι Παρατηρήσεις υπήρχαν σε κάθε είδος εκτός «υγείας».
						$saved['leave_types'][ $k ]['reasons'] = '1' === ( $t['health'] ?? '0' ) ? '0' : '1';
					}
				}
			}
			$saved['leave_v'] = 3;
			update_option( self::OPTION, $saved );
		}
		if ( ! empty( $saved ) && (int) $saved['leave_v'] < 5 ) {
			// 0.9.4: καταργείται η «ειδική άδεια άσκησης εκλογικού δικαιώματος»· οι εργάσιμες ορίζονται
			// σταθερά ανά είδος (workday_leaves), οπότε φεύγει το κουτάκι «μόνο εργάσιμες» της 0.9.3.
			if ( ! empty( $saved['leave_types'] ) && is_array( $saved['leave_types'] ) ) {
				$keep = array();
				foreach ( $saved['leave_types'] as $t ) {
					if ( ! is_array( $t ) || 'election' === ( $t['id'] ?? '' ) ) {
						continue;
					}
					unset( $t['work'] );
					$keep[] = $t;
				}
				$saved['leave_types'] = $keep;
			}
			$saved['leave_v'] = 5;
			update_option( self::OPTION, $saved );
		}
		if ( ! empty( $saved ) && (int) ( $saved['links_v'] ?? 0 ) < 1 ) {
			// 0.7.0: κεντρική σελίδα με ενότητες. Κάθε υπηρεσία παίρνει «Για» (όλους / γονείς / εκπαιδευτικούς)
			// και προστίθενται οι υπηρεσίες εκπαιδευτικών. Λίστα που έμεινε όπως ήταν → η νέα προεπιλογή.
			if ( isset( $saved['links'] ) && is_array( $saved['links'] ) ) {
				$urls = array();
				foreach ( $saved['links'] as $l ) {
					$urls[] = is_array( $l ) && isset( $l['url'] ) ? (string) $l['url'] : '';
				}
				$old = self::links_06_urls();
				if ( count( $urls ) === count( $old ) && ! array_diff( $urls, $old ) ) {
					unset( $saved['links'] );
				} else {
					foreach ( $saved['links'] as $k => $l ) {
						if ( is_array( $l ) && empty( $l['for'] ) ) {
							$saved['links'][ $k ]['for'] = self::guess_audience( $l['url'] ?? '' );
						}
					}
					foreach ( self::default_links() as $d ) {
						if ( 'teachers' === $d['for'] && ! in_array( $d['url'], $urls, true ) ) {
							$saved['links'][] = $d;
						}
					}
				}
			}
			$saved['links_v'] = 1;
			update_option( self::OPTION, $saved );
		}
		if ( ! empty( $saved ) && (int) ( $saved['links_v'] ?? 0 ) < 2 ) {
			// 0.7.1: νέα κείμενα ΟΠΣΥΔ, teachers.minedu, e-class· «Lessons» για όλους· νέα υπηρεσία e-me.
			// Αλλάζουν μόνο υπηρεσίες με τα κείμενα της 0.7.0 (όσες δεν πείραξε το σχολείο).
			if ( isset( $saved['links'] ) && is_array( $saved['links'] ) ) {
				$v070 = array(
					'Πληροφοριακό σύστημα υποψήφιων εκπαιδευτικών: αιτήσεις, πίνακες, αναπληρώσεις.' => 'https://opsyd.sch.gr/',
					'Μεταθέσεις και τοποθετήσεις εκπαιδευτικών.' => 'https://teachers.minedu.gov.gr/',
					'Τα ψηφιακά μαθήματα και το υλικό των εκπαιδευτικών, με λογαριασμό ΠΣΔ.' => 'https://eclass.sch.gr/',
					'Προγραμματισμός ζωντανών μαθημάτων με BigBlueButton ή Webex, με λογαριασμό ΠΣΔ.' => 'https://lessons.sch.gr/',
				);
				$new  = array();
				foreach ( self::default_links() as $d ) {
					$new[ $d['url'] ] = $d;
				}
				$urls = array();
				foreach ( $saved['links'] as $k => $l ) {
					$u      = is_array( $l ) && isset( $l['url'] ) ? (string) $l['url'] : '';
					$urls[] = $u;
					$desc   = is_array( $l ) && isset( $l['desc'] ) ? (string) $l['desc'] : '';
					if ( isset( $v070[ $desc ] ) && $v070[ $desc ] === $u && isset( $new[ $u ] ) ) {
						$saved['links'][ $k ] = array_merge( $l, array( 'title' => $new[ $u ]['title'], 'desc' => $new[ $u ]['desc'], 'for' => $new[ $u ]['for'] ) );
					}
				}
				foreach ( array( 'https://lessons.sch.gr/', 'https://e-me.edu.gr/' ) as $u ) {
					if ( ! in_array( $u, $urls, true ) ) {
						$saved['links'][] = $new[ $u ];
					}
				}
			}
			$saved['links_v'] = 2;
			update_option( self::OPTION, $saved );
		}
		$s = array_merge( self::defaults(), $saved );
		if ( ! is_array( $s['request_topics'] ) ) {
			$s['request_topics'] = self::default_request_topics();
		}
		// Μουσικό σχολείο: το θέμα «Μετεγγραφή λόγω εισαγωγής» μπαίνει στη λίστα, ώσπου το σχολείο να αποθηκεύσει τη δική του.
		if ( 'music' === $s['school_kind'] && '1' !== $s['music_topics'] ) {
			$ids = array();
			foreach ( $s['request_topics'] as $t ) {
				$ids[] = is_array( $t ) && isset( $t['id'] ) ? $t['id'] : '';
			}
			foreach ( self::music_request_topics() as $t ) {
				if ( ! in_array( $t['id'], $ids, true ) ) {
					$s['request_topics'][] = $t;
				}
			}
		}
		if ( ! is_array( $s['absence_cases'] ) || ! $s['absence_cases'] ) {
			$s['absence_cases'] = self::default_absence_cases();
		}
		if ( ! is_array( $s['leave_types'] ) || ! $s['leave_types'] ) {
			$s['leave_types'] = self::default_leave_types();
		}
		// Συμβατότητα (πρότυπα 0.2.x): school_type = primary | secondary.
		$s['school_type'] = 'primary' === $s['school_level'] ? 'primary' : 'secondary';
		if ( ! is_array( $s['links'] ) ) {
			$s['links'] = self::links_from_text( $s['links'] );
		}
		// Κενό «ΠΡΟΣ» ή ίδιο με την επωνυμία (η παλιά αυτόματη προεπιλογή, που αποθηκευόταν με το πρώτο «Αποθήκευση»).
		if ( '' === trim( (string) $s['school_to'] ) || trim( (string) $s['school_to'] ) === trim( (string) $s['school_name'] ) ) {
			// Χωρίς ρητό «ΠΡΟΣ»: «Τη Διεύθυνση του …» αν έχει δοθεί η γενική, αλλιώς η επωνυμία.
			$s['school_to'] = '' !== trim( (string) $s['school_gen'] ) ? 'Τη Διεύθυνση ' . trim( $s['school_gen'] ) : $s['school_name'];
		}
		self::$cache = $s;
		return $s;
	}

	public static function flush() {
		self::$cache = null;
	}

	/** Μετατρέπει τις ρυθμίσεις της 0.1.x στη νέα δομή, χωρίς απώλειες. */
	public static function migrate( $old ) {
		$map = array(
			'form_parent'      => 'parent_request',
			'form_leave'       => 'teacher_leave',
			'form_sick_leave'  => 'sick_leave',
			'form_declaration' => 'gov_declaration',
		);
		$forms = isset( $old['forms'] ) && is_array( $old['forms'] ) ? $old['forms'] : array();
		foreach ( $map as $old_key => $id ) {
			if ( isset( $old[ $old_key ] ) && ! isset( $forms[ $id ] ) ) {
				$forms[ $id ] = array( 'enabled' => '1' === (string) $old[ $old_key ] ? '1' : '0' );
			}
			unset( $old[ $old_key ] );
		}
		if ( isset( $old['frontend_theme'] ) ) {
			$old['theme'] = 'dark' === $old['frontend_theme'] ? 'dark' : 'light';
			unset( $old['frontend_theme'] );
		}
		if ( isset( $old['pdf_upload_enabled'] ) ) {
			$old['upload_enabled'] = '1' === (string) $old['pdf_upload_enabled'] ? '1' : '0';
			if ( '1' === $old['upload_enabled'] ) {
				foreach ( array_values( $map ) as $id ) {
					if ( isset( $forms[ $id ] ) ) {
						$forms[ $id ]['mode'] = 'upload';
					}
				}
			}
			unset( $old['pdf_upload_enabled'] );
		}
		unset( $old['email_enabled'] );
		// Έντυπα που δεν υπήρχαν στην 0.1.x ξεκινούν απενεργοποιημένα, ώστε μια
		// αναβάθμιση να μην εμφανίσει ξαφνικά νέα, μη ελεγμένα έντυπα στο κοινό.
		foreach ( array_keys( SEF_Forms::all() ) as $id ) {
			if ( ! isset( $forms[ $id ] ) ) {
				$forms[ $id ] = array( 'enabled' => '0' );
			}
		}
		$old['forms']    = $forms;
		$old['migrated'] = SEF_VERSION;
		return $old;
	}

	/** Ρύθμιση ενός εντύπου, με τις προεπιλογές του ορισμού του. */
	public static function form( $id, $def ) {
		$s    = self::get();
		$conf = isset( $s['forms'][ $id ] ) && is_array( $s['forms'][ $id ] ) ? $s['forms'][ $id ] : array();
		$mode = isset( $conf['mode'] ) ? $conf['mode'] : 'auto';
		if ( 'auto' !== $mode && ! in_array( $mode, $def['modes'], true ) ) {
			$mode = 'auto';
		}
		$gov = isset( $conf['gov'] ) && in_array( $conf['gov'], array( 'required', 'optional', 'none' ), true ) ? $conf['gov'] : $def['gov'];
		return array(
			'enabled' => isset( $conf['enabled'] ) ? '1' === $conf['enabled'] : ! empty( $def['default_enabled'] ),
			'mode'    => $mode,
			'gov'     => $gov,
			'file'    => isset( $conf['file'] ) ? absint( $conf['file'] ) : 0,
			// Εμφάνιση σε σχολείο άλλης βαθμίδας/τύπου (μόνο με ρητή επιλογή του διαχειριστή).
			'other_level' => isset( $conf['other_level'] ) && '1' === $conf['other_level'],
			// Περίοδος εμφάνισης (εποχικά έντυπα): «ηη/μμ», κάθε χρόνο. Κενό = πάντα.
			'from'    => isset( $conf['from'] ) ? $conf['from'] : ( $def['season'][0] ?? '' ),
			'to'      => isset( $conf['to'] ) ? $conf['to'] : ( $def['season'][1] ?? '' ),
			// Δεύτερη περίοδος (π.χ. επικαιροποίηση Ολοήμερου: Νοέμβριος και Φεβρουάριος).
			'from2'   => isset( $conf['from2'] ) ? $conf['from2'] : ( $def['season'][2] ?? '' ),
			'to2'     => isset( $conf['to2'] ) ? $conf['to2'] : ( $def['season'][3] ?? '' ),
		);
	}

	/* ---------------- Βαθμίδα / τύπος σχολείου ---------------- */

	public static function school_levels() {
		return array(
			'primary'  => 'Δημοτικό / Νηπιαγωγείο',
			'gymnasio' => 'Γυμνάσιο',
			'lykeio'   => 'Λύκειο',
		);
	}

	public static function school_kinds() {
		return array(
			'general' => 'Γενικό',
			'music'   => 'Μουσικό',
			'art'     => 'Καλλιτεχνικό',
			'model'   => 'Πρότυπο / Πειραματικό',
			'evening' => 'Εσπερινό',
		);
	}

	public static function gov_options() {
		return array(
			'required' => 'Βεβαίωση gov.gr: υποχρεωτική',
			'optional' => 'Βεβαίωση gov.gr: προαιρετική',
			'none'     => 'Βεβαίωση gov.gr: δεν χρειάζεται',
		);
	}

	public static function level() {
		return self::get()['school_level'];
	}

	/**
	 * Ταιριάζει η βαθμίδα του σχολείου με μια λίστα βαθμίδων εντύπου/συνδέσμου;
	 * Αποδεκτές τιμές: all, primary, secondary (= Γυμνάσιο και Λύκειο), gymnasio, lykeio.
	 */
	public static function level_matches( $levels, $level = null ) {
		$level  = null === $level ? self::level() : $level;
		$levels = (array) $levels;
		if ( ! $levels || in_array( 'all', $levels, true ) || in_array( $level, $levels, true ) ) {
			return true;
		}
		return 'primary' !== $level && in_array( 'secondary', $levels, true );
	}

	/** Ταιριάζει ο τύπος σχολείου (κενή λίστα = όλοι οι τύποι). */
	public static function kind_matches( $kinds ) {
		$kinds = array_filter( (array) $kinds );
		return ! $kinds || in_array( self::get()['school_kind'], $kinds, true );
	}

	/** Τμήματα από τις ρυθμίσεις (π.χ. «Α1, Α2, Β1»)· κενός πίνακας = ελεύθερο κείμενο. */
	public static function sections() {
		$raw = (string) self::get()['sections'];
		$out = array_filter( array_map( 'trim', preg_split( '/[,;\r\n]+/', $raw ) ), 'strlen' );
		return array_values( array_unique( $out ) );
	}

	/** Τι λείπει για να λειτουργήσει η αποστολή από την ιστοσελίδα (κενός πίνακας = τίποτα). */
	public static function upload_missing() {
		$s    = self::get();
		$miss = array();
		if ( ! is_email( $s['school_email'] ) ) {
			$miss[] = 'email σχολείου (καρτέλα «Σχολείο»)';
		}
		if ( empty( $s['privacy_url'] ) ) {
			$miss[] = 'σύνδεσμος ενημέρωσης προστασίας δεδομένων (καρτέλα «Σχολείο»)';
		}
		return $miss;
	}

	/**
	 * Ο τρόπος υποβολής που θα δει πραγματικά ο χρήστης, και γιατί (αν διαφέρει).
	 * 'auto' = αποστολή από την ιστοσελίδα όταν είναι ενεργή, αλλιώς email.
	 */
	public static function effective_mode( $def, $mode ) {
		$s      = self::get();
		$reason = '';
		if ( 'auto' === $mode ) {
			$mode = ( '1' === $s['upload_enabled'] && in_array( 'upload', $def['modes'], true ) ) ? 'upload' : 'email';
		}
		if ( 'upload' === $mode && ! self::can_upload() ) {
			$mode   = 'email';
			$reason = '1' !== $s['upload_enabled']
				? 'η αποστολή από την ιστοσελίδα δεν είναι ενεργή («Αποστολή & ασφάλεια»)'
				: 'λείπει: ' . implode( ', ', self::upload_missing() );
		}
		if ( 'email' === $mode && ! is_email( $s['school_email'] ) && in_array( 'print', $def['modes'], true ) ) {
			$mode   = 'print';
			$reason = 'δεν έχει οριστεί email σχολείου';
		}
		return array( 'mode' => $mode, 'reason' => $reason );
	}

	public static function school_year() {
		$s = self::get();
		if ( preg_match( '/^\d{4}-\d{4}$/', (string) $s['school_year'] ) ) {
			return $s['school_year'];
		}
		$y = (int) wp_date( 'Y' );
		$m = (int) wp_date( 'n' );
		return $m >= 7 ? $y . '-' . ( $y + 1 ) : ( $y - 1 ) . '-' . $y;
	}

	/** Πραγματικό όριο αρχείου: το μικρότερο από τη ρύθμιση και τα όρια PHP/WordPress. */
	public static function max_upload_bytes() {
		$s   = self::get();
		$cfg = max( 1, min( 10, (int) $s['max_mb'] ) ) * MB_IN_BYTES;
		$wp  = function_exists( 'wp_max_upload_size' ) ? (int) wp_max_upload_size() : 0;
		return $wp > 0 ? min( $cfg, $wp ) : $cfg;
	}

	public static function can_upload() {
		$s = self::get();
		return '1' === $s['upload_enabled'] && is_email( $s['school_email'] ) && ! empty( $s['privacy_url'] );
	}

	public static function levels() {
		return array(
			'all'       => 'Όλες οι βαθμίδες',
			'primary'   => 'Μόνο Δημοτικό / Νηπιαγωγείο',
			'secondary' => 'Γυμνάσιο και Λύκειο',
			'gymnasio'  => 'Μόνο Γυμνάσιο',
			'lykeio'    => 'Μόνο Λύκειο',
		);
	}

	/**
	 * Σύνδεσμοι για τη βαθμίδα του σχολείου (ή όλοι, αν $level = '').
	 * $for: null = όλοι, αλλιώς πίνακας «Για» (all / parents / teachers).
	 */
	public static function links( $level = null, $for = null ) {
		$s     = self::get();
		$level = null === $level ? $s['school_level'] : $level;
		$out   = array();
		foreach ( (array) $s['links'] as $l ) {
			if ( empty( $l['title'] ) || empty( $l['url'] ) || ! wp_http_validate_url( $l['url'] ) ) {
				continue;
			}
			$lv = isset( $l['level'] ) ? $l['level'] : 'all';
			if ( $level && ! self::level_matches( array( $lv ), $level ) ) {
				continue;
			}
			$fo = isset( $l['for'] ) && array_key_exists( $l['for'], self::audiences() ) ? $l['for'] : 'all';
			if ( null !== $for && ! in_array( $fo, (array) $for, true ) ) {
				continue;
			}
			$out[] = array( 'title' => $l['title'], 'url' => $l['url'], 'desc' => isset( $l['desc'] ) ? $l['desc'] : '', 'level' => $lv, 'for' => $fo );
		}
		return $out;
	}

	/** Ομάδες εντύπων («κατηγορίες»). */
	public static function groups() {
		return array(
			'parents'      => 'Γονείς & κηδεμόνες',
			'students'     => 'Μαθητές',
			'teachers'     => 'Εκπαιδευτικοί',
			'declarations' => 'Υπεύθυνες δηλώσεις',
		);
	}

	/**
	 * «Έγγραφα χωρίς κώδικα»: αρχεία για εκτύπωση (από τα Πολυμέσα) ή σύνδεσμοι,
	 * που ο διαχειριστής προσθέτει από τις ρυθμίσεις.
	 */
	public static function sanitize_docs( $rows ) {
		$out  = array();
		$seen = array();
		foreach ( (array) $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$title = isset( $row['title'] ) ? mb_substr( sanitize_text_field( $row['title'] ), 0, 140 ) : '';
			$kind  = isset( $row['kind'] ) && 'link' === $row['kind'] ? 'link' : 'download';
			$url   = isset( $row['url'] ) ? esc_url_raw( trim( (string) $row['url'] ), array( 'http', 'https' ) ) : '';
			$files = array();
			foreach ( preg_split( '/[,\s]+/', isset( $row['files'] ) ? (string) $row['files'] : '' ) as $fid ) {
				if ( absint( $fid ) ) {
					$files[] = absint( $fid );
				}
			}
			if ( '' === $title && ! $url && ! $files ) {
				continue; // κενή γραμμή
			}
			if ( '' === $title || ( 'link' === $kind && ! $url ) || ( 'download' === $kind && ! $files ) ) {
				add_settings_error( self::OPTION, 'sef_doc', 'Ένα «Έγγραφο» χωρίς τίτλο, αρχείο ή σύνδεσμο παραλείφθηκε.', 'warning' );
				continue;
			}
			$id = isset( $row['id'] ) ? preg_replace( '/[^a-z0-9_]/', '', strtolower( (string) $row['id'] ) ) : '';
			if ( 0 !== strpos( $id, 'doc_' ) || isset( $seen[ $id ] ) ) {
				$id = 'doc_' . strtolower( wp_generate_password( 6, false, false ) );
			}
			$seen[ $id ] = true;
			$group       = isset( $row['group'] ) && array_key_exists( $row['group'], self::groups() ) ? $row['group'] : 'parents';
			$level       = isset( $row['level'] ) && array_key_exists( $row['level'], self::levels() ) ? $row['level'] : 'all';
			$out[]       = array(
				'id'      => $id,
				'enabled' => ! empty( $row['enabled'] ) ? '1' : '0',
				'title'   => $title,
				'desc'    => isset( $row['desc'] ) ? mb_substr( sanitize_text_field( $row['desc'] ), 0, 300 ) : '',
				'group'   => $group,
				'level'   => $level,
				'kind'    => $kind,
				'url'     => 'link' === $kind ? $url : '',
				'files'   => 'download' === $kind ? array_slice( array_values( array_unique( $files ) ), 0, 5 ) : array(),
			);
		}
		return $out;
	}

	public static function sanitize( $in ) {
		$in  = is_array( $in ) ? $in : array();
		$d   = self::defaults();
		$out = array();

		$out['school_level'] = isset( $in['school_level'] ) && array_key_exists( $in['school_level'], self::school_levels() ) ? $in['school_level'] : 'primary';
		$out['school_kind']  = isset( $in['school_kind'] ) && array_key_exists( $in['school_kind'], self::school_kinds() ) ? $in['school_kind'] : 'general';
		$out['level_check']  = ''; // ο διαχειριστής αποθήκευσε → η βαθμίδα ελέγχθηκε
		$out['sections']     = isset( $in['sections'] ) ? mb_substr( sanitize_textarea_field( $in['sections'] ), 0, 1000 ) : '';
		foreach ( array( 'school_name', 'school_to', 'school_gen', 'school_city', 'school_address', 'school_phone', 'principal' ) as $k ) {
			$out[ $k ] = isset( $in[ $k ] ) ? mb_substr( sanitize_text_field( $in[ $k ] ), 0, 200 ) : '';
		}
		$out['school_email'] = isset( $in['school_email'] ) ? sanitize_email( $in['school_email'] ) : '';
		if ( '' !== $out['school_email'] && ! is_email( $out['school_email'] ) ) {
			add_settings_error( self::OPTION, 'sef_email', 'Η διεύθυνση email του σχολείου δεν είναι έγκυρη.' );
			$out['school_email'] = '';
		}
		$out['logo_id']     = isset( $in['logo_id'] ) ? absint( $in['logo_id'] ) : 0;
		$out['privacy_url'] = isset( $in['privacy_url'] ) ? esc_url_raw( $in['privacy_url'], array( 'http', 'https' ) ) : '';
		$out['theme']       = isset( $in['theme'] ) && in_array( $in['theme'], array( 'light', 'dark', 'auto' ), true ) ? $in['theme'] : 'light';
		$accent             = isset( $in['accent'] ) ? sanitize_hex_color( $in['accent'] ) : '';
		$out['accent']      = $accent ? $accent : $d['accent'];
		$out['color_source'] = isset( $in['color_source'] ) && 'custom' === $in['color_source'] ? 'custom' : 'theme';
		$out['color_v']      = 1;
		foreach ( array( 'gov_cert_url', 'gov_yd_url' ) as $k ) {
			$url       = isset( $in[ $k ] ) ? esc_url_raw( $in[ $k ], array( 'https' ) ) : '';
			$out[ $k ] = $url ? $url : $d[ $k ];
		}
		$out['upload_enabled'] = ! empty( $in['upload_enabled'] ) ? '1' : '0';
		$out['max_mb']         = isset( $in['max_mb'] ) ? max( 1, min( 10, (int) $in['max_mb'] ) ) : $d['max_mb'];
		$out['rate_limit']     = isset( $in['rate_limit'] ) ? max( 1, min( 100, (int) $in['rate_limit'] ) ) : $d['rate_limit'];
		$out['trust_proxy']    = ! empty( $in['trust_proxy'] ) ? '1' : '0';
		$out['sig_policy']     = isset( $in['sig_policy'] ) && in_array( $in['sig_policy'], array( 'off', 'warn', 'require' ), true ) ? $in['sig_policy'] : 'warn';
		$out['show_links']     = ! empty( $in['show_links'] ) ? '1' : '0';
		$out['links'] = array();
		if ( isset( $in['links'] ) && is_array( $in['links'] ) ) {
			foreach ( $in['links'] as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$title = isset( $row['title'] ) ? mb_substr( sanitize_text_field( $row['title'] ), 0, 120 ) : '';
				$url   = isset( $row['url'] ) ? esc_url_raw( trim( $row['url'] ), array( 'http', 'https' ) ) : '';
				if ( '' === $title && '' === $url ) {
					continue; // κενή γραμμή
				}
				if ( '' === $title || '' === $url ) {
					add_settings_error( self::OPTION, 'sef_link', 'Μια «Χρήσιμη υπηρεσία» χωρίς τίτλο ή έγκυρη διεύθυνση παραλείφθηκε.', 'warning' );
					continue;
				}
				$level          = isset( $row['level'] ) && array_key_exists( $row['level'], self::levels() ) ? $row['level'] : 'all';
				$out['links'][] = array(
					'title' => $title,
					'url'   => $url,
					'desc'  => isset( $row['desc'] ) ? mb_substr( sanitize_text_field( $row['desc'] ), 0, 200 ) : '',
					'level' => $level,
					'for'   => isset( $row['for'] ) && array_key_exists( $row['for'], self::audiences() ) ? $row['for'] : 'all',
				);
			}
		}
		$out['links_v']     = 2;
		$out['home_layout'] = isset( $in['home_layout'] ) && 'list' === $in['home_layout'] ? 'list' : 'sections';
		$out['forms_page']  = isset( $in['forms_page'] ) ? absint( $in['forms_page'] ) : 0;
		delete_transient( 'sef_forms_page' );
		if ( ! empty( $in['links_reset'] ) ) {
			$out['links'] = self::default_links();
		}
		$year                  = isset( $in['school_year'] ) ? trim( sanitize_text_field( $in['school_year'] ) ) : '';
		$out['school_year']    = preg_match( '/^\d{4}-\d{4}$/', $year ) ? $year : '';
		$out['remedial_ops']   = isset( $in['remedial_ops'] ) ? preg_replace( '/[^0-9A-Za-z\-]/', '', $in['remedial_ops'] ) : '';
		$out['remedial_subjects'] = isset( $in['remedial_subjects'] ) ? sanitize_textarea_field( $in['remedial_subjects'] ) : '';
		$out['allday_zones']      = isset( $in['allday_zones'] ) && '' !== trim( $in['allday_zones'] ) ? sanitize_textarea_field( $in['allday_zones'] ) : $d['allday_zones'];
		$out['music_school_name']  = isset( $in['music_school_name'] ) ? mb_substr( sanitize_text_field( $in['music_school_name'] ), 0, 150 ) : '';
		$out['music_school_email'] = isset( $in['music_school_email'] ) && is_email( trim( $in['music_school_email'] ) ) ? sanitize_email( trim( $in['music_school_email'] ) ) : '';
		$out['music_program']     = isset( $in['music_program'] ) ? mb_substr( sanitize_text_field( $in['music_program'] ), 0, 150 ) : '';
		// Η λίστα θεμάτων μετρά ως «επιλογή του σχολείου» μόνο αν η φόρμα έδειχνε ήδη τα θέματα του Μουσικού.
		$prev                     = get_option( self::OPTION, array() );
		$out['music_topics']      = 'music' === $out['school_kind'] && is_array( $prev ) && 'music' === ( $prev['school_kind'] ?? '' ) ? '1' : '';
		$out['meals_time']        = isset( $in['meals_time'] ) && '' !== trim( $in['meals_time'] ) ? mb_substr( sanitize_text_field( $in['meals_time'] ), 0, 40 ) : $d['meals_time'];
		$out['languages']         = isset( $in['languages'] ) && '' !== trim( $in['languages'] ) ? sanitize_textarea_field( $in['languages'] ) : $d['languages'];
		$out['departure_times']   = isset( $in['departure_times'] ) && '' !== trim( $in['departure_times'] ) ? sanitize_textarea_field( $in['departure_times'] ) : $d['departure_times'];

		$out['forms'] = array();
		$defs         = SEF_Forms::all();
		$posted       = isset( $in['forms'] ) && is_array( $in['forms'] ) ? $in['forms'] : array();
		foreach ( $defs as $id => $def ) {
			$p                    = isset( $posted[ $id ] ) && is_array( $posted[ $id ] ) ? $posted[ $id ] : array();
			$mode                 = isset( $p['mode'] ) && ( 'auto' === $p['mode'] || in_array( $p['mode'], $def['modes'], true ) ) ? $p['mode'] : 'auto';
			$out['forms'][ $id ] = array(
				'enabled' => ! empty( $p['enabled'] ) ? '1' : '0',
				'mode'    => $mode,
				'gov'     => isset( $p['gov'] ) && array_key_exists( $p['gov'], self::gov_options() ) ? $p['gov'] : $def['gov'],
				'file'    => isset( $p['file'] ) ? absint( $p['file'] ) : 0,
				'other_level' => ! empty( $p['other_level'] ) ? '1' : '0',
				'from'    => self::day_month( $p['from'] ?? '' ),
				'to'      => self::day_month( $p['to'] ?? '' ),
				'from2'   => self::day_month( $p['from2'] ?? '' ),
				'to2'     => self::day_month( $p['to2'] ?? '' ),
			);
		}
		$out['docs'] = self::sanitize_docs( isset( $in['docs'] ) ? $in['docs'] : array() );
		$out['leave_types'] = ! empty( $in['leave_reset'] ) || ! isset( $in['leave_types'] ) ? self::default_leave_types() : self::sanitize_leave_types( $in['leave_types'] );
		if ( ! $out['leave_types'] ) {
			$out['leave_types'] = self::default_leave_types();
			add_settings_error( self::OPTION, 'sef_leave', 'Η λίστα ειδών άδειας ήταν κενή· επανήλθε η προεπιλεγμένη.', 'warning' );
		}
		$out['leave_v'] = 5;
		$lh = array();
		foreach ( self::local_holidays( isset( $in['local_holidays'] ) ? (string) $in['local_holidays'] : '' ) as $h ) {
			$lh[] = sprintf( '%02d/%02d', $h['d'], $h['m'] ) . ( $h['y'] ? '/' . $h['y'] : '' ) . ( '' !== $h['label'] ? ' ' . $h['label'] : '' );
		}
		$out['local_holidays'] = implode( "\n", $lh );
		$out['request_topics'] = ! empty( $in['topics_reset'] ) || ! isset( $in['request_topics'] ) ? self::default_request_topics() : self::sanitize_request_topics( $in['request_topics'] );
		$out['absence_cases'] = ! empty( $in['absence_reset'] ) || ! isset( $in['absence_cases'] ) ? self::default_absence_cases() : self::sanitize_absence_cases( $in['absence_cases'] );
		if ( ! $out['absence_cases'] ) {
			$out['absence_cases'] = self::default_absence_cases();
			add_settings_error( self::OPTION, 'sef_absence', 'Η λίστα περιπτώσεων απουσιών ήταν κενή· επανήλθε η προεπιλεγμένη.', 'warning' );
		}
		$out['migrated'] = SEF_VERSION;
		$out['modes_v']  = 2;

		if ( '1' === $out['upload_enabled'] && ( ! $out['school_email'] || ! $out['privacy_url'] ) ) {
			add_settings_error( self::OPTION, 'sef_upload', 'Η αποστολή μέσω ιστοσελίδας ΔΕΝ θα εμφανιστεί ακόμη: χρειάζεται ' . ( ! $out['school_email'] ? 'email σχολείου' : '' ) . ( ! $out['school_email'] && ! $out['privacy_url'] ? ' και ' : '' ) . ( ! $out['privacy_url'] ? 'σύνδεσμο ενημέρωσης προστασίας δεδομένων (GDPR)' : '' ) . ' στην καρτέλα «Σχολείο». Μέχρι τότε τα έντυπα δείχνουν οδηγίες αποστολής με email.', 'warning' );
		}
		self::flush();
		return $out;
	}
}
