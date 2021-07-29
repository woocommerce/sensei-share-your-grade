<?php
/**
 * Main Sensei_Share_Your_Grade Class
 *
 * @class   Sensei_Share_Your_Grade
 * @version	1.0.0
 * @since   1.0.0
 * @package	Sensei_Share_Your_Grade
 * @author  Automattic
 */
final class Sensei_Share_Your_Grade {
	/**
	 * Sensei_Share_Your_Grade The single instance of Sensei_Share_Your_Grade.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * The token.
	 * @var     string
	 * @access  private
	 * @since   1.0.0
	 */
	private $_token;

	/**
	 * The version number.
	 * @var     string
	 * @access  private
	 * @since   1.0.0
	 */
	private $_version;

	/**
	 * The plugin assets URL.
	 * @var     string
	 * @access  private
	 * @since   1.0.0
	 */
	private $assets_url;

	/**
	 * A collection of the data we're working with for the current course results.
	 * @var     array
	 * @access  private
	 * @since   1.0.0
	 */
	private $_course_data;

	/**
	 * A collection of the data we're working with for the current lesson results.
	 * @var     array
	 * @access  private
	 * @since   1.0.0
	 */
	private $_lesson_data;

	/**
	 * Whether or not we've output the Facebook JavaScript SDK.
	 * @var     boolean
	 * @access  private
	 * @since   1.0.0
	 */
	private $_has_output_fb_sdk;

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function __construct () {
		$this->_token             = 'sensei-share-your-grade';
		$this->_version           = SENSEI_SHARE_YOUR_GRADE_VERSION;
		$this->assets_url         = esc_url( trailingslashit( plugins_url( '/assets/dist/', SENSEI_SHARE_YOUR_GRADE_PLUGIN_FILE ) ) );
		$this->_has_output_fb_sdk = false;

		register_activation_hook( SENSEI_SHARE_YOUR_GRADE_PLUGIN_FILE, array( $this, 'install' ) );

		$this->load_plugin_textdomain();

	} // End __construct()

	/**
	 * Set up all actions and filters.
	 */
	public static function init() {
		$instance = self::instance();
		add_action( 'init', array( $instance, 'load_localisation' ), 0 );

		if ( ! Sensei_Share_Your_Grade_Dependency_Checker::are_plugin_dependencies_met() ) {
			return;
		}

		/**
		 * Returns the main instance of Sensei_Share_Your_Grade to prevent the need to use globals.
		 *
		 * @since  1.0.0
		 * @return object Sensei_Share_Your_Grade
		 */
		function Sensei_Share_Your_Grade() {
			return Sensei_Share_Your_Grade::instance();
		} // End Sensei_Share_Your_Grade()

		// Set up the data we will need for our output.
		add_action( 'sensei_course_results_content_inside_after', array( $instance, 'setup_course_data_before_output' ), 20 );
		add_action( 'sensei_single_lesson_content_inside_after', array( $instance, 'setup_lesson_data_before_output' ), 20 );
		add_action( 'sensei_quiz_back_link', array( $instance, 'setup_lesson_data_before_output' ), 4 );

		// Display a message when viewing course results.
		add_action( 'sensei_course_results_content_inside_after', array( $instance, 'output_sharing_message' ), 30 );
		add_action( 'sensei_single_lesson_content_inside_after', array( $instance, 'output_sharing_message' ), 30 );
		add_action( 'sensei_quiz_back_link', array( $instance, 'output_sharing_message' ), 5 );

		// Display sharing buttons when viewing course results.
		add_action( 'sensei_course_results_content_inside_after', array( $instance, 'output_sharing_buttons' ), 40 );
		add_action( 'sensei_single_lesson_content_inside_after', array( $instance, 'output_sharing_buttons' ), 30 );
		add_action( 'sensei_quiz_back_link', array( $instance, 'output_sharing_buttons' ), 5 );

		// Load frontend CSS
		add_action( 'wp_enqueue_scripts', array( $instance, 'enqueue_styles' ), 10 );

		add_action( 'admin_enqueue_scripts', [ $instance, 'enqueue_eol_style' ] );
		add_action( 'after_plugin_row_' . SENSEI_SHARE_YOUR_GRADE_PLUGIN_BASENAME, [ $instance, 'add_eol_message' ] );
	}

