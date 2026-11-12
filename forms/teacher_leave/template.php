<?php
defined( 'ABSPATH' ) || exit;
/*
 * Αίτηση άδειας εκπαιδευτικού — στη μορφή των υποδειγμάτων των σχολικών μονάδων
 * (Ατομικά Στοιχεία αριστερά, «ΠΡΟΣ Τη Διεύθυνση» δεξιά).
 * Τα είδη άδειας ορίζονται στη διαχείριση (καρτέλα «Άδειες εκπαιδευτικών»).
 */
$types = SEF_Settings::leave_types();
$opts  = array();
$data  = array();
foreach ( $types as $tid => $t ) {
	$opts[ $tid ] = $t['label'];
	$data[ $tid ] = array(
		'gen'    => $t['gen'],
		'health' => $t['health'],
		// Τι μπαίνει μετά τις ημερομηνίες: λόγοι υγείας / προαιρετικοί λόγοι / υποχρεωτική περιγραφή / τίποτα.
		'notes'  => '1' === $t['health'] ? 'health' : ( '1' === $t['describe'] ? 'describe' : ( '1' === ( $t['reasons'] ?? '0' ) ? 'reasons' : '' ) ),
		'text'   => '1' !== $t['health'] && ( '1' === $t['describe'] || '1' === ( $t['reasons'] ?? '0' ) ) ? '1' : '',
		'docs'   => str_replace( "\n", '|', $t['docs'] ),
		// Μόνο εργάσιμες: το Σαββατοκύριακο δεν μετρά στις ημέρες (π.χ. κανονική άδεια).
		'work'   => '1' === ( $t['work'] ?? '0' ) ? '1' : '',
	);
}
?>
<div class="ap-cols" data-days='{"from":"from","to":"to","n":"days","words":"words","case":"gen","mode":"calendar","modeBy":"leave_type"}'>
	<div class="ap-col">
		<div class="ap-head">ΑΙΤΗΣΗ</div>
		<p class="ap-sec">Ατομικά Στοιχεία</p>
		<div class="ap-row"><?php $T->label( 'surname', 'Επώνυμο:' ); ?><?php $T->in( 'surname', array( 'req' => 1, 'autocomplete' => 'family-name' ) ); ?></div>
		<div class="ap-row"><?php $T->label( 'name', 'Όνομα:' ); ?><?php $T->in( 'name', array( 'req' => 1, 'autocomplete' => 'given-name' ) ); ?></div>
		<div class="ap-row"><?php $T->label( 'father', 'Όν. πατέρα:' ); ?><?php $T->in( 'father' ); ?></div>
		<div class="ap-row"><?php $T->label( 'mother', 'Όν. μητέρας:' ); ?><?php $T->in( 'mother' ); ?></div>
		<div class="ap-row"><?php $T->label( 'branch', 'Κλάδος / ειδικότητα:' ); ?><?php $T->in( 'branch', array( 'req' => 1, 'placeholder' => 'π.χ. ΠΕ70 Δασκάλων', 'maxlength' => 60 ) ); ?></div>
		<div class="ap-row"><span>Σχολική μονάδα:</span><span class="sef-fixed-val"><b><?php $T->e( $S['school_name'] ); ?></b></span></div>
		<div class="ap-row"><?php $T->label( 'registry', 'Αριθ. Μητρώου:' ); ?><?php $T->in( 'registry', array( 'maxlength' => 20 ) ); ?></div>
		<div class="ap-row"><?php $T->label( 'address', 'Δ/νση οικίας:' ); ?><?php $T->in( 'address', array( 'autocomplete' => 'street-address' ) ); ?></div>
		<div class="ap-row"><?php $T->label( 'postcode', 'Τ.Κ.:' ); ?><?php $T->in( 'postcode', array( 'inputmode' => 'numeric', 'autocomplete' => 'postal-code', 'maxlength' => 6 ) ); ?></div>
		<div class="ap-row"><?php $T->label( 'phone', 'Τηλ.:' ); ?><?php $T->in( 'phone', array( 'type' => 'tel' ) ); ?></div>
		<div class="ap-row"><?php $T->label( 'mobile', 'Κινητό τηλ.:' ); ?><?php $T->in( 'mobile', array( 'type' => 'tel', 'autocomplete' => 'tel' ) ); ?></div>
		<div class="ap-row"><?php $T->label( 'email', 'Email:' ); ?><?php $T->in( 'email', array( 'type' => 'email', 'autocomplete' => 'email' ) ); ?></div>
		<p class="ap-subject"><b>ΘΕΜΑ: «Χορήγηση <span data-mirror-opt="leave_type" data-opt-attr="gen" data-fallback="άδειας">άδειας</span>»</b></p>
		<div class="ap-place"><?php $T->in( 'place', array( 'req' => 1, 'value' => $T->city(), 'aria-label' => 'Τόπος', 'class' => 'w-m' ) ); ?><span>,</span><?php $T->in( 'date', array( 'type' => 'date', 'req' => 1, 'aria-label' => 'Ημερομηνία', 'data' => array( 'today' => '1' ) ) ); ?></div>
	</div>
	<div class="ap-col">
		<div class="ap-head">ΠΡΟΣ<span class="t">Τη Διεύθυνση</span><span class="s"><?php $T->e( $T->school_gen() ? $T->school_gen() : $S['school_name'] ); ?></span></div>
		<div class="ap-req">
			<p>Σας παρακαλώ να μου χορηγήσετε</p>
			<div class="yd-line"><?php $T->sel( 'leave_type', $opts, array( 'req' => 1, 'class' => 'grow', 'empty' => '— είδος άδειας —', 'aria-label' => 'Είδος άδειας', 'opt_data' => $data, 'data' => array( 'blank-keep' => '1' ) ) ); ?></div>
			<div class="yd-line">
				<?php $T->in( 'words', array( 'class' => 'w-m', 'placeholder' => 'ολογράφως', 'aria-label' => 'Αριθμός ημερών ολογράφως' ) ); ?>
				<span>(</span><?php $T->in( 'days', array( 'req' => 1, 'class' => 'w-xs', 'inputmode' => 'numeric', 'maxlength' => 3, 'aria-label' => 'Αριθμός ημερών' ) ); ?><span>)</span>
				<span data-plural="days" data-one="ημέρας" data-many="ημερών">ημερών</span><span>για το χρονικό διάστημα</span>
			</div>
			<div class="yd-line"><span>από</span><span data-weekday="from"></span><?php $T->in( 'from', array( 'type' => 'date', 'req' => 1, 'class' => 'w-m', 'aria-label' => 'Από' ) ); ?></div>
			<div class="yd-line"><span>έως και</span><span data-weekday="to"></span><?php $T->in( 'to', array( 'type' => 'date', 'req' => 1, 'class' => 'w-m', 'aria-label' => 'Έως και' ) ); ?><span data-show-if="leave_type@notes=" data-blank-hide>.</span><span data-show-if="leave_type@notes=describe" hidden>.</span></div>
			<p data-show-if="leave_type@notes=health" hidden>για λόγους υγείας.</p>
			<div class="ap-notes" data-show-if="leave_type@text=1" data-blank-show hidden>
				<p data-show-if="leave_type@notes=reasons" data-blank-show hidden>για τους παρακάτω λόγους:</p>
				<p data-show-if="leave_type@notes=describe" data-export-text="Είδος άδειας:" hidden>Περιγράψτε το είδος της άδειας:</p>
				<?php
				$T->ta(
					'notes',
					array(
						'rows'        => 3,
						'class'       => 'ap-reasons',
						'maxlength'   => 800,
						'aria-label'  => 'Λόγοι',
						'placeholder' => 'προαιρετικά — χωρίς δεδομένα υγείας',
						'data'        => array(
							'req-if'   => 'leave_type@notes=describe',
							'label-on' => 'Περιγράψτε το είδος της άδειας',
							'ph-on'    => 'υποχρεωτικό — π.χ. το είδος της άδειας, χωρίς δεδομένα υγείας',
						),
					)
				);
				?>
			</div>
			<div class="ap-attach" data-sef-attach="leave_type" data-blank-show hidden><b>Συνημμένα:</b><div class="ap-attach-list" data-sef-attach-list></div>
				<div class="ap-attach-other" data-hide-if="leave_type@notes=health"><?php $T->in( 'att_other', array( 'class' => 'grow', 'maxlength' => 150, 'placeholder' => 'άλλο συνημμένο (προαιρετικά)', 'aria-label' => 'Άλλο συνημμένο' ) ); ?></div>
			</div>
		</div>
		<div class="ap-sign">
			<?php $T->sel( 'signer', array( 'Ο/Η Αιτών/ούσα', 'Ο Αιτών', 'Η Αιτούσα' ), array( 'aria-label' => 'Ο αιτών ή η αιτούσα' ) ); ?>
			<div class="sp"></div>
			<span class="nm" data-mirror="name surname" aria-hidden="true"></span>
		</div>
	</div>
</div>
