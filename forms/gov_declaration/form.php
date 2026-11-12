<?php
defined( 'ABSPATH' ) || exit;
return array(
	'id'        => 'gov_declaration',
	'label'     => 'Υπεύθυνη δήλωση (ελεύθερο κείμενο)',
	'desc'      => 'Εκδίδεται απευθείας από το gov.gr, με το κείμενο που γράφετε εσείς. Μετά τη στέλνετε στο σχολείο.',
	'category'  => 'declarations',
	'kind'      => 'external',
	'icon'      => 'shield',
	'order'     => 210,
	'modes'     => array( 'email', 'upload' ),
	'subject'   => 'Υπεύθυνη δήλωση – {sender|Ονοματεπώνυμο} – {about|Θέμα}',
	'file_slug' => 'ypefthyni-dilosi',
	'doc_title' => 'Υπεύθυνη δήλωση gov.gr',
);
