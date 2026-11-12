<?php
/**
 * Απεγκατάσταση: διαγράφει τις ρυθμίσεις και τα προσωρινά δεδομένα του plugin.
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
delete_option( 'sef_settings' );
global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_sef\\_%' OR option_name LIKE '\\_transient\\_timeout\\_sef\\_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
