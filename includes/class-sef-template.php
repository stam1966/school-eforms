<?php
/**
 * Βοηθητικά για τα πρότυπα εντύπων (forms/<id>/template.php).
 * Μέσα στο template είναι διαθέσιμα: $T (αυτό το αντικείμενο), $S (ρυθμίσεις),
 * $F (ορισμός εντύπου). Όλη η έξοδος περνά από esc_*.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SEF_Template {
	public $prefix;
	public $s;
	public $f;

	public function __construct( $prefix, $settings, $form ) {
		$this->prefix = $prefix;
		$this->s      = $settings;
		$this->f      = $form;
	}

	public static function render( $form, $prefix ) {
		if ( empty( $form['template'] ) || ! is_readable( $form['template'] ) ) {
			return '';
		}
		$T = new self( $prefix, SEF_Settings::get(), $form );
		$S = $T->s;
		$F = $form;
		ob_start();
		include $form['template'];
		return ob_get_clean();
	}

	public function id( $name ) {
		return $this->prefix . '-' . preg_replace( '/[^A-Za-z0-9_-]/', '', $name );
	}

	public function e( $text ) {
		echo esc_html( $text );
	}

	/** Το όνομα του σχολείου σε γενική («του 22ου Δημοτικού Σχολείου Λαμίας») ή κενό, αν δεν ορίστηκε. */
	public function school_gen() {
		return trim( (string) $this->s['school_gen'] );
	}

	/** Αποδέκτης: το Μουσικό σχολείο της περιοχής, όταν το έντυπο του Μουσικού συμπληρώνεται σε Δημοτικό· αλλιώς το σχολείο. */
	public function addressee() {
		return ! empty( $this->f['to_music'] ) ? $this->f['to_music'] : $this->s['school_name'];
	}

	public function school_to() {
		return $this->s['school_to'];
	}

	public function city() {
		return $this->s['school_city'];
	}

	/** Ώρες αποχώρησης του σχολείου: array( array( 'time' => '13:15', 'text' => '…' ), … ). */
	public function departure_times() {
		return SEF_Settings::departure_times();
	}

	/** Γραμμές ρύθμισης (π.χ. «allday_zones», «languages»). */
	public function lines( $key ) {
		return SEF_Settings::lines( $key );
	}

	public function year() {
		return SEF_Settings::school_year();
	}

	public function classes() {
		return 'primary' === SEF_Settings::level()
			? array( 'Α΄', 'Β΄', 'Γ΄', 'Δ΄', 'Ε΄', 'ΣΤ΄' )
			: array( 'Α΄', 'Β΄', 'Γ΄' );
	}

	/** Βαθμίδα του σχολείου: primary | gymnasio | lykeio. */
	public function level() {
		return SEF_Settings::level();
	}

	/**
	 * Πεδίο «τμήμα»: λίστα, αν το σχολείο έχει ορίσει τμήματα στις ρυθμίσεις,
	 * αλλιώς ελεύθερο κείμενο.
	 */
	public function section( $o = array() ) {
		$list = SEF_Settings::sections();
		$o    = array_merge( array( 'aria-label' => 'Τμήμα' ), $o );
		if ( $list ) {
			unset( $o['placeholder'], $o['maxlength'] );
			$o['empty'] = '—';
			$this->sel( 'section', $list, $o );
		} else {
			$o = array_merge( array( 'maxlength' => 6, 'placeholder' => 'π.χ. 2' ), $o );
			$this->in( 'section', $o );
		}
	}

	/** Εικόνα από τα κοινά γραφικά του plugin (assets/img/), π.χ. emblem.jpg, espa-2021-2027.jpg. */
	public function img( $file ) {
		return esc_url( SEF_URL . 'assets/img/' . $file );
	}

	/** Αρχείο μέσα στον φάκελο του ίδιου του εντύπου (π.χ. λογότυπο προγράμματος). */
	public function asset( $file ) {
		return esc_url( $this->f['url'] . ltrim( $file, '/' ) );
	}

	private function attrs( $name, $o ) {
		$a = array(
			'id'   => $this->id( $name ),
			'name' => $name,
		);
		foreach ( array( 'placeholder', 'autocomplete', 'inputmode', 'maxlength', 'class', 'aria-label', 'min', 'max', 'pattern', 'title', 'value', 'rows' ) as $k ) {
			if ( isset( $o[ $k ] ) && '' !== $o[ $k ] ) {
				$a[ $k ] = $o[ $k ];
			}
		}
		if ( ! empty( $o['req'] ) ) {
			$a['data-req']      = '1';
			$a['aria-required'] = 'true';
		}
		if ( ! empty( $o['data'] ) && is_array( $o['data'] ) ) {
			foreach ( $o['data'] as $k => $v ) {
				$a[ 'data-' . $k ] = $v;
			}
		}
		$out = '';
		foreach ( $a as $k => $v ) {
			$out .= ' ' . $k . '="' . esc_attr( $v ) . '"';
		}
		return $out;
	}

	/** Πεδίο κειμένου. $o: req, type, placeholder, class, maxlength, autocomplete, inputmode, aria-label, data[] */
	public function in( $name, $o = array() ) {
		$type = isset( $o['type'] ) ? $o['type'] : 'text';
		if ( ! isset( $o['maxlength'] ) && in_array( $type, array( 'text', 'tel', 'email' ), true ) ) {
			$o['maxlength'] = 120;
		}
		echo '<input type="' . esc_attr( $type ) . '"' . $this->attrs( $name, $o ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput -- attrs() escapes.
	}

	/** Λίστα επιλογών: $options = [ 'τιμή', ... ] ή [ 'τιμή' => 'κείμενο' ]. Το $o['empty'] προσθέτει κενή πρώτη επιλογή. */
	public function sel( $name, $options, $o = array() ) {
		echo '<select' . $this->attrs( $name, $o ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput
		if ( isset( $o['empty'] ) ) {
			echo '<option value="">' . esc_html( $o['empty'] ) . '</option>';
		}
		$assoc = array_keys( $options ) !== range( 0, count( $options ) - 1 );
		foreach ( $options as $k => $label ) {
			$val  = $assoc ? $k : $label;
			$data = '';
			// $o['opt_data'][ τιμή ] = array( 'gen' => '…' ) → data-gen="…" στο <option>
			if ( ! empty( $o['opt_data'][ $val ] ) && is_array( $o['opt_data'][ $val ] ) ) {
				foreach ( $o['opt_data'][ $val ] as $dk => $dv ) {
					$data .= ' data-' . sanitize_key( $dk ) . '="' . esc_attr( $dv ) . '"';
				}
			}
			echo '<option value="' . esc_attr( $val ) . '"' . $data . '>' . esc_html( $label ) . '</option>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo '</select>';
	}

	public function ta( $name, $o = array() ) {
		if ( ! isset( $o['maxlength'] ) ) {
			$o['maxlength'] = 2500;
		}
		if ( ! isset( $o['rows'] ) ) {
			$o['rows'] = 3;
		}
		echo '<textarea' . $this->attrs( $name, $o ) . '></textarea>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}

	public function chk( $name, $label, $o = array() ) {
		$o['value'] = isset( $o['value'] ) ? $o['value'] : '1';
		$o['id_suffix'] = isset( $o['id_suffix'] ) ? $o['id_suffix'] : '';
		$attrs = $this->attrs( $name, $o );
		if ( $o['id_suffix'] ) {
			$attrs = str_replace( 'id="' . esc_attr( $this->id( $name ) ) . '"', 'id="' . esc_attr( $this->id( $name . '-' . $o['id_suffix'] ) ) . '"', $attrs );
		}
		$type = ! empty( $o['radio'] ) ? 'radio' : 'checkbox';
		echo '<label class="sef-chk"><input type="' . esc_attr( $type ) . '"' . $attrs . '><span class="sef-box" aria-hidden="true"></span><span>' . esc_html( $label ) . '</span></label>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}

	public function radio( $name, $value, $label ) {
		$this->chk( $name, $label, array( 'radio' => true, 'value' => $value, 'id_suffix' => sanitize_key( $value ) ) );
	}

	public function label( $name, $text, $class = '' ) {
		echo '<label class="' . esc_attr( $class ) . '" for="' . esc_attr( $this->id( $name ) ) . '"><span>' . wp_kses( $text, array( 'sup' => array(), 'b' => array() ) ) . '</span></label>';
	}

	/* ---------------- Υπεύθυνη Δήλωση (Ν.1599/1986) ---------------- */

	/** Κεφαλίδα και πίνακας στοιχείων δηλούντος. $o['title'] π.χ. «ΥΠΕΥΘΥΝΗ ΔΗΛΩΣΗ – ΑΙΤΗΣΗ». */
	public function yd_head( $o = array() ) {
		$title = isset( $o['title'] ) ? $o['title'] : 'ΥΠΕΥΘΥΝΗ ΔΗΛΩΣΗ';
		?>
		<?php if ( ! empty( $o['annex'] ) ) : ?><div class="yd-annex"><?php $this->e( $o['annex'] ); ?></div><?php endif; ?>
		<div class="yd-proto"><span>Αριθμ. Πρωτ.: ……………………</span><span>Ημερομηνία: ……………………</span></div>
		<img class="yd-emblem" src="<?php echo $this->img( 'emblem.jpg' ); // phpcs:ignore ?>" alt="Εθνόσημο" width="50" height="48">
		<p class="yd-state">ΕΛΛΗΝΙΚΗ ΔΗΜΟΚΡΑΤΙΑ</p>
		<p class="yd-title"><?php $this->e( $title ); ?><?php if ( ! empty( $o['title_cb'] ) && is_callable( $o['title_cb'] ) ) { echo ' '; call_user_func( $o['title_cb'], $this ); } ?></p>
		<p class="yd-sub">(άρθρο 8 Ν.1599/1986)</p>
		<div class="yd-box">Η ακρίβεια των στοιχείων που υποβάλλονται με αυτή τη δήλωση μπορεί να ελεγχθεί με βάση το αρχείο άλλων υπηρεσιών (άρθρο 8 παρ. 4 Ν. 1599/1986)</div>
		<div class="yd-grid">
			<div class="yd-row"><div class="yd-cell pros"><span class="yd-lab"><span>ΠΡΟΣ<sup>(1)</sup>:</span></span><span class="yd-fixed"><?php $this->e( $this->school_to() ); ?></span></div></div>
			<div class="yd-row c2">
				<div class="yd-cell"><?php $this->label( 'name', 'Ο – Η Όνομα:', 'yd-lab' ); ?><span class="v"><?php $this->in( 'name', array( 'req' => 1, 'autocomplete' => 'given-name' ) ); ?></span></div>
				<div class="yd-cell"><?php $this->label( 'surname', 'Επώνυμο:', 'yd-lab' ); ?><span class="v"><?php $this->in( 'surname', array( 'req' => 1, 'autocomplete' => 'family-name' ) ); ?></span></div>
			</div>
			<div class="yd-row"><div class="yd-cell"><?php $this->label( 'father', 'Όνομα και Επώνυμο Πατέρα:', 'yd-lab w' ); ?><span class="v"><?php $this->in( 'father' ); ?></span></div></div>
			<div class="yd-row"><div class="yd-cell"><?php $this->label( 'mother', 'Όνομα και Επώνυμο Μητέρας:', 'yd-lab w' ); ?><span class="v"><?php $this->in( 'mother' ); ?></span></div></div>
			<div class="yd-row"><div class="yd-cell"><?php $this->label( 'birth', 'Ημερομηνία γέννησης<sup>(2)</sup>:', 'yd-lab w' ); ?><span class="v"><?php $this->in( 'birth', array( 'placeholder' => 'ολογράφως, π.χ. δώδεκα Μαρτίου 1982' ) ); ?></span></div></div>
			<div class="yd-row"><div class="yd-cell"><?php $this->label( 'birthplace', 'Τόπος Γέννησης:', 'yd-lab w' ); ?><span class="v"><?php $this->in( 'birthplace' ); ?></span></div></div>
			<div class="yd-row c2">
				<div class="yd-cell"><?php $this->label( 'adt', 'Αριθμός Δελτίου Ταυτότητας:', 'yd-lab w' ); ?><span class="v"><?php $this->in( 'adt', array( 'req' => 1, 'maxlength' => 20 ) ); ?></span></div>
				<div class="yd-cell"><?php $this->label( 'tel', 'Τηλ:', 'yd-lab' ); ?><span class="v"><?php $this->in( 'tel', array( 'req' => 1, 'type' => 'tel', 'autocomplete' => 'tel', 'maxlength' => 30 ) ); ?></span></div>
			</div>
			<div class="yd-row c4">
				<div class="yd-cell"><?php $this->label( 'city', 'Τόπος Κατοικίας:', 'yd-lab' ); ?><span class="v"><?php $this->in( 'city', array( 'autocomplete' => 'address-level2' ) ); ?></span></div>
				<div class="yd-cell"><?php $this->label( 'street', 'Οδός:', 'yd-lab' ); ?><span class="v"><?php $this->in( 'street', array( 'autocomplete' => 'address-line1' ) ); ?></span></div>
				<div class="yd-cell"><?php $this->label( 'no', 'Αριθ:', 'yd-lab' ); ?><span class="v"><?php $this->in( 'no', array( 'maxlength' => 8 ) ); ?></span></div>
				<div class="yd-cell"><?php $this->label( 'tk', 'ΤΚ:', 'yd-lab' ); ?><span class="v"><?php $this->in( 'tk', array( 'inputmode' => 'numeric', 'autocomplete' => 'postal-code', 'maxlength' => 6 ) ); ?></span></div>
			</div>
			<div class="yd-row c2e">
				<div class="yd-cell"><?php $this->label( 'fax', 'Αρ. Τηλεομοιοτύπου (Fax):', 'yd-lab' ); ?><span class="v"><?php $this->in( 'fax', array( 'maxlength' => 30 ) ); ?></span></div>
				<div class="yd-cell"><?php $this->label( 'email', 'Δ/νση Ηλεκτρ. Ταχυδρομείου (Email):', 'yd-lab' ); ?><span class="v"><?php $this->in( 'email', array( 'type' => 'email', 'autocomplete' => 'email' ) ); ?></span></div>
			</div>
		</div>
		<?php
	}

	/** Αρχή του κειμένου δήλωσης. $extra: π.χ. αναφορά σε ΚΥΑ. */
	public function yd_intro( $extra = '' ) {
		echo '<p>Με ατομική μου ευθύνη και γνωρίζοντας τις κυρώσεις <sup>(3)</sup>, που προβλέπονται από τις διατάξεις της παρ. 6 του άρθρου 22 του Ν. 1599/1986' . ( $extra ? ', ' . esc_html( $extra ) : '' ) . ', δηλώνω ότι:</p>';
	}

	/** Υπογραφή και υποσημειώσεις. $o['notes_extra'] HTML (ήδη ασφαλές), $o['espa'] bool. */
	public function yd_tail( $o = array() ) {
		?>
		<div class="yd-sign">
			<div class="d"><span>Ημερομηνία:</span><?php $this->in( 'date', array( 'type' => 'date', 'req' => 1, 'class' => 'w-m', 'aria-label' => 'Ημερομηνία δήλωσης', 'data' => array( 'today' => '1' ) ) ); ?></div>
			<?php $this->sel( 'declarant', array( 'Ο – Η Δηλ.', 'Ο Δηλών', 'Η Δηλούσα' ), array( 'aria-label' => 'Ο δηλών ή η δηλούσα' ) ); ?>
			<div class="sp"></div>
			<span class="nm" data-mirror="name surname" aria-hidden="true"></span>
			<span class="cap">(Υπογραφή)</span>
		</div>
		<div class="yd-notes">
			<p>(1) Αναγράφεται από τον ενδιαφερόμενο πολίτη ή Αρχή ή η Υπηρεσία του δημόσιου τομέα, που απευθύνεται η αίτηση.</p>
			<p>(2) Αναγράφεται ολογράφως.</p>
			<p>(3) «Όποιος εν γνώσει του δηλώνει ψευδή γεγονότα ή αρνείται ή αποκρύπτει τα αληθινά με έγγραφη υπεύθυνη δήλωση του άρθρου 8 τιμωρείται με φυλάκιση τουλάχιστον τριών μηνών. Εάν ο υπαίτιος αυτών των πράξεων σκόπευε να προσπορίσει στον εαυτόν του ή σε άλλον περιουσιακό όφελος βλάπτοντας τρίτον ή σκόπευε να βλάψει άλλον, τιμωρείται με κάθειρξη μέχρι 10 ετών.</p>
			<p>(4) Σε περίπτωση ανεπάρκειας χώρου η δήλωση συνεχίζεται στην πίσω όψη της και υπογράφεται από τον δηλούντα ή την δηλούσα.</p>
			<?php if ( ! empty( $o['espa'] ) ) : ?>
				<img class="yd-espa" src="<?php echo $this->img( 'espa-2021-2027.jpg' ); // phpcs:ignore ?>" alt="Με τη συγχρηματοδότηση της Ευρωπαϊκής Ένωσης · ΕΣΠΑ 2021-2027" width="640" height="58">
			<?php endif; ?>
		</div>
		<?php
	}

	/** Γραμμή «ο μαθητής / η μαθήτρια … της τάξης … τμήμα …». */
	public function student_line( $lead = '' ) {
		?>
		<div class="yd-line">
			<?php if ( $lead ) : ?><span><?php $this->e( $lead ); ?></span><?php endif; ?>
			<?php $this->sel( 'student_gender', array( 'Ο μαθητής', 'Η μαθήτρια' ), array( 'req' => 1, 'empty' => '— επιλέξτε —', 'aria-label' => 'Μαθητής ή μαθήτρια' ) ); ?>
			<?php $this->in( 'student', array( 'req' => 1, 'class' => 'grow', 'placeholder' => 'ονοματεπώνυμο μαθητή/τριας', 'aria-label' => 'Ονοματεπώνυμο μαθητή ή μαθήτριας' ) ); ?>
			<span>της</span>
			<?php $this->sel( 'class', $this->classes(), array( 'req' => 1, 'empty' => '—', 'aria-label' => 'Τάξη' ) ); ?>
			<span>τάξης, τμήμα</span>
			<?php $this->section( array( 'req' => 1, 'class' => 'w-s' ) ); ?>
		</div>
		<?php
	}
}
