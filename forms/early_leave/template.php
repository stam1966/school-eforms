<?php
defined( 'ABSPATH' ) || exit;
$T->yd_head( array( 'title' => 'ΥΠΕΥΘΥΝΗ ΔΗΛΩΣΗ – ΑΙΤΗΣΗ' ) );
?>
<div class="yd-decl">
	<?php $T->yd_intro(); ?>
	<p>ως γονέας/κηδεμόνας παρακαλώ να επιτραπεί η πρόωρη αποχώρηση από το σχολείο του παιδιού μου:</p>
	<?php $T->student_line(); ?>
	<div class="yd-line">
		<span>την</span><?php $T->in( 'leave_date', array( 'type' => 'date', 'req' => 1, 'class' => 'w-m', 'aria-label' => 'Ημερομηνία αποχώρησης' ) ); ?>
		<span>και ώρα</span><?php $T->in( 'leave_time', array( 'type' => 'time', 'req' => 1, 'class' => 'w-s', 'aria-label' => 'Ώρα αποχώρησης' ) ); ?><span>,</span>
	</div>
	<div class="yd-line"><span>για τον εξής λόγο:</span><?php $T->in( 'reason', array( 'req' => 1, 'class' => 'grow', 'aria-label' => 'Λόγος αποχώρησης' ) ); ?></div>
	<p>Το παιδί θα παραληφθεί από:</p>
	<div class="yd-opts" role="radiogroup" aria-label="Ποιος θα παραλάβει" data-req-group="by">
		<?php $T->radio( 'by', 'me', 'εμένα, τον/την δηλούντα/ούσα.' ); ?>
		<?php $T->radio( 'by', 'other', 'το πρόσωπο που αναφέρω παρακάτω:' ); ?>
	</div>
	<div class="yd-line"><span>Ονοματεπώνυμο / σχέση:</span><?php $T->in( 'by_name', array( 'class' => 'grow', 'aria-label' => 'Ονοματεπώνυμο και σχέση προσώπου που θα παραλάβει' ) ); ?></div>
	<p>Αναλαμβάνω την ευθύνη για το παιδί μου από τη στιγμή της αποχώρησής του από το σχολείο.</p>
</div>
<?php $T->yd_tail(); ?>
