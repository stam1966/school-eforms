<?php
defined( 'ABSPATH' ) || exit;
$T->yd_head();
$subjects = array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $S['remedial_subjects'] ) ) ) );
?>
<div class="yd-decl">
	<?php $T->yd_intro(); ?>
	<div class="yd-line">
		<span>ως</span><?php $T->sel( 'role', array( 'γονέας', 'κηδεμόνας' ), array( 'req' => 1, 'empty' => '— επιλέξτε —', 'aria-label' => 'Γονέας ή κηδεμόνας' ) ); ?>
		<?php $T->sel( 'student_gender', array( 'του μαθητή', 'της μαθήτριας' ), array( 'req' => 1, 'empty' => '— επιλέξτε —', 'aria-label' => 'Μαθητή ή μαθήτριας' ) ); ?>
		<?php $T->in( 'student', array( 'req' => 1, 'class' => 'grow', 'placeholder' => 'ονοματεπώνυμο μαθητή/τριας', 'aria-label' => 'Ονοματεπώνυμο μαθητή ή μαθήτριας' ) ); ?><span>,</span>
	</div>
	<div class="yd-line">
		<span>του τμήματος</span><?php $T->section( array( 'req' => 1, 'class' => 'w-s', 'placeholder' => 'π.χ. Β2' ) ); ?>
		<span>της</span><?php $T->sel( 'class', $T->classes(), array( 'req' => 1, 'empty' => '—', 'aria-label' => 'Τάξη' ) ); ?><span>τάξης,</span>
	</div>
	<p><b>επιθυμώ να παρακολουθήσει κατά το σχολικό έτος <?php $T->e( $T->year() ); ?> τα παρακάτω μαθήματα:</b></p>
	<ul class="yd-list" data-req-min="1" aria-label="Μαθήματα ενισχυτικής διδασκαλίας">
		<?php foreach ( $subjects as $i => $subject ) : ?>
			<li><span class="nm"><?php echo (int) ( $i + 1 ); ?>.</span><span class="tx"><?php $T->e( $subject ); ?></span><?php $T->chk( 'subjects[]', 'Επιλογή', array( 'value' => $subject, 'id_suffix' => (string) $i ) ); ?></li>
		<?php endforeach; ?>
	</ul>
	<p><b>που προσφέρονται στο πλαίσιο της Πράξης «Ενισχυτική Διδασκαλία στη Δευτεροβάθμια Εκπαίδευση, σχολικό έτος <?php $T->e( $T->year() ); ?>»<?php if ( $S['remedial_ops'] ) : ?>, με κωδικό ΟΠΣ <?php $T->e( $S['remedial_ops'] ); ?><?php endif; ?> στο Πρόγραμμα «Ανθρώπινο Δυναμικό και Κοινωνική Συνοχή 2021-2027».</b></p>
</div>
<?php $T->yd_tail( array( 'espa' => true ) ); ?>
