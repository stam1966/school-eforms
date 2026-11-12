<?php
/**
 * Σελίδα ρυθμίσεων (Ρυθμίσεις → Ηλεκτρονικά έντυπα).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SEF_Admin {
	const SLUG = 'school-eforms';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_sef_test_mail', array( __CLASS__, 'test_mail' ) );
		add_action( 'admin_post_sef_copy_form', array( __CLASS__, 'copy_form' ) );
		add_action( 'admin_post_sef_restore_form', array( __CLASS__, 'restore_form' ) );
		add_action( 'admin_notices', array( __CLASS__, 'upgrade_notice' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( SEF_FILE ), array( __CLASS__, 'action_links' ) );
	}

	public static function action_links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=' . self::SLUG ) ) . '">Ρυθμίσεις</a>' );
		return $links;
	}

	public static function menu() {
		add_options_page( 'Ηλεκτρονικά έντυπα', 'Ηλεκτρονικά έντυπα', 'manage_options', self::SLUG, array( __CLASS__, 'page' ) );
	}

	public static function register() {
		register_setting(
			'sef_group',
			SEF_Settings::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'SEF_Settings', 'sanitize' ),
			)
		);
	}

	public static function assets( $hook ) {
		if ( 'settings_page_' . self::SLUG !== $hook ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'sef-admin', SEF_URL . 'assets/css/admin.css', array(), SEF_VERSION );
		wp_enqueue_script( 'sef-admin', SEF_URL . 'assets/js/admin.js', array( 'jquery', 'wp-color-picker' ), SEF_VERSION, true );
	}

	/** Αντιγραφή ενσωματωμένου εντύπου ή του παραδείγματος στον φάκελο πρόσθετων εντύπων. */
	public static function copy_form() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Δεν επιτρέπεται.' );
		}
		check_admin_referer( 'sef_copy_form' );
		$id   = isset( $_GET['form'] ) ? sanitize_key( wp_unslash( $_GET['form'] ) ) : '';
		$res  = SEF_Forms::copy_to_custom( $id );
		$args = array( 'page' => self::SLUG, 'tab' => 'forms' );
		if ( is_wp_error( $res ) ) {
			$args['sef_copy_err'] = rawurlencode( $res->get_error_message() );
		} else {
			$args['sef_copied'] = rawurlencode( basename( $res ) );
		}
		wp_safe_redirect( add_query_arg( $args, admin_url( 'options-general.php' ) ) );
		exit;
	}

	/** Επαναφορά στο ενσωματωμένο (το προσαρμοσμένο αρχειοθετείται, δεν διαγράφεται). */
	public static function restore_form() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Δεν επιτρέπεται.' );
		}
		check_admin_referer( 'sef_restore_form' );
		$id   = isset( $_GET['form'] ) ? sanitize_key( wp_unslash( $_GET['form'] ) ) : '';
		$res  = SEF_Forms::restore_builtin( $id );
		$args = array( 'page' => self::SLUG, 'tab' => 'forms' );
		if ( is_wp_error( $res ) ) {
			$args['sef_copy_err'] = rawurlencode( $res->get_error_message() );
		} else {
			$args['sef_restored'] = count( $res );
		}
		wp_safe_redirect( add_query_arg( $args, admin_url( 'options-general.php' ) ) );
		exit;
	}

	private static function restore_url( $id ) {
		return wp_nonce_url( add_query_arg( array( 'action' => 'sef_restore_form', 'form' => $id ), admin_url( 'admin-post.php' ) ), 'sef_restore_form' );
	}

	/** Ειδοποίηση στον Πίνακα ελέγχου / Πρόσθετα όταν προσαρμοσμένα έντυπα βασίζονται σε παλιά έκδοση. */
	public static function upgrade_notice() {
		if ( ! current_user_can( 'manage_options' ) || ! function_exists( 'get_current_screen' ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->id, array( 'dashboard', 'plugins' ), true ) ) {
			return;
		}
		$n = count( SEF_Forms::attention() );
		if ( ! $n ) {
			return;
		}
		printf(
			'<div class="notice notice-warning"><p><b>Ηλεκτρονικά έντυπα:</b> %1$s %2$s <a href="%3$s">Έλεγχος</a></p></div>',
			esc_html( 1 === $n ? '1 προσαρμοσμένο έντυπο βασίζεται σε παλαιότερη έκδοση του ενσωματωμένου' : $n . ' προσαρμοσμένα έντυπα βασίζονται σε παλαιότερη έκδοση των ενσωματωμένων' ),
			esc_html( '— οι βελτιώσεις της νέας έκδοσης δεν εφαρμόζονται σε αυτά.' ),
			esc_url( admin_url( 'options-general.php?page=' . self::SLUG . '&tab=forms' ) )
		);
	}

	private static function copy_url( $id ) {
		return wp_nonce_url( add_query_arg( array( 'action' => 'sef_copy_form', 'form' => $id ), admin_url( 'admin-post.php' ) ), 'sef_copy_form' );
	}

	public static function test_mail() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Δεν επιτρέπεται.' );
		}
		check_admin_referer( 'sef_test_mail' );
		$s    = SEF_Settings::get();
		$to   = $s['school_email'];
		$ok   = false;
		$err  = '';
		if ( is_email( $to ) ) {
			$catch = function ( $e ) use ( &$err ) {
				$err = $e instanceof WP_Error ? $e->get_error_message() : '';
			};
			add_action( 'wp_mail_failed', $catch );
			$ok = wp_mail( $to, '[e-Έντυπα] Δοκιμαστικό μήνυμα', "Αυτό είναι δοκιμαστικό μήνυμα από τα Ηλεκτρονικά έντυπα (School e-Forms " . SEF_VERSION . ").\nΑν το λάβατε, η αποστολή email της ιστοσελίδας λειτουργεί.\n", array( 'Content-Type: text/plain; charset=UTF-8' ) );
			remove_action( 'wp_mail_failed', $catch );
		}
		$args = array( 'page' => self::SLUG, 'sef_mail' => $ok ? 'ok' : 'fail', 'tab' => 'send' );
		if ( $err ) {
			$args['sef_err'] = rawurlencode( mb_substr( $err, 0, 180 ) );
		}
		wp_safe_redirect( add_query_arg( $args, admin_url( 'options-general.php' ) ) );
		exit;
	}

	private static function name( $key ) {
		return esc_attr( SEF_Settings::OPTION . '[' . $key . ']' );
	}

	private static function text( $s, $key, $label, $o = array() ) {
		$type = isset( $o['type'] ) ? $o['type'] : 'text';
		printf(
			'<tr><th scope="row"><label for="sef-%1$s">%2$s</label></th><td><input id="sef-%1$s" class="%5$s" type="%3$s" name="%4$s" value="%6$s"%7$s>%8$s</td></tr>',
			esc_attr( $key ),
			esc_html( $label ),
			esc_attr( $type ),
			self::name( $key ), // phpcs:ignore
			esc_attr( isset( $o['class'] ) ? $o['class'] : 'regular-text' ),
			esc_attr( $s[ $key ] ),
			isset( $o['attr'] ) ? ' ' . $o['attr'] : '', // phpcs:ignore -- σταθερές τιμές κώδικα
			isset( $o['desc'] ) ? '<p class="description">' . wp_kses_post( $o['desc'] ) . '</p>' : ''
		);
	}

	private static function check( $s, $key, $label, $desc = '' ) {
		printf(
			'<label class="sef-switch"><input type="checkbox" name="%1$s" value="1" %2$s><span>%3$s</span></label>%4$s',
			self::name( $key ), // phpcs:ignore
			checked( $s[ $key ], '1', false ),
			esc_html( $label ),
			$desc ? '<p class="description">' . wp_kses_post( $desc ) . '</p>' : ''
		);
	}

	/** Κείμενο βαθμίδων/τύπων για ενδείξεις «Μόνο για …». */
	private static function levels_text( $levels, $kinds = array() ) {
		$names = array( 'primary' => 'Δημοτικό', 'secondary' => 'Γυμνάσιο & Λύκειο', 'gymnasio' => 'Γυμνάσιο', 'lykeio' => 'Λύκειο', 'all' => 'όλες τις βαθμίδες' );
		$out   = array();
		foreach ( (array) $levels as $l ) {
			$out[] = isset( $names[ $l ] ) ? $names[ $l ] : $l;
		}
		$k = SEF_Settings::school_kinds();
		foreach ( array_filter( (array) $kinds ) as $kk ) {
			$out[] = ( isset( $k[ $kk ] ) ? $k[ $kk ] : $kk ) . ' σχολείο';
		}
		return implode( ', ', $out );
	}

	/** Επιλογή αρχείου/ων από τα Πολυμέσα (αποθηκεύει IDs χωρισμένα με κόμμα). */
	private static function media_field( $name, $ids, $multiple, $label ) {
		$ids  = array_filter( array_map( 'absint', is_array( $ids ) ? $ids : explode( ',', (string) $ids ) ) );
		$list = array();
		foreach ( $ids as $id ) {
			$path   = get_attached_file( $id );
			$list[] = '<span class="sef-media-item">' . esc_html( $path ? wp_basename( $path ) : '#' . $id ) . '</span>';
		}
		?>
		<span class="sef-media" data-sef-media data-multiple="<?php echo $multiple ? '1' : '0'; ?>">
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>" data-sef-media-ids>
			<span class="sef-media-list" data-sef-media-list><?php echo $list ? implode( ' ', $list ) : ''; // phpcs:ignore -- escaped above ?></span>
			<button type="button" class="button button-small" data-sef-media-pick><?php echo esc_html( $ids ? 'Αλλαγή' : $label ); ?></button>
			<button type="button" class="button-link-delete" data-sef-media-clear<?php echo $ids ? '' : ' hidden'; ?>>Αφαίρεση</button>
		</span>
		<?php
	}

	/** Μία γραμμή του πίνακα «Έγγραφα για εκτύπωση & σύνδεσμοι». */
	private static function doc_row( $i, $doc ) {
		$doc  = array_merge( array( 'id' => '', 'enabled' => '1', 'title' => '', 'desc' => '', 'group' => 'parents', 'level' => 'all', 'kind' => 'download', 'url' => '', 'files' => array() ), (array) $doc );
		$base = SEF_Settings::OPTION . '[docs][' . $i . ']';
		?>
		<tr data-sef-doc-row data-kind="<?php echo esc_attr( $doc['kind'] ); ?>">
			<td class="col-on"><input type="hidden" name="<?php echo esc_attr( $base . '[id]' ); ?>" value="<?php echo esc_attr( $doc['id'] ); ?>"><input type="checkbox" name="<?php echo esc_attr( $base . '[enabled]' ); ?>" value="1" <?php checked( $doc['enabled'], '1' ); ?> aria-label="Ενεργό"></td>
			<td>
				<input type="text" class="widefat" name="<?php echo esc_attr( $base . '[title]' ); ?>" value="<?php echo esc_attr( $doc['title'] ); ?>" placeholder="Τίτλος, π.χ. ΑΔΥΜ – Ατομικό Δελτίο Υγείας Μαθητή" maxlength="140" aria-label="Τίτλος">
				<input type="text" class="widefat sef-doc-desc" name="<?php echo esc_attr( $base . '[desc]' ); ?>" value="<?php echo esc_attr( $doc['desc'] ); ?>" placeholder="Σύντομη περιγραφή (προαιρετικά)" maxlength="300" aria-label="Περιγραφή">
				<?php if ( $doc['id'] ) : ?><code class="sef-doc-id">#sef/<?php echo esc_html( $doc['id'] ); ?></code><?php endif; ?>
			</td>
			<td class="col-group">
				<select name="<?php echo esc_attr( $base . '[group]' ); ?>" aria-label="Ομάδα"><?php foreach ( SEF_Settings::groups() as $k => $l ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $doc['group'], $k ); ?>><?php echo esc_html( $l ); ?></option><?php endforeach; ?></select>
				<select name="<?php echo esc_attr( $base . '[level]' ); ?>" aria-label="Βαθμίδα"><?php foreach ( SEF_Settings::levels() as $k => $l ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $doc['level'], $k ); ?>><?php echo esc_html( $l ); ?></option><?php endforeach; ?></select>
			</td>
			<td class="col-kind">
				<select name="<?php echo esc_attr( $base . '[kind]' ); ?>" data-sef-doc-kind aria-label="Μορφή">
					<option value="download" <?php selected( $doc['kind'], 'download' ); ?>>Μόνο για εκτύπωση (αρχείο)</option>
					<option value="link" <?php selected( $doc['kind'], 'link' ); ?>>Παραπομπή σε σύνδεσμο</option>
				</select>
			</td>
			<td>
				<span class="sef-doc-files"><?php self::media_field( $base . '[files]', $doc['files'], true, 'Επιλογή αρχείων' ); ?></span>
				<input type="url" class="widefat code sef-doc-url" name="<?php echo esc_attr( $base . '[url]' ); ?>" value="<?php echo esc_attr( $doc['url'] ); ?>" placeholder="https://" aria-label="Σύνδεσμος">
			</td>
			<td class="col-del"><button type="button" class="button-link-delete" data-sef-doc-del aria-label="Διαγραφή">✕</button></td>
		</tr>
		<?php
	}

	/** Μία γραμμή του πίνακα «Χρήσιμες υπηρεσίες». */
	private static function link_row( $i, $row, $school_type ) {
		$row   = array_merge( array( 'title' => '', 'url' => '', 'desc' => '', 'level' => 'all', 'for' => 'all' ), (array) $row );
		$base  = SEF_Settings::OPTION . '[links][' . $i . ']';
		$other = ! SEF_Settings::level_matches( array( $row['level'] ), $school_type );
		?>
		<tr class="<?php echo $other ? 'is-other-level' : ''; ?>" data-sef-link-row>
			<td class="col-drag"><button type="button" class="button-link" data-sef-up aria-label="Πάνω">↑</button><button type="button" class="button-link" data-sef-down aria-label="Κάτω">↓</button></td>
			<td><input type="text" class="widefat" name="<?php echo esc_attr( $base . '[title]' ); ?>" value="<?php echo esc_attr( $row['title'] ); ?>" maxlength="120" aria-label="Τίτλος"></td>
			<td><input type="url" class="widefat code" name="<?php echo esc_attr( $base . '[url]' ); ?>" value="<?php echo esc_attr( $row['url'] ); ?>" placeholder="https://" aria-label="Διεύθυνση"></td>
			<td><input type="text" class="widefat" name="<?php echo esc_attr( $base . '[desc]' ); ?>" value="<?php echo esc_attr( $row['desc'] ); ?>" maxlength="200" aria-label="Περιγραφή"></td>
			<td class="col-level"><select name="<?php echo esc_attr( $base . '[level]' ); ?>" aria-label="Βαθμίδα" data-sef-level>
				<?php foreach ( SEF_Settings::levels() as $k => $label ) : ?>
					<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $row['level'], $k ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select></td>
			<td class="col-for"><select name="<?php echo esc_attr( $base . '[for]' ); ?>" aria-label="Για">
				<?php foreach ( SEF_Settings::audiences() as $k => $label ) : ?>
					<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $row['for'], $k ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select></td>
			<td class="col-del"><button type="button" class="button-link-delete" data-sef-link-del aria-label="Διαγραφή">✕</button></td>
		</tr>
		<?php
	}

	/** Γραμμή του πίνακα «Θέματα αιτήσεων». */
	private static function topic_row( $i, $row ) {
		$row  = array_merge( array( 'id' => '', 'label' => '', 'subject' => '', 'text' => '', 'enabled' => '1' ), (array) $row );
		$base = SEF_Settings::OPTION . '[request_topics][' . $i . ']';
		?>
		<tr data-sef-leave-row>
			<td class="col-drag"><button type="button" class="button-link" data-sef-up aria-label="Πάνω">↑</button><button type="button" class="button-link" data-sef-down aria-label="Κάτω">↓</button><input type="hidden" name="<?php echo esc_attr( $base . '[id]' ); ?>" value="<?php echo esc_attr( $row['id'] ); ?>"></td>
			<td class="col-on"><label class="sef-toggle"><input type="checkbox" name="<?php echo esc_attr( $base . '[enabled]' ); ?>" value="1" <?php checked( '1', $row['enabled'] ); ?>><span class="screen-reader-text">Ενεργό</span></label></td>
			<td><input type="text" class="widefat" name="<?php echo esc_attr( $base . '[label]' ); ?>" value="<?php echo esc_attr( $row['label'] ); ?>" maxlength="120" aria-label="Θέμα (στη λίστα)"></td>
			<td><input type="text" class="widefat" name="<?php echo esc_attr( $base . '[subject]' ); ?>" value="<?php echo esc_attr( $row['subject'] ); ?>" maxlength="200" aria-label="ΘΕΜΑ της αίτησης"></td>
			<td><textarea class="widefat" rows="3" name="<?php echo esc_attr( $base . '[text]' ); ?>" aria-label="Κείμενο μετά το «Παρακαλώ»"><?php echo esc_textarea( $row['text'] ); ?></textarea></td>
			<td class="col-del"><button type="button" class="button-link-delete" data-sef-leave-del aria-label="Διαγραφή">✕</button></td>
		</tr>
		<?php
	}

	/** Γραμμή του πίνακα «Περιπτώσεις απουσιών». */
	private static function absence_row( $i, $row ) {
		$row  = array_merge( array( 'id' => '', 'label' => '', 'text' => '', 'level' => 'all', 'docs' => '', 'enabled' => '1' ), (array) $row );
		$base = SEF_Settings::OPTION . '[absence_cases][' . $i . ']';
		?>
		<tr data-sef-leave-row class="<?php echo SEF_Settings::level_matches( array( $row['level'] ) ) ? '' : 'is-other-level'; ?>">
			<td class="col-drag"><button type="button" class="button-link" data-sef-up aria-label="Πάνω">↑</button><button type="button" class="button-link" data-sef-down aria-label="Κάτω">↓</button><input type="hidden" name="<?php echo esc_attr( $base . '[id]' ); ?>" value="<?php echo esc_attr( $row['id'] ); ?>"></td>
			<td class="col-on"><label class="sef-toggle"><input type="checkbox" name="<?php echo esc_attr( $base . '[enabled]' ); ?>" value="1" <?php checked( '1', $row['enabled'] ); ?>><span class="screen-reader-text">Ενεργή</span></label></td>
			<td><input type="text" class="widefat" name="<?php echo esc_attr( $base . '[label]' ); ?>" value="<?php echo esc_attr( $row['label'] ); ?>" maxlength="120" aria-label="Περίπτωση (στη λίστα)"></td>
			<td><textarea class="widefat" rows="3" name="<?php echo esc_attr( $base . '[text]' ); ?>" aria-label="Φράση στη δήλωση"><?php echo esc_textarea( $row['text'] ); ?></textarea></td>
			<td class="col-level"><select name="<?php echo esc_attr( $base . '[level]' ); ?>" aria-label="Βαθμίδα">
				<?php foreach ( SEF_Settings::levels() as $k => $label ) : ?>
					<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $row['level'], $k ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select></td>
			<td><textarea class="widefat" rows="2" name="<?php echo esc_attr( $base . '[docs]' ); ?>" aria-label="Συνημμένα (ένα ανά γραμμή)" placeholder="ένα ανά γραμμή"><?php echo esc_textarea( $row['docs'] ); ?></textarea></td>
			<td class="col-del"><button type="button" class="button-link-delete" data-sef-leave-del aria-label="Διαγραφή">✕</button></td>
		</tr>
		<?php
	}

	/** Γραμμή του πίνακα «Είδη άδειας». */
	private static function leave_row( $i, $row ) {
		$row  = array_merge( array( 'id' => '', 'label' => '', 'gen' => '', 'health' => '0', 'reasons' => '0', 'describe' => '0', 'docs' => '', 'enabled' => '1' ), (array) $row );
		$base = SEF_Settings::OPTION . '[leave_types][' . $i . ']';
		?>
		<tr data-sef-leave-row>
			<td class="col-drag"><button type="button" class="button-link" data-sef-up aria-label="Πάνω">↑</button><button type="button" class="button-link" data-sef-down aria-label="Κάτω">↓</button><input type="hidden" name="<?php echo esc_attr( $base . '[id]' ); ?>" value="<?php echo esc_attr( $row['id'] ); ?>"></td>
			<td class="col-on"><label class="sef-toggle"><input type="checkbox" name="<?php echo esc_attr( $base . '[enabled]' ); ?>" value="1" <?php checked( '1', $row['enabled'] ); ?>><span class="screen-reader-text">Ενεργό</span></label></td>
			<td><input type="text" class="widefat" name="<?php echo esc_attr( $base . '[label]' ); ?>" value="<?php echo esc_attr( $row['label'] ); ?>" maxlength="120" aria-label="Είδος (στη λίστα)" placeholder="π.χ. κανονική άδεια"></td>
			<td><input type="text" class="widefat" name="<?php echo esc_attr( $base . '[gen]' ); ?>" value="<?php echo esc_attr( $row['gen'] ); ?>" maxlength="120" aria-label="Για το ΘΕΜΑ (γενική)" placeholder="π.χ. κανονικής άδειας"></td>
			<td class="col-flags">
				<label><input type="checkbox" name="<?php echo esc_attr( $base . '[reasons]' ); ?>" value="1" <?php checked( '1', $row['reasons'] ); ?>> «για τους παρακάτω λόγους»</label><br>
				<label><input type="checkbox" name="<?php echo esc_attr( $base . '[health]' ); ?>" value="1" <?php checked( '1', $row['health'] ); ?>> «για λόγους υγείας»</label><br>
				<label><input type="checkbox" name="<?php echo esc_attr( $base . '[describe]' ); ?>" value="1" <?php checked( '1', $row['describe'] ); ?>> Υποχρ. περιγραφή</label>
			</td>
			<td><textarea class="widefat" rows="2" name="<?php echo esc_attr( $base . '[docs]' ); ?>" aria-label="Συνημμένα (ένα ανά γραμμή)" placeholder="ένα ανά γραμμή"><?php echo esc_textarea( $row['docs'] ); ?></textarea></td>
			<td class="col-del"><button type="button" class="button-link-delete" data-sef-leave-del aria-label="Διαγραφή">✕</button></td>
		</tr>
		<?php
	}

	public static function page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s     = SEF_Settings::get();
		$forms = SEF_Forms::all();
		$cats  = SEF_Forms::categories();
		$modes = SEF_Forms::modes();
		$tab   = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'school'; // phpcs:ignore WordPress.Security.NonceVerification
		$logo  = $s['logo_id'] ? wp_get_attachment_image_url( (int) $s['logo_id'], 'medium' ) : '';
		$tabs  = array(
			'school' => 'Σχολείο',
			'forms'  => 'Έντυπα',
			'send'   => 'Αποστολή & ασφάλεια',
			'look'   => 'Εμφάνιση',
			'links'  => 'Χρήσιμες υπηρεσίες',
			'docs'   => 'Έγγραφα για εκτύπωση & σύνδεσμοι',
			'topics'   => 'Θέματα αιτήσεων',
			'absences' => 'Περιπτώσεις απουσιών',
			'leaves' => 'Άδειες εκπαιδευτικών',
			'help'   => 'Οδηγίες',
		);
		$issues = array();
		if ( ! is_email( $s['school_email'] ) ) {
			$issues[] = 'Δεν έχει οριστεί email σχολείου: τα έντυπα θα προτείνουν μόνο εκτύπωση και κατάθεση.';
		}
		if ( ! $s['privacy_url'] ) {
			$issues[] = 'Δεν έχει οριστεί σύνδεσμος ενημέρωσης προστασίας δεδομένων (GDPR)' . ( '1' === $s['upload_enabled'] ? ': γι\' αυτό η αποστολή από την ιστοσελίδα ΔΕΝ εμφανίζεται, παρότι είναι ενεργή.' : '.' );
		}
		if ( '1' === $s['level_check'] ) {
			$issues[] = 'Ελέγξτε τη βαθμίδα (Γυμνάσιο ή Λύκειο) στην καρτέλα «Σχολείο»· ορίστηκε αυτόματα σε «Γυμνάσιο».';
		}
		if ( ! $s['school_city'] ) {
			$issues[] = 'Συμπληρώστε τον «Τόπο» για να προσυμπληρώνεται στις αιτήσεις.';
		}
		?>
		<div class="wrap sef-admin">
			<h1 class="sef-admin-title">Ηλεκτρονικά έντυπα <span class="sef-ver"><?php echo esc_html( SEF_VERSION ); ?></span></h1>
			<p class="sef-admin-lead">Τα έντυπα συμπληρώνονται και μετατρέπονται σε PDF <b>στη συσκευή του χρήστη</b>. Η ιστοσελίδα δεν αποθηκεύει προσωπικά δεδομένα. Ενσωμάτωση: <code>[school_eforms]</code></p>

			<?php settings_errors( SEF_Settings::OPTION ); ?>
			<?php if ( isset( $_GET['sef_restored'] ) ) : // phpcs:ignore ?>
				<div class="notice notice-success is-dismissible"><p>Επαναφέρθηκαν <?php echo (int) $_GET['sef_restored']; // phpcs:ignore ?> έντυπα στην ενσωματωμένη μορφή. Οι προσαρμοσμένες εκδοχές φυλάχτηκαν στο <code class="sef-code-path"><?php echo esc_html( SEF_Forms::archive_dir() ); ?></code>.</p></div>
			<?php endif; ?>
			<?php
			$attention = SEF_Forms::attention();
			if ( $attention ) :
				?>
				<div class="sef-issues sef-attention"><strong>Μετά την αναβάθμιση σε <?php echo esc_html( SEF_VERSION ); ?>:</strong>
					<ul>
					<?php foreach ( $attention as $aid => $af ) : ?>
						<li><b><?php echo esc_html( $af['label'] ); ?></b> —
							<?php if ( ! empty( $af['conflict'] ) ) : ?>
								ο φάκελος πρόσθετου εντύπου «<?php echo esc_html( $aid ); ?>» έχει πλέον τον ίδιο κωδικό με ενσωματωμένο έντυπο και το αντικαθιστά. Μετονομάστε τον φάκελο και το 'id' με πρόθεμα <code>sx_</code> ή επαναφέρετε το ενσωματωμένο.
							<?php else : ?>
								η προσαρμοσμένη εκδοχή αντιγράφηκε από την έκδοση <?php echo esc_html( $af['base_version'] ); ?>· το ενσωματωμένο έντυπο έχει αλλάξει από τότε.
							<?php endif; ?>
							<a href="<?php echo esc_url( self::restore_url( $aid ) ); ?>">Επαναφορά στο ενσωματωμένο</a>
						</li>
					<?php endforeach; ?>
					</ul>
					<p class="description">Η επαναφορά δεν σβήνει τίποτα: η προσαρμοσμένη εκδοχή μετακινείται στο φάκελο αρχείου. Αν θέλετε να κρατήσετε τις αλλαγές σας στη νέα έκδοση, πατήστε «Επαναφορά» και μετά «Αντιγραφή για προσαρμογή» και περάστε ξανά τις αλλαγές σας.</p>
				</div>
			<?php endif; ?>
			<?php if ( isset( $_GET['sef_copied'] ) ) : // phpcs:ignore ?>
				<div class="notice notice-success is-dismissible"><p>Δημιουργήθηκε ο φάκελος <code class="sef-code-path"><?php echo esc_html( SEF_Forms::custom_dir() . sanitize_key( wp_unslash( $_GET['sef_copied'] ) ) ); // phpcs:ignore ?></code>. Επεξεργαστείτε τα αρχεία <code>form.php</code> και <code>template.php</code> με τη Διαχείριση αρχείων του hosting ή FTP.</p></div>
			<?php elseif ( isset( $_GET['sef_copy_err'] ) ) : // phpcs:ignore ?>
				<div class="notice notice-error is-dismissible"><p><?php echo esc_html( rawurldecode( sanitize_text_field( wp_unslash( $_GET['sef_copy_err'] ) ) ) ); // phpcs:ignore ?></p></div>
			<?php endif; ?>
			<?php if ( isset( $_GET['sef_mail'] ) ) : // phpcs:ignore ?>
				<?php if ( 'ok' === $_GET['sef_mail'] ) : // phpcs:ignore ?>
					<div class="notice notice-success is-dismissible"><p>Το δοκιμαστικό email στάλθηκε στο <b><?php echo esc_html( $s['school_email'] ); ?></b>. Ελέγξτε τα εισερχόμενα (και τα ανεπιθύμητα).</p></div>
				<?php else : ?>
					<div class="notice notice-error is-dismissible"><p>Η αποστολή δοκιμαστικού email απέτυχε. Ελέγξτε τη ρύθμιση SMTP της ιστοσελίδας.<?php echo isset( $_GET['sef_err'] ) ? ' Μήνυμα: ' . esc_html( rawurldecode( sanitize_text_field( wp_unslash( $_GET['sef_err'] ) ) ) ) : ''; // phpcs:ignore ?></p></div>
				<?php endif; ?>
			<?php endif; ?>
			<?php if ( $issues ) : ?>
				<div class="sef-issues"><strong>Για να είναι πλήρης η λειτουργία:</strong><ul><?php foreach ( $issues as $i ) { echo '<li>' . esc_html( $i ) . '</li>'; } ?></ul></div>
			<?php endif; ?>

			<nav class="nav-tab-wrapper sef-tabs">
				<?php foreach ( $tabs as $k => $label ) : ?>
					<a href="#<?php echo esc_attr( $k ); ?>" class="nav-tab<?php echo $k === $tab ? ' nav-tab-active' : ''; ?>" data-sef-tab="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>

			<form method="post" action="options.php" class="sef-admin-form">
				<?php settings_fields( 'sef_group' ); ?>

				<section class="sef-panel" data-sef-panel="school"<?php echo 'school' === $tab ? '' : ' hidden'; ?>>
					<table class="form-table" role="presentation">
						<tr><th scope="row"><label for="sef-school_level">Βαθμίδα</label></th><td>
							<select id="sef-school_level" name="<?php echo self::name( 'school_level' ); // phpcs:ignore ?>">
								<?php foreach ( SEF_Settings::school_levels() as $k => $label ) : ?>
									<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $s['school_level'], $k ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description">Καθορίζει ποια έντυπα εμφανίζονται, τις τάξεις στις λίστες επιλογής και τις «Χρήσιμες υπηρεσίες».</p>
							<?php if ( '1' === $s['level_check'] ) : ?><p class="sef-status-line is-bad">Η βαθμίδα ορίστηκε αυτόματα σε «Γυμνάσιο» από την παλαιότερη ρύθμιση «Γυμνάσιο / Λύκειο». Αν το σχολείο είναι Λύκειο, αλλάξτε την και αποθηκεύστε.</p><?php endif; ?>
						</td></tr>
						<tr><th scope="row"><label for="sef-school_kind">Τύπος σχολείου</label></th><td>
							<select id="sef-school_kind" name="<?php echo self::name( 'school_kind' ); // phpcs:ignore ?>">
								<?php foreach ( SEF_Settings::school_kinds() as $k => $label ) : ?>
									<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $s['school_kind'], $k ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description">Ενεργοποιεί τα ειδικά έντυπα του τύπου (π.χ. Μουσικό), όταν προστεθούν.</p>
						</td></tr>
						<tr><th scope="row"><label for="sef-sections">Τμήματα</label></th><td>
							<textarea id="sef-sections" class="large-text" rows="2" name="<?php echo self::name( 'sections' ); // phpcs:ignore ?>" placeholder="π.χ. Α1, Α2, Β1, Β2, Γ1"><?php echo esc_textarea( $s['sections'] ); ?></textarea>
							<p class="description">Προαιρετικό. Αν συμπληρωθεί, το «Τμήμα» στα έντυπα γίνεται λίστα επιλογής· αλλιώς ελεύθερο κείμενο. Χωρίστε με κόμμα ή νέα γραμμή.</p>
						</td></tr>
						<?php
						self::text( $s, 'school_name', 'Επωνυμία σχολείου', array( 'attr' => 'maxlength="200" required' ) );
						self::text( $s, 'principal', 'Διευθυντής / Διευθύντρια', array( 'desc' => 'Ονοματεπώνυμο· προαιρετικό (θα χρησιμοποιείται στα έντυπα των επόμενων βημάτων).' ) );
						$sug = SEF_Settings::suggest_gen( $s['school_name'] );
						self::text( $s, 'school_gen', 'Επωνυμία σε γενική', array( 'attr' => 'maxlength="200" placeholder="' . esc_attr( $sug ) . '"', 'desc' => 'Όπως συμπληρώνει τη φράση «Τη Διεύθυνση …», π.χ. «του 22ου Δημοτικού Σχολείου Λαμίας». Χρησιμοποιείται στο «ΠΡΟΣ» των αιτήσεων εκπαιδευτικών και, αν ο «Αποδέκτης» μείνει κενός, και στις υπεύθυνες δηλώσεις («Τη Διεύθυνση του …»).' . ( $sug && '' === trim( (string) $s['school_gen'] ) ? ' <button type="button" class="button button-small" data-sef-fill="sef-school_gen" data-value="' . esc_attr( $sug ) . '">Χρήση πρότασης: ' . esc_html( $sug ) . '</button>' : '' ) ) );
						$raw_to = get_option( SEF_Settings::OPTION, array() );
						$s_to   = $s;
						$s_to['school_to'] = isset( $raw_to['school_to'] ) && trim( (string) $raw_to['school_to'] ) !== trim( (string) $s['school_name'] ) ? $raw_to['school_to'] : ''; // η αποθηκευμένη τιμή, όχι η αυτόματη
						self::text( $s_to, 'school_to', 'Αποδέκτης στα έντυπα («ΠΡΟΣ»)', array( 'desc' => 'Όπως θα τυπώνεται στο πεδίο «ΠΡΟΣ», π.χ. «Το 8ο Γυμνάσιο Λαμίας». Αν μείνει κενό: «Τη Διεύθυνση» + η επωνυμία σε γενική (τώρα: «' . esc_html( $s['school_to'] ) . '»), ή αλλιώς η επωνυμία.' ) );
						self::text( $s, 'school_city', 'Τόπος', array( 'desc' => 'Προσυμπληρώνεται στο «Τόπος, ημερομηνία» των αιτήσεων, π.χ. «Λαμία».' ) );
						self::text( $s, 'school_address', 'Διεύθυνση', array( 'desc' => 'Εμφανίζεται στις οδηγίες κατάθεσης.' ) );
						self::text( $s, 'school_phone', 'Τηλέφωνο', array( 'type' => 'tel', 'class' => 'regular-text' ) );
						self::text( $s, 'school_email', 'Email σχολείου', array( 'type' => 'email', 'desc' => 'Εδώ στέλνουν οι γονείς/εκπαιδευτικοί τα βεβαιωμένα έγγραφα. Προτείνεται η επίσημη διεύθυνση @sch.gr.' ) );
						self::text( $s, 'privacy_url', 'Ενημέρωση προστασίας δεδομένων', array( 'type' => 'url', 'attr' => 'placeholder="https://"', 'desc' => 'Σελίδα με την ενημέρωση GDPR του σχολείου. Απαραίτητη για την αποστολή από την ιστοσελίδα.' ) );
						?>
						<tr><th scope="row">Λογότυπο</th><td>
							<div class="sef-logo-field">
								<img src="<?php echo esc_url( $logo ); ?>" alt="" data-sef-logo-preview<?php echo $logo ? '' : ' hidden'; ?>>
								<input type="hidden" name="<?php echo self::name( 'logo_id' ); // phpcs:ignore ?>" value="<?php echo esc_attr( (int) $s['logo_id'] ); ?>" data-sef-logo-id>
								<button type="button" class="button" data-sef-logo-pick>Επιλογή εικόνας</button>
								<button type="button" class="button-link-delete" data-sef-logo-clear<?php echo $logo ? '' : ' hidden'; ?>>Αφαίρεση</button>
							</div>
							<p class="description">Προαιρετικό· εμφανίζεται στην αρχική των εντύπων.</p>
						</td></tr>
					</table>
				</section>

				<section class="sef-panel" data-sef-panel="forms"<?php echo 'forms' === $tab ? '' : ' hidden'; ?>>
					<p>Ενεργοποιήστε τα έντυπα που χρειάζεστε και διαλέξτε <b>πώς υποβάλλεται</b> το καθένα. Τα έντυπα άλλης βαθμίδας ή άλλου τύπου σχολείου εμφανίζονται αχνά και δεν φαίνονται στην ιστοσελίδα, εκτός αν επιλέξετε «Εμφάνιση και σε αυτό το σχολείο».</p>
					<?php
					foreach ( $cats as $cid => $c ) :
						$in_cat = array_filter( $forms, function ( $f ) use ( $cid ) { return $cid === $f['category'] && 'admin' !== $f['source']; } );
						if ( ! $in_cat ) {
							continue;
						}
						?>
						<h2 class="sef-cat-title sef-c-<?php echo esc_attr( $cid ); ?>"><?php echo esc_html( $c['title'] ); ?></h2>
						<table class="widefat sef-forms-table">
							<thead><tr><th class="col-on">Ενεργό</th><th>Έντυπο</th><th class="col-mode">Τρόπος υποβολής</th><th class="col-sc">Shortcode</th></tr></thead>
							<tbody>
							<?php
							foreach ( $forms as $id => $f ) :
								if ( $cid !== $f['category'] || 'admin' === $f['source'] ) {
									continue;
								}
								$conf  = SEF_Settings::form( $id, $f );
								$other = ! SEF_Forms::fits( $f );
								$tomus = SEF_Forms::to_music( $f );
								$base  = SEF_Settings::OPTION . '[forms][' . $id . ']';
								?>
								<tr class="<?php echo $other ? 'is-other-level' : ''; ?>">
									<td class="col-on"><label class="sef-toggle"><input type="checkbox" name="<?php echo esc_attr( $base . '[enabled]' ); ?>" value="1" <?php checked( $conf['enabled'] ); ?>><span class="screen-reader-text">Ενεργό: <?php echo esc_html( $f['label'] ); ?></span></label></td>
									<td><strong><?php echo esc_html( $f['label'] ); ?></strong>
										<?php if ( 'custom' === $f['source'] ) : ?><span class="sef-source sef-source-custom">Πρόσθετο</span>
										<?php elseif ( 'override' === $f['source'] ) : ?><span class="sef-source sef-source-override">Προσαρμοσμένο<?php echo $f['base_version'] ? ' · από ' . esc_html( $f['base_version'] ) : ''; ?></span>
											<?php if ( ! empty( $f['outdated'] ) ) : ?><span class="sef-source sef-source-warn">Παλιά βάση</span><?php endif; ?>
											<?php if ( ! empty( $f['conflict'] ) ) : ?><span class="sef-source sef-source-warn">Σύγκρουση κωδικού</span><?php endif; ?>
										<?php endif; ?>
										<br><span class="description"><?php echo esc_html( $f['desc'] ); ?></span>
										<?php if ( $tomus ) : ?><br><em class="sef-level-note">Στο Δημοτικό απευθύνεται στο «<?php echo esc_html( $s['music_school_name'] ); ?>»· ο γονέας το στέλνει <?php echo is_email( $s['music_school_email'] ) ? 'με email στο ' . esc_html( $s['music_school_email'] ) . ' ή το τυπώνει' : 'τυπωμένο (δεν έχει οριστεί email Μουσικού)'; ?>.</em><?php endif; ?>
										<?php if ( $other && ! empty( $f['primary_to_music'] ) && 'primary' === $s['school_level'] ) : ?><br><em class="sef-level-note">Για να εμφανίζεται στο Δημοτικό, συμπληρώστε παρακάτω «Μουσικό σχολείο της περιοχής».</em><?php endif; ?>
										<?php if ( $other ) : ?><br><em class="sef-level-note">Προορίζεται για: <?php echo esc_html( self::levels_text( $f['levels'], $f['school_kinds'] ) ); ?></em>
											<br><label class="sef-other-level"><input type="checkbox" name="<?php echo esc_attr( $base . '[other_level]' ); ?>" value="1" <?php checked( $conf['other_level'] ); ?>> Εμφάνιση και σε αυτό το σχολείο</label>
										<?php endif; ?></td>
									<td class="col-mode">
										<?php if ( 'link' === $f['kind'] ) : ?>
											<span class="description">Παραπομπή: <a href="<?php echo esc_url( $f['link_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( wp_parse_url( $f['link_url'], PHP_URL_HOST ) ); ?></a></span>
											<input type="hidden" name="<?php echo esc_attr( $base . '[mode]' ); ?>" value="auto">
										<?php else : ?>
										<select name="<?php echo esc_attr( $base . '[mode]' ); ?>">
											<?php foreach ( array_merge( array( 'auto' ), $f['modes'] ) as $m ) : ?>
												<option value="<?php echo esc_attr( $m ); ?>" <?php selected( $conf['mode'], $m ); ?>><?php echo esc_html( $modes[ $m ] ); ?></option>
											<?php endforeach; ?>
										</select>
										<?php
										$eff   = SEF_Settings::effective_mode( $f, $conf['mode'] );
										$names = array( 'upload' => 'αποστολή από την ιστοσελίδα', 'email' => 'οδηγίες αποστολής με email', 'print' => 'εκτύπωση και κατάθεση' );
										?>
										<?php if ( 'sheet' === $f['kind'] ) : ?>
											<select name="<?php echo esc_attr( $base . '[gov]' ); ?>" class="sef-gov-select" aria-label="Ψηφιακή βεβαίωση gov.gr">
												<?php foreach ( SEF_Settings::gov_options() as $gk => $gl ) : ?>
													<option value="<?php echo esc_attr( $gk ); ?>" <?php selected( $conf['gov'], $gk ); ?>><?php echo esc_html( $gl ); ?></option>
												<?php endforeach; ?>
											</select>
										<?php else : ?>
											<input type="hidden" name="<?php echo esc_attr( $base . '[gov]' ); ?>" value="<?php echo esc_attr( $conf['gov'] ); ?>">
										<?php endif; ?>
										<span class="sef-eff<?php echo $eff['reason'] ? ' is-down' : ''; ?>">Στην ιστοσελίδα: <b><?php echo esc_html( $names[ $eff['mode'] ] ); ?></b><?php echo $eff['reason'] ? ' — ' . esc_html( $eff['reason'] ) : ''; ?></span>
										<span class="sef-season">Περίοδος εμφάνισης: από <input type="text" class="sef-dm" name="<?php echo esc_attr( $base . '[from]' ); ?>" value="<?php echo esc_attr( $conf['from'] ); ?>" placeholder="ηη/μμ" maxlength="5" aria-label="Εμφάνιση από (ηη/μμ)"> έως <input type="text" class="sef-dm" name="<?php echo esc_attr( $base . '[to]' ); ?>" value="<?php echo esc_attr( $conf['to'] ); ?>" placeholder="ηη/μμ" maxlength="5" aria-label="Εμφάνιση έως (ηη/μμ)">
											και από <input type="text" class="sef-dm" name="<?php echo esc_attr( $base . '[from2]' ); ?>" value="<?php echo esc_attr( $conf['from2'] ); ?>" placeholder="ηη/μμ" maxlength="5" aria-label="Δεύτερη περίοδος από (ηη/μμ)"> έως <input type="text" class="sef-dm" name="<?php echo esc_attr( $base . '[to2]' ); ?>" value="<?php echo esc_attr( $conf['to2'] ); ?>" placeholder="ηη/μμ" maxlength="5" aria-label="Δεύτερη περίοδος έως (ηη/μμ)">
											<?php if ( '' !== $conf['from'] . $conf['to'] . $conf['from2'] . $conf['to2'] ) : ?><b class="<?php echo SEF_Settings::season_ok( $conf ) ? 'sef-in' : 'sef-out'; ?>"><?php echo SEF_Settings::season_ok( $conf ) ? '✓ εμφανίζεται τώρα' : '✕ εκτός περιόδου — δεν εμφανίζεται τώρα'; ?></b><?php else : ?><small>κενό = πάντα</small><?php endif; ?>
										</span>
										<?php endif; ?>
									</td>
									<td class="col-sc"><code>[school_eforms form="<?php echo esc_html( $id ); ?>"]</code>
										<?php if ( 'sheet' === $f['kind'] ) : ?>
											<?php self::media_field( $base . '[file]', $conf['file'], false, 'Κενό έντυπο (Word/PDF)' ); ?>
										<?php endif; ?>
										<?php if ( 'builtin' === $f['source'] && 'external' !== $f['kind'] ) : ?><br><a href="<?php echo esc_url( self::copy_url( $id ) ); ?>" class="sef-copy-link">Αντιγραφή για προσαρμογή</a><?php endif; ?>
										<?php if ( 'override' === $f['source'] ) : ?><br><a href="<?php echo esc_url( self::restore_url( $id ) ); ?>" class="sef-copy-link">Επαναφορά στο ενσωματωμένο</a><?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					<?php endforeach; ?>
					<div class="sef-card-admin">
						<h2>Πρόσθετα και προσαρμοσμένα υποδείγματα</h2>
						<p>Τα υποδείγματα του σχολείου μπαίνουν στον φάκελο <code class="sef-code-path"><?php echo esc_html( SEF_Forms::custom_dir() ); ?></code>, έξω από το plugin, ώστε <b>να μη σβήνονται στις ενημερώσεις</b>. <?php echo is_dir( SEF_Forms::custom_dir() ) ? '' : '(Ο φάκελος δεν υπάρχει ακόμη· δημιουργείται με τα κουμπιά.)'; ?></p>
						<ul class="sef-modes-help">
							<li><b>Νέο έντυπο:</b> φάκελος με νέο κωδικό. Εμφανίζεται εδώ απενεργοποιημένο, με σήμα «Πρόσθετο».</li>
							<li><b>Προσαρμογή ενσωματωμένου:</b> «Αντιγραφή για προσαρμογή» δίπλα στο έντυπο. Το αντίγραφο (σήμα «Προσαρμοσμένο») αντικαθιστά το αρχικό· αν διαγραφεί, επιστρέφει το αρχικό.</li>
						</ul>
						<p><b>Σε κάθε αναβάθμιση:</b> τα ενσωματωμένα έντυπα αντικαθίστανται πλήρως με τη νέα τους μορφή. Τα πρόσθετα και τα προσαρμοσμένα του σχολείου <b>δεν αγγίζονται</b>· αν κάποιο προσαρμοσμένο βασίζεται σε παλαιότερη μορφή, εμφανίζεται εδώ ειδοποίηση με επιλογή επαναφοράς. Δώστε στα νέα πρόσθετα έντυπα κωδικό που ξεκινά με <code>sx_</code> για να μη συμπέσει ποτέ με μελλοντικό ενσωματωμένο.</p>
						<p><a class="button" href="<?php echo esc_url( self::copy_url( 'example' ) ); ?>">Δημιουργία παραδείγματος</a>
						<?php if ( array_filter( $forms, function ( $f ) { return 'override' === $f['source']; } ) ) : ?>
							<a class="button" href="<?php echo esc_url( self::restore_url( 'all' ) ); ?>" onclick="return confirm('Όλα τα προσαρμοσμένα έντυπα θα επιστρέψουν στην ενσωματωμένη μορφή. Οι εκδοχές σας θα φυλαχτούν στο φάκελο αρχείου. Συνέχεια;');">Επαναφορά όλων στα ενσωματωμένα</a>
						<?php endif; ?>
						<span class="description">Αντιγράφει ένα σχολιασμένο υπόδειγμα υπεύθυνης δήλωσης, που εξηγεί όλα τα διαθέσιμα πεδία. Τα αρχεία επεξεργάζονται με τη Διαχείριση αρχείων του hosting ή FTP.</span></p>
					</div>
					<h2>Ενισχυτική διδασκαλία</h2>
					<table class="form-table" role="presentation">
						<?php
						self::text( $s, 'school_year', 'Σχολικό έτος', array( 'class' => 'small-text sef-w-year', 'attr' => 'placeholder="' . esc_attr( SEF_Settings::school_year() ) . '" pattern="\d{4}-\d{4}"', 'desc' => 'Κενό = αυτόματα (' . esc_html( SEF_Settings::school_year() ) . '). Χρησιμοποιείται σε όλα τα έντυπα.' ) );
						self::text( $s, 'remedial_ops', 'Κωδικός ΟΠΣ πράξης', array( 'class' => 'regular-text' ) );
						?>
						<tr><th scope="row"><label for="sef-remedial_subjects">Μαθήματα</label></th><td><textarea id="sef-remedial_subjects" class="large-text" rows="6" name="<?php echo self::name( 'remedial_subjects' ); // phpcs:ignore ?>"><?php echo esc_textarea( $s['remedial_subjects'] ); ?></textarea><p class="description">Ένα μάθημα ανά γραμμή.</p></td></tr>
					</table>
					<h2>Εποχικά έντυπα</h2>
					<table class="form-table" role="presentation">
						<tr><th scope="row"><label for="sef-allday_zones">Ζώνες Ολοήμερου</label></th><td><textarea id="sef-allday_zones" class="large-text" rows="3" name="<?php echo self::name( 'allday_zones' ); // phpcs:ignore ?>"><?php echo esc_textarea( $s['allday_zones'] ); ?></textarea><p class="description">Μία ανά γραμμή, όπως θα τυπώνονται στην «Εγγραφή στο Ολοήμερο» (κουτάκια).</p></td></tr>
						<tr><th scope="row"><label for="sef-music_school_name">Μουσικό σχολείο της περιοχής</label></th><td><input id="sef-music_school_name" type="text" class="regular-text" maxlength="150" name="<?php echo self::name( 'music_school_name' ); // phpcs:ignore ?>" value="<?php echo esc_attr( $s['music_school_name'] ); ?>" placeholder="π.χ. Μουσικό Γυμνάσιο Λαμίας με Λ.Τ.">
							<p><label for="sef-music_school_email">Email του:</label> <input id="sef-music_school_email" type="email" class="regular-text" name="<?php echo self::name( 'music_school_email' ); // phpcs:ignore ?>" value="<?php echo esc_attr( $s['music_school_email'] ); ?>" placeholder="π.χ. mail@gym-mous-lamias.fth.sch.gr"></p>
							<p class="description">Για <b>Δημοτικά</b>: με το όνομα συμπληρωμένο, τα έντυπα του Μουσικού για τους γονείς της ΣΤ΄ («Αίτηση για τη διαδικασία επιλογής στην Α΄ τάξη», «Συμμετοχή σε εκπαιδευτικό πρόγραμμα») μπορούν να ενεργοποιηθούν και εδώ. Απευθύνονται στο Μουσικό και ο γονέας τα στέλνει με email σε αυτό το email ή τα τυπώνει· ποτέ αποστολή από την ιστοσελίδα.</p></td></tr>
						<tr><th scope="row"><label for="sef-music_program">Μουσικό: εκπαιδευτικό πρόγραμμα</label></th><td><input id="sef-music_program" type="text" class="regular-text" maxlength="150" name="<?php echo self::name( 'music_program' ); // phpcs:ignore ?>" value="<?php echo esc_attr( $s['music_program'] ); ?>"><p class="description">Το όνομα του προγράμματος για μαθητές Δημοτικού (π.χ. «Ρυθμοί φιλίας, μελωδίες ειρήνης»), για το έντυπο «Συμμετοχή σε εκπαιδευτικό πρόγραμμα του σχολείου». Ο γονέας μπορεί να το διορθώσει.</p></td></tr>
						<tr><th scope="row"><label for="sef-meals_time">Σχολικά Γεύματα: παραμονή</label></th><td><input id="sef-meals_time" type="text" class="regular-text" name="<?php echo self::name( 'meals_time' ); // phpcs:ignore ?>" value="<?php echo esc_attr( $s['meals_time'] ); ?>"><p class="description">Το διάστημα μετά το υποχρεωτικό πρωινό πρόγραμμα για το γεύμα των μαθητών εκτός Ολοήμερου, π.χ. «13.15-13.45».</p></td></tr>
						<tr><th scope="row"><label for="sef-languages">Δεύτερες ξένες γλώσσες</label></th><td><textarea id="sef-languages" class="large-text" rows="2" name="<?php echo self::name( 'languages' ); // phpcs:ignore ?>"><?php echo esc_textarea( $s['languages'] ); ?></textarea><p class="description">Μία ανά γραμμή, όπως λέγονται στη δήλωση (π.χ. «Γαλλικά»).</p></td></tr>
					</table>
					<p class="description">Η <b>περίοδος εμφάνισης</b> κάθε εντύπου ορίζεται στη γραμμή του, στον πίνακα παραπάνω (π.χ. από 01/06 έως 30/09, κάθε χρόνο). Εκτός περιόδου το έντυπο δεν εμφανίζεται, ούτε με σύνδεσμο.</p>

					<h2>Δήλωση αποχώρησης μαθητή (Δημοτικό)</h2>
					<table class="form-table" role="presentation">
						<tr><th scope="row"><label for="sef-departure_times">Ώρες αποχώρησης</label></th><td><textarea id="sef-departure_times" class="large-text" rows="4" name="<?php echo self::name( 'departure_times' ); // phpcs:ignore ?>"><?php echo esc_textarea( $s['departure_times'] ); ?></textarea><p class="description">Μία ανά γραμμή, με τη μορφή <code>ώρα | περιγραφή</code>, π.χ. <code>13:15 | μετά τη λήξη του υποχρεωτικού πρωινού προγράμματος</code>. Στη «συνοδευόμενη αποχώρηση» εμφανίζονται ως κουτάκια· στην «αποχώρηση χωρίς συνοδό» ως λίστα ωρών.</p></td></tr>
					</table>
				</section>

				<section class="sef-panel" data-sef-panel="send"<?php echo 'send' === $tab ? '' : ' hidden'; ?>>
					<div class="sef-card-admin">
						<h2>Πώς φτάνουν τα έγγραφα στο σχολείο</h2>
						<ol class="sef-modes-help">
							<li><b>Με email από τον χρήστη (προτείνεται).</b> Η σελίδα δείχνει το email του σχολείου και έτοιμο «Θέμα». Ο χρήστης στέλνει το βεβαιωμένο PDF από το δικό του email. Η ιστοσελίδα δεν αγγίζει κανένα αρχείο.</li>
							<li><b>Από την ιστοσελίδα.</b> Ο χρήστης ανεβάζει το βεβαιωμένο PDF και το WordPress το στέλνει στο email του σχολείου (με το SMTP της ιστοσελίδας). Το αρχείο υπάρχει μόνο προσωρινά και διαγράφεται αμέσως.</li>
							<li><b>Εκτύπωση και κατάθεση.</b> Για έντυπα που κατατίθενται ιδιοχείρως.</li>
						</ol>
					</div>
					<table class="form-table" role="presentation">
						<tr><th scope="row">Αποστολή από την ιστοσελίδα</th><td><?php self::check( $s, 'upload_enabled', 'Ενεργή', 'Ισχύει για όλα τα έντυπα με τρόπο «Αυτόματα» (προεπιλογή) ή «αποστολή από την ιστοσελίδα» στην καρτέλα «Έντυπα». Πριν την ενεργοποιήσετε, κάντε «Δοκιμαστικό email» παρακάτω.' ); ?>
							<?php
							$miss = SEF_Settings::upload_missing();
							if ( '1' === $s['upload_enabled'] && $miss ) :
								?>
								<p class="sef-status-line is-bad">✕ Δεν εμφανίζεται ακόμη στην ιστοσελίδα. Λείπει: <?php echo esc_html( implode( ', ', $miss ) ); ?>.</p>
							<?php
							elseif ( '1' === $s['upload_enabled'] ) :
								$n_up = count( array_filter( SEF_Forms::available(), function ( $f ) { return 'upload' === $f['mode']; } ) );
								?>
								<p class="sef-status-line is-ok">✓ Ενεργή σε <?php echo (int) $n_up; ?> ενεργά έντυπα.</p>
							<?php endif; ?>
						</td></tr>
						<?php
						self::text( $s, 'max_mb', 'Μέγιστο μέγεθος PDF (MB)', array( 'type' => 'number', 'class' => 'small-text', 'attr' => 'min="1" max="10"', 'desc' => 'Ισχύον όριο με βάση και τις ρυθμίσεις του διακομιστή: <b>' . esc_html( size_format( SEF_Settings::max_upload_bytes() ) ) . '</b> (PHP upload_max_filesize: ' . esc_html( ini_get( 'upload_max_filesize' ) ) . ').' ) );
						self::text( $s, 'rate_limit', 'Όριο αποστολών ανά ώρα', array( 'type' => 'number', 'class' => 'small-text', 'attr' => 'min="1" max="100"', 'desc' => 'Ανά διεύθυνση IP. Αν οι εκπαιδευτικοί στέλνουν από το δίκτυο του σχολείου (κοινή IP), αυξήστε το.' ) );
						?>
						<tr><th scope="row">Πίσω από proxy / Cloudflare</th><td><?php self::check( $s, 'trust_proxy', 'Χρήση της πραγματικής IP από τις κεφαλίδες του proxy', 'Ενεργοποιήστε <b>μόνο</b> αν η ιστοσελίδα είναι πίσω από Cloudflare ή reverse proxy· αλλιώς όλοι οι χρήστες μετρώνται ως ένας.' ); ?></td></tr>
						<tr><th scope="row"><label for="sef-sig_policy">Έλεγχος ψηφιακής υπογραφής</label></th><td>
							<select id="sef-sig_policy" name="<?php echo self::name( 'sig_policy' ); // phpcs:ignore ?>">
								<option value="warn" <?php selected( $s['sig_policy'], 'warn' ); ?>>Ενημέρωση: αποστολή με σήμανση αν λείπει υπογραφή</option>
								<option value="require" <?php selected( $s['sig_policy'], 'require' ); ?>>Αυστηρό: απόρριψη PDF χωρίς ψηφιακή υπογραφή</option>
								<option value="off" <?php selected( $s['sig_policy'], 'off' ); ?>>Χωρίς έλεγχο</option>
							</select>
							<p class="description">Εντοπίζει αν το PDF φέρει ψηφιακή υπογραφή (όπως τα έγγραφα του gov.gr). <b>Δεν</b> επαληθεύει τη γνησιότητα· αυτό γίνεται με τον κωδικό του εγγράφου στο gov.gr. Δοκιμάστε πρώτα με ένα πραγματικό βεβαιωμένο αρχείο πριν επιλέξετε «Αυστηρό».</p>
						</td></tr>
						<?php
						self::text( $s, 'gov_cert_url', 'Σελίδα «Ψηφιακή βεβαίωση εγγράφου»', array( 'type' => 'url', 'class' => 'large-text' ) );
						self::text( $s, 'gov_yd_url', 'Σελίδα «Υπεύθυνη δήλωση»', array( 'type' => 'url', 'class' => 'large-text' ) );
						?>
					</table>
				</section>

				<section class="sef-panel" data-sef-panel="look"<?php echo 'look' === $tab ? '' : ' hidden'; ?>>
					<table class="form-table" role="presentation">
						<tr><th scope="row">Αρχική σελίδα εντύπων</th><td>
							<label><input type="radio" name="<?php echo self::name( 'home_layout' ); // phpcs:ignore ?>" value="sections" <?php checked( $s['home_layout'], 'sections' ); ?>> Κεντρική σελίδα με ενότητες <span class="description">(«Γονείς &amp; μαθητές», «Εκπαιδευτικοί»· οι υπεύθυνες δηλώσεις και οι υπηρεσίες για όλους στην κεντρική σελίδα)</span></label><br>
							<label><input type="radio" name="<?php echo self::name( 'home_layout' ); // phpcs:ignore ?>" value="list" <?php checked( $s['home_layout'], 'list' ); ?>> Όλα σε μία λίστα <span class="description">(όλα τα έντυπα μαζί, με φίλτρο κατηγορίας)</span></label>
							<p class="description">Για μία μόνο σελίδα μπορείτε να το αλλάξετε και στο shortcode: <code>[school_eforms layout="list"]</code>. Τα shortcode με <code>group="…"</code> ή <code>form="…"</code> δείχνουν πάντα μόνο τη συγκεκριμένη ομάδα ή το έντυπο.</p>
						</td></tr>
						<tr><th scope="row"><label for="sef-forms-page">Σελίδα με τα έντυπα</label></th><td>
							<?php
							wp_dropdown_pages(
								array(
									'name'              => self::name( 'forms_page' ),
									'id'                => 'sef-forms-page',
									'selected'          => (int) $s['forms_page'],
									'show_option_none'  => '— αυτόματα (η σελίδα με το [school_eforms]) —',
									'option_none_value' => '0',
								)
							);
							?>
							<p class="description">Εδώ οδηγεί ο σύνδεσμος-προβολή <code>[school_eforms_link]</code>, που μπαίνει στην αρχική σελίδα, σε sidebar ή σε widget «Shortcode». Παίρνει τα χρώματα του θέματος (φόντο: «Secondary», έμφαση: «Primary» στο Flatsome) και προσαρμόζεται μόνος του σε στενό χώρο. Βλ. «Οδηγίες» για τις επιλογές του.</p>
						</td></tr>
						<tr><th scope="row"><label for="sef-theme">Χρωματικό θέμα</label></th><td>
							<select id="sef-theme" name="<?php echo self::name( 'theme' ); // phpcs:ignore ?>">
								<option value="light" <?php selected( $s['theme'], 'light' ); ?>>Ανοιχτόχρωμο</option>
								<option value="dark" <?php selected( $s['theme'], 'dark' ); ?>>Σκουρόχρωμο</option>
								<option value="auto" <?php selected( $s['theme'], 'auto' ); ?>>Αυτόματο (όπως η συσκευή του χρήστη)</option>
							</select>
							<p class="description">Αφορά μόνο την εμφάνιση στην ιστοσελίδα. Τα έντυπα και τα PDF είναι πάντα λευκά, Α4.</p>
						</td></tr>
						<tr><th scope="row">Χρώμα</th><td>
							<label><input type="radio" name="<?php echo self::name( 'color_source' ); // phpcs:ignore ?>" value="theme" <?php checked( $s['color_source'], 'theme' ); ?>> Από το θέμα του ιστότοπου <span class="description">(Flatsome: Theme Options → «Secondary color»· αν το θέμα δεν δίνει χρώμα, χρησιμοποιείται το «Κύριο χρώμα»)</span></label><br>
							<label><input type="radio" name="<?php echo self::name( 'color_source' ); // phpcs:ignore ?>" value="custom" <?php checked( $s['color_source'], 'custom' ); ?>> Δικό μου χρώμα (το «Κύριο χρώμα» παρακάτω)</label>
							<p class="description">Σε κάθε περίπτωση η σελίδα ελέγχει την αντίθεση (τουλάχιστον 4,5:1): σε ανοιχτά χρώματα (π.χ. χρυσό) τα γράμματα των κουμπιών γίνονται σκούρα και οι σύνδεσμοι σκουραίνουν.</p>
						</td></tr>
						<tr><th scope="row"><label for="sef-accent">Κύριο χρώμα</label></th><td><input id="sef-accent" type="text" class="sef-color" name="<?php echo self::name( 'accent' ); // phpcs:ignore ?>" value="<?php echo esc_attr( $s['accent'] ); ?>" data-default-color="#0d5eaf"><p class="description">Κουμπιά και επισημάνσεις, όταν επιλέξετε «Δικό μου χρώμα» ή όταν το θέμα δεν δίνει χρώμα.</p></td></tr>
					</table>
					<h2 id="sef-promo-codes">Σύνδεσμος-προβολή για την αρχική σελίδα και τα widget</h2>
					<p class="description">Έτοιμα shortcode για να οδηγήσετε τους επισκέπτες στη σελίδα των εντύπων. Αντιγράψτε το κατάλληλο και επικολλήστε το: στην <b>αρχική σελίδα</b> (στο Flatsome: UX Builder → «Shortcode» ή «Text»), σε <b>sidebar/υποσέλιδο</b> (Εμφάνιση → Widgets → μπλοκ «Shortcode»). Για widget χωρίς shortcode, υπάρχει και το έτοιμο widget <b>«Ηλεκτρονικά έντυπα (School e-Forms)»</b> στο Εμφάνιση → Widgets.</p>
					<table class="widefat striped sef-promo-presets">
						<thead><tr><th>Για</th><th>Shortcode</th><th></th></tr></thead>
						<tbody>
						<?php
						$presets = array(
							array( 'Αρχική σελίδα', 'οριζόντια κάρτα με κουμπί «Είσοδος»', '[school_eforms_link]' ),
							array( 'Αρχική σελίδα — πλήρης', 'και σύνδεσμοι στις ενότητες + «Αυτή την περίοδο»', '[school_eforms_link sections="yes" season="yes"]' ),
							array( 'Sidebar / widget', 'στενή στήλη, τίτλος h3', '[school_eforms_link heading="h3"]' ),
							array( 'Sidebar — με εποχικά', 'και «Αυτή την περίοδο: …»', '[school_eforms_link heading="h3" season="yes"]' ),
							array( 'Συμπαγής', 'μόνο τίτλος και κουμπί (υποσέλιδο, στενός χώρος)', '[school_eforms_link heading="h3" kicker="" text=""]' ),
						);
						foreach ( $presets as $i => $pr ) :
							?>
							<tr><td><strong><?php echo esc_html( $pr[0] ); ?></strong><br><span class="description"><?php echo esc_html( $pr[1] ); ?></span></td>
								<td><input type="text" class="large-text code" readonly value="<?php echo esc_attr( $pr[2] ); ?>" id="sef-preset-<?php echo (int) $i; ?>" aria-label="Shortcode: <?php echo esc_attr( $pr[0] ); ?>"></td>
								<td><button type="button" class="button" data-sef-copy="#sef-preset-<?php echo (int) $i; ?>">Αντιγραφή</button></td></tr>
						<?php endforeach; ?>
						</tbody>
					</table>

					<h3>Φτιάξτε το δικό σας</h3>
					<div class="sef-promo-builder" data-sef-promo-builder>
						<div class="sef-pb-opts">
							<p><label><input type="radio" name="sef_pb_place" value="home" checked> Αρχική σελίδα</label> &nbsp; <label><input type="radio" name="sef_pb_place" value="side"> Sidebar / widget</label></p>
							<p><label><input type="checkbox" data-pb="sections"> Σύνδεσμοι «Γονείς &amp; μαθητές» / «Εκπαιδευτικοί»</label><br>
							<label><input type="checkbox" data-pb="season"> «Αυτή την περίοδο: …» (ανοιχτά εποχικά έντυπα)</label></p>
							<p><label>Μικρή επικεφαλίδα<br><input type="text" class="regular-text" data-pb="kicker" value="Νέα υπηρεσία του σχολείου"></label></p>
							<p><label>Τίτλος<br><input type="text" class="regular-text" data-pb="title" value="Ηλεκτρονικά έντυπα και αιτήσεις"></label></p>
							<p><label>Κείμενο<br><textarea class="large-text" rows="2" data-pb="text">Αιτήσεις και δηλώσεις γονέων, άδειες εκπαιδευτικών και υπεύθυνες δηλώσεις, από το σπίτι.</textarea></label></p>
							<p><label>Κουμπί<br><input type="text" class="regular-text" data-pb="button" value="Είσοδος"></label></p>
							<p><label for="sef-pb-out"><b>Το shortcode σας</b></label><br><input type="text" class="large-text code" readonly id="sef-pb-out" data-pb-out value="[school_eforms_link]"> <button type="button" class="button button-primary" data-sef-copy="#sef-pb-out">Αντιγραφή</button></p>
						</div>
						<div class="sef-pb-preview" data-pb-preview>
							<p class="description">Προεπισκόπηση (στο site παίρνει τα χρώματα του θέματος):</p>
							<div class="sef-pb-frame" data-pb-frame><?php echo SEF_Promo::shortcode( array( 'sections' => 'yes', 'season' => 'yes', 'url' => '#' ) ); // phpcs:ignore ?></div>
						</div>
					</div>
				</section>

				<section class="sef-panel" data-sef-panel="links"<?php echo 'links' === $tab ? '' : ' hidden'; ?>>
					<p><?php self::check( $s, 'show_links', 'Εμφάνιση «Χρήσιμων υπηρεσιών» στην ιστοσελίδα' ); ?></p>
					<p class="description">Στη στήλη <b>«Για»</b> ορίζετε πού εμφανίζεται κάθε υπηρεσία: <b>Όλους</b> = στην κεντρική σελίδα, <b>Γονείς &amp; μαθητές</b> ή <b>Εκπαιδευτικούς</b> = μέσα στην αντίστοιχη ενότητα. (Με τη διάταξη «Όλα σε μία λίστα» εμφανίζονται όλες μαζί στο τέλος.)</p>
					<p class="description">Η ιστοσελίδα δείχνει τις υπηρεσίες για <b>όλες τις βαθμίδες</b> και όσες αφορούν τη βαθμίδα του σχολείου (τώρα: <b><?php echo esc_html( SEF_Settings::school_levels()[ $s['school_level'] ] ); ?></b>). Οι υπόλοιπες εμφανίζονται εδώ αχνά και δεν φαίνονται στο κοινό.</p>
					<table class="widefat sef-links-table" data-sef-links>
						<thead><tr><th class="col-drag" aria-label="Σειρά"></th><th>Τίτλος</th><th>Διεύθυνση (URL)</th><th>Σύντομη περιγραφή</th><th class="col-level">Βαθμίδα</th><th class="col-for">Για</th><th class="col-del"></th></tr></thead>
						<tbody>
							<?php
							foreach ( array_values( (array) $s['links'] ) as $i => $row ) {
								self::link_row( $i, $row, $s['school_level'] );
							}
							?>
						</tbody>
					</table>
					<template data-sef-link-template><?php self::link_row( '__i__', array(), $s['school_level'] ); ?></template>
					<p class="sef-links-actions">
						<button type="button" class="button" data-sef-link-add>+ Προσθήκη υπηρεσίας</button>
						<label class="sef-reset-links"><input type="checkbox" name="<?php echo self::name( 'links_reset' ); // phpcs:ignore ?>" value="1"> Επαναφορά της προεπιλεγμένης λίστας κατά την αποθήκευση</label>
					</p>
					<p class="description">Η σειρά αλλάζει με τα βελάκια ↑ ↓. Οι διευθύνσεις εγγραφών (Νηπιαγωγείο, Α΄ Δημοτικού, ΓΕΛ/ΕΠΑΛ) είναι αυτές που ανακοίνωσε το Υπουργείο για το 2026-2027· ελέγχετέ τες κάθε χρόνο.</p>
				</section>

				<section class="sef-panel" data-sef-panel="docs"<?php echo 'docs' === $tab ? '' : ' hidden'; ?>>
					<p>Έντυπα <b>μόνο για εκτύπωση</b> (αρχείο PDF/Word από τα Πολυμέσα, π.χ. ΑΔΥΜ) και <b>παραπομπές</b> σε υπηρεσίες. Εμφανίζονται ως κάρτες μαζί με τα ηλεκτρονικά έντυπα, χωρίς να χρειάζεται κώδικας ή πίνακας TablePress.</p>
					<table class="widefat sef-docs-table" data-sef-docs>
						<thead><tr><th class="col-on">Ενεργό</th><th>Τίτλος &amp; περιγραφή</th><th class="col-group">Ομάδα / βαθμίδα</th><th class="col-kind">Μορφή</th><th>Αρχεία ή σύνδεσμος</th><th class="col-del"></th></tr></thead>
						<tbody>
							<?php
							foreach ( array_values( (array) $s['docs'] ) as $i => $doc ) {
								self::doc_row( $i, $doc );
							}
							?>
						</tbody>
					</table>
					<template data-sef-doc-template><?php self::doc_row( '__i__', array() ); ?></template>
					<p class="sef-links-actions"><button type="button" class="button" data-sef-doc-add>+ Προσθήκη εγγράφου</button></p>
					<p class="description">Σταθερός σύνδεσμος κάθε εγγράφου: η σελίδα των εντύπων με <code>#sef/κωδικός</code> (ο κωδικός φαίνεται μετά την αποθήκευση).</p>
				</section>

				<section class="sef-panel" data-sef-panel="topics"<?php echo 'topics' === $tab ? '' : ' hidden'; ?>>
					<p>Έτοιμα θέματα της <b>«Αίτησης προς το σχολείο»</b>. Ο γονέας διαλέγει θέμα και συμπληρώνονται αυτόματα το <b>ΘΕΜΑ</b> και το <b>κείμενο μετά το «Παρακαλώ»</b>· μετά τα διορθώνει ελεύθερα. Με τελείες (……) σημειώνετε ό,τι πρέπει να συμπληρώσει ο γονέας. Υπάρχει πάντα και «Άλλο θέμα».</p>
					<table class="widefat sef-leaves-table" data-sef-leaves data-sef-topics>
						<thead><tr><th class="col-drag" aria-label="Σειρά"></th><th class="col-on">Ενεργό</th><th>Θέμα (στη λίστα)</th><th>ΘΕΜΑ της αίτησης</th><th>Κείμενο μετά το «Παρακαλώ»</th><th class="col-del"></th></tr></thead>
						<tbody>
							<?php
							foreach ( array_values( (array) $s['request_topics'] ) as $i => $row ) {
								self::topic_row( $i, $row );
							}
							?>
						</tbody>
					</table>
					<template data-sef-topic-template><?php self::topic_row( '__i__', array() ); ?></template>
					<p class="sef-links-actions">
						<button type="button" class="button" data-sef-topic-add>+ Προσθήκη θέματος</button>
						<label class="sef-reset-links"><input type="checkbox" name="<?php echo self::name( 'topics_reset' ); // phpcs:ignore ?>" value="1"> Επαναφορά της προεπιλεγμένης λίστας κατά την αποθήκευση</label>
					</p>
				</section>

				<section class="sef-panel" data-sef-panel="absences"<?php echo 'absences' === $tab ? '' : ' hidden'; ?>>
					<p>Οι περιπτώσεις της <b>«Δικαιολόγησης απουσιών»</b>. Ο γονέας διαλέγει περίπτωση και στη δήλωση τυπώνεται η <b>φράση</b> της, μαζί με τα συνημμένα ως κουτάκια. Η λίστα αλλάζει με τις εγκυκλίους, γι' αυτό επεξεργάζεται εδώ.</p>
					<ul class="sef-modes-help">
						<li><b>Περίπτωση:</b> όπως εμφανίζεται στη λίστα της οθόνης (δεν τυπώνεται στο PDF).</li>
						<li><b>Φράση:</b> συνεχίζει τη δήλωση μετά τις ημερομηνίες, π.χ. «και παρακαλώ να δικαιολογηθούν οι απουσίες του/της για τις ημέρες αυτές …». Χωρίς διάγνωση.</li>
						<li><b>Βαθμίδα:</b> περιπτώσεις άλλης βαθμίδας εμφανίζονται εδώ αχνά και δεν φαίνονται στο κοινό (π.χ. η «μη προσμέτρηση» αφορά Γυμνάσιο/Λύκειο).</li>
					</ul>
					<table class="widefat sef-leaves-table" data-sef-leaves data-sef-absences>
						<thead><tr><th class="col-drag" aria-label="Σειρά"></th><th class="col-on">Ενεργή</th><th>Περίπτωση (στη λίστα)</th><th>Φράση στη δήλωση</th><th class="col-level">Βαθμίδα</th><th>Συνημμένα</th><th class="col-del"></th></tr></thead>
						<tbody>
							<?php
							foreach ( array_values( (array) $s['absence_cases'] ) as $i => $row ) {
								self::absence_row( $i, $row );
							}
							?>
						</tbody>
					</table>
					<template data-sef-absence-template><?php self::absence_row( '__i__', array() ); ?></template>
					<p class="sef-links-actions">
						<button type="button" class="button" data-sef-absence-add>+ Προσθήκη περίπτωσης</button>
						<label class="sef-reset-links"><input type="checkbox" name="<?php echo self::name( 'absence_reset' ); // phpcs:ignore ?>" value="1"> Επαναφορά της προεπιλεγμένης λίστας κατά την αποθήκευση</label>
					</p>
				</section>

				<section class="sef-panel" data-sef-panel="leaves"<?php echo 'leaves' === $tab ? '' : ' hidden'; ?>>
					<p>Τα είδη άδειας της <b>«Αίτησης άδειας εκπαιδευτικού»</b> — όσες χορηγεί ο/η Διευθυντής/ντρια της σχολικής μονάδας. Η προεπιλογή ακολουθεί τον πίνακα της Δ/νσης Π.Ε. Φθιώτιδας (αριστερή στήλη). Μόνο ονόματα, χωρίς διάρκειες· η λίστα αλλάζει με τις εγκυκλίους, γι' αυτό επεξεργάζεται εδώ.</p>
					<ul class="sef-modes-help">
						<li><b>Είδος:</b> όπως συμπληρώνει τη φράση «Παρακαλώ να μου χορηγήσετε …».</li>
						<li><b>Για το ΘΕΜΑ:</b> σε γενική — γίνεται «Χορήγηση …» στο έντυπο και «Αίτηση …» στο θέμα του email.</li>
						<li><b>«για τους παρακάτω λόγους»:</b> η αίτηση έχει προαιρετικό πεδίο λόγων (όπως η κανονική άδεια στα υποδείγματα).</li>
						<li><b>«για λόγους υγείας»:</b> η αίτηση γράφει σταθερά αυτή τη φράση και δεν έχει πεδίο λόγων (καμία διάγνωση).</li>
						<li><b>Υποχρ. περιγραφή:</b> πεδίο υποχρεωτικό, με ετικέτα «Περιγράψτε το είδος της άδειας» (π.χ. «άλλη άδεια»).</li>
						<li>Χωρίς σημάδι: ούτε λόγοι ούτε περιγραφή — μόνο είδος, ημέρες και συνημμένα.</li>
						<li><b>Συνημμένα:</b> ονόματα δικαιολογητικών, ένα ανά γραμμή· εμφανίζονται ως κουτάκια. Ποτέ πεδίο διάγνωσης.</li>
						<li><b>Ημέρες:</b> σε εργάσιμες μετρούν η κανονική, ασθένειας τέκνου, εξετάσεων / φοιτητική, σχολικής επίδοσης, αιμοδοσίας, συμμετοχής σε δίκη, πατρότητας, γάμου, θανάτου, γυναικολογικού ελέγχου, νοσήματος και αναπηρίας — χωρίς Σάββατο-Κυριακή, αργίες και διακοπές (βλ. παρακάτω). Οι αναρρωτικές, η «άλλη άδεια» και όσα είδη προσθέσετε μετρούν ημερολογιακές ημέρες.</li>
					</ul>
					<table class="widefat sef-leaves-table" data-sef-leaves>
						<thead><tr><th class="col-drag" aria-label="Σειρά"></th><th class="col-on">Ενεργό</th><th>Είδος (στη λίστα)</th><th>Για το ΘΕΜΑ (γενική)</th><th class="col-flags">Κείμενο</th><th>Συνημμένα</th><th class="col-del"></th></tr></thead>
						<tbody>
							<?php
							foreach ( array_values( (array) $s['leave_types'] ) as $i => $row ) {
								self::leave_row( $i, $row );
							}
							?>
						</tbody>
					</table>
					<template data-sef-leave-template><?php self::leave_row( '__i__', array() ); ?></template>
					<p class="sef-links-actions">
						<button type="button" class="button" data-sef-leave-add>+ Προσθήκη είδους</button>
						<label class="sef-reset-links"><input type="checkbox" name="<?php echo self::name( 'leave_reset' ); // phpcs:ignore ?>" value="1"> Επαναφορά της προεπιλεγμένης λίστας κατά την αποθήκευση</label>
					</p>
					<h3>Μη εργάσιμες ημέρες</h3>
					<p>Στις άδειες που μετρούν σε εργάσιμες δεν υπολογίζονται, εκτός από το Σαββατοκύριακο: οι διακοπές Χριστουγέννων (24/12–7/1) και Πάσχα (Μεγάλη Δευτέρα έως Κυριακή του Θωμά), η Καθαρά Δευτέρα, η 25η Μαρτίου, η 1η Μαΐου, η 28η Οκτωβρίου και του Αγίου Πνεύματος (η 30ή Ιανουαρίου μετρά ως εργάσιμη). Οι κινητές υπολογίζονται κάθε χρόνο από την ημερομηνία του Πάσχα.</p>
					<table class="form-table" role="presentation"><tr><th scope="row"><label for="sef-local_holidays">Τοπικές αργίες</label></th><td><textarea id="sef-local_holidays" class="large-text" rows="3" name="<?php echo self::name( 'local_holidays' ); // phpcs:ignore ?>" placeholder="π.χ. 23/04 Πολιούχος"><?php echo esc_textarea( $s['local_holidays'] ); ?></textarea><p class="description">Μία ανά γραμμή: <code>ηη/μμ</code> για κάθε χρόνο ή <code>ηη/μμ/εεεε</code> για μία φορά, προαιρετικά με όνομα (π.χ. <code>23/04 Πολιούχος</code>). Αν πέφτει σε εργάσιμη μέσα στην άδεια, δεν υπολογίζεται. Έως 20 γραμμές.</p></td></tr></table>
					<p class="description">Σύνδεσμος που ανοίγει την αίτηση με έτοιμο είδος: η σελίδα των εντύπων με <code>#sef/teacher_leave?leave_type=sick</code> (ο κωδικός του είδους φαίνεται στη διεύθυνση όταν το επιλέξετε· της αναρρωτικής είναι <code>sick</code>). Οι άδειες που χορηγεί η Διεύθυνση (κύησης, λοχείας, ανατροφής, άνευ αποδοχών κ.ά.) δεν μπαίνουν εδώ: προσθέστε τα υποδείγματά τους ως αρχεία στην καρτέλα «Έγγραφα για εκτύπωση &amp; σύνδεσμοι», με ομάδα «Εκπαιδευτικοί». Κατατίθενται κι αυτά στο σχολείο.</p>
				</section>

				<div class="sef-submit" data-sef-submit<?php echo 'help' === $tab ? ' hidden' : ''; ?>><?php submit_button( 'Αποθήκευση ρυθμίσεων', 'primary', 'submit', false ); ?></div>
			</form>

			<section class="sef-panel" data-sef-panel="send-extra"<?php echo 'send' === $tab ? '' : ' hidden'; ?>>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sef-test-mail">
					<input type="hidden" name="action" value="sef_test_mail">
					<?php wp_nonce_field( 'sef_test_mail' ); ?>
					<p><button type="submit" class="button" <?php disabled( ! is_email( $s['school_email'] ) ); ?>>Δοκιμαστικό email στο <?php echo esc_html( $s['school_email'] ? $s['school_email'] : '(δεν έχει οριστεί)' ); ?></button>
					<span class="description">Ελέγχει ότι το WordPress μπορεί να στείλει email (αποθηκεύστε πρώτα τις αλλαγές).</span></p>
				</form>
			</section>

			<section class="sef-panel" data-sef-panel="help"<?php echo 'help' === $tab ? '' : ' hidden'; ?>>
				<div class="sef-card-admin">
					<h2>Ενσωμάτωση σε σελίδα</h2>
					<table class="widefat striped sef-sc-table">
						<tbody>
							<tr><td><code>[school_eforms]</code></td><td>Όλα τα ενεργά έντυπα: κεντρική σελίδα με ενότητες («Γονείς &amp; μαθητές», «Εκπαιδευτικοί») ή όλα σε μία λίστα, κατά τη ρύθμιση «Εμφάνιση → Αρχική σελίδα εντύπων». Ενότητες με σύνδεσμο: <code>#sef/parents</code>, <code>#sef/teachers</code>.</td></tr>
							<tr><td><code>[school_eforms_link]</code></td><td>Σύνδεσμος-προβολή προς τη σελίδα των εντύπων, για την αρχική σελίδα ή ένα sidebar/widget. Επιλογές: <code>title="…"</code>, <code>text="…"</code>, <code>kicker="…"</code> (μικρή επικεφαλίδα· κενό = καμία), <code>button="Είσοδος"</code>, <code>sections="yes"</code> (και σύνδεσμοι «Γονείς &amp; μαθητές», «Εκπαιδευτικοί»), <code>season="yes"</code> («Αυτή την περίοδο: …»), <code>heading="h3"</code> (σε sidebar), <code>bg="#1f5f63"</code> / <code>highlight="#d4a72c"</code> (δικά σας χρώματα), <code>url="…"</code>.</td></tr>
							<tr><td><code>[school_eforms layout="list"]</code></td><td>Για αυτή τη σελίδα: όλα σε μία λίστα (<code>layout="sections"</code> = ενότητες).</td></tr>
							<tr><td><code>[school_eforms group="parents"]</code></td><td>Μόνο μία ομάδα: <code>parents</code>, <code>students</code>, <code>teachers</code>, <code>declarations</code> (δέχεται και το παλιό <code>category=</code>).</td></tr>
							<tr><td><code>[school_eforms show_intro="no"]</code></td><td>Χωρίς την εισαγωγή (τίτλος, κείμενο) όταν η σελίδα έχει δική της.</td></tr>
							<tr><td><code>[school_eforms show_steps="no"]</code></td><td>Χωρίς το «Πώς λειτουργεί» σε 4 βήματα.</td></tr>
							<tr><td><code>[school_eforms search="yes"]</code></td><td>Αναζήτηση πάντα ορατή (προεπιλογή: όταν υπάρχουν 6+ έντυπα· <code>no</code> = ποτέ).</td></tr>
							<tr><td><code>[school_eforms form="absences"]</code></td><td>Ένα μόνο έντυπο, απευθείας ανοιχτό (κωδικοί στην καρτέλα «Έντυπα»).</td></tr>
						</tbody>
					</table>
					<p class="description">Οι κωδικοί της έκδοσης 0.1.x (<code>parent</code>, <code>leave</code>, <code>declaration</code>· και <code>sick_leave</code>, που ανοίγει πλέον την ενιαία Αίτηση άδειας) εξακολουθούν να λειτουργούν.</p>
				</div>
				<div class="sef-card-admin">
					<h2>Πώς λειτουργεί για τον χρήστη</h2>
					<ol class="sef-modes-help">
						<li>Συμπληρώνει το έντυπο πάνω στο επίσημο υπόδειγμα (υπεύθυνη δήλωση Ν.1599/1986 ή υπηρεσιακή αίτηση).</li>
						<li>Πατά «Λήψη PDF»: δημιουργείται στον browser PDF Α4 με πραγματικό ελληνικό κείμενο.</li>
						<li>Το βεβαιώνει στο gov.gr (υπάρχει οδηγός με εικόνες).</li>
						<li>Το στέλνει στο σχολείο με τον τρόπο που ορίσατε. Μπορεί πρώτα να ελέγξει τοπικά ότι το αρχείο είναι το βεβαιωμένο.</li>
					</ol>
				</div>
				<div class="sef-card-admin">
					<h2>Προστασία δεδομένων</h2>
					<p>Τα στοιχεία των εντύπων δεν φτάνουν ποτέ στον διακομιστή. Μόνο στην «αποστολή από την ιστοσελίδα» διαβιβάζεται το βεβαιωμένο PDF στο email του σχολείου, χωρίς αποθήκευση. Δεν χρησιμοποιούνται cookies, εξωτερικές γραμματοσειρές ή υπηρεσίες τρίτων. Τα πρότυπα είναι ενδεικτικά· ελέγξτε τα πριν τα ενεργοποιήσετε.</p>
				</div>
			</section>
		</div>
		<?php
	}
}
