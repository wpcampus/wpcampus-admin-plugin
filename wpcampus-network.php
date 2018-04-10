<?php
/**
 * Plugin Name:       WPCampus: Network
 * Plugin URI:        https://wpcampus.org
 * Description:       Manages network-wide functionality for the WPCampus network of sites.
 * Version:           1.0.0
 * Author:            WPCampus
 * Author URI:        https://wpcampus.org
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wpcampus
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) or die();

require_once wpcampus_network()->plugin_dir . 'inc/class-wpcampus-network-global.php';
require_once wpcampus_network()->plugin_dir . 'inc/wpcampus-forms.php';

/**
 * Class WPCampus_Network
 */
class WPCampus_Network {

	/**
	 * Holds the directory path
	 * and absolute URL to the
	 * main plugin directory.
	 *
	 * @var string
	 */
	public $plugin_dir,
		$plugin_url,
		$plugin_base;

	/**
	 * Holds the URL to the main site.
	 *
	 * @var string
	 */
	private $network_site_url;

	/**
	 * Whether or not we want
	 * to print the network banner,
	 * notifications, or footer.
	 *
	 * @var string
	 */
	public $enable_network_banner,
		$enable_network_notifications,
		$enable_network_footer,
		$enable_watch_videos,
		$enable_mailchimp_popup;

	/**
	 * Holds the class instance.
	 *
	 * @var WPCampus_Network
	 */
	private static $instance;

	/**
	 * Returns the instance of this class.
	 *
	 * @return WPCampus_Network
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			$class_name = __CLASS__;
			self::$instance = new $class_name;
		}
		return self::$instance;
	}

	/**
	 * Warming up the engine.
	 */
	protected function __construct() {

		$this->plugin_dir = plugin_dir_path( __FILE__ );
		$this->plugin_url = plugin_dir_url( __FILE__ );
		$this->plugin_base = dirname( plugin_basename( __FILE__ ) );

	}

	/**
	 * Method to keep our instance from
	 * being cloned or unserialized.
	 *
	 * @return void
	 */
	private function __clone() {}
	private function __wakeup() {}

	/**
	 * Get the main site URL.
	 */
	public function get_network_site_url() {
		if ( isset( $this->network_site_url ) ) {
			return $this->network_site_url;
		}
		$this->network_site_url = network_site_url();
		return $this->network_site_url;
	}

	/**
	 * Gets markup for list of social media icons.
	 *
	 * @return string|HTML - the markup.
	 */
	public function get_social_media_icons() {

		$images_dir = $this->plugin_dir . 'assets/images/';
		$social = array(
			'slack' => array(
				'title' => sprintf( __( 'Join %1$s on %2$s', 'wpcampus' ), 'WPCampus', 'Slack' ),
				'href'  => 'https://wpcampus.org/get-involved/',
			),
			'twitter' => array(
				'title' => sprintf( __( 'Follow %1$s on %2$s', 'wpcampus' ), 'WPCampus', 'Twitter' ),
				'href'  => 'https://twitter.com/wpcampusorg',
			),
			'facebook' => array(
				'title' => sprintf( __( 'Follow %1$s on %2$s', 'wpcampus' ), 'WPCampus', 'Facebook' ),
				'href'  => 'https://www.facebook.com/wpcampus',
			),
			'youtube' => array(
				'title' => sprintf( __( 'Follow %1$s on %2$s', 'wpcampus' ), 'WPCampus', 'YouTube' ),
				'href'  => 'https://www.youtube.com/wpcampusorg',
			),
			'github' => array(
				'title' => sprintf( __( 'Follow %1$s on %2$s', 'wpcampus' ), 'WPCampus', 'GitHub' ),
				'href'  => 'https://github.com/wpcampus/',
			),
		);

		$icons = '<ul class="social-media-icons" role="navigation">';

		foreach ( $social as $key => $info ) {
			$filename = "{$images_dir}{$key}.php";
			if ( file_exists( $filename ) ) {
				$icons .= sprintf( '<li class="%1$s"><a href="%2$s" title="%3$s">%4$s</a></li>',
					$key,
					$info['href'],
					$info['title'],
					file_get_contents( $filename )
				);
			}
		}

		$icons .= '</ul>';

		return $icons;
	}

