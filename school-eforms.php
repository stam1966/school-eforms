<?php
/**
 * Plugin Name:       School e-Forms
 * Description:       Ηλεκτρονικά έντυπα σχολείου: συμπλήρωση πάνω στο επίσημο υπόδειγμα, PDF με πραγματικό ελληνικό κείμενο, ψηφιακή βεβαίωση μέσω gov.gr και αποστολή στο σχολείο. Χωρίς αποθήκευση προσωπικών δεδομένων.
 * Plugin URI:        https://github.com/stam1966/school-eforms
 * Version:           0.9.10
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            School e-Forms Project
 * License:           GPL-2.0-or-later
 * Text Domain:       school-eforms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SEF_VERSION', '0.9.10' );
define( 'SEF_FILE', __FILE__ );
define( 'SEF_DIR', plugin_dir_path( __FILE__ ) );
define( 'SEF_URL', plugin_dir_url( __FILE__ ) );

require_once SEF_DIR . 'includes/class-sef-settings.php';
require_once SEF_DIR . 'includes/class-sef-forms.php';
require_once SEF_DIR . 'includes/class-sef-template.php';
require_once SEF_DIR . 'includes/class-sef-frontend.php';
require_once SEF_DIR . 'includes/class-sef-promo.php';
require_once SEF_DIR . 'includes/class-sef-ajax.php';
require_once SEF_DIR . 'includes/class-sef-admin.php';
require_once SEF_DIR . 'includes/class-sef-updater.php';

SEF_Frontend::init();
SEF_Promo::init();
SEF_Ajax::init();
SEF_Updater::init(); // ενημερώσεις από το GitHub (και στο cron, όχι μόνο στη διαχείριση)
if ( is_admin() ) {
	SEF_Admin::init();
}
