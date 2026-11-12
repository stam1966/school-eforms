<?php
defined( 'ABSPATH' ) || exit;
/* Κατά το υπόδειγμα «ΠΑΡΑΡΤΗΜΑ Ι – Υπεύθυνη δήλωση μόνιμης κατοικίας». */
$T->yd_head( array( 'title' => 'ΥΠΕΥΘΥΝΗ ΔΗΛΩΣΗ', 'annex' => 'ΠΑΡΑΡΤΗΜΑ Ι' ) );
?>
<div class="yd-decl">
	<?php $T->yd_intro(); ?>
	<div class="yd-line"><span>Το όνομα του παιδιού μου που φοιτά στην ΣΤ΄ τάξη είναι:</span><?php $T->in( 'student', array( 'req' => 1, 'class' => 'grow', 'placeholder' => 'ονοματεπώνυμο μαθητή/τριας', 'aria-label' => 'Ονοματεπώνυμο παιδιού (ΣΤ΄ τάξη)' ) ); ?></div>
	<div class="yd-line"><span>και η διεύθυνση κατοικίας μου είναι:</span><?php $T->in( 'res_address', array( 'req' => 1, 'class' => 'grow', 'maxlength' => 160, 'placeholder' => 'οδός, αριθμός, περιοχή, Τ.Κ.', 'aria-label' => 'Διεύθυνση κατοικίας' ) ); ?></div>
	<div class="yd-line"><span>Ως συνοδευτικό αποδεικτικό στοιχείο κατοικίας επισυνάπτω:</span><?php $T->in( 'proof', array( 'req' => 1, 'class' => 'grow', 'maxlength' => 160, 'placeholder' => 'π.χ. λογαριασμό ΔΕΚΟ, μισθωτήριο', 'aria-label' => 'Αποδεικτικό κατοικίας' ) ); ?></div>
</div>
<?php $T->yd_tail(); ?>
