<?php
/**
 * bbPress Report Content
 *
 * @package   bbpress-report-content
 * @author    Josh Eaton <josh@josheaton.org>
 * @license   GPL-2.0+
 * @link      http://www.josheaton.org/
 * @copyright 2013 Josh Eaton
 */

/**
 * bbPress Report Content class
 *
 * @package bbp_ReportContent
 * @author  Josh Eaton <josh@josheaton.org>
 */
class bbp_ReportContent {

	protected $version = '1.0.5';
	protected $plugin_slug = 'bbpress-report-content';
	protected static $instance = null;
	protected $plugin_screen_hook_suffix = null;
	protected $plugin_path = null;

	/**
	 * Initialize the plugin by setting localization, filters, and administration functions.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		// Get plugin path
		$this->plugin_path = dirname( plugin_dir_path( __FILE__ ) );

		// Stopgap in case somehow bbPress becomes inactive after activation
		add_action( 'admin_init', array( 'bbp_ReportContent', 'check_for_bbpress' ) );

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		/************************************************************************
		 * Admin
		 ***********************************************************************/
		// Register post status
		add_action( 'bbp_register_post_statuses',       array( $this, 'register_post_status'         )           );

		// Add admin row background color for .status-reported
		add_action( 'admin_head',                       array( $this, 'admin_css'                    )           );

		// Topic row actions, handler and notices
		add_filter( 'post_row_actions',                 array( $this, 'topic_row_actions'            ),   10,  2 );
		add_action( 'load-edit.php',                    array( $this, 'toggle_topic_admin_handler'   )           );
		add_filter( 'admin_notices',                    array( $this, 'toggle_topic_notice_admin'    )           );

		// Reply row actions, handler and notices
		add_filter( 'post_row_actions',                 array( $this, 'reply_row_actions'            ),   10,  2 );
		add_action( 'load-edit.php',                    array( $this, 'toggle_reply_admin_handler'   )           );
		add_filter( 'admin_notices',                    array( $this, 'toggle_reply_notice_admin'    )           );

		// Topic column headers
		add_filter( 'bbp_admin_topics_column_headers',  array( $this, 'admin_topics_column_headers'  )           );
		add_action( 'bbp_admin_topics_column_data',     array( $this, 'admin_topics_column_data'     ),   10,  2 );

		// Reply column headers
		add_filter( 'bbp_admin_replies_column_headers', array( $this, 'admin_replies_column_headers' )           );
		add_action( 'bbp_admin_replies_column_data',    array( $this, 'admin_replies_column_data'    ),   10,  2 );

		/************************************************************************
		 * Topics
		 ***********************************************************************/
		// Add status to list of topic statuses
		add_filter( 'bbp_get_topic_statuses',           array( $this, 'add_topic_status'      )           );

		// Add admin links
		add_filter( 'bbp_topic_admin_links',            array( $this, 'add_topic_admin_links' ),   10,  2 );

		// Report handler
		add_action( 'bbp_get_request',                  array( $this, 'toggle_topic_handler'  ),    1     );

		// Add post status to topics query for view=all
		add_filter( 'bbp_after_has_topics_parse_args',  array( $this, 'insert_report_status'  )           );

		// Add notice to reported topic
		add_action( 'bbp_template_before_single_topic', array( $this, 'output_topic_notice'   )           );


		/************************************************************************
		 * Replies
		 ***********************************************************************/
		// Add admin links
		add_filter( 'bbp_reply_admin_links',            array( $this, 'add_reply_admin_links' ),   10,  2 );

		// Report handler
		add_action( 'bbp_get_request',                  array( $this, 'toggle_reply_handler'  ),    1     );

		// Add post status to replies query for view=all
		add_filter( 'bbp_after_has_replies_parse_args', array( $this, 'insert_report_status'  )           );

