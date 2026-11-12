<?php
defined( 'ABSPATH' ) || exit;
$T->yd_head();
?>
<div class="yd-decl">
	<?php $T->yd_intro(); ?>
	<?php $T->student_line( 'ως γονέας/κηδεμόνας:' ); ?>
	<div class="yd-opts" role="radiogroup" aria-label="Συναίνεση" data-req-group="consent">
		<?php $T->radio( 'consent', 'yes', 'συναινώ' ); ?>
		<?php $T->radio( 'consent', 'no', 'δεν συναινώ' ); ?>
	</div>
	<div class="yd-line"><span>στη συμμετοχή του παιδιού μου στη δράση / εκδρομή:</span><?php $T->in( 'activity', array( 'req' => 1, 'class' => 'grow', 'aria-label' => 'Τίτλος δράσης ή εκδρομής' ) ); ?></div>
	<div class="yd-line">
		<span>που θα πραγματοποιηθεί την</span><?php $T->in( 'activity_date', array( 'type' => 'date', 'req' => 1, 'class' => 'w-m', 'aria-label' => 'Ημερομηνία δράσης' ) ); ?>
		<span>στο/στην</span><?php $T->in( 'activity_place', array( 'class' => 'grow', 'aria-label' => 'Τόπος δράσης' ) ); ?><span>.</span>
	</div>
	<p>Έλαβα γνώση του προγράμματος της δράσης και αποδέχομαι τους όρους συμμετοχής που ανακοίνωσε το σχολείο.</p>
	<div class="yd-line"><span>Παρατηρήσεις (προαιρετικά):</span><?php $T->in( 'notes', array( 'class' => 'grow', 'maxlength' => 200, 'aria-label' => 'Παρατηρήσεις' ) ); ?></div>
</div>
<?php $T->yd_tail(); ?>
