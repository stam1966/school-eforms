<?php
defined( 'ABSPATH' ) || exit;
return array(
	'id'        => 'remedial',
	'label'     => 'Ενισχυτική διδασκαλία',
	'desc'      => 'Θέλετε το παιδί σας να παρακολουθήσει ενισχυτική διδασκαλία. Υπεύθυνη δήλωση γονέα: επιλέγετε τα μαθήματα.',
	'category'  => 'parents',
	'levels'    => array( 'secondary' ),
	'icon'      => 'book',
	'order'     => 30,
	'subject'   => 'Ενισχυτική διδασκαλία – {student|Ονοματεπώνυμο μαθητή} – {class}{section|Τμήμα}',
	'filename'  => 'Υπεύθυνη_Δήλωση_Ενισχυτικής_{student}',
	'file_slug' => 'dilosi-enischytikis',
	'doc_title' => 'Υπεύθυνη Δήλωση – Ενισχυτική διδασκαλία',
	'hint'      => 'Συμπληρώστε τα στοιχεία σας και σημειώστε ένα ή περισσότερα μαθήματα.',
);
