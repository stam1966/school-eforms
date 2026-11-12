<?php
defined( 'ABSPATH' ) || exit;
/*
 * Κατά τα υποδείγματα ΣΥΝ.2 «Αίτηση (γονέων/κηδεμόνων)» και ΣΥΝ.3 «Υπεύθυνη δήλωση» των Μουσικών Σχολείων.
 * Σχολικό έτος: το επόμενο (η αίτηση υποβάλλεται πριν αρχίσει).
 */
$cur  = $T->year();
$next = preg_match( '/^(\d{4})-(\d{4})$/', $cur, $m ) ? ( (int) $m[1] + 1 ) . '-' . ( (int) $m[2] + 1 ) : $cur;
$parent = function ( $n ) use ( $T ) {
	$req = '1' === $n ? 1 : 0;
	?>
	<div class="ap-row"><?php $T->label( "p{$n}_surname", 'Επώνυμο:' ); ?><?php $T->in( "p{$n}_surname", array( 'req' => $req ) ); ?></div>
	<div class="ap-row"><?php $T->label( "p{$n}_name", 'Όνομα:' ); ?><?php $T->in( "p{$n}_name", array( 'req' => $req ) ); ?></div>
	<div class="ap-row"><?php $T->label( "p{$n}_rel", 'Σχέση με τον/τη μαθητή/τρια:' ); ?><?php $T->in( "p{$n}_rel", array( 'req' => $req, 'placeholder' => 'π.χ. μητέρα' ) ); ?></div>
	<div class="ap-row"><?php $T->label( "p{$n}_tel", 'Τηλέφωνο:' ); ?><?php $T->in( "p{$n}_tel", array( 'req' => $req, 'type' => 'tel' ) ); ?></div>
	<div class="ap-row"><?php $T->label( "p{$n}_email", 'E-mail:' ); ?><?php $T->in( "p{$n}_email", array( 'type' => 'email' ) ); ?></div>
	<?php
};
?>
<div class="ma-top"><span class="ma-annex">ΣΥΝ.2</span><span class="ma-proto">Αρ. Πρωτ.: ……………………</span></div>
<div class="ap-cols ma-cols">
	<div class="ap-col">
		<div class="ap-head ma-head">ΑΙΤΗΣΗ<span class="t">(γονέων/κηδεμόνων)</span></div>
		<p class="ap-sec">ΣΤΟΙΧΕΙΑ ΓΟΝΕΩΝ/ΚΗΔΕΜΟΝΩΝ</p>
		<p class="ma-n">i)</p>
		<?php $parent( '1' ); ?>
		<p class="ma-note">Τα παρακάτω στοιχεία (ii) δεν συμπληρώνονται ΜΟΝΟ στην περίπτωση όπου υφίσταται δικαστική απόφαση, σύμφωνα με την υπό στοιχεία Φ1/117162/ΓΔ4/20-9-2021 εγκύκλιο του Υπουργείου Παιδείας.</p>
		<p class="ma-n">ii)</p>
		<?php $parent( '2' ); ?>
	</div>
	<div class="ap-col">
		<div class="ap-head ma-head ma-date"><span class="ma-dl">Ημερομηνία:</span><?php $T->in( 'date', array( 'type' => 'date', 'req' => 1, 'class' => 'w-m', 'aria-label' => 'Ημερομηνία', 'data' => array( 'today' => '1' ) ) ); ?></div>
		<div class="ap-req">
			<p><b>Προς τη Διεύθυνση του Μουσικού Σχολείου</b></p>
			<p class="ma-school"><?php $T->e( $T->addressee() ); ?></p>
			<div class="yd-line"><span>Παρακαλώ όπως δεχθείτε στη διαδικασία επιλογής μαθητών/μαθητριών για την Α΄ Τάξη του Μ.Σ. για το σχολικό έτος</span><?php $T->in( 'adm_year', array( 'req' => 1, 'class' => 'w-m', 'value' => $next, 'maxlength' => 9, 'aria-label' => 'Σχολικό έτος' ) ); ?><span>τον/την</span></div>
			<div class="yd-line"><?php $T->in( 'student', array( 'req' => 1, 'class' => 'grow', 'placeholder' => 'όνομα και επώνυμο μαθητή/τριας', 'aria-label' => 'Όνομα και επώνυμο υποψήφιου μαθητή ή μαθήτριας' ) ); ?></div>
			<div class="yd-line"><span>που φοιτά στην ΣΤ΄ Τάξη και πρόκειται να απολυθεί από το</span><?php $T->in( 'primary_school', array( 'req' => 1, 'class' => 'grow', 'placeholder' => 'π.χ. 5ο Λαμίας', 'aria-label' => 'Δημοτικό Σχολείο όπου φοιτά' ) ); ?></div>
			<div class="yd-line"><?php $T->sel( 'school_type', array( 'δημόσιο', 'ιδιωτικό' ), array( 'req' => 1, 'empty' => '— δημόσιο / ιδιωτικό —', 'aria-label' => 'Δημόσιο ή ιδιωτικό σχολείο', 'data' => array( 'blank-text' => 'δημόσιο / ιδιωτικό' ) ) ); ?><span>Δημοτικό Σχολείο.</span></div>
		</div>
		<div class="ma-signs">
			<p class="ma-cap">Οι αιτούντες<br>(υπογραφές)</p>
			<div class="ma-two">
				<div><div class="sp"></div><span class="nm" data-mirror="p1_name p1_surname" aria-hidden="true"></span></div>
				<div><div class="sp"></div><span class="nm" data-mirror="p2_name p2_surname" aria-hidden="true"></span></div>
			</div>
		</div>
		<div class="ma-att">
			<p><b>ΣΥΝΗΜΜΕΝΑ ΥΠΟΒΑΛΛΩ:</b></p>
			<ol>
				<li><span class="bl">1.</span><span>Λογαριασμό κοινής ωφέλειας (ύδρευσης ή ρεύματος ή τηλεφώνου) ή άλλο πρόσφορο αποδεικτικό μέσο.</span></li>
				<li><span class="bl">2.</span><span>Αντίγραφο ταυτότητας γονέων/κηδεμόνων ή αντίγραφο ταυτότητας γονέα/κηδεμόνα.</span></li>
				<li><span class="bl">3.</span><span>Τυχόν δικαστική απόφαση (προσωρινή απόφαση ασφαλιστικών μέτρων, οριστική δικαστική απόφαση) αν και εφόσον δεν υφίσταται συναίνεση των δύο γονέων/κηδεμόνων, σύμφωνα με την υπό στοιχεία Φ1/117162/ΓΔ4/20-09-2021 εγκύκλιο του Υπουργείου Παιδείας.</span></li>
			</ol>
		</div>
	</div>
