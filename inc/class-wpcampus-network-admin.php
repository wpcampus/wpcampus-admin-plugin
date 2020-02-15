<?php

/**
 * PHP class that holds the admin
 * functionality for the plugin.
 *
 * @category    Class
 * @package     WPCampus Network
 */
class WPCampus_Network_Admin {

	const OPTIONS_TITLE = 'WPCampus: Settings';
	const OPTIONS_MENU_TITLE = 'WPCampus';
	const OPTIONS_MENU_SLUG = 'wpcampus-settings';
	const OPTIONS_USER_CAP = 'manage_options';

	/**
	 * We don't need to instantiate this class.
	 */
	protected function __construct() { }

	/**
	 * Registers all of our hooks and what not.
	 */
	public static function register() {
		$plugin = new self();

		add_action( 'network_admin_menu', array( $plugin, 'add_network_admin_pages' ) );

		add_action( 'admin_menu', [ $plugin, 'add_admin_pages' ] );

		add_action( 'admin_menu', array( $plugin, 'modify_admin_menu' ) );
		add_action( 'edit_form_after_title', array( $plugin, 'print_meta_boxes_after_title' ), 0 );

		add_action( 'load-comment.php', array( $plugin, 'manage_comments_page_access' ) );
		add_action( 'load-edit-comments.php', array( $plugin, 'manage_comments_page_access' ) );

	}

	/**
	 * Add pages to the network admin.
	 */
	public function add_network_admin_pages() {

		// Add our main settings page.
		add_menu_page(
			sprintf( __( 'Manage %s', 'wpcampus-network' ), 'WPCampus' ),
			'WPCampus',
			'manage_wpc_network',
			'manage-wpc-network',
			array( $this, 'print_manage_network_page' )
		);
	}

	/**
	 * Registers an ACF options page.
	 */
	public function add_admin_pages() {

		if ( ! function_exists( 'acf_add_options_page' ) ) {
			$this->add_default_options_page();
			return;
		}

		acf_add_options_page(
			[
				'page_title' => self::OPTIONS_TITLE,
				'menu_title' => self::OPTIONS_MENU_TITLE,
				'capability' => self::OPTIONS_USER_CAP,
				'menu_slug'  => self::OPTIONS_MENU_SLUG,
			] );

		$this->add_acf_fields();
	}

	/**
	 * We only add this page if ACF isn't enabled.
	 */
	private function add_default_options_page() {

		add_menu_page(
			self::OPTIONS_TITLE,
			self::OPTIONS_MENU_TITLE,
			self::OPTIONS_USER_CAP,
			self::OPTIONS_MENU_SLUG,
			[ $this, 'print_default_options_page' ]
		);
	}

	/**
	 * Print default options page which tells the user
	 * to enable ACF in order to access settings.
	 */
	public function print_default_options_page() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<p>WPCampus settings require the Advanced Custom Fields PRO plugin. <a href="<?php echo admin_url( 'plugins.php' ); ?>">Manage plugins</a></p>
		</div>
		<?php
	}

	/**
	 * Adds/registers our ACF fields.
	 */
	private function add_acf_fields() {
		require_once plugin_dir_path( __FILE__ ) . 'wpcampus-network-fields.php';
	}

	/**
	 * Print our main management page.
	 */
	public function print_manage_network_page() {

		$page_slug = 'manage-wpc-network';

		?>
		<div class="wrap">
			<h1><?php echo get_admin_page_title(); ?></h1>
			<?php

			// Process actions.
			if ( isset( $_GET['wpc_nonce'] ) ) :

				if ( ! wp_verify_nonce( $_GET['wpc_nonce'], 'add_all_users_to_all_blogs' ) ) ://|| current_user_can( 'manage_wpc_network_users' ) ) :
					?>
					<div class="notice notice-error is-dismissible">
						<p><?php _e( "The action couldn't be completed because you do not have authentication.", 'wpcampus-network' ); ?></p>
					</div>
				<?php
				else :

					// Add all users to all blogs.
					wpcampus_network()->assign_all_users_to_all_blogs();

					?>
					<div class="notice notice-success is-dismissible">
						<p><?php _e( 'All users were added to all blogs.', 'wpcampus-network' ); ?></p>
					</div>
				<?php
				endif;
			endif;

			// Setup action links.
			$actions = array();

			//if ( current_user_can( 'manage_wpc_network_users' ) ) {
			$actions[] = array(
				'href'  => wp_nonce_url(
					add_query_arg(
						array(
							'page' => $page_slug,
							//'wpc_action' => 'add_all_users_to_all_blogs',
						),
						network_admin_url( 'admin.php' )
					),
					'add_all_users_to_all_blogs',
					'wpc_nonce'
				),
				'label' => __( 'Add all users to all blogs', 'wpcampus-network' ),
			);
			//}

			if ( empty( $actions ) ) :
				?>
				<p><?php _e( "There's nothing for you to do.", 'wpcampus-network' ); ?></p>
			<?php
			else :
				?>
				<ul style="list-style:disc;margin-left:2em;">
					<?php

					foreach ( $actions as $action ) :
						?>
						<li><a href="<?php echo $action['href']; ?>"><?php echo $action['label']; ?></a></li>
					<?php
					endforeach;

					?>
				</ul>
			<?php
			endif;

			?>
		</div>
		<?php
	}

	/**
	 *
	 */
	public function modify_admin_menu() {
		global $submenu, $menu;

		if ( ! current_user_can( 'moderate_comments' ) ) {

			foreach ( $menu as $menu_key => $menu_item ) {

				if ( 'edit-comments.php' == $menu_item[2] ) {
					unset( $menu[ $menu_key ] );
				}
			}

			if ( isset( $submenu['edit-comments.php'] ) ) {
				unset( $submenu['edit-comments.php'] );
			}
		}
	}

	/**
	 * Print meta boxes after the title, before the editor.
	 */
	public function print_meta_boxes_after_title() {
		global $post, $wp_meta_boxes;

		do_meta_boxes( get_current_screen(), 'wpc_after_title', $post );

		unset( $wp_meta_boxes['post']['wpc_after_title'] );
	}

	/**
	 * Manage access to comments page.
	 */
	public function manage_comments_page_access() {
		if ( ! current_user_can( 'moderate_comments' ) ) {
			wp_die(
				__( 'You do not have permission to moderate comments.', 'wpcampus-network' ),
				__( 'Moderating Comments', 'wpcampus-network' ),
				array( 'back_link' => true )
			);
		}
	}
}

WPCampus_Network_Admin::register();
