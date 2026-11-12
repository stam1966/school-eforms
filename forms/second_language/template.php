<?php
defined( 'ABSPATH' ) || exit;
$langs = $T->lines( 'languages' );
$T->yd_head( array( 'title' => 'ΥΠΕΥΘΥΝΗ ΔΗΛΩΣΗ' ) );
?>
<div class="yd-decl">
	<?php $T->yd_intro(); ?>
	<p>ως γονέας/κηδεμόνας</p>
	<?php $T->student_line(); ?>
	<div class="yd-line sef-screen-only">
		<span>Δήλωση:</span><?php $T->sel( 'kind', array( 'choose' => 'επιλογή γλώσσας', 'change' => 'αλλαγή γλώσσας' ), array( 'req' => 1, 'empty' => '— επιλέξτε —', 'aria-label' => 'Επιλογή ή αλλαγή', 'data' => array( 'blank-keep' => '1' ) ) ); ?>
	</div>
	<div data-show-if="kind=choose" hidden>
		<div class="yd-line"><span>επιλέγω ως δεύτερη ξένη γλώσσα για το σχολικό έτος <?php $T->e( $T->year() ); ?> τα</span><?php $T->sel( 'lang', $langs, array( 'data' => array( 'req-if' => 'kind=choose' ), 'empty' => '—', 'aria-label' => 'Δεύτερη ξένη γλώσσα' ) ); ?><span>.</span></div>
	</div>
	<div data-show-if="kind=change" hidden>
		<div class="yd-line"><span>παρακαλώ να αλλάξει η δεύτερη ξένη γλώσσα από τα</span><?php $T->sel( 'lang_from', $langs, array( 'data' => array( 'req-if' => 'kind=change' ), 'empty' => '—', 'aria-label' => 'Από γλώσσα' ) ); ?><span>στα</span><?php $T->sel( 'lang_to', $langs, array( 'data' => array( 'req-if' => 'kind=change' ), 'empty' => '—', 'aria-label' => 'Σε γλώσσα' ) ); ?><span>,</span></div>
		<div class="yd-line"><span>για τον εξής λόγο:</span><?php $T->in( 'reason', array( 'class' => 'grow', 'maxlength' => 200, 'data' => array( 'req-if' => 'kind=change' ), 'aria-label' => 'Λόγος αλλαγής' ) ); ?></div>
		<p>Έλαβα γνώση ότι η αλλαγή γίνεται δεκτή μόνο εφόσον επιτρέπεται από τις ισχύουσες διατάξεις και τη δυνατότητα του σχολείου.</p>
	</div>
	<div class="sef-blank-only" hidden data-blank-show data-blank-field="kind">
		<div class="yd-opts">
			<span class="sef-chk"><span class="sef-box" aria-hidden="true"></span><span>επιλέγω ως δεύτερη ξένη γλώσσα για το σχολικό έτος <?php $T->e( $T->year() ); ?>: <?php $T->e( implode( ' / ', $langs ) ); ?> (κυκλώστε)</span></span>
			<span class="sef-chk"><span class="sef-box" aria-hidden="true"></span><span>παρακαλώ να αλλάξει η δεύτερη ξένη γλώσσα από τα ……………… στα ……………… για τον εξής λόγο: ……………………………………………</span></span>
		</div>
	</div>
</div>
<?php $T->yd_tail(); ?>