</div>

<div class="sef-pb" data-sef-pb aria-hidden="true"><span class="sef-screen-only">— Σελίδα 2: Υπεύθυνη δήλωση μόνιμης κατοικίας —</span></div>

<div class="ma-p2">
	<div class="ma-top"><span class="ma-annex">ΣΥΝ.3</span></div>
	<p class="ma-h">ΣΤΟΙΧΕΙΑ ΓΟΝΕΩΝ/ΚΗΔΕΜΟΝΩΝ</p>
	<p class="ma-line">i) <span class="ma-fill" data-mirror="p1_surname p1_name"></span></p>
	<p class="ma-line">ii) <span class="ma-fill" data-mirror="p2_surname p2_name"></span></p>
	<p class="ma-small">(ΕΠΩΝΥΜΟ) (ΟΝΟΜΑ)</p>
	<p class="ma-decl">Η ΠΑΡΟΥΣΑ ΑΙΤΗΣΗ ΕΠΕΧΕΙ ΘΕΣΗ ΥΠΕΥΘΥΝΗΣ ΔΗΛΩΣΗΣ ΣΥΜΦΩΝΑ ΜΕ ΤΟ ΑΡΘΡΟ 8 ΤΟΥ Ν. 1599/1986, ΣΤΗΝ ΟΠΟΙΑ ΑΝΑΓΡΑΦΟΝΤΑΙ ΤΑ ΣΤΟΙΧΕΙΑ ΤΟΥ/ΤΗΣ/ΩΝ ΑΙΤΟΥΝΤΟΣ/ΑΙΤΟΥΣΑΣ/ΑΙΤΟΥΝΤΩΝ</p>
	<p class="ma-h">ΥΠΕΥΘΥΝΗ ΔΗΛΩΣΗ:</p>
	<p>Δηλών<?php $T->sel( 'decl_form', array( 'ω', 'ουμε' ), array( 'aria-label' => 'Δηλώνω ή δηλώνουμε', 'data' => array( 'blank-text' => '……' ) ) ); ?> υπεύθυνα ότι η διεύθυνση της μόνιμης κατοικίας του/της υποψηφίου/ας <b><span class="ma-fill" data-mirror="student"></span></b> είναι η ακόλουθη:</p>
	<div class="ma-addr">
		<div class="ap-row"><?php $T->label( 'res_street', 'Οδός:' ); ?><?php $T->in( 'res_street', array( 'req' => 1 ) ); ?></div>
		<div class="ap-row"><?php $T->label( 'res_no', 'Αριθμός:' ); ?><?php $T->in( 'res_no', array( 'req' => 1, 'maxlength' => 10 ) ); ?></div>
		<div class="ap-row"><?php $T->label( 'res_tk', 'Τ.Κ.:' ); ?><?php $T->in( 'res_tk', array( 'req' => 1, 'inputmode' => 'numeric', 'maxlength' => 6 ) ); ?></div>
		<div class="ap-row"><?php $T->label( 'res_unit', 'Δημοτική Ενότητα/Διαμέρισμα:' ); ?><?php $T->in( 'res_unit' ); ?></div>
		<div class="ap-row"><?php $T->label( 'res_dimos', 'Δήμος:' ); ?><?php $T->in( 'res_dimos', array( 'req' => 1 ) ); ?></div>
	</div>
	<div class="ma-two ma-two-p2">
		<div><p>Γονέας/Κηδεμόνας (i)<br>Ο/Η Δηλών/ούσα</p><div class="sp"></div><span class="nm" data-mirror="p1_name p1_surname" aria-hidden="true"></span><p class="cap">(υπογραφή)</p></div>
		<div><p>Γονέας/Κηδεμόνας (ii)<br>Ο/Η Δηλών/ούσα</p><div class="sp"></div><span class="nm" data-mirror="p2_name p2_surname" aria-hidden="true"></span><p class="cap">(υπογραφή)</p></div>
	</div>
</div>