	/**
	 * Add the custom styling to hide the box shadow of the plugin row.
	 */
	public function enqueue_eol_style() {
		$screen = get_current_screen();

		if ( ! $screen || ! in_array( $screen->id, [ 'plugins', 'plugins-network' ], true ) ) {
			return;
		}

		wp_register_style( 'sensei-share-your-grade-eol-style', false );
		wp_enqueue_style( 'sensei-share-your-grade-eol-style' );
		wp_add_inline_style(
			'sensei-share-your-grade-eol-style',
			sprintf( '[data-plugin="%1$s"] td, [data-plugin="%1$s"] th { box-shadow: none!important;  }', SENSEI_SHARE_YOUR_GRADE_PLUGIN_BASENAME )
		);
	}

	/**
	 * Adds the end of maintenance message on the plugin listing.
	 */
	public function add_eol_message() {

		if ( is_network_admin() ) {
			$active_class = is_plugin_active_for_network( SENSEI_SHARE_YOUR_GRADE_PLUGIN_BASENAME ) ? ' active' : '';
		} else {
			$active_class = is_plugin_active( SENSEI_SHARE_YOUR_GRADE_PLUGIN_BASENAME ) ? ' active' : '';
		}

		/** @var WP_Plugins_List_Table $wp_list_table */
		$wp_list_table = _get_list_table(
			'WP_Plugins_List_Table',
			array(
				'screen' => get_current_screen(),
			)
		);

		printf(
			'<tr class="plugin-update-tr%s">' .
			'<td colspan="%s" class="plugin-update colspanchange">' .
			'<div class="notice inline notice-warning notice-alt"><p>',
			$active_class,
			esc_attr( $wp_list_table->get_column_count() ),
		);

		echo esc_html__( 'This plugin is no longer being maintained.', 'sensei-share-your-grade' );
		echo ' <a href="https://senseilms.com/2021/07/29/retiring-two-sensei-lms-extensions/" rel="noreferrer noopener">' . esc_html__( 'More information', 'sensei-share-your-grade' ) . '</a>';

		echo '</p></div></td></tr>';

	}

