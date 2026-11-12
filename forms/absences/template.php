<?php
defined( 'ABSPATH' ) || exit;
/*
 * Δικαιολόγηση απουσιών: ένα έντυπο με επιλογή περίπτωσης. Οι περιπτώσεις (και η φράση που
 * τυπώνεται, τα συνημμένα και η βαθμίδα τους) ορίζονται στη διαχείριση — καρτέλα «Περιπτώσεις απουσιών».
 * Στο PDF τυπώνεται μόνο η φράση της περίπτωσης, ποτέ το όνομα της λίστας (που μπορεί να αναφέρει νόσημα).
 */
$cases = SEF_Settings::absence_cases();
$opts  = array();
$data  = array();
foreach ( $cases as $cid => $c ) {
	$opts[ $cid ] = $c['label'];
	$data[ $cid ] = array(
		'text' => $c['text'],
		'docs' => str_replace( "\n", '|', $c['docs'] ),
	);
}
$secondary = 'primary' !== $T->level();
$T->yd_head( array( 'title' => 'ΥΠΕΥΘΥΝΗ ΔΗΛΩΣΗ – ΑΙΤΗΣΗ' ) );
?>
<div class="yd-decl" data-days='{"from":"from","to":"to","n":"days","words":"words","mode":"weekdays"}'>
	<?php $T->yd_intro( 'gymnasio' === $T->level() ? 'και με βάση την ΚΥΑ 102791/ΓΔ4/10-09-2024 (ΦΕΚ Β΄ 5130)' : '' ); ?>
	<?php $T->student_line(); ?>
	<div class="yd-line"><span>απουσίασε από τα μαθήματα για</span><?php $T->sel( 'reason', array( 'λόγους υγείας', 'οικογενειακούς λόγους', 'άλλο σοβαρό λόγο' ), array( 'req' => 1, 'empty' => '— επιλέξτε —', 'aria-label' => 'Λόγος απουσίας' ) ); ?></div>
	<div class="yd-line">
		<span>ημέρες</span><?php $T->in( 'words', array( 'req' => 1, 'class' => 'w-m', 'placeholder' => 'ολογράφως', 'aria-label' => 'Αριθμός ημερών ολογράφως' ) ); ?>
		<span>(</span><?php $T->in( 'days', array( 'req' => 1, 'class' => 'w-xs', 'inputmode' => 'numeric', 'maxlength' => 3, 'aria-label' => 'Αριθμός ημερών' ) ); ?><span>)</span>
		<span>από</span><?php $T->in( 'from', array( 'type' => 'date', 'req' => 1, 'class' => 'w-m', 'aria-label' => 'Από ημερομηνία' ) ); ?>
		<span>μέχρι και</span><?php $T->in( 'to', array( 'type' => 'date', 'req' => 1, 'class' => 'w-m', 'aria-label' => 'Μέχρι και ημερομηνία' ) ); ?>
	</div>
	<?php if ( $secondary ) : ?>
	<div class="yd-line" data-hide-empty>
		<span>ή μόνο τις ώρες</span><?php $T->in( 'hours', array( 'class' => 'grow', 'maxlength' => 80, 'placeholder' => 'προαιρετικά, για μερική απουσία — π.χ. 1η–3η ώρα', 'aria-label' => 'Ώρες μερικής απουσίας' ) ); ?>
	</div>
	<?php endif; ?>
	<div class="yd-line sef-screen-only">
		<span>Περίπτωση:</span><?php $T->sel( 'case', $opts, array( 'req' => 1, 'class' => 'grow', 'empty' => '— επιλέξτε περίπτωση —', 'aria-label' => 'Περίπτωση δικαιολόγησης', 'opt_data' => $data, 'data' => array( 'blank-keep' => '1' ) ) ); ?>
	</div>
	<p data-blank-hide data-blank-field="case"><span data-mirror-opt="case" data-opt-attr="text" data-fallback="και παρακαλώ να δικαιολογηθούν οι απουσίες του/της για τις ημέρες αυτές.">και παρακαλώ να δικαιολογηθούν οι απουσίες του/της για τις ημέρες αυτές.</span></p>
	<div class="ap-attach yd-attach" data-sef-attach="case" hidden><b>Συνημμένα:</b><div class="ap-attach-list" data-sef-attach-list></div></div>
	<?php /* Κενό έντυπο: όλες οι περιπτώσεις ως κουτάκια, για συμπλήρωση με το χέρι. */ ?>
	<p class="sef-blank-only" hidden data-blank-show data-blank-field="case">και παρακαλώ να δικαιολογηθούν οι απουσίες του/της για τις ημέρες αυτές (σημειώστε την περίπτωση):</p>
	<div class="yd-opts sef-blank-only" hidden data-blank-show data-blank-field="case">
		<?php foreach ( $cases as $c ) : ?>
			<span class="sef-chk"><span class="sef-box" aria-hidden="true"></span><span><?php $T->e( $c['label'] . ( '' !== $c['docs'] ? ' (συνημμένα: ' . str_replace( "\n", ', ', $c['docs'] ) . ')' : '' ) ); ?></span></span>
		<?php endforeach; ?>
	</div>
</div>
<?php $T->yd_tail(); ?>
