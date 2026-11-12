<?php
defined( 'ABSPATH' ) || exit;
/* Εποχικό (βήμα 3): επιλογή ή αλλαγή δεύτερης ξένης γλώσσας — Δημοτικό. */
return array(
	'id'              => 'second_language',
	'label'           => 'Δεύτερη ξένη γλώσσα',
	'desc'            => 'Επιλογή της δεύτερης ξένης γλώσσας (για την Ε΄ τάξη) ή αίτημα αλλαγής της.',
	'category'        => 'parents',
	'levels'          => array( 'primary' ),
	'icon'            => 'pen',
	'order'           => 16,
	'default_enabled' => false,
	'subject'         => 'Δεύτερη ξένη γλώσσα – {student|Ονοματεπώνυμο μαθητή} – {class}{section|Τμήμα}',
	'filename'        => 'Δεύτερη_Ξένη_Γλώσσα_{student}',
	'file_slug'       => 'defteri-xeni-glossa',
	'doc_title'       => 'Υπεύθυνη Δήλωση – Δεύτερη ξένη γλώσσα',
	'hint'            => 'Διαλέξτε «Επιλογή» ή «Αλλαγή» και τη γλώσσα. Οι γλώσσες είναι όσες διδάσκονται στο σχολείο.',
);
