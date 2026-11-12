<?php
defined( 'ABSPATH' ) || exit;
return array(
	'id'        => 'teacher_request',
	'label'     => 'Υπηρεσιακή αίτηση εκπαιδευτικού',
	'desc'      => 'Γενική αίτηση προς τη διεύθυνση: βεβαίωση προϋπηρεσίας, αλλαγή στοιχείων ή άλλο αίτημα.',
	'category'  => 'teachers',
	'icon'      => 'pen',
	'order'     => 130,
	'subject'   => 'Αίτηση εκπαιδευτικού – {surname|Επώνυμο} {name|Όνομα}',
	'filename'  => 'Αίτηση_Εκπαιδευτικού_{surname}',
	'file_slug' => 'aitisi-ekpaideftikou',
	'doc_title' => 'Αίτηση εκπαιδευτικού',
	'hint'      => 'Γράψτε το θέμα με λίγα λόγια και δεξιά τι ζητάτε.',
	'stamp'     => true,
);
