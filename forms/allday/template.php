<?php
defined( 'ABSPATH' ) || exit;
$zones = $T->lines( 'allday_zones' );
$times = $T->departure_times();
$T->yd_head( array( 'title' => 'ΥΠΕΥΘΥΝΗ ΔΗΛΩΣΗ – ΑΙΤΗΣΗ' ) );
?>
<div class="yd-decl">
	<?php $T->yd_intro(); ?>
	<p>ως γονέας/κηδεμόνας, παρακαλώ να εγγραφεί στο Ολοήμερο Πρόγραμμα του σχολείου για το σχολικό έτος <?php $T->e( $T->year() ); ?></p>
	<?php $T->student_line(); ?>
	<p>στις παρακάτω ζώνες:</p>
	<div class="yd-opts" role="group" aria-label="Ζώνες Ολοήμερου" data-req-min="1">
		<?php foreach ( $zones as $i => $z ) : ?>
			<?php $T->chk( 'zone_' . $i, $z ); ?>
		<?php endforeach; ?>
	</div>
	<div class="yd-line">
		<span>Ώρα αποχώρησης:</span><?php $T->sel( 'leave_time', array_column( $times, 'time' ), array( 'req' => 1, 'empty' => '—', 'aria-label' => 'Ώρα αποχώρησης' ) ); ?>
		<span>, κατά τις ημέρες</span><?php $T->in( 'days_text', array( 'class' => 'grow', 'value' => 'Δευτέρα έως Παρασκευή', 'aria-label' => 'Ημέρες' ) ); ?><span>.</span>
	</div>
	<div class="yd-line" data-hide-empty><span>Παρατηρήσεις:</span><?php $T->in( 'notes', array( 'class' => 'grow', 'maxlength' => 200, 'placeholder' => 'προαιρετικά — π.χ. διαφορετική ώρα κάποια ημέρα (χωρίς δεδομένα υγείας)', 'aria-label' => 'Παρατηρήσεις' ) ); ?></div>
	<p>Έλαβα γνώση ότι η εγγραφή ολοκληρώνεται μετά την έγκριση του σχολείου, σύμφωνα με τις ισχύουσες διατάξεις, και ότι θα ενημερώνω εγγράφως το σχολείο για κάθε μεταβολή.</p>
</div>
<?php $T->yd_tail(); ?>
