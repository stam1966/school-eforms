<?php
defined( 'ABSPATH' ) || exit;
return array(
	'id'        => 'participation',
	'label'     => 'Συμμετοχή σε δράση ή εκδρομή',
	'desc'      => 'Δηλώνετε αν συναινείτε στη συμμετοχή του παιδιού σας σε εκδρομή, επίσκεψη ή σχολική δράση.',
	'category'  => 'parents',
	'icon'      => 'flag',
	'order'     => 60,
	'default_enabled' => false,
	'subject'   => 'Δήλωση συμμετοχής – {student|Ονοματεπώνυμο μαθητή} – {class}{section|Τμήμα}',
	'filename'  => 'Δήλωση_Συμμετοχής_{student}',
	'file_slug' => 'dilosi-symmetoxis',
	'doc_title' => 'Υπεύθυνη Δήλωση – Συμμετοχή σε δράση',
	'hint'      => 'Γράψτε τη δράση όπως αναφέρεται στην ενημέρωση του σχολείου.',
);