	/**
	 * Load the plugin's localisation file.
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function load_localisation () {
		load_plugin_textdomain( 'sensei-share-your-grade', false, dirname( SENSEI_SHARE_YOUR_GRADE_PLUGIN_BASENAME ) . '/languages/' );
	} // End load_localisation()

	/**
	 * Load the plugin textdomain from the main WordPress "languages" folder.
	 * @since  1.0.0
	 * @return  void
	 */
	public function load_plugin_textdomain () {
		$domain = 'sensei-share-your-grade';
		// The "plugin_locale" filter is also used in load_plugin_textdomain()
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, dirname( SENSEI_SHARE_YOUR_GRADE_PLUGIN_BASENAME ) . '/languages/' );
	} // End load_plugin_textdomain()

	/**
	 * Load frontend CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return void
	 */
	public function enqueue_styles () {
		global $woothemes_sensei;

		wp_register_style( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'css/frontend.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-frontend' );
	} // End enqueue_styles()

	/**
	 * Determines if we're on a single lesson/quiz page
	 * @access  public
	 * @since   1.0.0
	 * @return  boolean
	 */
	public function is_lesson() {
		global $post;
		if( 'lesson' == $post->post_type || 'quiz' == $post->post_type ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get the course URL for the current course/lesson
	 * @access  public
	 * @since   1.0.0
	 * @return  string
	 */
	public function get_course_url() {
		$course_url = '';
		if( $this->is_lesson() ) {
			$course_url = get_permalink( $this->_lesson_data['course_id'] );
		} else {
			$course_url = get_permalink( $this->_course_data['course_id'] );
		}
		$course_url = esc_url( $course_url );

		return $course_url;
	} // End get_course_url()

	/**
	 * Set up the necessary course data, before we begin output.
	 * @access  public
	 * @since   1.0.0
	 * @return  string
	 */
	public function setup_course_data_before_output () {
		global $course, $current_user;

		if ( ! is_a( $course, 'WP_Post' ) || ! is_a( $current_user, 'WP_User' ) ) return;

		$course_id = intval( $course->ID );
		$user_id = intval( $current_user->ID );

		$pass_mark = WooThemes_Sensei_Utils::sensei_course_pass_grade( $course_id );
		$user_grade = WooThemes_Sensei_Utils::sensei_course_user_grade( $course_id, $user_id );
		$has_passed = WooThemes_Sensei_Utils::sensei_user_passed_course( $course_id, $user_id );

		$args = array(
			'has_passed' => $has_passed,
			'pass_mark' => $pass_mark,
			'user_grade' => $user_grade,
			'course_id' => $course_id,
			'user_id' => $user_id
		);

		$this->set_current_course_data( $args );

		do_action( 'sensei_share_your_grade_setup_course_data_before_output' );
	} // End setup_data_before_output()

	/**
	 * Set up the necessary lesson data, before we begin output.
	 * @access  public
	 * @since   1.0.0
	 * @return  string
	 */
	public function setup_lesson_data_before_output () {
		global $woothemes_sensei, $post, $current_user;

		if ( ! ( is_singular( 'lesson' ) || is_singular( 'quiz' ) ) || ! is_a( $current_user, 'WP_User' ) || ! is_user_logged_in() ) return;

		// Get the lesson id
		if( 'lesson' == $post->post_type ) {
			$lesson_id = $post->ID;
		} elseif( 'quiz' == $post->post_type ) {
			$lesson_id = absint( get_post_meta( $post->ID, '_quiz_lesson', true ) );
		} else {
			return;
		}

		$course_id = get_post_meta( $lesson_id, '_lesson_course', true );
		$user_id = intval( $current_user->ID );
		$has_passed = false;

		// Find out if the user has passed the current lesson
		$user_lesson_status = WooThemes_Sensei_Utils::user_lesson_status( $lesson_id, $user_id );
		$has_passed = WooThemes_Sensei_Utils::user_completed_lesson( $user_lesson_status );
		if ( $has_passed ) {
			// Get Quiz ID
			$lesson_quiz_id = $woothemes_sensei->post_types->lesson->lesson_quizzes( $lesson_id );

			// Get the user's grade
			$user_grade = get_comment_meta( $user_lesson_status->comment_ID, 'grade', true );
			// and pass percentage
			$pass_mark = abs( round( doubleval( get_post_meta( $lesson_quiz_id, '_quiz_passmark', true ) ), 2 ) );
		}
		// No action required if the user hasn't passed the lesson
		if ( false == $has_passed ) {
			return;
		}

		$args = array(
			'has_passed' => $has_passed,
			'pass_mark' => $pass_mark,
			'user_grade' => $user_grade,
			'course_id' => $course_id,
			'user_id' => $user_id,
			'lesson_id' => $lesson_id
		);

		$this->set_current_lesson_data( $args );

		do_action( 'sensei_share_your_grade_setup_lesson_data_before_output' );
	} // End setup_data_before_output()

	/**
	 * Output some introductory text, as well as a message preview, for sharing.
	 * @access  public
	 * @since   1.0.0
	 * @return  string
	 */
	public function output_sharing_message () {
		$message = $this->get_message();
		if ( '' != $message ) {
			echo '<div class="sensei-share-your-grade message">' . "\n";
			echo apply_filters( 'sensei_share_your_grade_preview_heading', '<h2>' . __( 'Share your progress!', 'sensei-share-your-grade' ) . '</h2>' );
			echo sprintf( apply_filters( 'sensei_share_your_grade_preview_description', '<p>' . __( 'Go on, get social! Share your progress with your friends and family on social media.', 'sensei-share-your-grade' ) . '</p>' ) );
			echo '</div><!--/.sensei-share-your-grade message-->' . "\n";
		}
		do_action( 'sensei_share_your_grade_output_sharing_message' );
	} // End output_sharing_message()

	/**
	 * Output the sharing buttons.
	 * @access  public
	 * @since   1.0.0
	 * @return  string
	 */
	public function output_sharing_buttons () {
		$message = $this->get_message();
		if ( '' != $message ) {
			echo '<ul class="sensei-share-your-grade buttons">' . "\n";
			$networks = $this->_get_supported_networks();
			if ( 0 < count( $networks ) ) {
				foreach ( $networks as $k => $v ) {
					if ( '' != $v && 'method' != $v && function_exists( $v ) ) {
						echo '<li>';
						$v();
						echo '</li>';
					} else {
						if ( 'method' == $v && method_exists( $this, 'render_' . $k . '_button' ) ) {
							echo '<li>';
							$this->{'render_' . $k . '_button'}( $message );
							echo '</li>';
						}
					}
				}
			}
			echo '</ul><!--/.sensei-share-your-grade buttons-->' . "\n";
		}
		do_action( 'sensei_share_your_grade_output_sharing_buttons' );
	} // End output_sharing_buttons()

	/**
	 * Return a formatted Twitter sharing button.
	 * @access  public
	 * @since   1.0.0
	 * @return  string
	 */
	public function render_twitter_button ( $message, $args = array() ) {
		$defaults = array(
			// No url required as it's already in the message
			'url' => 'none',
			'via' => '',
			'text' => $message,
			'related' => '',
			'count' => 'none',
			'lang' => '',
			'counturl' => '',
			'hashtags' => '',
			'size' => '',
			'dnt' => ''
		);

		$args = (array)apply_filters( 'sensei_share_your_grade_twitter_button_args', $args );
		$args = wp_parse_args( $args, $defaults );

		// Make sure we have args. Otherwise, don't output.
		if ( 0 < count( $args ) ) {
			// If an argument is not in the defaults, remove it.
			foreach ( $args as $k => $v ) {
				if ( ! in_array( $k, array_keys( $defaults ) ) ) {
					unset( $args[$k] );
				}
			}

			// Prepare the "data" attributes.
			$atts = '';
			foreach ( $args as $k => $v ) {
				$atts .= ' data-' . $k . '="' . esc_attr( $v ) . '"';
			}

			$html = '<a href="https://twitter.com/share" class="twitter-share-button"' . $atts . '>' . __( 'Tweet your Grade', 'sensei-share-your-grade' ) . '</a>' . "\n";
			$html .= '<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="https://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>' . "\n";

			echo $html;
		}
	} // End render_twitter_button()

	/**
	 * Return a formatted Facebook sharing button.
	 * @access  public
	 * @since   1.0.0
	 * @return  string
	 */
	public function render_facebook_button ( $message = '', $args = array() ) {
		// Only output the Facebook JavaScript SDK once.
		if ( false == $this->_has_output_fb_sdk ) {
			echo '<div id="fb-root"></div>
					<script>(function(d, s, id) {
					  var js, fjs = d.getElementsByTagName(s)[0];
					  if (d.getElementById(id)) return;
					  js = d.createElement(s); js.id = id;
					  js.src = "//connect.facebook.net/en_GB/all.js#xfbml=1&appId=307306569286690";
					  fjs.parentNode.insertBefore(js, fjs);
					}(document, \'script\', \'facebook-jssdk\'));</script>' . "\n";
			$this->_has_output_fb_sdk = true;
		}

		$defaults = array(
			'href' => $this->get_course_url(),
			'type' => 'button'
		);

		$args = (array)apply_filters( 'sensei_share_your_grade_facebook_button_args', $args );
		$args = wp_parse_args( $args, $defaults );

		// Make sure we have args. Otherwise, don't output.
		if ( 0 < count( $args ) ) {
			// If an argument is not in the defaults, remove it.
			foreach ( $args as $k => $v ) {
				if ( ! in_array( $k, array_keys( $defaults ) ) ) {
					unset( $args[$k] );
				}
			}

			// Prepare the "data" attributes.
			$atts = '';
			foreach ( $args as $k => $v ) {
				$atts .= ' data-' . $k . '="' . esc_attr( $v ) . '"';
			}

			$html = '<div class="fb-share-button"' . $atts . '></div>' . "\n";

			echo $html;
		}
	} // End render_facebook_button()

	/**
	 * Return a formatted LinkedIn sharing button.
	 * @access  public
	 * @since   1.0.0
	 * @return  string
	 */
	public function render_linkedin_button ( $message = '', $args = array() ) {

		$defaults = array(
			'url' => $this->get_course_url(),
			'counter' => '' // Empty for no counter, 'top' for a top counter and 'right' for a right counter.
		);

		$args = (array)apply_filters( 'sensei_share_your_grade_linkedin_button_args', $args );
		$args = wp_parse_args( $args, $defaults );

		// Make sure we have args. Otherwise, don't output.
		if ( 0 < count( $args ) ) {
			// If an argument is not in the defaults, remove it.
			foreach ( $args as $k => $v ) {
				if ( ! in_array( $k, array_keys( $defaults ) ) ) {
					unset( $args[$k] );
				}
			}

			// Prepare the "data" attributes.
			$atts = '';
			foreach ( $args as $k => $v ) {
				if ( '' != $v ) {
					$atts .= ' data-' . $k . '="' . esc_attr( $v ) . '"';
				}
			}

			$html = '<script src="//platform.linkedin.com/in.js" type="text/javascript">lang: en_US</script>' . "\n";
			$html .= '<script type="IN/Share" ' . $atts . '></script>'. "\n";

			echo $html;
		}
	} // End render_linkedin_button()

	/**
	 * Return a formatted message to be shared.
	 * @access  public
	 * @since   1.0.0
	 * @return  string
	 */
	public function get_message () {
		global $post;
		if( $this->is_lesson() ) {
			// If lesson is not passed, leave the message blank so nothing will be output
			if ( ! isset( $this->_lesson_data['has_passed'] ) || true !== $this->_lesson_data['has_passed'] ) {
				$message = '';
				return $message;
			}
		}
		$status = $this->_get_status();
		$message = $this->_format_message( $this->get_message_template( $status ) );
		return apply_filters( 'sensei_share_your_grade_message', $message );
	} // End get_message()

	/**
	 * Return the appropriate text message templated, based on "passed" status.
	 * @access  public
	 * @since   1.0.0
	 * @param 	string $status "passed" or "failed."
	 * @return  string
	 */
	public function get_message_template ( $status = 'failed' ) {
		if ( 'passed' == $status ) {
			$template = $this->get_message_template_passed();
		} elseif ( 'completed' == $status ) {
			$template = $this->get_message_template_completed();
		} else {
			$template = $this->get_message_template_failed();
		}
		return $template;
	} // End get_message_template()

	/**
	 * Return a text template for the message to be shared, if the student has completed.
	 * @access  public
	 * @since   1.0.2
	 * @return  string
	 */
	public function get_message_template_completed () {
		return apply_filters( 'sensei_share_your_grade_message_template_completed', __( 'I just %%STATUS%% %%POST_NAME%%, over at %%SITE_NAME%%! Take the course, today! %%COURSE_PERMALINK%%', 'sensei-share-your-grade' ) );
	} // End get_message_template_completed()

	/**
	 * Return a text template for the message to be shared, if the student has passed.
	 * @access  public
	 * @since   1.0.0
	 * @return  string
	 */
	public function get_message_template_passed () {
		return apply_filters( 'sensei_share_your_grade_message_template_passed', __( 'I just %%STATUS%% %%POST_NAME%%, over at %%SITE_NAME%% with %%PERCENTAGE%%%! Take the course, today! %%COURSE_PERMALINK%%', 'sensei-share-your-grade' ) );
	} // End get_message_template_passed()

	/**
	 * Return a text template for the message to be shared, if the student has failed.
	 * @access  public
	 * @since   1.0.0
	 * @return  string
	 */
	public function get_message_template_failed () {
		return apply_filters( 'sensei_share_your_grade_message_template_failed', __( 'Cheer me on as I work to pass the %%POST_NAME%% course, over at %%SITE_NAME%%! Take the course with me, today! %%COURSE_PERMALINK%%', 'sensei-share-your-grade' ) );
	} // End get_message_template_failed()

	/**
	 * Return 'passed' or 'failed', depending on the user's status with the current course.
	 * @access  private
	 * @since   1.0.0
	 * @return  string
	 */
	private function _get_passed_or_failed () {
		return $this->_get_status();
	} // End _get_passed_or_failed()

	/**
	 * Return overall user's status, depending on the user's status with the current course or lesson and their grade.
	 * @access  private
	 * @since   1.0.2
	 * @return  string
	 */
	private function _get_status () {
		$template = 'failed';
		if ( isset( $this->_course_data['has_passed'] ) && true == $this->_course_data['has_passed'] ) {
			$template = 'passed';
		}
		elseif ( true == $this->_lesson_data['has_passed'] && 0 < intval($this->_lesson_data['user_grade']) ) {
			$template = 'passed';
		}
		elseif ( true == $this->_lesson_data['has_passed'] && 0 === intval($this->_lesson_data['user_grade']) ) {
			$template = 'completed';
		}
		return $template;
	} // End _get_status()

	/**
	 * Format the given message, replacing the various placeholders.
	 * @access  private
	 * @since   1.0.0
	 * @param 	string $unformatted_text The raw message template.
	 * @param 	object $course The course to format the message for.
	 * @param 	object $student The student object to format the message for.
	 * @return  string
	 */
	private function _format_message ( $unformatted_text ) {
		$message = $unformatted_text;
		$c_data = $this->_course_data;
		$l_data = $this->_lesson_data;

		$message = str_replace( '%%SITE_NAME%%', get_bloginfo( 'name' ), $message );
		$message = str_replace( '%%COURSE_PERMALINK%%', $this->get_course_url(), $message );

		if( $this->is_lesson() ) {
			$message = str_replace( '%%POST_NAME%%', get_the_title( $l_data['lesson_id'] ), $message );
			$message = str_replace( '%%STATUS%%', $l_data['status_text'], $message );
			$message = str_replace( '%%PERCENTAGE%%', intval( $l_data['user_grade'] ), $message );
		} else {
			$message = str_replace( '%%POST_NAME%%', get_the_title( $c_data['course_id'] ), $message );
			$message = str_replace( '%%STATUS%%', $c_data['status_text'], $message );
			$message = str_replace( '%%PERCENTAGE%%', intval( $c_data['user_grade'] ), $message );
		}

		return $message;
	} // End _format_message()

	/**
	 * Return a filtered array of supported networks. Users can specify a callback function for any custom sharing methods.
	 * @access  private
	 * @since   1.0.0
	 * @return  string
	 */
	private function _get_supported_networks () {
		return (array)apply_filters( 'sensei_share_your_grade_supported_networks', array( 'twitter' => 'method', 'facebook' => 'method', 'linkedin' => 'method' ) );
	} // End _get_supported_networks()

	/**
	 * Set the data we'll be using for the current course.
	 * @access  public
	 * @since   1.0.0
	 * @param 	array $args Arguments to store.
	 * @return  string
	 */
	public function set_current_course_data ( $args = array() ) {
		if ( 0 < count( $args ) ) {
			foreach ( $args as $k => $v ) {
				$this->_course_data[$k] = $v;
			}

			if ( isset( $this->_course_data['has_passed'] ) && true == $this->_course_data['has_passed'] ) {
				$this->_course_data['status_text'] = __( 'passed', 'sensei-share-your-grade' );
			} else {
				$this->_course_data['status_text'] = __( 'failed', 'sensei-share-your-grade' );
			}
		}
	} // End set_current_course_data()

	/**
	 * Set the data we'll be using for the current lesson.
	 * @access  public
	 * @since   1.0.0
	 * @param 	array $args Arguments to store.
	 * @return  string
	 */
	public function set_current_lesson_data ( $args = array() ) {
		if ( 0 < count( $args ) ) {
			foreach ( $args as $k => $v ) {
				$this->_lesson_data[$k] = $v;
			}

			if ( isset( $this->_lesson_data['has_passed'] ) && true == $this->_lesson_data['has_passed'] ) {
				if ( isset( $this->_lesson_data['user_grade'] ) && 0 === intval( $this->_lesson_data['user_grade'] ) ) {
					$this->_lesson_data['status_text'] = __( 'completed', 'sensei-share-your-grade' );
				} else {
					$this->_lesson_data['status_text'] = __( 'passed', 'sensei-share-your-grade' );
				}
			} else {
				$this->_lesson_data['status_text'] = __( 'failed', 'sensei-share-your-grade' );
			}
		}
	} // End set_current_lesson_data()

	/**
	 * Main Sensei_Share_Your_Grade Instance
	 *
	 * Ensures only one instance of Sensei_Share_Your_Grade is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Sensei_Share_Your_Grade()
	 * @return Sensei_Share_Your_Grade instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0.0' );
	} // End __clone()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0.0' );
	} // End __wakeup()

	/**
	 * Installation. Runs on activation.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function install () {
		$this->_log_version_number();
	} // End install()

	/**
	 * Log the plugin version number.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	private function _log_version_number () {
		// Log the version number.
		update_option( $this->_token . '_version', $this->_version );
	} // End _log_version_number()
} // End Class
