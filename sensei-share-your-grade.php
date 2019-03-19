<?php
/**
 * Plugin Name: Sensei Share Your Grade
 * Plugin URI: https://woocommerce.com/products/sensei-share-your-grade/
 * Description: Hi, I'm here to help you share your course results via Twitter, Facebook and more, once you've completed a course.
 * Author: Automattic
 * Version: 1.0.3
 * Author URI: https://automattic.com/
 * Woo: 435830:700f6f6786c764debcd5dfb789f5f506
 *
 * Requires at least: 3.8
 * Tested up to: 4.1
 *
 * Text Domain: sensei-share-your-grade
 * Domain Path: /languages/
 *
 * @package Sensei_Share_Your_Grade
 * @category Extension
 * @author Matty Cohen
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once dirname( __FILE__ ) . '/includes/class-sensei-share-your-grade-dependency-checker.php';

if ( ! Sensei_Share_Your_Grade_Dependency_Checker::are_dependencies_met() ) {
	return;
}

define( 'SENSEI_SHARE_YOUR_GRADE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once dirname( __FILE__ ) . '/includes/class-sensei-share-your-grade.php';

Sensei_Share_Your_Grade();

/**
 * Returns the main instance of Sensei_Share_Your_Grade to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Sensei_Share_Your_Grade
 */
function Sensei_Share_Your_Grade() {
	return Sensei_Share_Your_Grade::instance( __FILE__, '1.0.3' );
} // End Sensei_Share_Your_Grade()
