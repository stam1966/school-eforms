<?php defined( 'ABSPATH' ) || exit; ?>
<div class="ap-cols">
	<div class="ap-col">
		<div class="ap-head">ΑΙΤΗΣΗ</div>
		<p class="ap-sec">Στοιχεία εκπαιδευτικού</p>
		<div class="ap-row"><?php $T->label( 'surname', 'Επώνυμο:' ); ?><?php $T->in( 'surname', array( 'req' => 1, 'autocomplete' => 'family-name' ) ); ?></div>
		<div class="ap-row"><?php $T->label( 'name', 'Όνομα:' ); ?><?php $T->in( 'name', array( 'req' => 1, 'autocomplete' => 'given-name' ) ); ?></div>
		<div class="ap-row"><?php $T->label( 'father', 'Όνομα πατέρα:' ); ?><?php $T->in( 'father' ); ?></div>
		<div class="ap-row"><?php $T->label( 'branch', 'Κλάδος/Ειδικότητα:' ); ?><?php $T->in( 'branch', array( 'req' => 1, 'maxlength' => 60 ) ); ?></div>
		<div class="ap-row"><?php $T->label( 'registry', 'Αριθμός μητρώου:' ); ?><?php $T->in( 'registry', array( 'maxlength' => 20 ) ); ?></div>
		<div class="ap-row"><?php $T->label( 'mobile', 'Τηλέφωνο:' ); ?><?php $T->in( 'mobile', array( 'type' => 'tel', 'autocomplete' => 'tel' ) ); ?></div>
		<div class="ap-row"><?php $T->label( 'email', 'Email:' ); ?><?php $T->in( 'email', array( 'type' => 'email', 'autocomplete' => 'email' ) ); ?></div>
		<div class="ap-subject"><?php $T->label( 'subject', '<b>ΘΕΜΑ:</b>' ); ?><?php $T->ta( 'subject', array( 'req' => 1, 'rows' => 2, 'maxlength' => 200, 'placeholder' => 'π.χ. Χορήγηση βεβαίωσης προϋπηρεσίας' ) ); ?></div>
		<div class="ap-place"><?php $T->in( 'place', array( 'req' => 1, 'value' => $T->city(), 'aria-label' => 'Τόπος', 'class' => 'w-m' ) ); ?><span>,</span><?php $T->in( 'date', array( 'type' => 'date', 'req' => 1, 'aria-label' => 'Ημερομηνία', 'data' => array( 'today' => '1' ) ) ); ?></div>
	</div>
	<div class="ap-col">
		<?php if ( $T->school_gen() ) : ?>
		<div class="ap-head">ΠΡΟΣ<span class="t">Τη Διεύθυνση</span><span class="s"><?php $T->e( $T->school_gen() ); ?></span></div>
		<?php else : ?>
		<div class="ap-head">ΠΡΟΣ<span class="t">Τον/Τη Διευθυντή/ντρια</span><span class="s"><?php $T->e( $T->school_to() ); ?></span></div>
		<?php endif; ?>
		<div class="ap-req">
			<p><b>Παρακαλώ</b></p>
			<?php $T->ta( 'request', array( 'req' => 1, 'rows' => 9, 'maxlength' => 2500, 'aria-label' => 'Τι ζητάτε' ) ); ?>
		</div>
		<div class="ap-sign">
			<?php $T->sel( 'signer', array( 'Ο/Η αιτών/ούσα', 'Ο αιτών', 'Η αιτούσα' ), array( 'aria-label' => 'Ο αιτών ή η αιτούσα' ) ); ?>
			<div class="sp"></div>
			<span class="nm" data-mirror="name surname" aria-hidden="true"></span>
		</div>
	</div>
</div>
