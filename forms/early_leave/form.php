<?php
defined( 'ABSPATH' ) || exit;
return array(
	'id'        => 'early_leave',
	'label'     => 'Πρόωρη αποχώρηση μαθητή',
	'desc'      => 'Ζητάτε το παιδί σας να αποχωρήσει νωρίτερα από το σχολείο σε συγκεκριμένη ημέρα και ώρα.',
	'category'  => 'parents',
	'icon'      => 'clock',
	'order'     => 50,
	'default_enabled' => false,
	'subject'   => 'Πρόωρη αποχώρηση – {student|Ονοματεπώνυμο μαθητή} – {class}{section|Τμήμα}',
	'filename'  => 'Πρόωρη_Αποχώρηση_{student}',
	'file_slug' => 'proori-apoxorisi',
	'doc_title' => 'Υπεύθυνη Δήλωση – Πρόωρη αποχώρηση μαθητή',
	'hint'      => 'Στείλτε το έγκαιρα, ώστε το σχολείο να το έχει πριν από την αποχώρηση.',
);
