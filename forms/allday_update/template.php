<?php
defined( 'ABSPATH' ) || exit;
/*
 * Κατά το υπόδειγμα «Επικαιροποίηση εγγραφής μαθητών/μαθητριών στο Ολοήμερο Πρόγραμμα» (0.9.9):
 * ενημερωτικό κείμενο προς τους γονείς (Π.Δ. 79/2017) και από κάτω η αίτηση για έως τέσσερα παιδιά.
 * Ώρες αποχώρησης: όσες από τη ρύθμιση «Ώρες αποχώρησης» είναι μετά τη λήξη του πρωινού (από 14:00).
 * Προθεσμία «έως την …»: η λήξη της τρέχουσας (ή της επόμενης) περιόδου εμφάνισης του εντύπου.
 */
$times = array_values(
	array_filter(
		array_column( $T->departure_times(), 'time' ),
		function ( $t ) {
			return strcmp( str_pad( $t, 5, '0', STR_PAD_LEFT ), '14:00' ) >= 0;
		}
	)
);
$deadline = '';
$conf     = SEF_Settings::form( $F['id'], $F );
$periods  = array();
foreach ( array( array( 'from', 'to' ), array( 'from2', 'to2' ) ) as $pp ) {
	if ( preg_match( '/^\d{2}\/\d{2}$/', (string) $conf[ $pp[1] ] ) ) {
		$periods[] = array( (string) $conf[ $pp[0] ], (string) $conf[ $pp[1] ] );
	}
}
if ( $periods ) {
	$y     = (int) wp_date( 'Y' );
	$today = (int) wp_date( 'n' ) * 100 + (int) wp_date( 'j' );
	$key   = function ( $dm ) { return (int) substr( $dm, 3, 2 ) * 100 + (int) substr( $dm, 0, 2 ); };
	$best  = null;
	foreach ( $periods as $p ) {
		// Η πλησιέστερη λήξη από σήμερα και μετά = λήξη της περιόδου που τρέχει ή της επόμενης.
		$end  = $key( $p[1] );
		$cand = ( $end >= $today ? $y : $y + 1 ) * 10000 + $end;
		if ( null === $best || $cand < $best ) {
			$best = $cand;
		}
	}
	$deadline = sprintf( '%d/%d/%d', $best % 100, intdiv( $best % 10000, 100 ), intdiv( $best, 10000 ) );
}
?>
<div class="ol-top"><span><?php $T->e( $S['school_name'] ); ?></span><span>Σχολικό έτος <?php $T->e( $T->year() ); ?></span></div>
<p class="ol-title">Επικαιροποίηση εγγραφής μαθητών/μαθητριών στο Ολοήμερο Πρόγραμμα</p>
<div class="ou-info">
	<p>Σύμφωνα με το Π.Δ. 79/2017 που καθορίζει τη λειτουργία των Σχολικών Μονάδων, κατά το <b>β΄ δεκαπενθήμερο Νοεμβρίου και το α΄ δεκαπενθήμερο Φεβρουαρίου</b> πρέπει να επικαιροποιείται η εγγραφή των μαθητών/τριών στο Ολοήμερο Πρόγραμμα.</p>
	<p>Για τον λόγο αυτό σας παρακαλούμε να συμπληρώσετε την παρακάτω αίτηση <b><u>επικαιροποίησης</u></b> της εγγραφής του/των παιδιού/ών σας στο Ολοήμερο Πρόγραμμα και να <b>την επιστρέψετε στο σχολείο<?php echo $deadline ? ' έως την ' . esc_html( $deadline ) : ''; ?></b>. Τυχόν αμέλεια ή παράλειψη της συμπλήρωσης και της κατάθεσης της αίτησης στο σχολείο οδηγεί αυτόματα στη διαγραφή των μαθητών/τριών από τη φοίτηση στο Ολοήμερο Πρόγραμμα.</p>
	<p>Για οποιαδήποτε διευκρίνιση απευθυνθείτε στον Διευθυντή του σχολείου.</p>
	<p>Παρακαλούμε <b>σημειώστε σωστά την ώρα αποχώρησης</b>, διότι είναι πολύ σημαντικό για τον σωστό προγραμματισμό της λειτουργίας των Τμημάτων.</p>
