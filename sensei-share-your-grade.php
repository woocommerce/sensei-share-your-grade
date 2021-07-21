<?php
/**
 * Plugin Name: Sensei Share Your Grade
 * Plugin URI: https://woocommerce.com/products/sensei-share-your-grade/
 * Description: Let your students strut their stuff (and promote your course) by sharing their progress on social media. This plugin is no longer maintained.
 * Author: Automattic
 * Version: 2.0.2
 * Author URI: https://automattic.com/
 * Woo: 435830:700f6f6786c764debcd5dfb789f5f506
 *
 * Requires at least: 5.4
 * Tested up to: 5.6
 * Requires PHP: 7.0
 *
 * Text Domain: sensei-share-your-grade
 * Domain Path: /languages/
 *
 * @package Sensei_Share_Your_Grade
 * @category Extension
 * @author Matty Cohen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SENSEI_SHARE_YOUR_GRADE_VERSION', '2.0.2' );
define( 'SENSEI_SHARE_YOUR_GRADE_PLUGIN_FILE', __FILE__ );
define( 'SENSEI_SHARE_YOUR_GRADE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once dirname( __FILE__ ) . '/includes/class-sensei-share-your-grade-dependency-checker.php';

if ( ! Sensei_Share_Your_Grade_Dependency_Checker::are_system_dependencies_met() ) {
	return;
}

require_once dirname( __FILE__ ) . '/includes/class-sensei-share-your-grade.php';

// Load the plugin after all the other plugins have loaded.
add_action( 'plugins_loaded', array( 'Sensei_Share_Your_Grade', 'init' ), 5 );

Sensei_Share_Your_Grade::instance();
