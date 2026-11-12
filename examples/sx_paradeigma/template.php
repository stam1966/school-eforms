<?php
/**
 * ΠΑΡΑΔΕΙΓΜΑ ΠΡΟΤΥΠΟΥ (το «χαρτί» Α4).
 *
 * Διαθέσιμα:  $T  βοηθητικές συναρτήσεις   $S  ρυθμίσεις σχολείου   $F  ορισμός εντύπου
 *
 * Έτοιμα κομμάτια Υπεύθυνης Δήλωσης (Ν.1599/1986):
 *   $T->yd_head()                    εθνόσημο, τίτλος, πίνακας στοιχείων δηλούντος
 *   $T->yd_head( array( 'title' => 'ΥΠΕΥΘΥΝΗ ΔΗΛΩΣΗ – ΑΙΤΗΣΗ' ) )
 *   $T->yd_intro()                   «Με ατομική μου ευθύνη … δηλώνω ότι:»
 *   $T->student_line( 'ως γονέας:' ) μαθητής/μαθήτρια, ονοματεπώνυμο, τάξη, τμήμα
 *   $T->yd_tail()                    ημερομηνία, υπογραφή, υποσημειώσεις (1)–(4)
 *
 * Πεδία (το πρώτο όρισμα είναι το «όνομα» του πεδίου — λατινικά, μοναδικό):
 *   $T->in( 'onoma', array( 'req' => 1, 'class' => 'grow', 'placeholder' => '…' ) )
 *        type: 'text' (προεπιλογή) | 'date' | 'time' | 'tel' | 'email'
 *        class στις γραμμές κειμένου: grow (γεμίζει) | w-xs | w-s | w-m | w-l
 *   $T->sel( 'onoma', array( 'Α', 'Β' ), array( 'req' => 1, 'empty' => '— επιλέξτε —' ) )
 *   $T->ta( 'onoma', array( 'rows' => 4, 'class' => 'yd-text' ) )      πολλές γραμμές
 *   $T->chk( 'onoma', 'Κείμενο επιλογής' )                              τετραγωνάκι
 *   $T->radio( 'onoma', 'timi', 'Κείμενο' )                             μία από πολλές
 *   'req' => 1  = υποχρεωτικό. Για ομάδα επιλογών βάλτε στο περιβάλλον
 *   data-req-group="onoma" (radio) ή data-req-min="1" (τετραγωνάκια).
 *
 * Άλλα:  $T->e( 'κείμενο' )  ασφαλής εκτύπωση κειμένου
 *        $T->school_to()  $T->city()  $T->year()  $T->classes()
 *        $T->img( 'espa-2021-2027.jpg' )   κοινά γραφικά του plugin
 *        $T->asset( 'logo.png' )           αρχείο μέσα σε αυτόν τον φάκελο
 *
 * Κανόνες για σωστό PDF: μην χρησιμοποιείτε text-transform, σκιές ή
 * διαβαθμίσεις χρώματος μέσα στο έντυπο. Το έντυπο πρέπει να χωρά σε Α4.
 */
defined( 'ABSPATH' ) || exit;

$T->yd_head();
?>
<div class="yd-decl">
	<?php $T->yd_intro(); ?>
	<?php $T->student_line( 'ως γονέας/κηδεμόνας:' ); ?>

	<p>δηλώνω ότι:</p>
	<?php $T->ta( 'statement', array( 'req' => 1, 'rows' => 4, 'class' => 'yd-text', 'aria-label' => 'Κείμενο δήλωσης', 'placeholder' => 'Γράψτε εδώ τη δήλωσή σας…' ) ); ?>

	<p>Επιθυμώ να ενημερώνομαι:</p>
	<div class="yd-opts" role="radiogroup" aria-label="Τρόπος ενημέρωσης" data-req-group="contact">
		<?php $T->radio( 'contact', 'email', 'με email' ); ?>
		<?php $T->radio( 'contact', 'phone', 'τηλεφωνικά' ); ?>
	</div>

	<div class="yd-line">
		<span>Ισχύει από</span><?php $T->in( 'valid_from', array( 'type' => 'date', 'class' => 'w-m', 'aria-label' => 'Ισχύει από' ) ); ?>
		<span>για το σχολικό έτος <?php $T->e( $T->year() ); ?>.</span>
	</div>
</div>
<?php $T->yd_tail(); ?>
