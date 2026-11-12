<?php
defined( 'ABSPATH' ) || exit;
$T->yd_head( array( 'title' => 'ΥΠΕΥΘΥΝΗ ΔΗΛΩΣΗ' ) );
?>
<div class="yd-decl">
	<?php $T->yd_intro(); ?>
	<div class="yd-line">
		<span>ως</span><?php $T->sel( 'capacity', array( 'γονέας', 'κηδεμόνας', 'ασκών/ούσα την επιμέλεια' ), array( 'req' => 1, 'empty' => '— ιδιότητα —', 'aria-label' => 'Ιδιότητα' ) ); ?>
		<span>του/της μαθητή/μαθήτριας</span><?php $T->in( 'student', array( 'req' => 1, 'class' => 'grow', 'placeholder' => 'ονοματεπώνυμο μαθητή/τριας', 'aria-label' => 'Ονοματεπώνυμο μαθητή ή μαθήτριας' ) ); ?>
	</div>
	<p>που εγγράφεται στην Α΄ τάξη του σχολείου για το σχολικό έτος <?php $T->e( $T->year() ); ?>, δηλώνω ότι:</p>
	<ul class="yd-bullets">
		<li><span class="bl" aria-hidden="true">•</span><span>τα στοιχεία κατοικίας και επικοινωνίας μου είναι αυτά που αναγράφονται στην παρούσα·</span></li>
		<li><span class="bl" aria-hidden="true">•</span><span>η γονική μέριμνα ασκείται</span></li>
	</ul>
	<div class="yd-opts" role="radiogroup" aria-label="Άσκηση γονικής μέριμνας" data-req-group="custody">
		<?php $T->radio( 'custody', 'joint', 'από κοινού από τους δύο γονείς.' ); ?>
		<?php $T->radio( 'custody', 'sole', 'αποκλειστικά από εμένα.' ); ?>
	</div>
	<p>Δεύτερο πρόσωπο άμεσης επικοινωνίας:</p>
	<div class="yd-line yd-person">
		<?php $T->in( 'c2_name', array( 'class' => 'grow', 'placeholder' => 'ονοματεπώνυμο', 'aria-label' => 'Δεύτερο πρόσωπο επικοινωνίας: ονοματεπώνυμο' ) ); ?>
		<span>σχέση:</span><?php $T->in( 'c2_rel', array( 'class' => 'w-m', 'aria-label' => 'Σχέση με τον μαθητή' ) ); ?>
		<span>τηλ.:</span><?php $T->in( 'c2_tel', array( 'type' => 'tel', 'class' => 'w-m', 'aria-label' => 'Τηλέφωνο δεύτερου προσώπου' ) ); ?>
	</div>
	<p>Αναλαμβάνω να ενημερώνω εγγράφως το σχολείο για κάθε αλλαγή των παραπάνω στοιχείων.</p>
</div>
<?php $T->yd_tail(); ?>
