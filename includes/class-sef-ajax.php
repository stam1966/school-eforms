<?php
/**
 * Αποστολή βεβαιωμένου PDF στο σχολείο.
 *  - Ο έλεγχος (nonce + ερώτηση) ζητείται «φρέσκος» με AJAX τη στιγμή που
 *    χρειάζεται, ώστε να λειτουργεί και με page cache.
 *  - Κάθε ερώτηση ισχύει μία φορά (transient που διαγράφεται στη χρήση).
 *  - Τίποτα δεν αποθηκεύεται μόνιμα· το αρχείο υπάρχει μόνο προσωρινά.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SEF_Ajax {
	const ACTION_SEND      = 'sef_send_document';
	const ACTION_CHALLENGE = 'sef_challenge';
	const CHALLENGE_TTL    = 2 * HOUR_IN_SECONDS;

	public static function init() {
		foreach ( array( self::ACTION_SEND => 'send', self::ACTION_CHALLENGE => 'challenge' ) as $action => $method ) {
			add_action( 'wp_ajax_' . $action, array( __CLASS__, $method ) );
			add_action( 'wp_ajax_nopriv_' . $action, array( __CLASS__, $method ) );
		}
	}

	private static function fail( $message, $status = 400, $extra = array() ) {
		wp_send_json_error( array_merge( array( 'message' => $message ), $extra ), $status );
	}

	/** Νέα ερώτηση ελέγχου + nonce. Δεν αποθηκεύεται σε cache. */
	public static function challenge() {
		nocache_headers();
		if ( ! SEF_Settings::can_upload() ) {
			self::fail( 'Η αποστολή από την ιστοσελίδα δεν είναι ενεργή.', 403 );
		}
		$a   = wp_rand( 2, 9 );
		$b   = wp_rand( 2, 9 );
		$cid = wp_generate_password( 20, false, false );
		set_transient( 'sef_ch_' . $cid, array( 'sum' => $a + $b, 't' => time() ), self::CHALLENGE_TTL );
		wp_send_json_success(
			array(
				'cid'      => $cid,
				'question' => sprintf( 'Πόσο κάνει %d + %d;', $a, $b ),
				'nonce'    => wp_create_nonce( self::ACTION_SEND ),
			)
		);
	}

	private static function verify_challenge( $cid, $answer ) {
		$cid = preg_replace( '/[^A-Za-z0-9]/', '', (string) $cid );
		if ( strlen( $cid ) !== 20 ) {
			return false;
		}
		$key  = 'sef_ch_' . $cid;
		$data = get_transient( $key );
		delete_transient( $key ); // μίας χρήσης, ακόμη και αν η απάντηση είναι λάθος
		if ( ! is_array( $data ) || ! isset( $data['sum'], $data['t'] ) ) {
			return false;
		}
		if ( time() - (int) $data['t'] < 3 ) {
			return false; // υπερβολικά γρήγορα για άνθρωπο
		}
		return (int) $answer === (int) $data['sum'];
	}

	private static function client_ip() {
		$s  = SEF_Settings::get();
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		if ( '1' === $s['trust_proxy'] ) {
			foreach ( array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR' ) as $h ) {
				if ( ! empty( $_SERVER[ $h ] ) ) {
					$first = trim( explode( ',', sanitize_text_field( wp_unslash( $_SERVER[ $h ] ) ) )[0] );
					if ( filter_var( $first, FILTER_VALIDATE_IP ) ) {
						$ip = $first;
						break;
					}
				}
			}
		}
		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : 'unknown';
	}

	private static function throttle() {
		$s     = SEF_Settings::get();
		$key   = 'sef_rl_' . substr( hash_hmac( 'sha256', self::client_ip(), wp_salt( 'auth' ) ), 0, 32 );
		$count = (int) get_transient( $key );
		if ( $count >= (int) $s['rate_limit'] ) {
			return false;
		}
		set_transient( $key, $count + 1, HOUR_IN_SECONDS );
		return true;
	}

	private static function upload_error_message( $code ) {
		switch ( (int) $code ) {
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				return sprintf( 'Το αρχείο είναι μεγαλύτερο από το επιτρεπόμενο όριο (%s).', size_format( SEF_Settings::max_upload_bytes() ) );
			case UPLOAD_ERR_PARTIAL:
				return 'Το αρχείο ανέβηκε μερικώς. Δοκιμάστε ξανά.';
			case UPLOAD_ERR_NO_FILE:
				return 'Δεν επιλέχθηκε αρχείο PDF.';
			default:
				return 'Ο διακομιστής δεν μπόρεσε να παραλάβει το αρχείο. Επικοινωνήστε με το σχολείο.';
		}
	}

	/** Εντοπίζει αν το PDF φέρει ψηφιακή υπογραφή (όχι επαλήθευση γνησιότητας). */
	public static function pdf_has_signature( $path ) {
		$data = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( false === $data ) {
			return false;
		}
		return false !== strpos( $data, '/ByteRange' ) && (bool) preg_match( '#/Type\s*/Sig\b|adbe\.pkcs7|ETSI\.CAdES#', $data );
	}

	public static function send() {
		nocache_headers();
		if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) ) { // phpcs:ignore
			self::fail( 'Μη αποδεκτή μέθοδος.', 405 );
		}
		if ( ! check_ajax_referer( self::ACTION_SEND, 'sef_nonce', false ) ) {
			self::fail( 'Η σελίδα έμεινε ανοιχτή πολύ ώρα. Πατήστε ξανά «Αποστολή».', 403, array( 'refresh' => true ) );
		}
		$p = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification -- ελέγχθηκε παραπάνω.
		if ( ! empty( $p['sef_website'] ) ) {
			self::fail( 'Η αποστολή απορρίφθηκε.' );
		}
		if ( ! SEF_Settings::can_upload() ) {
			self::fail( 'Η αποστολή από την ιστοσελίδα δεν είναι ενεργή. Στείλτε το έγγραφο με email.', 503 );
		}
		$form_id = isset( $p['sef_form'] ) ? SEF_Forms::resolve_id( sanitize_key( $p['sef_form'] ) ) : '';
		$avail   = SEF_Forms::available();
		if ( ! isset( $avail[ $form_id ] ) || 'upload' !== $avail[ $form_id ]['mode'] ) {
			self::fail( 'Αυτό το έντυπο δεν υποβάλλεται από την ιστοσελίδα.', 403 );
		}
		$form = $avail[ $form_id ];
		if ( ! empty( $form['health_by'] ) ) {
			// Αίτηση άδειας: τα είδη «για λόγους υγείας» δεν στέλνονται ποτέ από την ιστοσελίδα (μόνο email ή χαρτί).
			$kind  = isset( $p['sef_kind'] ) ? sanitize_key( $p['sef_kind'] ) : '';
			$types = SEF_Settings::leave_types();
			if ( '' === $kind || ! isset( $types[ $kind ] ) || '1' === $types[ $kind ]['health'] ) {
				self::fail( 'Η αίτηση άδειας για λόγους υγείας δεν στέλνεται από την ιστοσελίδα. Στείλτε την με email.', 403 );
			}
		}
		if ( empty( $p['sef_consent'] ) || '1' !== $p['sef_consent'] ) {
			self::fail( 'Απαιτείται αποδοχή της ενημέρωσης για τα προσωπικά δεδομένα.' );
		}
		if ( empty( $p['sef_verified'] ) || '1' !== $p['sef_verified'] ) {
			self::fail( 'required' === $form['gov'] ? 'Επιβεβαιώστε ότι επισυνάπτετε το αρχείο που βγάλατε από το gov.gr.' : 'Επιβεβαιώστε ότι επισυνάπτετε το τελικό PDF του εντύπου.' );
		}
		if ( ! self::verify_challenge( isset( $p['sef_cid'] ) ? $p['sef_cid'] : '', isset( $p['sef_answer'] ) ? $p['sef_answer'] : '' ) ) {
			self::fail( 'Η απάντηση στον έλεγχο δεν ήταν σωστή. Σας δίνουμε νέα ερώτηση.', 400, array( 'refresh' => true ) );
		}
		if ( ! self::throttle() ) {
			self::fail( 'Έγιναν πολλές αποστολές από αυτή τη σύνδεση. Δοκιμάστε σε λίγο ή στείλτε με email.', 429 );
		}

		$name    = sanitize_text_field( isset( $p['name'] ) ? $p['name'] : '' );
		$email   = sanitize_email( isset( $p['email'] ) ? $p['email'] : '' );
		$subject = sanitize_text_field( isset( $p['subject'] ) ? $p['subject'] : '' );
		$code    = strtoupper( preg_replace( '/[^A-Za-z0-9\-]/', '', isset( $p['code'] ) ? (string) $p['code'] : '' ) );
		if ( ! $name || mb_strlen( $name ) > 150 || ! is_email( $email ) || strlen( $email ) > 150 ) {
			self::fail( 'Συμπληρώστε έγκυρο ονοματεπώνυμο και email.' );
		}
		if ( ! $subject || mb_strlen( $subject ) > 200 ) {
			self::fail( 'Συμπληρώστε το θέμα.' );
		}
		$code = substr( $code, 0, 40 );

		// ---- Αρχείο ----
		if ( empty( $_FILES['sef_pdf'] ) || ! isset( $_FILES['sef_pdf']['error'] ) ) {
			self::fail( 'Δεν επιλέχθηκε αρχείο PDF.' );
		}
		$file = $_FILES['sef_pdf']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( UPLOAD_ERR_OK !== (int) $file['error'] ) {
			self::fail( self::upload_error_message( $file['error'] ) );
		}
		$size = (int) $file['size'];
		if ( $size < 100 || $size > SEF_Settings::max_upload_bytes() || ! is_uploaded_file( $file['tmp_name'] ) ) {
			self::fail( sprintf( 'Το αρχείο είναι κενό ή ξεπερνά το όριο των %s.', size_format( SEF_Settings::max_upload_bytes() ) ) );
		}
		if ( 'pdf' !== strtolower( pathinfo( sanitize_file_name( $file['name'] ), PATHINFO_EXTENSION ) ) ) {
			self::fail( 'Επιτρέπονται μόνο αρχεία PDF.' );
		}
		$fh  = fopen( $file['tmp_name'], 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$sig = $fh ? fread( $fh, 5 ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( $fh ) {
			fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
		if ( '%PDF-' !== $sig ) {
			self::fail( 'Το αρχείο δεν είναι PDF.' );
		}
		if ( class_exists( 'finfo' ) ) {
			$mime = ( new finfo( FILEINFO_MIME_TYPE ) )->file( $file['tmp_name'] );
			if ( 'application/pdf' !== $mime ) {
				self::fail( 'Το αρχείο δεν αναγνωρίζεται ως PDF.' );
			}
		}
		$s      = SEF_Settings::get();
		$signed = self::pdf_has_signature( $file['tmp_name'] );
		// Ο έλεγχος υπογραφής αφορά μόνο έντυπα με υποχρεωτική βεβαίωση gov.gr.
		$policy = 'required' === $form['gov'] ? $s['sig_policy'] : 'off';
		if ( ! $signed && 'require' === $policy ) {
			self::fail( 'Το αρχείο δεν φέρει ψηφιακή υπογραφή. Ανεβάστε το PDF που κατεβάσατε από το gov.gr μετά τη βεβαίωση, όχι το αρχικό.', 400, array( 'unsigned' => true ) );
		}

		// Προσωρινό αντίγραφο με σωστό όνομα: λειτουργεί σε όλες τις εκδόσεις WP.
		$dir = trailingslashit( get_temp_dir() ) . 'sef-' . wp_generate_password( 16, false, false );
		if ( ! wp_mkdir_p( $dir ) ) {
			self::fail( 'Προσωρινό σφάλμα διακομιστή. Στείλτε το έγγραφο με email.', 500 );
		}
		$attach = $dir . '/' . $form['file_slug'] . '-' . wp_date( 'Y-m-d' ) . '.pdf';
		if ( ! copy( $file['tmp_name'], $attach ) ) {
			rmdir( $dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			self::fail( 'Προσωρινό σφάλμα διακομιστή. Στείλτε το έγγραφο με email.', 500 );
		}

		$sig_line = 'off' === $policy ? ( 'required' !== $form['gov'] ? ( $signed ? "Ψηφιακή βεβαίωση gov.gr: υπάρχει (προαιρετική για αυτό το έντυπο).\n" : "Ψηφιακή βεβαίωση gov.gr: δεν απαιτείται για αυτό το έντυπο.\n" ) : '' ) : ( $signed
			? "Ψηφιακή υπογραφή: εντοπίστηκε στο αρχείο (η γνησιότητα ελέγχεται στο gov.gr με τον κωδικό του εγγράφου).\n"
			: "⚠ Ψηφιακή υπογραφή: ΔΕΝ εντοπίστηκε. Πιθανόν ο αποστολέας επισύναψε το αρχικό, μη βεβαιωμένο PDF.\n" );
		$body  = 'Νέο έγγραφο από τα ηλεκτρονικά έντυπα του σχολείου' . "\n";
		$body .= str_repeat( '-', 48 ) . "\n";
		$body .= 'Έντυπο: ' . $form['label'] . "\n";
		$body .= 'Θέμα: ' . $subject . "\n";
		$body .= 'Αποστολέας: ' . $name . "\n";
		$body .= 'Email αποστολέα: ' . $email . "\n";
		$body .= $code ? 'Κωδικός εγγράφου gov.gr (δηλώθηκε): ' . $code . "\n" : '';
		$body .= 'Ημερομηνία παραλαβής: ' . wp_date( 'd/m/Y H:i' ) . "\n";
		$body .= $sig_line;
		$body .= "\nΤο μήνυμα δεν αποτελεί πρωτοκόλληση. Η εφαρμογή δεν επαληθεύει την ταυτότητα του αποστολέα· για έλεγχο γνησιότητας χρησιμοποιήστε τον κωδικό του εγγράφου στην υπηρεσία επαλήθευσης του gov.gr.\n";
		$body .= "Η απάντηση (Reply) πηγαίνει στο email του αποστολέα.\n\nSchool e-Forms " . SEF_VERSION . "\n";

		$headers = array( 'Content-Type: text/plain; charset=UTF-8', 'Reply-To: ' . $email );
		$prefix  = $signed || 'off' === $policy ? '' : '[ΧΩΡΙΣ ΥΠΟΓΡΑΦΗ] ';
		$sent    = wp_mail( $s['school_email'], $prefix . '[e-Έντυπα] ' . $form['label'] . ' – ' . $subject, $body, $headers, array( $attach ) );

		@unlink( $attach ); // phpcs:ignore
		@rmdir( $dir ); // phpcs:ignore

		if ( ! $sent ) {
			self::fail( 'Το σύστημα email του σχολείου δεν δέχτηκε το μήνυμα. Στείλτε το έγγραφο από το δικό σας email.', 502, array( 'fallback' => true ) );
		}
		wp_send_json_success(
			array(
				'message' => 'Το έγγραφο στάλθηκε στο σχολείο. Κρατήστε το αρχείο σας· η αποστολή email δεν σημαίνει πρωτοκόλληση.',
				'signed'  => $signed,
			)
		);
	}
}