</div>
<p class="ou-title">ΑΙΤΗΣΗ ΕΠΙΚΑΙΡΟΠΟΙΗΣΗΣ ΕΓΓΡΑΦΗΣ ΜΑΘΗΤΗ/ΩΝ ΣΤΟ ΟΛΟΗΜΕΡΟ ΠΡΟΓΡΑΜΜΑ</p>
<div class="yd-line sef-screen-only"><span>Πόσα παιδιά σας είναι στο Ολοήμερο;</span><?php $T->sel( 'kids', array( '1' => '1', '2' => '2', '3' => '3', '4' => '4' ), array( 'aria-label' => 'Αριθμός παιδιών', 'data' => array( 'blank-keep' => '1' ) ) ); ?></div>
<div class="yd-line"><span><b>Ονοματεπώνυμο γονέα/κηδεμόνα:</b></span><?php $T->in( 'p_name', array( 'req' => 1, 'class' => 'grow', 'autocomplete' => 'name', 'aria-label' => 'Ονοματεπώνυμο γονέα ή κηδεμόνα' ) ); ?></div>
<?php
for ( $i = 1; $i <= 4; $i++ ) :
	$vis = array( 1 => '', 2 => ' data-hide-if="kids=1"', 3 => ' data-show-if="kids=3|4" hidden', 4 => ' data-show-if="kids=4" hidden' );
	?>
	<div class="ou-child"<?php echo $vis[ $i ]; // phpcs:ignore ?>>
		<div class="yd-line">
			<span><b><?php echo (int) $i; ?>. Ονοματεπώνυμο μαθητή/τριας:</b></span><?php $T->in( 'c' . $i . '_name', array( 'req' => 1, 'class' => 'grow', 'aria-label' => $i . 'ο παιδί: ονοματεπώνυμο' ) ); ?>
			<span>Τάξη:</span><?php $T->sel( 'c' . $i . '_class', $T->classes(), array( 'req' => 1, 'empty' => '—', 'aria-label' => $i . 'ο παιδί: τάξη' ) ); ?>
		</div>
		<div class="ol-row"><span><b>Ώρα αποχώρησης</b> <i>(σημειώστε στο ανάλογο πεδίο)</i>:</span>
			<div class="ol-opts" role="radiogroup" aria-label="<?php echo esc_attr( $i . 'ο παιδί: ώρα αποχώρησης' ); ?>" data-req-group="c<?php echo (int) $i; ?>_time">
				<?php foreach ( $times as $t ) : ?><?php $T->radio( 'c' . $i . '_time', $t, str_replace( ':', '.', $t ) ); ?><?php endforeach; ?>
			</div>
		</div>
		<div class="ol-row"><span>Το παιδί <b>παραλαμβάνεται</b> κατά την αποχώρηση ή <b>αποχωρεί μόνο του</b>; <i>(σημειώστε στο ανάλογο πεδίο)</i>:</span>
			<div class="ol-opts" role="radiogroup" aria-label="<?php echo esc_attr( $i . 'ο παιδί: τρόπος αποχώρησης' ); ?>" data-req-group="c<?php echo (int) $i; ?>_pick">
				<?php $T->radio( 'c' . $i . '_pick', 'picked', 'παραλαμβάνεται' ); ?><?php $T->radio( 'c' . $i . '_pick', 'alone', 'αποχωρεί μόνο του' ); ?>
			</div>
		</div>
	</div>
<?php endfor; ?>
<div class="ol-sign">
	<div class="ap-place"><?php $T->in( 'place', array( 'req' => 1, 'value' => $T->city(), 'aria-label' => 'Τόπος', 'class' => 'w-m' ) ); ?><span>,</span><?php $T->in( 'date', array( 'type' => 'date', 'req' => 1, 'aria-label' => 'Ημερομηνία', 'data' => array( 'today' => '1' ) ) ); ?></div>
	<div class="ap-sign">
		<span>Ο γονέας/κηδεμόνας</span>
		<div class="sp"></div>
		<span class="nm" data-mirror="p_name" aria-hidden="true"></span>
		<span class="cap">(ονοματεπώνυμο &amp; υπογραφή)</span>
	</div>
</div>
