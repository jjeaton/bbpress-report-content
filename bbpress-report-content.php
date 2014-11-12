<?php
/**
 * bbPress - Report Content
 *
 * Allows users to report inappropriate content in topics and replies.
 *
 * @package   bbpress-report-content
 * @author    Josh Eaton <josh@josheaton.org>
 * @license   GPL-2.0+
 * @link      http://www.josheaton.org/
 * @copyright 2013 Josh Eaton
 *
 * @wordpress-plugin
 * Plugin Name: bbPress - Report Content
 * Plugin URI:  http://www.josheaton.org/wordpress-plugins/bbpress-report-content/
 * Description: Allows users to report inappropriate content in topics and replies.
 * Version:     1.0.5
 * Author:      Josh Eaton
 * Author URI:  http://www.josheaton.org/
 * Text Domain: bbpress-report-content
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path: /lang
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Include the plugin class
require_once( plugin_dir_path( __FILE__ ) . 'classes/class-bbpress-report-content.php' );

// Get the class instance
add_action( 'plugins_loaded', array( 'bbp_ReportContent', 'get_instance' ) );

// Register activation hook
register_activation_hook( __FILE__, array( 'bbp_ReportContent', 'activation_check' ) );