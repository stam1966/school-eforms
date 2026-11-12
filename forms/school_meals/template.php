<?php
defined( 'ABSPATH' ) || exit;
/* Κατά το υπόδειγμα «Υπεύθυνη δήλωση γονέα / κηδεμόνα – Σχολικά Γεύματα» (δύο εκδοχές). */
$school = $T->school_gen() ? $T->school_gen() : 'του σχολείου ' . $S['school_name'];
$prog   = 'πρόγραμμα «Σχολικά Γεύματα» που υλοποιεί το Υπουργείο Κοινωνικής Συνοχής &amp; Οικογένειας, σε συνεργασία με το Υπουργείο Παιδείας, Θρησκευμάτων &amp; Αθλητισμού';
$head   = array_filter( array( $S['school_name'], $S['school_email'], $S['school_phone'] ? 'Τηλ. ' . $S['school_phone'] : '' ) );
?>
<div class="ol-top ol-top-c"><span><?php $T->e( implode( '   |   ', $head ) ); ?></span></div>
<p class="ol-title">ΥΠΕΥΘΥΝΗ ΔΗΛΩΣΗ ΓΟΝΕΑ / ΚΗΔΕΜΟΝΑ<br>
	<?php
	$T->sel(
		'allday_in',
		array(
			'yes' => 'ΓΙΑ ΜΑΘΗΤΕΣ ΕΓΓΕΓΡΑΜΜΕΝΟΥΣ ΣΤΟ ΟΛΟΗΜΕΡΟ ΠΡΟΓΡΑΜΜΑ',
			'no'  => 'ΓΙΑ ΜΑΘΗΤΕΣ ΠΟΥ ΔΕΝ ΠΑΡΑΚΟΛΟΥΘΟΥΝ ΟΛΟΗΜΕΡΟ ΠΡΟΓΡΑΜΜΑ',
		),
		array( 'req' => 1, 'empty' => '— το παιδί είναι στο Ολοήμερο; —', 'class' => 'yd-kind', 'aria-label' => 'Είναι το παιδί εγγεγραμμένο στο Ολοήμερο;', 'data' => array( 'blank-keep' => '1', 'blank-need' => '1' ) )
	);
	?>
</p>
<div class="yd-decl">
	<div class="yd-line"><span>Ο/Η κάτωθι υπογεγραμμένος/η</span><?php $T->in( 'p_name', array( 'req' => 1, 'class' => 'grow', 'autocomplete' => 'name', 'aria-label' => 'Ονοματεπώνυμο γονέα ή κηδεμόνα' ) ); ?></div>
	<div class="yd-line"><span>δηλώνω υπεύθυνα ότι</span><?php $T->sel( 'meal', array( 'yes' => 'δέχομαι', 'no' => 'δεν δέχομαι' ), array( 'req' => 1, 'empty' => '— επιλέξτε —', 'aria-label' => 'Συμμετοχή: δέχομαι ή δεν δέχομαι', 'data' => array( 'blank-text' => 'δέχομαι / δεν δέχομαι' ) ) ); ?><span>το παιδί μου</span><?php $T->in( 'student', array( 'req' => 1, 'class' => 'grow', 'placeholder' => 'ονοματεπώνυμο μαθητή/τριας', 'aria-label' => 'Ονοματεπώνυμο παιδιού' ) ); ?><span>,</span></div>
	<div class="yd-line"><span>μαθητής/τρια της</span><?php $T->sel( 'class', $T->classes(), array( 'req' => 1, 'empty' => '—', 'aria-label' => 'Τάξη' ) ); ?><span>Τάξης, <?php echo esc_html( $school ); ?>,</span></div>
	<p data-show-if="allday_in=yes" data-blank-keep hidden>να συμμετέχει στο <?php echo $prog; // phpcs:ignore -- σταθερό κείμενο ?>.</p>
	<p data-show-if="allday_in=no" data-blank-keep hidden>να παραμένει στο Σχολείο πέραν του υποχρεωτικού Πρωινού Προγράμματος (<?php $T->e( $S['meals_time'] ); ?>) και να συμμετέχει στο <?php echo $prog; // phpcs:ignore ?>.</p>
	<div data-hide-if="meal=no">
		<div class="yd-line"><span>Δηλώνω επίσης, ότι το παιδί μου</span><?php $T->sel( 'intol', array( 'no' => 'δεν έχει', 'yes' => 'έχει' ), array( 'data' => array( 'req-if' => 'meal=yes', 'blank-text' => 'δεν έχει / έχει (υπογραμμίστε)' ), 'empty' => '— επιλέξτε —', 'aria-label' => 'Αλλεργία ή δυσανεξία: δεν έχει ή έχει' ) ); ?><span>αλλεργία, δυσανεξία σε συγκεκριμένες τροφές.</span></div>
		<div data-show-if="intol=yes" data-blank-show hidden>
			<p>Σε περίπτωση που έχει κάποια αλλεργία-δυσανεξία, γράψτε, εφόσον γνωρίζετε τις τροφές που την προκαλούν:</p>
			<div class="yd-line"><?php $T->in( 'intol_what', array( 'class' => 'grow', 'maxlength' => 200, 'placeholder' => 'π.χ. γάλα, ξηροί καρποί', 'aria-label' => 'Τροφές που προκαλούν αλλεργία ή δυσανεξία' ) ); ?></div>
		</div>
	</div>
</div>
<div class="ol-sign">
	<div class="ap-place"><?php $T->in( 'place', array( 'req' => 1, 'value' => $T->city(), 'aria-label' => 'Τόπος', 'class' => 'w-m' ) ); ?><span>,</span><?php $T->in( 'date', array( 'type' => 'date', 'req' => 1, 'aria-label' => 'Ημερομηνία', 'data' => array( 'today' => '1' ) ) ); ?></div>
	<div class="ap-sign">
		<span>Ο/Η Δηλών/ούσα</span>
		<div class="sp"></div>
		<span class="nm" data-mirror="p_name" aria-hidden="true"></span>
		<span class="cap">(υπογραφή – ονοματεπώνυμο)</span>
	</div>
</div>
