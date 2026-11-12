<?php
defined( 'ABSPATH' ) || exit;
/* Κατά το υπόδειγμα «Αίτηση συμμετοχής στο εκπαιδευτικό πρόγραμμα» (Μουσικό Γυμνάσιο Λαμίας με Λ.Τ.). */
$program = trim( (string) $S['music_program'] );
// Σε ιστοσελίδα Δημοτικού το παιδί φοιτά στο ίδιο το σχολείο: προσυμπλήρωση από την «επωνυμία σε γενική».
$own = ! empty( $F['to_music'] ) ? preg_replace( '/^του\s+/u', '', $T->school_gen() ) : '';
?>
<div class="ap-cols">
	<div class="ap-col">
		<div class="ap-head">ΑΙΤΗΣΗ<span class="t">(Κηδεμόνος)</span></div>
		<div class="ap-row"><?php $T->label( 'surname', 'Επώνυμο:' ); ?><?php $T->in( 'surname', array( 'req' => 1, 'autocomplete' => 'family-name' ) ); ?></div>
		<div class="ap-row"><?php $T->label( 'name', 'Όνομα:' ); ?><?php $T->in( 'name', array( 'req' => 1, 'autocomplete' => 'given-name' ) ); ?></div>
		<div class="ap-row"><?php $T->label( 'father', 'Πατρώνυμο:' ); ?><?php $T->in( 'father' ); ?></div>
		<div class="ap-row"><?php $T->label( 'mother', 'Μητρώνυμο:' ); ?><?php $T->in( 'mother' ); ?></div>
		<div class="ap-row"><?php $T->label( 'address', 'Διεύθυνση:' ); ?><?php $T->in( 'address', array( 'autocomplete' => 'street-address' ) ); ?></div>
		<div class="ap-row"><?php $T->label( 'town', 'Πόλη:' ); ?><?php $T->in( 'town', array( 'autocomplete' => 'address-level2' ) ); ?></div>
		<div class="ap-row"><?php $T->label( 'mobile', 'Τηλέφωνο:' ); ?><?php $T->in( 'mobile', array( 'req' => 1, 'type' => 'tel', 'autocomplete' => 'tel' ) ); ?></div>
		<div class="ap-row"><?php $T->label( 'email', 'Email:' ); ?><?php $T->in( 'email', array( 'type' => 'email', 'autocomplete' => 'email' ) ); ?></div>
		<p class="ap-subject"><b>Θέμα: Συμμετοχή στο εκπαιδευτικό πρόγραμμα «<span data-mirror="program"><?php $T->e( $program ); ?></span>»</b></p>
		<div class="ap-place"><?php $T->in( 'place', array( 'req' => 1, 'value' => $T->city(), 'aria-label' => 'Τόπος', 'class' => 'w-m' ) ); ?><span>,</span><?php $T->in( 'date', array( 'type' => 'date', 'req' => 1, 'aria-label' => 'Ημερομηνία', 'data' => array( 'today' => '1' ) ) ); ?></div>
	</div>
	<div class="ap-col">
		<div class="ap-head">ΠΡΟΣ<span class="s"><?php $T->e( $T->addressee() ); ?></span></div>
		<div class="ap-req">
			<div class="yd-line"><span>Ως γονέας/κηδεμόνας του/της μαθητή/μαθήτριας</span><?php $T->in( 'student', array( 'req' => 1, 'class' => 'grow', 'placeholder' => 'ονοματεπώνυμο μαθητή/τριας', 'aria-label' => 'Ονοματεπώνυμο μαθητή ή μαθήτριας' ) ); ?></div>
			<div class="yd-line"><span>της ΣΤ΄ τάξης του</span><?php $T->in( 'primary_school', array( 'req' => 1, 'class' => 'grow', 'value' => $own, 'placeholder' => 'π.χ. 5ου Δημοτικού Σχολείου Λαμίας', 'aria-label' => 'Δημοτικό Σχολείο όπου φοιτά (σε γενική)' ) ); ?><span>,</span></div>
			<p>επιθυμώ να συμμετάσχει το παιδί μου στο εκπαιδευτικό πρόγραμμα του σχολείου σας με τίτλο:</p>
			<div class="yd-line"><?php $T->in( 'program', array( 'req' => 1, 'class' => 'grow', 'value' => $program, 'maxlength' => 150, 'aria-label' => 'Τίτλος εκπαιδευτικού προγράμματος' ) ); ?></div>
			<div class="yd-line"><span>κατά το τρέχον σχολικό έτος</span><?php $T->in( 'year', array( 'req' => 1, 'class' => 'w-m', 'value' => $T->year(), 'maxlength' => 9, 'aria-label' => 'Σχολικό έτος' ) ); ?></div>
		</div>
		<div class="ap-sign">
			<?php $T->sel( 'signer', array( 'Ο/Η αιτών/ούσα', 'Ο αιτών', 'Η αιτούσα' ), array( 'aria-label' => 'Ο αιτών ή η αιτούσα' ) ); ?>
			<div class="sp"></div>
			<span class="nm" data-mirror="name surname" aria-hidden="true"></span>
		</div>
	</div>
</div>