	/**
	 * Prints markup for list of social media icons.
	 *
	 * @return void
	 */
	public function print_social_media_icons() {
		echo $this->get_social_media_icons();
	}

	/**
	 * Enable and disable the network banner.
	 *
	 * We need this to know whether or not to enqueue styles.
	 *
	 * @return void
	 */
	public function enable_network_banner() {
		$this->enable_network_banner = true;
	}
	public function disable_network_banner() {
		$this->enable_network_banner = false;
	}

	/**
	 * Enable and disable the network notifications.
	 *
	 * We need this to know whether or not to enqueue styles.
	 *
	 * @return void
	 */
	public function enable_network_notifications() {
		$this->enable_network_notifications = true;
	}
	public function disable_network_notifications() {
		$this->enable_network_notifications = false;
	}

	/**
	 * Enable and disable the network footer.
	 *
	 * We need this to know whether or not to enqueue styles.
	 *
	 * @return void
	 */
	public function enable_network_footer() {
		$this->enable_network_footer = true;
	}
	public function disable_network_footer() {
		$this->enable_network_footer = false;
	}

	/**
	 * Get the network banner markup.
	 *
	 * @return string|HTML - the markup.
	 */
	public function get_network_banner( $args = array() ) {

		// Make sure it's enabled.
		if ( ! $this->enable_network_banner ) {
			return;
		}

		// Parse incoming $args with defaults.
		$args = wp_parse_args( $args, array(
			'skip_nav_id'       => '',
			'skip_nav_label'    => __( 'Skip to Content', 'wpcampus' ),
		));

		// Build the banner.
		$banner = '';

		// Add skip navigation.
		if ( ! empty( $args['skip_nav_id'] ) ) {

			// Make sure we have a valid ID.
			$skip_nav_id = preg_replace( '/[^a-z0-9\-]/i', '', $args['skip_nav_id'] );
			if ( ! empty( $skip_nav_id ) ) {
				$banner .= sprintf( '<a href="#%s" id="wpc-skip-to-content">%s</a>',
					$skip_nav_id,
					$args['skip_nav_label']
				);
			}
		}

		// Add the banner.
		$banner .= '<div id="wpc-network-banner" role="navigation">
			<div class="wpc-container">
				<div class="wpc-logo">
					<a href="https://wpcampus.org">
						<?xml version="1.0" encoding="utf-8"?>
						<svg version="1.1" id="WPCampusOrgLogo" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 1275 100" style="enable-background:new 0 0 1275 100;" xml:space="preserve">
							<title>' . sprintf( __( '%1$s: Where %2$s Meets Higher Education', 'wpcampus' ), 'WPCampus', 'WordPress' ) . '</title>
							<style type="text/css">.st0{opacity:0.7;enable-background:new;}</style>
							<path class="st0" d="M113.5,1.5l-23.4,97H77.9L56.8,23.2L37.1,98.5H24.6L0,1.5h12.6L32,80.1L52.6,1.5h9.9l22.2,78.6l18.1-78.6H113.5 z"/>
							<path class="st0" d="M152.6,98.5h-12.2v-97h34.4c10.8,0,18.7,2.9,23.8,8.8s7.7,12.5,7.7,20c0,8.3-2.8,15.2-8.4,20.7 c-5.6,5.4-12.9,8.2-21.9,8.2h-23.5L152.6,98.5L152.6,98.5z M152.6,49h22.3c5.7,0,10.4-1.7,13.9-5.2c3.5-3.4,5.3-8,5.3-13.6 c0-4.8-1.6-9.1-4.7-12.9c-3.1-3.8-7.7-5.7-13.6-5.7h-23.2V49z"/>
							<path d="M288.1,61.5l27.2,1.6c-1.3,11.9-5.7,21-13.2,27.4c-7.5,6.3-16.7,9.5-27.7,9.5c-13.2,0-23.8-4.4-31.9-13.1 c-8.1-8.7-12.2-20.8-12.2-36.1c0-15.2,3.8-27.5,11.5-36.8s18.4-14,32.1-14c12.8,0,22.7,3.6,29.6,10.7s10.8,16.5,11.7,28.3l-27.8,1.5 c0-6.5-1.2-11.2-3.7-14.1s-5.4-4.3-8.8-4.3c-9.4,0-14.1,9.4-14.1,28.3c0,10.6,1.2,17.7,3.7,21.5c2.4,3.8,5.9,5.7,10.3,5.7 C282.7,77.5,287.1,72.2,288.1,61.5z"/>
							<path d="M397.3,98.5l-5.5-19.1h-26L360,98.5h-24.2l29.9-97h31.5l30.4,97H397.3z M370.9,58.2h15.7l-7.8-28.1L370.9,58.2z"/>
							<path d="M558.6,1.5v97H531V29.1l-18,69.4h-18.9l-18.7-69.4v69.4h-22.3v-97H492L506.1,53l13.4-51.5H558.6z"/>
							<path d="M619.8,63.3v35.3h-30.2v-97H631c10.3,0,18.2,1.2,23.6,3.6c5.4,2.4,9.6,6,12.8,10.9s4.7,10.4,4.7,16.4 c0,9.2-3.2,16.7-9.7,22.4c-6.4,5.7-15,8.5-25.8,8.5h-16.8V63.3z M619.4,42.4h10c8.8,0,13.1-3.2,13.1-9.7c0-6.1-4.1-9.1-12.2-9.1 h-10.9L619.4,42.4L619.4,42.4z"/>
							<path d="M777.8,1.5v64.3c0,12.2-3.6,20.9-10.8,26.3c-7.2,5.3-16.6,8-28.3,8c-12.2,0-22-2.6-29.5-7.7c-7.4-5.1-11.1-13.5-11.1-25V1.5 h30.3v62.3c0,4.6,1,8,2.9,10.2c2,2.1,5.1,3.2,9.4,3.2c3.6,0,6.5-0.8,8.8-2.3s3.7-3.3,4.1-5.2c0.4-1.9,0.7-5.7,0.7-11.4V1.5H777.8z" />
							<path d="M805.9,70.4l27.6-5c2.3,7.8,8.3,11.7,17.9,11.7c7.5,0,11.2-2,11.2-6c0-2.1-0.9-3.7-2.6-4.9c-1.7-1.2-4.8-2.2-9.3-3.1 c-17-3.3-27.9-7.5-32.8-12.8c-4.8-5.3-7.2-11.4-7.2-18.6c0-9.1,3.5-16.8,10.4-22.8c6.9-6.1,16.9-9.1,30-9.1 C871,0,884,7.9,890.4,23.8l-24.7,7.5c-2.6-6.5-7.7-9.7-15.6-9.7c-6.5,0-9.7,2-9.7,6c0,1.8,0.7,3.2,2.2,4.2s4.3,1.9,8.5,2.8 c11.6,2.5,19.9,4.6,24.7,6.5c4.9,1.9,9,5.1,12.2,9.7c3.3,4.6,4.9,10,4.9,16.2c0,9.8-4,17.8-11.9,23.9c-8,6.1-18.4,9.1-31.3,9.1 C826.1,100,811.5,90.1,805.9,70.4z"/>
							<path d="M939.8,72.6v25.9h-27V72.6H939.8z"/>
							<path d="M1003.3,100c-13.6,0-24.8-4.5-33.4-13.6c-8.6-9-12.9-21.2-12.9-36.3c0-14.5,4.1-26.5,12.3-35.9C977.6,4.7,989,0,1003.6,0 c13.5,0,24.5,4.5,33,13.4s12.8,20.8,12.8,35.7c0,15.4-4.3,27.7-12.9,37S1016.8,100,1003.3,100z M1003.1,78c5,0,8.6-2.2,10.8-6.6 s3.3-12.4,3.3-24.1c0-16.9-4.5-25.3-13.6-25.3c-9.8,0-14.6,9.6-14.6,28.9C989.1,68.9,993.7,78,1003.1,78z"/>
							<path d="M1162.2,98.5h-33L1115,61.4h-9.4v37.1h-29.8v-97h50.7c11.2,0,19.9,2.6,26,7.9c6.2,5.2,9.3,12.1,9.3,20.7 c0,5.6-1.1,10.5-3.4,14.8s-6.9,8.1-13.8,11.3L1162.2,98.5z M1105.7,40.7h12.7c3.7,0,6.8-0.8,9-2.3c2.3-1.6,3.4-3.9,3.4-6.9 c0-6.2-3.8-9.3-11.4-9.3h-13.7V40.7z"/>
							<path d="M1275,44.1v54.4h-14.6c-1.2-4-2.5-7.4-3.9-10.2c-6,7.8-14.9,11.7-26.7,11.7c-12.5,0-22.8-4.4-30.8-13.1 c-8.1-8.7-12.1-20.6-12.1-35.5c0-14.5,3.9-26.7,11.7-36.6C1206.4,4.9,1218,0,1233.4,0c11.6,0,20.9,2.9,27.9,8.8 c7.1,5.9,11.6,14.2,13.7,25l-28.9,2.8c-1.4-10.1-5.9-15.1-13.3-15.1c-9.8,0-14.6,9.3-14.6,27.9c0,11.2,1.6,18.6,4.7,22.2 s6.9,5.4,11.4,5.4c3.6,0,6.7-1.1,9.2-3.3c2.5-2.2,3.8-5.2,3.9-9h-16.1V44.1H1275z"/>
						</svg>
					</a>
				</div>
				<div class="wpc-menu-container" role="navigation">
					<button class="wpc-toggle-menu" data-toggle="wpc-network-banner" aria-label="' . __( 'Toggle menu', 'wpcampus' ) . '">
						<div class="wpc-toggle-bar"></div>
					</button>
					<ul class="wpc-menu">
						<li><a href="https://wpcampus.org/about/">' . sprintf( __( 'What is %s?', 'wpcampus' ), 'WPCampus' ) . '</a></li>
						<li><a href="https://wpcampus.org/conferences/">' . __( 'Conferences', 'wpcampus' ) . '</a></li>
						<li><a href="https://wpcampus.org/contact/">' . __( 'Contact', 'wpcampus' ) . '</a></li>
						<li class="highlight"><a href="https://wpcampus.org/get-involved/">' . __( 'Get Involved', 'wpcampus' ) . '</a></li>
					</ul>' . $this->get_social_media_icons() .
		        '</div>
			</div>
		</div>';

		return $banner;
	}

