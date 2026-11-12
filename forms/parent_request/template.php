<?php
defined( 'ABSPATH' ) || exit;
// Έτοιμα θέματα (διαχείριση → «Θέματα αιτήσεων»): συμπληρώνουν ΘΕΜΑ και κείμενο, που διορθώνονται ελεύθερα.
$topics = SEF_Settings::request_topics();
$opts   = array();
$data   = array();
foreach ( $topics as $tid => $t ) {
	$opts[ $tid ] = $t['label'];
	$data[ $tid ] = array( 'subject' => $t['subject'], 'text' => $t['text'] );
}
?>
<div class="ap-cols">
	<div class="ap-col">
		<div class="ap-head">ΑΙΤΗΣΗ</div>
		<p class="ap-sec">Στοιχεία αιτούντος / αιτούσας</p>
		<div class="ap-row"><?php $T->label( 'surname', 'Επώνυμο:' ); ?><?php $T->in( 'surname', array( 'req' => 1, 'autocomplete' => 'family-name' ) ); ?></div>
		<div class="ap-row"><?php $T->label( 'name', 'Όνομα:' ); ?><?php $T->in( 'name', array( 'req' => 1, 'autocomplete' => 'given-name' ) ); ?></div>
		<div class="ap-row"><?php $T->label( 'role', 'Ιδιότητα:' ); ?><?php $T->sel( 'role', array( 'Γονέας', 'Κηδεμόνας' ), array( 'req' => 1, 'empty' => '— επιλέξτε —' ) ); ?></div>
		<div class="ap-row"><?php $T->label( 'student', 'Μαθητής/τρια:' ); ?><?php $T->in( 'student', array( 'req' => 1, 'placeholder' => 'ονοματεπώνυμο' ) ); ?></div>
		<div class="ap-row"><?php $T->label( 'class', 'Τάξη:' ); ?><?php $T->sel( 'class', $T->classes(), array( 'req' => 1, 'empty' => '—' ) ); ?><?php $T->label( 'section', 'Τμήμα:' ); ?><?php $T->section(); ?></div>
		<div class="ap-row"><?php $T->label( 'address', 'Δ/νση κατοικίας:' ); ?><?php $T->in( 'address', array( 'autocomplete' => 'street-address' ) ); ?></div>
		<div class="ap-row"><?php $T->label( 'mobile', 'Τηλ. κινητό:' ); ?><?php $T->in( 'mobile', array( 'req' => 1, 'type' => 'tel', 'autocomplete' => 'tel' ) ); ?></div>
		<div class="ap-row"><?php $T->label( 'email', 'Email:' ); ?><?php $T->in( 'email', array( 'type' => 'email', 'autocomplete' => 'email' ) ); ?></div>
		<?php if ( $opts ) : ?>
		<div class="ap-row sef-screen-only"><?php $T->label( 'topic', 'Έτοιμο θέμα:' ); ?><?php $T->sel( 'topic', $opts + array( 'other' => 'Άλλο θέμα (γράφω μόνος/η)' ), array( 'empty' => '— διαλέξτε, αν ταιριάζει —', 'opt_data' => $data ) ); ?></div>
		<?php endif; ?>
		<div class="ap-subject"><?php $T->label( 'subject', '<b>ΘΕΜΑ:</b>' ); ?><?php $T->ta( 'subject', array( 'req' => 1, 'rows' => 2, 'maxlength' => 200, 'placeholder' => 'π.χ. Χορήγηση βεβαίωσης φοίτησης', 'data' => array( 'fill-from' => 'topic', 'fill-attr' => 'subject' ) ) ); ?></div>
		<div class="ap-place"><?php $T->in( 'place', array( 'req' => 1, 'value' => $T->city(), 'aria-label' => 'Τόπος', 'class' => 'w-m' ) ); ?><span>,</span><?php $T->in( 'date', array( 'type' => 'date', 'req' => 1, 'aria-label' => 'Ημερομηνία', 'data' => array( 'today' => '1' ) ) ); ?></div>
	</div>
	<div class="ap-col">
		<div class="ap-head">ΠΡΟΣ<span class="s"><?php $T->e( $T->school_to() ); ?></span></div>
		<div class="ap-req">
			<p><b>Παρακαλώ</b></p>
			<?php $T->ta( 'request', array( 'req' => 1, 'rows' => 9, 'maxlength' => 2500, 'aria-label' => 'Τι ζητάτε από το σχολείο', 'placeholder' => 'π.χ. να μου χορηγήσετε βεβαίωση φοίτησης για το παιδί μου, για να την προσκομίσω …', 'data' => array( 'fill-from' => 'topic', 'fill-attr' => 'text' ) ) ); ?>
		</div>
		<div class="ap-sign">
			<?php $T->sel( 'signer', array( 'Ο/Η αιτών/ούσα', 'Ο αιτών', 'Η αιτούσα' ), array( 'aria-label' => 'Ο αιτών ή η αιτούσα' ) ); ?>
			<div class="sp"></div>
			<span class="nm" data-mirror="name surname" aria-hidden="true"></span>
		</div>
	</div>
</div>
