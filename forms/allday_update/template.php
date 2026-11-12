<?php
defined( 'ABSPATH' ) || exit;
/*
 * Μορφή κατά το υπόδειγμα του 22ου Δημ. Λαμίας: ένα πλαίσιο για κάθε παιδί.
 * Ώρες αποχώρησης: όσες από τη ρύθμιση «Ώρες αποχώρησης» είναι μετά τη λήξη του πρωινού (από 14:00).
 */
$times = array_values(
	array_filter(
		array_column( $T->departure_times(), 'time' ),
		function ( $t ) {
			return strcmp( str_pad( $t, 5, '0', STR_PAD_LEFT ), '14:00' ) >= 0;
		}
	)
);
?>
<div class="ol-top"><span><?php $T->e( $S['school_name'] ); ?></span><span>Σχολικό έτος <?php $T->e( $T->year() ); ?></span></div>
<p class="ol-title">ΑΙΤΗΣΗ ΕΠΙΚΑΙΡΟΠΟΙΗΣΗΣ ΕΓΓΡΑΦΗΣ ΜΑΘΗΤΗ/ΩΝ<br>ΣΤΟ ΟΛΟΗΜΕΡΟ ΠΡΟΓΡΑΜΜΑ</p>
<div class="yd-line sef-screen-only"><span>Πόσα παιδιά σας είναι στο Ολοήμερο;</span><?php $T->sel( 'kids', array( '1' => '1', '2' => '2', '3' => '3' ), array( 'aria-label' => 'Αριθμός παιδιών', 'data' => array( 'blank-keep' => '1' ) ) ); ?></div>
<div class="yd-line"><span>Ονοματεπώνυμο γονέα/κηδεμόνα:</span><?php $T->in( 'p_name', array( 'req' => 1, 'class' => 'grow', 'autocomplete' => 'name', 'aria-label' => 'Ονοματεπώνυμο γονέα ή κηδεμόνα' ) ); ?></div>
<?php for ( $i = 1; $i <= 3; $i++ ) : ?>
	<div class="ol-child"<?php echo 2 === $i ? ' data-hide-if="kids=1"' : ( 3 === $i ? ' data-show-if="kids=3" hidden' : '' ); ?>>
		<span class="ol-n"><?php echo (int) $i; ?>ο</span>
		<div class="yd-line">
			<span>Ονοματεπώνυμο μαθητή/τριας:</span><?php $T->in( 'c' . $i . '_name', array( 'req' => 1, 'class' => 'grow', 'aria-label' => $i . 'ο παιδί: ονοματεπώνυμο' ) ); ?>
			<span>Τάξη:</span><?php $T->sel( 'c' . $i . '_class', $T->classes(), array( 'req' => 1, 'empty' => '—', 'aria-label' => $i . 'ο παιδί: τάξη' ) ); ?>
		</div>
		<div class="ol-row"><span>Ώρα αποχώρησης:</span>
			<div class="ol-opts" role="radiogroup" aria-label="<?php echo esc_attr( $i . 'ο παιδί: ώρα αποχώρησης' ); ?>" data-req-group="c<?php echo (int) $i; ?>_time">
				<?php foreach ( $times as $t ) : ?><?php $T->radio( 'c' . $i . '_time', $t, $t ); ?><?php endforeach; ?>
			</div>
		</div>
		<div class="ol-row"><span>Το παιδί κατά την αποχώρηση:</span>
			<div class="ol-opts" role="radiogroup" aria-label="<?php echo esc_attr( $i . 'ο παιδί: τρόπος αποχώρησης' ); ?>" data-req-group="c<?php echo (int) $i; ?>_pick">
				<?php $T->radio( 'c' . $i . '_pick', 'picked', 'παραλαμβάνεται' ); ?><?php $T->radio( 'c' . $i . '_pick', 'alone', 'αποχωρεί μόνο του' ); ?>
			</div>
		</div>
		<div class="ol-row"><span>Επιθυμώ τη συμμετοχή του στο τμήμα Πρόωρης Υποδοχής*:</span>
			<div class="ol-opts" role="radiogroup" aria-label="<?php echo esc_attr( $i . 'ο παιδί: Πρόωρη Υποδοχή' ); ?>" data-req-group="c<?php echo (int) $i; ?>_early">
				<?php $T->radio( 'c' . $i . '_early', 'yes', 'ΝΑΙ' ); ?><?php $T->radio( 'c' . $i . '_early', 'no', 'ΟΧΙ' ); ?>
			</div>
		</div>
	</div>
<?php endfor; ?>
<p class="ol-note">* με την προϋπόθεση της εγγραφής και φοίτησης του μαθητή στο ολοήμερο τμήμα.</p>
<div class="ol-sign">
	<div class="ap-place"><?php $T->in( 'place', array( 'req' => 1, 'value' => $T->city(), 'aria-label' => 'Τόπος', 'class' => 'w-m' ) ); ?><span>,</span><?php $T->in( 'date', array( 'type' => 'date', 'req' => 1, 'aria-label' => 'Ημερομηνία', 'data' => array( 'today' => '1' ) ) ); ?></div>
	<div class="ap-sign">
		<span>Ο γονέας/κηδεμόνας</span>
		<div class="sp"></div>
		<span class="nm" data-mirror="p_name" aria-hidden="true"></span>
		<span class="cap">(ονοματεπώνυμο &amp; υπογραφή)</span>
	</div>
</div>