	/**
	 * Print the network banner markup.
	 *
	 * @return void
	 */
	public function print_network_banner( $args = array() ) {
		echo $this->get_network_banner( $args );
	}

	/**
	 * Get the network notifications markup.
	 *
	 * @return string|HTML - the markup.
	 */
	public function get_network_notifications() {

		// Make sure it's enabled.
		if ( ! $this->enable_network_notifications ) {
			return;
		}

		// Build the notifications.
		$notifications = '<div id="wpc-notifications"></div>
		<script id="wpc-notification-template" type="x-tmpl-mustache">
			{{#.}}
				<div class="wpc-notification">
					<div class="wpc-container">
						<div class="wpc-notification-message">
							{{{content.rendered}}}
						</div>
					</div>
				</div>
			{{/.}}
		</script>';

		return $notifications;
	}

	/**
	 * Print the network notifications markup.
	 *
	 * @return void
	 */
	public function print_network_notifications() {
		echo $this->get_network_notifications();
	}

	/**
	 * Get the network footer markup.
	 *
	 * @return string|HTML - the markup.
	 */
	public function get_network_footer() {

		// Make sure it's enabled.
		if ( ! $this->enable_network_footer ) {
			return;
		}

		$images_dir = "{$this->plugin_url}assets/images/";

		$home_url = 'https://wpcampus.org/';
		$get_involved_url = 'https://wpcampus.org/get-involved/';
		$github_url = 'https://github.com/wpcampus/wpcampus-wp-theme';
		$wp_org_url = 'https://wordpress.org/';

		// Build the footer.
		$footer = '<div id="wpc-network-footer" role="contentinfo">
			<div class="wpc-container">
				<a class="wpc-logo" href="' . $home_url . '"><img src="' . $images_dir . 'wpcampus-black-tagline.svg" alt="' . sprintf( __( '%1$s: Where %2$s Meets Higher Education', 'wpcampus' ), 'WPCampus', 'WordPress' ) . '" /></a><br />';

		// Add the footer menu.
		$footer .= wp_nav_menu( array(
			'echo'              => false,
			'theme_location'    => 'footer',
			'container'         => false,
			'menu_id'           => 'wpc-network-footer-menu',
			'menu_class'        => 'wpc-network-footer-menu',
			'fallback_cb'       => false,
		));

		$footer .= '<p class="message"><strong>' . sprintf( __( '%1$s is a community of networking, resources, and events for those using %2$s in the world of higher education.', 'wpcampus' ), 'WPCampus', 'WordPress' ) . '</strong><br />' . sprintf( __( 'If you are not a member of the %1$s community, we\'d love for you to %2$sget involved%3$s.', 'wpcampus' ), 'WPCampus', '<a href="' . $get_involved_url . '">', '</a>' ) . '</p>
				<p class="disclaimer">' . sprintf( __( 'This site is powered by %1$s. You can view, and contribute to, the theme on %2$s.', 'wpcampus' ), '<a href="' . $wp_org_url . '">WordPress</a>', '<a href="' . $github_url . '">GitHub</a>' ) . '<br />' . sprintf( __( '%1$s events are not %2$s and are not affiliated with the %3$s Foundation.', 'wpcampus' ), 'WPCampus', 'WordCamps', 'WordPress' ) . '</p>' .
		        $this->get_social_media_icons() . '<p class="copyright">&copy; ' . date( 'Y' ) . ' <a href="' . $home_url . '">WPCampus</a></p>
			</div>
		</div>';

		return $footer;
	}

	/**
	 * Print the network footer markup.
	 *
	 * @return void
	 */
	public function print_network_footer() {
		echo $this->get_network_footer();
	}

	/**
	 * Print the code of conduct message.
	 */
	public function print_code_of_conduct_message() {
		?>
		<div id="wpc-code-of-conduct">
			<div class="wpc-container">
				<div class="container-title"><?php _e( 'Our Code of Conduct', 'wpcampus' ); ?></div>
				<p><?php printf( __( '%1$s seeks to provide a friendly, safe environment.  All participants should be able to engage in productive dialogue. They should share and learn with each other in an atmosphere of mutual respect. We require all participants adhere to our %2$scode of conduct%3$s. This applies to all community interaction and events.', 'wpcampus' ), 'WPCampus', '<a href="https://wpcampus.org/code-of-conduct/">', '</a>' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Enable and disable the Mailchimp popup form.
	 *
	 * We need this to know whether or not to enqueue assets.
	 *
	 * @return void
	 */
	public function enable_mailchimp_popup() {
		$this->enable_mailchimp_popup = true;
	}
	public function disable_mailchimp_popup() {
		$this->enable_mailchimp_popup = false;
	}

	/**
	 * Print the MailChimp signup form.
	 */
	public function print_mailchimp_signup() {

		?>
		<link href="//cdn-images.mailchimp.com/embedcode/classic-10_7.css" rel="stylesheet" type="text/css">
		<link href="<?php echo trailingslashit( $this->plugin_url . 'assets/build/css' ); ?>wpc-network-mailchimp.min.css" rel="stylesheet" type="text/css">
		<div id="mc_embed_signup">
			<form action="https://wpcampus.us11.list-manage.com/subscribe/post?u=6d71860d429d3461309568b92&amp;id=05f39a2a20" method="post" id="mc-embedded-subscribe-form" class="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>
				<div id="mc_embed_signup_scroll">
					<h2><?php printf( __( 'Subscribe to %s mailing list', 'wpcampus' ), 'WPCampus' ); ?></h2>
					<div class="indicates-required"><span class="asterisk">*</span> indicates required</div>
					<p>Sign-up to receive email updates about the WPCampus community and conferences.</p>
					<div class="mc-field-group-row name">
						<div class="mc-field-group first-name">
							<label for="mce-FNAME">First Name </label>
							<input type="text" value="" name="FNAME" class="" id="mce-FNAME">
						</div>
						<div class="mc-field-group last-name">
							<label for="mce-LNAME">Last Name </label>
							<input type="text" value="" name="LNAME" class="" id="mce-LNAME">
						</div>
					</div>
					<div class="mc-field-group email">
						<label for="mce-EMAIL">Email Address  <span class="asterisk">*</span></label>
						<input type="email" value="" name="EMAIL" class="required email" id="mce-EMAIL">
					</div>
					<div id="mce-responses" class="clear">
						<div class="response" id="mce-error-response" style="display:none"></div>
						<div class="response" id="mce-success-response" style="display:none"></div>
					</div>    <!-- real people should not fill this in and expect good things - do not remove this or risk form bot signups-->
					<div style="position: absolute; left: -5000px;" aria-hidden="true"><input type="text" name="b_6d71860d429d3461309568b92_05f39a2a20" tabindex="-1" value=""></div>
					<div class="clear"><input type="submit" value="<?php esc_attr_e( 'Subscribe to mailing list', 'wpcampus' ); ?>" name="subscribe" id="mc-embedded-subscribe" class="button"></div>
				</div>
			</form>
		</div>
		<script type='text/javascript' src='//s3.amazonaws.com/downloads.mailchimp.com/js/mc-validate.js'></script><script type='text/javascript'>(function($) {window.fnames = new Array(); window.ftypes = new Array();fnames[1]='FNAME';ftypes[1]='text';fnames[2]='LNAME';ftypes[2]='text';fnames[0]='EMAIL';ftypes[0]='email';fnames[6]='MMERGE6';ftypes[6]='radio';fnames[3]='MMERGE3';ftypes[3]='text';fnames[5]='MMERGE5';ftypes[5]='radio';}(jQuery));var $mcj = jQuery.noConflict(true);</script>
		<?php
	}

	/**
	 * Enable and disable the network watch videos assets.
	 *
	 * We need this to know whether or not to enqueue styles.
	 *
	 * @return void
	 */
	public function enable_watch_videos() {
		$this->enable_watch_videos = true;
	}
	public function disable_watch_videos() {
		$this->enable_watch_videos = false;
	}

	/**
	 * Processes and returns the markup
	 * for displaying videos.
	 *
	 * @param   $args - array - arguments for display.
	 * @return  string - the markup.
	 */
	public function print_watch_videos( $args = array() ) {

		$args = wp_parse_args( $args, array(
			'playlist'   => null,
			'show_event' => true,
		));

		/*<div class="wpc-videos-filters">
			<form action="/videos/">
				<span class="form-label"><strong><?php _e( 'Filter videos:', 'wpcampus' ); ?></strong></span>
				<select name="e" class="filter filter-event" title="<?php esc_attr_e( 'Filter videos by event', 'wpcampus' ); ?>">
					<option value=""><?php _e( 'All events', 'wpcampus' ); ?></option>
					<option value="podcast"<?php selected( ! empty( $filters['type'] ) && 'podcast' == $filters['type'] ) ?>><?php printf( __( '%s Podcast', 'wpcampus' ), 'WPCampus' ); ?></option>
					<?php

					foreach ( $playlists as $playlist ) :
						?><option value="<?php echo $playlist->slug; ?>"<?php selected( ! empty( $filters['playlist'] ) && $filters['playlist'] == $playlist->slug ) ?>><?php echo $playlist->name; ?></option><?php
					endforeach;

					?>
				</select>
				<select name="c" class="filter filter-category" title="<?php esc_attr_e( 'Filter videos by category', 'wpcampus' ); ?>">
					<option value=""><?php _e( 'All categories', 'wpcampus' ); ?></option>
					<?php

					foreach ( $categories as $cat ) :
						?><option value="<?php echo $cat->slug; ?>"<?php selected( ! empty( $filters['category'] ) && $filters['category'] == $cat->slug ) ?>><?php echo $cat->name; ?></option><?php
					endforeach;

					?>
				</select>
				<?php

				// Filter by authors.
				if ( function_exists( 'wpcampus_media_videos' ) && method_exists( wpcampus_media_videos(), 'get_video_authors' ) ) :

					// Get authors.
					$authors = wpcampus_media_videos()->get_video_authors();
					if ( ! empty( $authors ) ) :

						?>
						<select name="a" class="filter filter-author" title="<?php esc_attr_e( 'Filter videos by author', 'wpcampus' ); ?>">
							<option value=""><?php _e( 'All authors', 'wpcampus' ); ?></option>
							<?php

							foreach ( $authors as $author ) :
								?><option value="<?php echo $author->nicename; ?>"<?php selected( ! empty( $filters['author'] ) && $filters['author'] == $author->nicename ); ?>><?php echo $author->display_name; ?></option><?php
							endforeach;

							?>
						</select>
						<?php
					endif;
				endif;

				?>
				<input type="search" class="search-videos" name="search" value="<?php echo ! empty( $filters['search'] ) ? esc_attr( $filters['search'] ) : ''; ?>" placeholder="<?php esc_attr_e( 'Search videos', 'wpcampus' ); ?>" title="<?php esc_attr_e( 'Search videos', '' ); ?>" />
				<input type="submit" class="update-videos" value="<?php esc_attr_e( 'Update', 'wpcampus' ); ?>" title="<?php esc_attr_e( 'Update videos', 'wpcampus' ); ?>" />
			</form>
			<?php

			// Print clear filters button.
			if ( ! empty( $filters ) ) :
				?><a class="button red expand clear" href="/videos/"><?php _e( 'Clear filters', 'wpcampus' ); ?></a><?php
			endif;

			?>
		</div>
		<?php

		// Create shortcode arguments.
		$shortcode_args = '';
		foreach ( $filters as $filter_key => $filter_value ) {
			$shortcode_args .= " {$filter_key}=\"{$filter_value}\"";
		}

		// Print the list of videos.
		echo do_shortcode( "[wpc_videos{$shortcode_args}]" );*/

		/*// Get the post types.
		$podcast_post_type = $this->podcast_post_type;
		$video_post_type = $this->video_post_type;

		// Define the array of defaults.
		$defaults = array(
			'playlist'      => '',
			'category'      => '',
			'author'        => '',
			'search'        => '',
			'type'          => '',
			'random'        => 'false',
			'orderby'       => 'title',
			'order'         => 'ASC',
		);

		// Parse incoming $args into an array and merge it with $defaults.
		$args = wp_parse_args( $args, $defaults );

		// Define the video args.
		$videos_args = array(
			'posts_per_page'    => -1,
			'orderby'           => $args['orderby'],
			'order'             => $args['order'],
			'post_type'         => array( $video_post_type, $podcast_post_type ),
			'post_status'       => 'publish',
			'suppress_filters'  => false,
		);

		// Do we want a specific playlist?
		if ( ! empty( $args['playlist'] ) ) {
			$videos_args['playlist'] = $args['playlist'];
		}

		// Do we want a specific type?
		if ( ! empty( $args['type'] ) ) {
			$videos_args['post_type'] = $args['type'];
		}

		// Do we want a specific category?
		if ( ! empty( $args['category'] ) ) {
			$videos_args['category_name'] = $args['category'];
		}

		// Do we want a specific author?
		if ( ! empty( $args['author'] ) ) {
			$videos_args['author_name'] = $args['author'];
		}

		// Are we searching?
		if ( ! empty( $args['search'] ) ) {
			$videos_args['search'] = $args['search'];
		}

		// Get the videos.
		$videos = get_posts( $videos_args );

		// Make sure we have videos.
		if ( empty( $videos ) ) {
			return '<p><em>' . __( 'There are no videos available.', 'wpcampus.' ) . '</em></p>';
		}

		// Process any shuffling or ordering.
		if ( 'true' === $args['random'] ) {
			shuffle( $videos );
		} else {

			// Order the items.
			switch ( $args['orderby'] ) {

				case 'title':

					// Make sure we have an order.
					$order = strcasecmp( 'desc', $args['order'] ) == 0 ? 'desc' : 'asc';

					if ( 'desc' == $order ) {
						usort( $videos, function( $a, $b ) {
							return strcasecmp( preg_replace( '/([^a-z])/i', '', $b->post_title ), preg_replace( '/([^a-z])/i', '', $a->post_title ) );
						});
					} else {
						usort( $videos, function( $a, $b ) {
							return strcasecmp( preg_replace( '/([^a-z])/i', '', $a->post_title ), preg_replace( '/([^a-z])/i', '', $b->post_title ) );
						});
					}

					break;
			}
		}*/

		$watch_attrs = array();

		if ( ! empty( $args['playlist'] ) ) {
			$watch_attrs['playlist'] = $args['playlist'];
		}

		$watch_attrs_string = '';
		foreach( $watch_attrs as $attr => $value ) {
			$watch_attrs_string .= ' data-' . $attr . '="' . esc_attr( $value ) . '"';
		}

		?>
		<div class="wpc-watch loading"<?php echo $watch_attrs_string; ?>>
			<span class="wpc-watch-loading-message"><?php _e( 'Loading videos...', 'wpcampus-network' ); ?></span>
		</div>
		<script id="wpc-watch-template" type="text/x-handlebars-template">
			{{videos_count_message}}
			{{#each .}}
				<div class="wpc-watch-video">
					<div class="video-media">
						<a class="video-popup" role="button" title="Watch {{post_title}} video" href="{{watch_permalink}}">
							<img class="video-thumbnail" src="{{thumbnail}}" alt="<?php _e( 'Thumbnail for the video', 'wpcampus-network' ); ?>" />
							<span class="video-play"></span>
						</a>
					</div>
					<div class="video-info">
						<div class="video-title"><a href="{{permalink}}">{{{post_title}}}</a></div>
						<div class="video-meta">
							<?php

							if ( isset( $args['show_event'] ) && true == $args['show_event'] ) :
								?>
								<span class="video-event">{{event_name}}</span>
								<?php
							endif;

							/*// Get the playlist(s).
							$playlists = wp_get_object_terms( $video->ID, 'playlist' );

							if ( ! empty( $playlists ) ) {
								foreach ( $playlists as $playlist ) {

									// Build the list with link.
									$playlists_str[] = '<a href="/videos/' . $playlist->slug . '/">' . $playlist->name . '</a>';

									// Remove name from title.
									$video_title = preg_replace( '/\s?\-\s?' . $playlist->name . '\s?/i', '', $video_title );

								}
							}*/

							?>
							{{#if authors}}
								<ul class="video-authors">
									{{#each authors}}
										<li class="video-author">
											<a href="{{permalink}}">
												<img class="video-author-avatar" src="{{avatar}}" alt="Avatar for {{display_name}}">
												<span class="video-author-name">{{display_name}}</span>
											</a>
										</li>
									{{/each}}
								</ul>
							{{/if}}
						</div>
					</div>
				</div>
			{{/each}}
		</script>
		<?php
	}
}

/**
 * Returns the instance of our main WPCampus_Network class.
 *
 * Will come in handy when we need to access the
 * class to retrieve data throughout the plugin.
 *
 * @return	WPCampus_Network
 */
function wpcampus_network() {
	return WPCampus_Network::instance();
}

// Let's get this show on the road.
wpcampus_network();

/**
 * Interact with the banner.
 */
function wpcampus_enable_network_banner() {
	wpcampus_network()->enable_network_banner();
}
function wpcampus_disable_network_banner() {
	wpcampus_network()->disable_network_banner();
}
function wpcampus_get_network_banner() {
	return wpcampus_network()->get_network_banner();
}
function wpcampus_print_network_banner( $args = array() ) {
	wpcampus_network()->print_network_banner( $args );
}

/**
 * Interact with the notifications.
 */
function wpcampus_enable_network_notifications() {
	wpcampus_network()->enable_network_notifications();
}
function wpcampus_disable_network_notifications() {
	wpcampus_network()->disable_network_notifications();
}
function wpcampus_get_network_notifications() {
	return wpcampus_network()->get_network_notifications();
}
function wpcampus_print_network_notifications() {
	wpcampus_network()->print_network_notifications();
}

/**
 * Interact with the footer.
 */
function wpcampus_enable_network_footer() {
	wpcampus_network()->enable_network_footer();
}
function wpcampus_disable_network_footer() {
	wpcampus_network()->disable_network_footer();
}
function wpcampus_get_network_footer() {
	return wpcampus_network()->get_network_footer();
}
function wpcampus_print_network_footer() {
	wpcampus_network()->print_network_footer();
}

/**
 * Interact with social media.
 */
function wpcampus_print_social_media_icons() {
	wpcampus_network()->print_social_media_icons();
}

/**
 * Interact with the code of conduct.
 */
function wpcampus_print_code_of_conduct_message() {
	wpcampus_network()->print_code_of_conduct_message();
}

/**
 * Interact with the MailChimp signup.
 */
function wpcampus_enable_mailchimp_popup() {
	wpcampus_network()->enable_mailchimp_popup();
}
function wpcampus_disable_mailchimp_popup() {
	wpcampus_network()->disable_mailchimp_popup();
}
function wpcampus_print_mailchimp_signup() {
	wpcampus_network()->print_mailchimp_signup();
}

/**
 * Get markup for the watch videos page.
 */
function wpcampus_enable_watch_videos() {
	wpcampus_network()->enable_watch_videos();
}
function wpcampus_disable_watch_videos() {
	wpcampus_network()->disable_watch_videos();
}
function wpcampus_print_watch_videos( $args = array() ) {
	wpcampus_network()->print_watch_videos( $args );
}