		// Add notice to reported reply
		add_action( 'bbp_theme_before_reply_content',   array( $this, 'output_reply_notice'   )           );
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		if ( ! self::is_bbpress_active() ) {
			return;
		}

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/lang/' );
	}

	/**
	 * Checks if bbPress is active.
	 *
	 * @since 1.0.5
	 * @return boolean
	 */
	public static function is_bbpress_active() {
		return class_exists( 'bbPress' );
	}

	/**
	 * Check for bbPress on activation.
	 *
	 * @since 1.0.5
	 * @return void
	 */
	public static function activation_check() {
	    if ( ! self::is_bbpress_active() ) {
	        deactivate_plugins( plugin_basename( __FILE__ ) );
	        wp_die( __( 'bbPress - Report Content requires bbPress to be activated.', 'bbp-report-content' ) );
	    }
	}

	/**
	 * Check for bbPress on admin load.
	 *
	 * @since 1.0.5
	 * @return void
	 */
	public static function check_for_bbpress() {
		if ( ! self::is_bbpress_active() ) {
			if ( is_plugin_active( plugin_basename( __FILE__ ) ) ) {
				deactivate_plugins( plugin_basename( __FILE__ ) );

				add_action( 'admin_notices', array( 'bbp_ReportContent', 'disabled_notice' ) );

				if ( isset( $_GET['activate'] ) ) {
					unset( $_GET['activate'] );
				}
			}
		}
	}

	/**
	 * Display a notice when plugin is disabled due to missing dependencies.
	 *
	 * @since 1.0.5
	 * @return void
	 */
	public static function disabled_notice() {
		echo '<div class="updated"><p><strong>' . esc_html__( 'bbPress - Report Content was deactivated. This plugin requires bbPress to be active.', 'bbp-report-content' ) . '</strong></p></div>';
	}

	/**
	 * Display CSS in the admin
	 *
	 * It's only one class so saving an HTTP request by just including it inline.
	 *
	 * @since 1.0.0
	 *
	 * @return null
	 */
	public function admin_css() {
		// Only output for topics and replies
		if ( ! in_array( get_current_screen()->post_type, array(
			bbp_get_topic_post_type(),
			bbp_get_reply_post_type()
			)
		) )
			return;
		?>
		<style type="text/css">
			.status-reported { background-color: rgba(215, 44, 44, 0.1);}
		</style>
		<?php
	}

	/**
	 * Return the reported post status ID
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_reported_status_id() {
		return apply_filters( 'bbp_rc_reported_post_status',    'reported'    );
	}

	/**
	 * Register the custom post status used by the plugin
	 *
	 * @since 1.0.0
	 *
	 * @return null
	 */
	public function register_post_status() {
		// Reported
		register_post_status(
			$this->get_reported_status_id(),
			apply_filters( 'bbp_rc_register_reported_post_status', array(
				'label'                     => _x( 'User Reported', 'post', 'bbpress-report-content' ),
				'label_count'               => _nx_noop( 'User Reported <span class="count">(%s)</span>', 'User Reported <span class="count">(%s)</span>', 'post', 'bbpress-report-content' ),
				'public'                    => true,
				'exclude_from_search'       => true,
				'show_in_admin_status_list' => true,
				'show_in_admin_all_list'    => true
			) )
		 );
	}

	/**
	 * Add Topic Status
	 *
	 * Adds our custom status to the dropdown in the Topic Admin screen
	 *
	 * @param array $statuses list of statuses
	 * @return array
	 */
	public function add_topic_status( $statuses ) {
		// Add our custom status to the list
		$statuses[$this->get_reported_status_id()] = _x( 'Reported', 'Mark topic as reported', 'bbpress-report-content' );

		return $statuses;
	}

	/**
	 * Add our Report link to the topic admin links
	 *
	 * @since 1.0.0
	 *
	 * @param array $links list of admin links
	 * @param int $topic_id id of the current topic
	 *
	 * @return array
	 */
	public function add_topic_admin_links( $links, $topic_id ) {

		// Only display for logged in users
		if ( ! is_user_logged_in() )
			return $links;

		$args = array();

		// Parse arguments against default values
		$r = bbp_parse_args( $args, array (
			'id'     => $topic_id,
			'before' => '<span class="bbp-admin-links">',
			'after'  => '</span>',
			'sep'    => ' | ',
			'links'  => array()
		), 'get_topic_admin_links' );

		$links['report'] = $this->get_topic_report_link( $r );

		return $links;
	}

	/**
	 * Render the topic report admin link
	 *
	 * @param  array $args [description]
	 * @return string
	 */
	function get_topic_report_link( $args = '' ) {

		// Parse arguments against default values
		$r = bbp_parse_args( $args, array(
			'id'           => 0,
			'link_before'  => '',
			'link_after'   => '',
			'sep'          => ' | ',
			'report_text'    => esc_html__( 'Report',   'bbpress-report-content' ),
			'unreport_text'  => esc_html__( 'Unreport', 'bbpress-report-content' )
		), 'get_topic_report_link' );

		$topic = bbp_get_topic( bbp_get_topic_id( (int) $r['id'] ) );

		if ( empty( $topic ) )
			return;

		$reported = $this->is_topic_reported( $topic->ID );

		// Only display un-report link for moderators and up
		if ( $reported && ! current_user_can( 'moderate', $topic->ID ) ) {
			return;
		}

		$display = $reported ? $r['unreport_text'] : $r['report_text'];
		$uri     = add_query_arg( array( 'action' => 'bbp_rc_toggle_topic_report', 'topic_id' => $topic->ID ) );
		$uri     = wp_nonce_url( $uri, 'report-topic_' . $topic->ID );
		$classes = array( 'bbp-topic-report-link' );
		if ( true === $reported ) {
			$classes[] = 'reported';
		} else {
			$classes[] = 'unreported';
		}
		$retval  = $r['link_before'] . '<a href="' . esc_url( $uri ) . '" class="' . join( ' ', array_map( 'esc_attr', $classes ) ) . '" title="' . __( 'Report inappropriate content', 'bbpress-report-content' ) . '">' . $display . '</a>' . $r['link_after'];

		return apply_filters( 'bbp_rc_get_topic_report_link', $retval, $r );
	}

	/**
	 * Is the topic marked as reported?
	 *
	 * @since 1.0.0
	 *
	 * @param int $topic_id Optional. Topic id
	 * @uses bbp_get_topic_id() To get the topic id
	 * @uses bbp_get_topic_status() To get the topic status
	 * @uses apply_filters() Calls 'bbp_is_topic_reported' with the topic id
	 * @return bool True if reported, false if not.
	 */
	public function is_topic_reported( $topic_id = 0 ) {
		$topic_status = bbp_get_topic_status( bbp_get_topic_id( $topic_id ) ) === $this->get_reported_status_id();
		return (bool) apply_filters( 'bbp_rc_is_topic_reported', (bool) $topic_status, $topic_id );
	}

	/**
	 * Handles the front end reporting/un-reporting of topics
	 *
	 * @since 1.0.0
	 *
	 * @param string $action The requested action to compare this function to
	 */
	public function toggle_topic_handler( $action = '' ) {

		// Bail if required GET actions aren't passed
		if ( empty( $_GET['topic_id'] ) )
			return;

		// Setup possible get actions
		$possible_actions = array(
			'bbp_rc_toggle_topic_report',
		);

		// Bail if actions aren't meant for this function
		if ( !in_array( $action, $possible_actions ) )
			return;

		$failure   = '';                         // Empty failure string
		$view_all  = false;                      // Assume not viewing all
		$topic_id  = (int) $_GET['topic_id'];    // What's the topic id?
		$success   = false;                      // Flag
		$post_data = array( 'ID' => $topic_id ); // Prelim array
		$redirect  = '';                         // Empty redirect URL

		// Make sure topic exists
		$topic = bbp_get_topic( $topic_id );
		if ( empty( $topic ) )
			return;

		// Bail if non-logged-in user
		if ( ! is_user_logged_in() )
			return;

		// What action are we trying to perform?
		switch ( $action ) {

			// Toggle reported
			case 'bbp_rc_toggle_topic_report' :
				check_ajax_referer( 'report-topic_' . $topic_id );

				$is_reported  = $this->is_topic_reported( $topic_id );
				$success  = true === $is_reported ? $this->unreport_topic( $topic_id ) : $this->report_topic( $topic_id );
				$failure  = true === $is_reported ? __( '<strong>ERROR</strong>: There was a problem unmarking the topic as reported.', 'bbpress-report-content' ) : __( '<strong>ERROR</strong>: There was a problem reporting the topic.', 'bbpress-report-content' );
				// $view_all = !$is_reported; // Only need this if we want to hide it, like spam

				break;
		}

		// No errors
		if ( false !== $success && !is_wp_error( $success ) ) {

			// Redirect back to the topic's forum
			if ( isset( $sub_action ) && ( 'delete' === $sub_action ) ) {
				$redirect = bbp_get_forum_permalink( $success->post_parent );

			// Redirect back to the topic
			} else {

				// Get the redirect destination
				$permalink = bbp_get_topic_permalink( $topic_id );
				$redirect  = bbp_add_view_all( $permalink, $view_all );
			}

			wp_safe_redirect( $redirect );

			// For good measure
			exit();

		// Handle errors
		} else {
			bbp_add_error( 'bbp_rc_toggle_topic', $failure );
		}
	}

	/**
	 * Marks a topic as reported
	 *
	 * @since 1.0.0
	 *
	 * @param int $topic_id Topic id
	 * @uses bbp_get_topic() To get the topic
	 * @uses do_action() Calls 'bbp_rc_report_topic' with the topic id
	 * @uses add_post_meta() To add the previous status to a meta
	 * @uses wp_update_post() To update the topic with the new status
	 * @uses do_action() Calls 'bbp_rc_reported_topic' with the topic id
	 * @return mixed False or {@link WP_Error} on failure, topic id on success
	 */
	public function report_topic( $topic_id = 0 ) {

		// Get the topic
		$topic = bbp_get_topic( $topic_id );
		if ( empty( $topic ) )
			return $topic;

		// Bail if topic is reported
		if ( $this->get_reported_status_id() === $topic->post_status )
			return false;

		// Execute pre report code
		do_action( 'bbp_rc_report_topic', $topic_id );

		// TODO: Spam trashes replies, let's check if we should do anything with replies
		// when a topic is reported

		// Add the user id of the user who reported
		update_post_meta( $topic_id, '_bbp_report_user_id', wp_get_current_user()->ID );

		// Add the original post status as post meta for future restoration
		add_post_meta( $topic_id, '_bbp_report_meta_status', $topic->post_status );

		// Set post status to report
		$topic->post_status = $this->get_reported_status_id();

		// No revisions
		remove_action( 'pre_post_update', 'wp_save_post_revision' );

		// Update the topic
		$topic_id = wp_update_post( $topic );

		// Execute post report code
		do_action( 'bbp_rc_reported_topic', $topic_id );

		// Return topic_id
		return $topic_id;
	}

	/**
	 * Un-reports a topic
	 *
	 * @since 1.0.0
	 *
	 * @param int $topic_id Topic id
	 * @uses bbp_get_topic() To get the topic
	 * @uses do_action() Calls 'bbp_rc_unreport_topic' with the topic id
	 * @uses get_post_meta() To get the previous status
	 * @uses delete_post_meta() To delete the previous status meta
	 * @uses wp_update_post() To update the topic with the new status
	 * @uses do_action() Calls 'bbp_rc_unreported_topic' with the topic id
	 * @return mixed False or {@link WP_Error} on failure, topic id on success
	 */
	public function unreport_topic( $topic_id = 0 ) {

		// Get topic
		$topic = bbp_get_topic( $topic_id );
		if ( empty( $topic ) )
			return $topic;

		// Bail if already un-reported
		if ( $this->get_reported_status_id() !== $topic->post_status )
			return false;

		// Bail if user doesn't have moderate capability
		if ( !current_user_can( 'moderate', $topic->ID ) )
			return false;

		// Execute pre open code
		do_action( 'bbp_rc_unreport_topic', $topic_id );

		// Get previous status
		$topic_status       = get_post_meta( $topic_id, '_bbp_report_meta_status', true );

		// Set previous status
		$topic->post_status = $topic_status;

		// Remove old status meta
		delete_post_meta( $topic_id, '_bbp_report_meta_status' );

		// Add the user id of the user who reported
		delete_post_meta( $topic_id, '_bbp_report_user_id' );

		// No revisions
		remove_action( 'pre_post_update', 'wp_save_post_revision' );

		// Update topic
		$topic_id = wp_update_post( $topic );

		// Execute post open code
		do_action( 'bbp_rc_unreported_topic', $topic_id );

		// Return topic_id
		return $topic_id;
	}

	/**
	 * Add the reported status to the list of post statuses so it shows up in view=all
	 *
	 * Used in the has_topics and has_replies queries
	 *
	 * @param  array $r query args
	 * @return array
	 */
	public function insert_report_status( $r ) {

		// Ignore admin queries and only proceed if we're in a view=all query
		if ( is_admin() || !bbp_get_view_all() )
			return $r;

		if ( ! isset( $r['post_status'] ) )
			return $r;

		$statuses = explode( ',', $r['post_status'] );

		// Add our custom status to the has_topics query args
		$statuses[] = $this->get_reported_status_id();

		$r['post_status'] = implode( ',', $statuses );

		return $r;
	}


	/************************************************************************
	 * REPLIES
	 ***********************************************************************/

	/**
	 * Add our Report link to the reply admin links
	 *
	 * @since 1.0.0
	 *
	 * @param array $links list of admin links
	 * @param int $reply_id id of the current reply
	 *
	 * @return array
	 */
	public function add_reply_admin_links( $links, $reply_id ) {

		// Only display for logged in users
		if ( ! is_user_logged_in() )
			return $links;

		$args = array();

		$r = bbp_parse_args( $args, array(
			'id'     => $reply_id,
			'before' => '<span class="bbp-admin-links">',
			'after'  => '</span>',
			'sep'    => ' | ',
			'links'  => array()
		), 'get_reply_admin_links' );

		$links['report'] = $this->get_reply_report_link( $r );

		return $links;
	}

	/**
	 * Render the reply report admin link
	 *
	 * @param  array $args [description]
	 * @return string
	 */
	public function get_reply_report_link( $args = '' ) {

		// Parse arguments against default values
		$r = bbp_parse_args( $args, array(
			'id'           => 0,
			'link_before'  => '',
			'link_after'   => '',
			'report_text'    => esc_html__( 'Report',   'bbpress-report-content' ),
			'unreport_text'  => esc_html__( 'Unreport', 'bbpress-report-content' )
		), 'get_reply_report_link' );

		$reply = bbp_get_reply( bbp_get_reply_id( (int) $r['id'] ) );

		if ( empty( $reply ) )
			return;

		$reported = $this->is_reply_reported( $reply->ID );

		// Only display un-report link for
		if ( $reported && ! current_user_can( 'moderate', $reply->ID ) ) {
			return;
		}

		$display  = $reported ? $r['unreport_text'] : $r['report_text'];
		$uri      = add_query_arg( array( 'action' => 'bbp_rc_toggle_reply_report', 'reply_id' => $reply->ID ) );
		$uri      = wp_nonce_url( $uri, 'report-reply_' . $reply->ID );
		$classes = array( 'bbp-reply-report-link' );
		if ( true === $reported ) {
			$classes[] = 'reported';
		} else {
			$classes[] = 'unreported';
		}
		$retval   = $r['link_before'] . '<a href="' . esc_url( $uri ) . '" class="' . join( ' ', array_map( 'esc_attr', $classes ) ) . '" title="' . __( 'Report inappropriate content', 'bbpress-report-content' ) . '">' . $display . '</a>' . $r['link_after'];

		return apply_filters( 'bbp_rc_get_reply_report_link', $retval, $r );
	}

	/**
	 * Is the reply marked as reported?
	 *
	 * @since 1.0.0
	 *
	 * @param int $reply_id Optional. Reply id
	 * @uses bbp_get_reply_id() To get the reply id
	 * @uses bbp_get_reply_status() To get the reply status
	 * @return bool True if report, false if not.
	 */
	public function is_reply_reported( $reply_id = 0 ) {
		$reply_status = bbp_get_reply_status( bbp_get_reply_id( $reply_id ) ) === $this->get_reported_status_id();
		return (bool) apply_filters( 'bbp_rc_is_reply_reported', (bool) $reply_status, $reply_id );
	}


	/**
	 * Handles the front end reporting/un-reporting of replies
	 *
	 * @since 1.0.0
	 *
	 * @param string $action The requested action to compare this function to
	 */
	public function toggle_reply_handler( $action = '' ) {

		// Bail if required GET actions aren't passed
		if ( empty( $_GET['reply_id'] ) )
			return;

		// Setup possible get actions
		$possible_actions = array(
			'bbp_rc_toggle_reply_report',
		);

		// Bail if actions aren't meant for this function
		if ( !in_array( $action, $possible_actions ) )
			return;

		$failure   = '';                         // Empty failure string
		$view_all  = false;                      // Assume not viewing all
		$reply_id  = (int) $_GET['reply_id'];    // What's the reply id?
		$success   = false;                      // Flag
		$post_data = array( 'ID' => $reply_id ); // Prelim array
		$redirect  = '';                         // Empty redirect URL

		// Make sure reply exists
		$reply = bbp_get_reply( $reply_id );
		if ( empty( $reply ) )
			return;

		// Bail if non-logged-in user
		if ( ! is_user_logged_in() )
			return;

		// What action are we trying to perform?
		switch ( $action ) {

			// Toggle reported
			case 'bbp_rc_toggle_reply_report' :
				check_ajax_referer( 'report-reply_' . $reply_id );

				$is_reported  = $this->is_reply_reported( $reply_id );
				$success  = true === $is_reported ? $this->unreport_reply( $reply_id ) : $this->report_reply( $reply_id );
				$failure  = true === $is_reported ? __( '<strong>ERROR</strong>: There was a problem unmarking the reply as reported.', 'bbpress-report-content' ) : __( '<strong>ERROR</strong>: There was a problem reporting the reply.', 'bbpress-report-content' );
				// $view_all = !$is_reported; // Only need this if we want to hide it, like spam

				break;
		}

		// No errors
		if ( ( false !== $success ) && !is_wp_error( $success ) ) {

			/** Redirect **********************************************************/

			// Redirect to
			$redirect_to = bbp_get_redirect_to();

			// Get the reply URL
			$reply_url = bbp_get_reply_url( $reply_id, $redirect_to );

			// Add view all if needed
			if ( !empty( $view_all ) )
				$reply_url = bbp_add_view_all( $reply_url, true );

			// Redirect back to reply
			wp_safe_redirect( $reply_url );

			// For good measure
			exit();

		// Handle errors
		} else {
			bbp_add_error( 'bbp_rc_toggle_reply', $failure );
		}
	}

	/**
	 * Marks a reply as reported
	 *
	 * @since bbPress (r2740)
	 *
	 * @param int $reply_id Reply id
	 * @uses bbp_get_reply() To get the reply
	 * @uses do_action() Calls 'bbp_rc_reported_reply' with the reply ID
	 * @uses add_post_meta() To add the previous status to a meta
	 * @uses wp_update_post() To insert the updated post
	 * @uses do_action() Calls 'bbp_rc_reported_reply' with the reply ID
	 * @return mixed False or {@link WP_Error} on failure, reply id on success
	 */
	function report_reply( $reply_id = 0 ) {

		// Get reply
		$reply = bbp_get_reply( $reply_id );
		if ( empty( $reply ) )
			return $reply;

		// Bail if already reported
		if ( $this->get_reported_status_id() === $reply->post_status )
			return false;

		// Execute pre report code
		do_action( 'bbp_rc_report_reply', $reply_id );

		// Add the user id of the user who reported
		update_post_meta( $reply_id, '_bbp_report_user_id', wp_get_current_user()->ID );

		// Add the original post status as post meta for future restoration
		add_post_meta( $reply_id, '_bbp_report_meta_status', $reply->post_status );

		// Set post status to report
		$reply->post_status = $this->get_reported_status_id();

		// No revisions
		remove_action( 'pre_post_update', 'wp_save_post_revision' );

		// Update the reply
		$reply_id = wp_update_post( $reply );

		// Execute post report code
		do_action( 'bbp_rc_reported_reply', $reply_id );

		// Return reply_id
		return $reply_id;
	}

	/**
	 * Un-reports a reply
	 *
	 * @@since 1.0.0
	 *
	 * @param int $reply_id Reply id
	 * @uses bbp_get_reply() To get the reply
	 * @uses do_action() Calls 'bbp_rc_unreport_reply' with the reply ID
	 * @uses get_post_meta() To get the previous status meta
	 * @uses delete_post_meta() To delete the previous status meta
	 * @uses wp_update_post() To insert the updated post
	 * @uses do_action() Calls 'bbp_rc_unreported_reply' with the reply ID
	 * @return mixed False or {@link WP_Error} on failure, reply id on success
	 */
	function unreport_reply( $reply_id = 0 ) {

		// Get reply
		$reply = bbp_get_reply( $reply_id );
		if ( empty( $reply ) )
			return $reply;

		// Bail if already not reported
		if ( $this->get_reported_status_id() !== $reply->post_status )
			return false;

		// Bail if user doesn't have moderate capability
		if ( !current_user_can( 'moderate', $reply->ID ) )
			return false;

		// Execute pre unreport code
		do_action( 'bbp_rc_unreport_reply', $reply_id );

		// Get pre report status
		$reply->post_status = get_post_meta( $reply_id, '_bbp_report_meta_status', true );

		// If no previous status, default to publish
		if ( empty( $reply->post_status ) ) {
			$reply->post_status = bbp_get_public_status_id();
		}

		// Delete pre report meta
		delete_post_meta( $reply_id, '_bbp_report_meta_status' );

		// Add the user id of the user who reported
		delete_post_meta( $reply_id, '_bbp_report_user_id' );

		// No revisions
		remove_action( 'pre_post_update', 'wp_save_post_revision' );

		// Update the reply
		$reply_id = wp_update_post( $reply );

		// Execute post unreport code
		do_action( 'bbp_rc_unreported_reply', $reply_id );

		// Return reply_id
		return $reply_id;
	}

	/**
	 * Ouput a notice on the front end when a topic has been reported
	 *
	 * @return null
	 */
	public function output_topic_notice() {
		global $post;

		if ( ! $this->is_topic_reported( get_the_ID() ) )
			return;

		echo '<div class="bbp-template-notice error bbp-rc-topic-is-reported">';
			echo '<p>';
				echo apply_filters( 'bbp_rc_topic_notice', __( 'This topic has been reported for inappropriate content', 'bbpress-report-content' ) );
			echo '</p>';
		echo '</div>';
	}

	/**
	 * Ouput a notice on the front end when a reply has been reported
	 *
	 * @return null
	 */
	public function output_reply_notice() {
		global $post;

		$reply_id = get_the_ID();

		// If post is a topic, return. (handled with 'output_topic_notice')
		if ( bbp_is_topic( $reply_id ) ) {
			return;
		}

		if ( ! $this->is_reply_reported( $reply_id ) )
			return;

		echo '<div class="error bbp-rc-reply-is-reported">';
			echo '<p>';
				echo apply_filters( 'bbp_rc_reply_notice', __( '<em>This reply has been reported for inappropriate content.</em>', 'bbpress-report-content' ) );
			echo '</p>';
		echo '</div>';
	}

	/**
	 * Topic Row actions
	 *
	 * Add "unreport" link to reported Topics
	 *
	 * @since 1.0.0
	 *
	 * @param array $actions Actions
	 * @param array $topic Topic object
	 * @return array $actions Actions
	 */
	public function topic_row_actions( $actions, $topic ) {

		// Bail if we're not editing topics
		if ( bbp_get_topic_post_type() != get_current_screen()->post_type )
			return $actions;

		// Only show the actions if the user is capable of viewing them :)
		if ( current_user_can( 'moderate', $topic->ID ) ) {

			// Report
			$report_uri  = wp_nonce_url( add_query_arg( array( 'topic_id' => $topic->ID, 'action' => 'bbp_rc_toggle_topic_report' ), remove_query_arg( array( 'bbp_topic_toggle_notice', 'topic_id', 'failed', 'super' ) ) ), 'report-topic_'  . $topic->ID );
			if ( $this->is_topic_reported( $topic->ID ) )
				$actions['report'] = '<a href="' . esc_url( $report_uri ) . '" title="' . esc_attr__( 'Mark the topic as unreported', 'bbpress-report-content' ) . '">' . esc_html__( 'Unreport', 'bbpress-report-content' ) . '</a>';
		}

		return $actions;
	}

	/**
	 * Handle admin topic toggling
	 *
	 * @since 1.0.0
	 *
	 * @param string $action The requested action to compare this function to
	 */
	public function toggle_topic_admin_handler() {

		// Bail if we're not editing topics
		if ( bbp_get_topic_post_type() != get_current_screen()->post_type )
			return;

		// Only proceed if GET is a topic toggle action
		if ( bbp_is_get_request() && !empty( $_GET['action'] ) && in_array( $_GET['action'], array( 'bbp_rc_toggle_topic_report' ) ) && !empty( $_GET['topic_id'] ) ) {
			$action    = $_GET['action'];            // What action is taking place?
			$topic_id  = (int) $_GET['topic_id'];    // What's the topic id?
			$success   = false;                      // Flag
			$post_data = array( 'ID' => $topic_id ); // Prelim array
			$topic     = bbp_get_topic( $topic_id );

			// Bail if topic is missing
			if ( empty( $topic ) )
				wp_die( __( 'The topic was not found!', 'bbpress-report-content' ) );

			if ( !current_user_can( 'moderate', $topic->ID ) ) // What is the user doing here?
				wp_die( __( 'You do not have the permission to do that!', 'bbpress-report-content' ) );

			switch ( $action ) {
				case 'bbp_rc_toggle_topic_report' :
					check_admin_referer( 'report-topic_' . $topic_id );

					$is_reported  = $this->is_topic_reported( $topic_id );
					$message      = true === $is_reported ? 'unreported' : 'reported';
					$success      = true === $is_reported ? $this->unreport_topic( $topic_id ) : $this->report_topic( $topic_id );

					break;
			}

			$message = array( 'bbp_topic_toggle_notice' => $message, 'topic_id' => $topic->ID );

			if ( false === $success || is_wp_error( $success ) )
				$message['failed'] = '1';

			// Redirect back to the topic
			$redirect = add_query_arg( $message, remove_query_arg( array( 'action', 'topic_id' ) ) );
			wp_safe_redirect( $redirect );

			// For good measure
			exit();
		} // end if GET request, etc.
	}

	/**
	 * Prints an admin notice when a topic has been (un)reported
	 *
	 * @since 1.0.0
	 *
	 * @return null
	 */
	public function toggle_topic_notice_admin() {

		// Bail if we're not editing topics
		if ( bbp_get_topic_post_type() != get_current_screen()->post_type )
			return;

		// Only proceed if GET is a topic toggle action
		if ( bbp_is_get_request() && !empty( $_GET['bbp_topic_toggle_notice'] ) && in_array( $_GET['bbp_topic_toggle_notice'], array( 'unreported' ) ) && !empty( $_GET['topic_id'] ) ) {
			$notice     = $_GET['bbp_topic_toggle_notice'];         // Which notice?
			$topic_id   = (int) $_GET['topic_id'];                  // What's the topic id?
			$is_failure = !empty( $_GET['failed'] ) ? true : false; // Was that a failure?

			// Bails if no topic_id or notice
			if ( empty( $notice ) || empty( $topic_id ) )
				return;

			// Bail if topic is missing
			$topic = bbp_get_topic( $topic_id );
			if ( empty( $topic ) )
				return;

			$topic_title = bbp_get_topic_title( $topic->ID );

			switch ( $notice ) {
				case 'unreported'    :
					$message = $is_failure === true ? sprintf( __( 'There was a problem unreporting the topic "%1$s".', 'bbpress-report-content' ), $topic_title ) : sprintf( __( 'Topic "%1$s" successfully unreported.', 'bbpress-report-content' ), $topic_title );
					break;
			}

			?>

			<div id="message" class="<?php echo $is_failure === true ? 'error' : 'updated'; ?> fade">
				<p style="line-height: 150%"><?php echo esc_html( $message ); ?></p>
			</div>

			<?php
		}
	}

	/**
	 * Reply Row actions
	 *
	 * Add "unreport" link to reported Replies
	 *
	 * @since 1.0.0
	 *
	 * @param array $actions Actions
	 * @param array $reply Reply object
	 * @return array $actions Actions
	 */
	public function reply_row_actions( $actions, $reply ) {

		// Bail if we're not editing replies
		if ( bbp_get_reply_post_type() != get_current_screen()->post_type )
			return $actions;

		// Only show the actions if the user is capable of viewing them :)
		if ( current_user_can( 'moderate', $reply->ID ) ) {

			// Report
			$report_uri  = wp_nonce_url( add_query_arg( array( 'reply_id' => $reply->ID, 'action' => 'bbp_rc_toggle_reply_report' ), remove_query_arg( array( 'bbp_reply_toggle_notice', 'reply_id', 'failed', 'super' ) ) ), 'report-reply_'  . $reply->ID );
			if ( $this->is_reply_reported( $reply->ID ) )
				$actions['report'] = '<a href="' . esc_url( $report_uri ) . '" title="' . esc_attr__( 'Mark the reply as unreported', 'bbpress-report-content' ) . '">' . esc_html__( 'Unreport', 'bbpress-report-content' ) . '</a>';
		}

		return $actions;
	}

	/**
	 * Handle admin reply toggling
	 *
	 * @since 1.0.0
	 *
	 * @param string $action The requested action to compare this function to
	 */
	public function toggle_reply_admin_handler() {

		// Bail if we're not editing replies
		if ( bbp_get_reply_post_type() != get_current_screen()->post_type )
			return;

		// Only proceed if GET is a reply toggle action
		if ( bbp_is_get_request() && !empty( $_GET['action'] ) && in_array( $_GET['action'], array( 'bbp_rc_toggle_reply_report' ) ) && !empty( $_GET['reply_id'] ) ) {
			$action    = $_GET['action'];            // What action is taking place?
			$reply_id  = (int) $_GET['reply_id'];    // What's the reply id?
			$success   = false;                      // Flag
			$post_data = array( 'ID' => $reply_id ); // Prelim array
			$reply     = bbp_get_reply( $reply_id );

			// Bail if reply is missing
			if ( empty( $reply ) )
				wp_die( __( 'The reply was not found!', 'bbpress-report-content' ) );

			if ( !current_user_can( 'moderate', $reply->ID ) ) // What is the user doing here?
				wp_die( __( 'You do not have the permission to do that!', 'bbpress-report-content' ) );

			switch ( $action ) {
				case 'bbp_rc_toggle_reply_report' :
					check_admin_referer( 'report-reply_' . $reply_id );

					$is_reported  = $this->is_reply_reported( $reply_id );
					$message      = true === $is_reported ? 'unreported' : 'reported';
					$success      = true === $is_reported ? $this->unreport_reply( $reply_id ) : $this->report_reply( $reply_id );

					break;
			}

			$message = array( 'bbp_reply_toggle_notice' => $message, 'reply_id' => $reply->ID );

			if ( false === $success || is_wp_error( $success ) )
				$message['failed'] = '1';

			// Redirect back to the reply
			$redirect = add_query_arg( $message, remove_query_arg( array( 'action', 'reply_id' ) ) );
			wp_safe_redirect( $redirect );

			// For good measure
			exit();
		} // end if GET request, etc.
	}

	/**
	 * Prints an admin notice when a reply has been (un)reported
	 *
	 * @since 1.0.0
	 *
	 * @return null
	 */
	public function toggle_reply_notice_admin() {

		// Bail if we're not editing replies
		if ( bbp_get_reply_post_type() != get_current_screen()->post_type )
			return;

		// Only proceed if GET is a reply toggle action
		if ( bbp_is_get_request() && !empty( $_GET['bbp_reply_toggle_notice'] ) && in_array( $_GET['bbp_reply_toggle_notice'], array( 'unreported' ) ) && !empty( $_GET['reply_id'] ) ) {
			$notice     = $_GET['bbp_reply_toggle_notice'];         // Which notice?
			$reply_id   = (int) $_GET['reply_id'];                  // What's the reply id?
			$is_failure = !empty( $_GET['failed'] ) ? true : false; // Was that a failure?

			// Bails if no reply_id or notice
			if ( empty( $notice ) || empty( $reply_id ) )
				return;

			// Bail if reply is missing
			$reply = bbp_get_reply( $reply_id );
			if ( empty( $reply ) )
				return;

			$reply_title = bbp_get_reply_title( $reply->ID );

			switch ( $notice ) {
				case 'unreported'    :
					$message = $is_failure === true ? sprintf( __( 'There was a problem unreporting the reply "%1$s".', 'bbpress-report-content' ), $reply_title ) : sprintf( __( 'Reply "%1$s" successfully unreported.', 'bbpress-report-content' ), $reply_title );
					break;
			}

			?>

			<div id="message" class="<?php echo $is_failure === true ? 'error' : 'updated'; ?> fade">
				<p style="line-height: 150%"><?php echo esc_html( $message ); ?></p>
			</div>

			<?php
		}
	}

	/**
	 * Add "reported by" column to Topics admin screen
	 *
	 * @param  array $columns admin columns
	 * @return array
	 */
	public function admin_topics_column_headers( $columns ) {

		if ( !isset($_GET['post_status']) || 'reported' != $_GET['post_status'] || bbp_get_topic_post_type() != get_current_screen()->post_type )
			return $columns;

		// Add "reported by" column
		$columns['bbp_rc_user'] = __( 'Reported By', 'bbpress-report-content' );

		return $columns;
	}

	/**
	 * Render the admin topic column data
	 *
	 * @param  string $column current admin column
	 * @param  int $topic_id the topic
	 * @return null
	 */
	public function admin_topics_column_data( $column, $topic_id ) {

		if ( !isset($_GET['post_status']) || 'reported' != $_GET['post_status'] || bbp_get_topic_post_type() != get_current_screen()->post_type )
			return;

		switch ( $column ) {
			case 'bbp_rc_user':
				$user_id = get_post_meta( $topic_id, '_bbp_report_user_id', true );
				$user    = $this->get_username( $user_id );
				$link    = '<a class="bbp-rc-user-col" href="' . admin_url( 'user-edit.php?user_id=' . intval($user_id) ) . '">' . esc_html( $user ) . '</a>';
				echo $link;
				break;
		}
	}

	/**
	 * Add "reported by" column to Replies admin screen
	 *
	 * @param  array $columns admin columns
	 * @return array
	 */
	public function admin_replies_column_headers( $columns ) {

		if ( !isset($_GET['post_status']) || 'reported' != $_GET['post_status'] || bbp_get_reply_post_type() != get_current_screen()->post_type )
			return $columns;

		// Add "reported by" column
		$columns['bbp_rc_user'] = __( 'Reported By', 'bbpress-report-content' );

		return $columns;
	}

	/**
	 * Render the admin reply column data
	 *
	 * @param  string $column current admin column
	 * @param  int $reply_id the reply
	 * @return null
	 */
	public function admin_replies_column_data( $column, $reply_id ) {

		if ( !isset($_GET['post_status']) || 'reported' != $_GET['post_status'] || bbp_get_reply_post_type() != get_current_screen()->post_type )
			return;

		switch ( $column ) {
			case 'bbp_rc_user':
				$user_id = get_post_meta( $reply_id, '_bbp_report_user_id', true );
				$user    = $this->get_username( $user_id );
				$link    = '<a class="bbp-rc-user-col" href="' . admin_url( 'user-edit.php?user_id=' . intval($user_id) ) . '">' . esc_html( $user ) . '</a>';
				echo $link;
				break;
		}
	}

	/**
	 * Helper to get username whether user is logged in or not
	 *
	 * @param  [int]     $user_id [WP user id]
	 * @return [string]           [WP user name]
	 */
	function get_username( $user_id ) {
		// Check if user is logged in
		if ( 0 != $user_id ) {
			$user = get_userdata( $user_id );
			$username = $user->user_login;
		} else {
			$username = __('Guest', 'bbpress-report-content');
		}

		return $username;
	}

} // end class bbp_ReportContent
