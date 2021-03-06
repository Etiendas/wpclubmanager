<?php
/**
 * Installation related functions and actions.
 *
 * @author 		ClubPress
 * @category 	Admin
 * @package 	WPClubManager/Classes
 * @version     1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WPCM_Install' ) ) :

class WPCM_Install {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {

		register_activation_hook( WPCM_PLUGIN_FILE, array( $this, 'install' ) );

		add_action( 'admin_init', array( $this, 'install_actions' ) );
		add_action( 'admin_init', array( $this, 'check_version' ), 5 );
		add_action( 'in_plugin_update_message-wp-club-manager/wpclubmanager.php', array( $this, 'in_plugin_update_message' ) );
	}

	/**
	 * check_version function.
	 *
	 * @access public
	 * @return void
	 */
	public function check_version() {
		if ( ! defined( 'IFRAME_REQUEST' ) && ( get_option( 'wpclubmanager_version' ) != WPCM()->version ) ) {
			$this->install();

			do_action( 'wpclubmanager_updated' );
		}
	}

	/**
	 * Install actions such as installing pages when a button is clicked.
	 */
	public function install_actions() {
		
		if ( ! empty( $_GET['do_update_wpclubmanager'] ) ) {

			$this->update();

			// Update complete
			delete_option( '_wpcm_needs_update' );
			delete_transient( '_wpcm_activation_redirect' );

			// What's new redirect
			wp_redirect( admin_url( 'index.php?page=wpcm-about&wpcm-updated=true' ) );
			exit;
		}
	}

	/**
	 * Install WPCM
	 */
	public function install() {
		$this->create_options();
		$this->create_roles();

		// Register post types
		include_once( 'class-wpcm-post-types.php' );
		WPCM_Post_Types::register_post_types();
		WPCM_Post_Types::register_taxonomies();

		// Queue upgrades
		$current_version = get_option( 'wpclubmanager_version', null );
		if ( $current_version ) {
			update_option( 'wpcm_version_upgraded_from', $current_version );
		}
		
		// Update version
		update_option( 'wpclubmanager_version', WPCM()->version );

		// Check if pages are needed
		// if ( ! get_option( 'wpcm_sport' ) ) {
		// 	update_option( '_wpcm_needs_welcome', 1 );
		// }

		// Bail if activating from network, or bulk
		// if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
		// 	return;
		// }

		// Flush rules after install
		flush_rewrite_rules();

		// Redirect to welcome screen
		set_transient( '_wpcm_activation_redirect', 1, 60 * 60 );

	}

	/**
	 * Handle updates
	 */
	public function update() {
		// Do updates
		$current_version = get_option( 'wpclubmanager_version' );

		if ( version_compare( $current_version, '1.1.0', '<' ) ) {
			include( 'updates/wpclubmanager-update-1.1.0.php' );
			update_option( 'wpclubmanager_version', '1.1.0' );
		}

		update_option( 'wpclubmanager_version', WPCM()->version );
	}

	/**
	 * Default options
	 *
	 * Sets up the default options used on the settings page
	 *
	 * @access public
	 */
	function create_options() {
		// Include settings so that we can run through defaults
		include_once( 'admin/class-wpcm-admin-settings.php' );

		$settings = WPCM_Admin_Settings::get_settings_pages();

		foreach ( $settings as $section ) {
			foreach ( $section->get_settings() as $value ) {
				if ( isset( $value['default'] ) && isset( $value['id'] ) ) {
					$autoload = isset( $value['autoload'] ) ? (bool) $value['autoload'] : true;
					add_option( $value['id'], $value['default'], '', ( $autoload ? 'yes' : 'no' ) );
				}
			}
		}

		if ( ! get_option( 'wpclubmanager_installed' ) ) {
			// Configure default sport
			$sport = 'soccer';
			$options = wpcm_get_sport_presets();
			WPCM_Admin_Settings::configure_sport( $options[ $sport ] );
			update_option( 'wpcm_sport', $sport );
			update_option( 'wpclubmanager_installed', 1 );
		}
	}

	/**
	 * Create roles and capabilities
	 */
	public function create_roles() {
		global $wp_roles;

		if ( class_exists( 'WP_Roles' ) ) {
			if ( ! isset( $wp_roles ) ) {
				$wp_roles = new WP_Roles();
			}
		}

		if ( is_object( $wp_roles ) ) {

			// Player role
			add_role( 'player', __( 'Player', 'wpclubmanager' ), array(
				'level_1' 						=> true,
				'level_0' 						=> true,

	            'read' 							=> true,
	            'delete_posts' 					=> true,
	            'edit_posts' 					=> true,
	            'upload_files' 					=> true,

	            'edit_wpcm_player'				=> true,
	            'read_wpcm_player'				=> true,
	            'edit_wpcm_players' 			=> true,
	            'edit_published_wpcm_players' 	=> true,
				'assign_wpcm_player_terms' 		=> true,
			) );

			add_role( 'staff', __( 'Staff', 'wpclubmanager' ), array(
				'level_1' 						=> true,
				'level_0' 						=> true,

	            'read' 							=> true,
	            'delete_posts' 					=> true,
	            'edit_posts' 					=> true,
	            'upload_files' 					=> true,

	            'edit_wpcm_staff'				=> true,
	            'read_wpcm_staff'				=> true,
	            'edit_wpcm_staff' 				=> true,
	            'edit_published_wpcm_staff' 	=> true,
				'assign_wpcm_staff_terms' 		=> true,

	            'edit_wpcm_player'				=> true,
	            'read_wpcm_player'				=> true,
	            'delete_wpcm_player'			=> true,
	            'edit_wpcm_playeres' 			=> true,
	            'publish_wpcm_players' 			=> true,
	            'delete_wpcm_players' 			=> true,
	            'delete_published_wpcm_players' => true,
	            'edit_published_wpcm_players' 	=> true,
				'assign_wpcm_player_terms' 		=> true,

				'edit_wpcm_club'				=> true,
	            'read_wpcm_club'				=> true,
	            'delete_wpcm_club'				=> true,
	            'edit_wpcm_clubes' 				=> true,
	            'publish_wpcm_clubs' 			=> true,
	            'delete_wpcm_clubs' 			=> true,
	            'delete_published_wpcm_clubs' 	=> true,
	            'edit_published_wpcm_clubs' 	=> true,
				'assign_wpcm_club_terms' 		=> true,

				'edit_wpcm_match'				=> true,
	            'read_wpcm_match'				=> true,
	            'delete_wpcm_match'				=> true,
	            'edit_wpcm_matches' 			=> true,
	            'publish_wpcm_matches' 			=> true,
	            'delete_wpcm_matches' 			=> true,
	            'delete_published_wpcm_matches' => true,
	            'edit_published_wpcm_matches' 	=> true,
				'assign_wpcm_match_terms' 		=> true,

				'edit_wpcm_sponsor'				=> true,
	            'read_wpcm_sponsor'				=> true,
	            'delete_wpcm_sponsor'			=> true,
	            'edit_wpcm_sponsores' 			=> true,
	            'publish_wpcm_sponsors' 		=> true,
	            'delete_wpcm_sponsors' 			=> true,
	            'delete_published_wpcm_sponsors'=> true,
	            'edit_published_wpcm_sponsors' 	=> true,
				'assign_wpcm_sponsor_terms' 	=> true,
		        )
		    );

			// Manager role
			add_role( 'team_manager', __( 'Team Manager', 'wpclubmanager' ), array(
				'level_2' 						=> true,
				'level_1' 						=> true,
				'level_0' 						=> true,

	            'read' 							=> true,
	            'delete_posts' 					=> true,
	            'edit_posts' 					=> true,
	            'delete_published_posts' 		=> true,
	            'publish_posts' 				=> true,
	            'upload_files' 					=> true,
	            'edit_published_posts' 			=> true,

	            'edit_wpcm_player'				=> true,
	            'read_wpcm_player'				=> true,
	            'delete_wpcm_player'			=> true,
	            'edit_wpcm_players' 			=> true,
	            'publish_wpcm_players' 			=> true,
	            'delete_wpcm_players' 			=> true,
	            'delete_published_wpcm_players' => true,
	            'edit_published_wpcm_players' 	=> true,
				'assign_wpcm_player_terms' 		=> true,

	            'edit_wpcm_staff'				=> true,
	            'read_wpcm_staff'				=> true,
	            'delete_wpcm_staff'				=> true,
	            'edit_wpcm_staffs' 				=> true,
	            'publish_wpcm_staffs' 			=> true,
	            'delete_wpcm_staffs' 			=> true,
	            'delete_published_wpcm_staffs' 	=> true,
	            'edit_published_wpcm_staffs' 	=> true,
				'assign_wpcm_staff_terms' 		=> true,

				'edit_wpcm_match'				=> true,
	            'read_wpcm_match'				=> true,
	            'delete_wpcm_match'				=> true,
	            'edit_wpcm_matchs' 				=> true,
	            'publish_wpcm_matchs' 			=> true,
	            'delete_wpcm_matchs' 			=> true,
	            'delete_published_wpcm_matchs' 	=> true,
	            'edit_published_wpcm_matchs' 	=> true,
				'assign_wpcm_match_terms' 		=> true,
			) );

			$capabilities = $this->get_core_capabilities();

			foreach ( $capabilities as $cap_group ) {
				foreach ( $cap_group as $cap ) {
					$wp_roles->add_cap( 'player', $cap );
					$wp_roles->add_cap( 'staff', $cap );
					$wp_roles->add_cap( 'team_manager', $cap );
					$wp_roles->add_cap( 'administrator', $cap );
				}
			}
		}
	}

	/**
	 * Get capabilities for WooCommerce - these are assigned to admin/shop manager during installation or reset
	 *
	 * @access public
	 * @return array
	 */
	public function get_core_capabilities() {
		$capabilities = array();

		$capabilities['core'] = array(
			'manage_wpclubmanager'
		);

		$capability_types = array( 'wpcm_club', 'wpcm_player', 'wpcm_sponsor', 'wpcm_staff', 'wpcm_match' );

		foreach ( $capability_types as $capability_type ) {

			$capabilities[ $capability_type ] = array(
				// Post type
				"edit_{$capability_type}",
				"read_{$capability_type}",
				"delete_{$capability_type}",
				"edit_{$capability_type}s",
				"edit_others_{$capability_type}s",
				"publish_{$capability_type}s",
				"read_private_{$capability_type}s",
				"delete_{$capability_type}s",
				"delete_private_{$capability_type}s",
				"delete_published_{$capability_type}s",
				"delete_others_{$capability_type}s",
				"edit_private_{$capability_type}s",
				"edit_published_{$capability_type}s",

				// Terms
				"manage_{$capability_type}_terms",
				"edit_{$capability_type}_terms",
				"delete_{$capability_type}_terms",
				"assign_{$capability_type}_terms"
			);
		}

		return $capabilities;
	}

	/**
	 * wpclubmanager_remove_roles function.
	 *
	 * @access public
	 * @return void
	 */
	public function remove_roles() {
		global $wp_roles;

		if ( class_exists( 'WP_Roles' ) ) {
			if ( ! isset( $wp_roles ) ) {
				$wp_roles = new WP_Roles();
			}
		}

		if ( is_object( $wp_roles ) ) {

			$capabilities = $this->get_core_capabilities();

			foreach ( $capabilities as $cap_group ) {
				foreach ( $cap_group as $cap ) {
					$wp_roles->remove_cap( 'player', $cap );
					$wp_roles->remove_cap( 'staff', $cap );
					$wp_roles->remove_cap( 'team_manager', $cap );
					$wp_roles->remove_cap( 'administrator', $cap );
				}
			}

			remove_role( 'player' );
			remove_role( 'staff' );
			remove_role( 'team_manager' );
		}
	}

	/**
	 * Active plugins pre update option filter
	 *
	 * @param string $new_value
	 * @return string
	 */
	function pre_update_option_active_plugins( $new_value ) {
		$old_value = (array) get_option( 'active_plugins' );

		if ( $new_value !== $old_value && in_array( W3TC_FILE, (array) $new_value ) && in_array( W3TC_FILE, (array) $old_value ) ) {
			$this->_config->set( 'notes.plugins_updated', true );
			try {
				$this->_config->save();
			} catch( Exception $ex ) {}
		}

		return $new_value;
	}

	/**
	 * Show plugin changes. Code adapted from W3 Total Cache.
	 *
	 * @return void
	 */
	function in_plugin_update_message() {
		$response = wp_remote_get( 'https://plugins.svn.wordpress.org/wp-club-manager/trunk/readme.txt' );

		if ( ! is_wp_error( $response ) && ! empty( $response['body'] ) ) {

			// Output Upgrade Notice
			$matches = null;
			$regexp = '~==\s*Upgrade Notice\s*==\s*=\s*(.*)\s*=(.*)(=\s*' . preg_quote( WPCM_VERSION ) . '\s*=|$)~Uis';

			if ( preg_match( $regexp, $response['body'], $matches ) ) {
				$version = trim( $matches[1] );
				$notices = (array) preg_split('~[\r\n]+~', trim( $matches[2] ) );

				if ( version_compare( WPCM_VERSION, $version, '<' ) ) {

					echo '<div style="font-weight: normal; background: #cc99c2; color: #fff !important; border: 1px solid #b76ca9; padding: 9px; margin: 9px 0;">';

					foreach ( $notices as $index => $line ) {
						echo '<p style="margin: 0; font-size: 1.1em; color: #fff; text-shadow: 0 1px 1px #b574a8;">' . wp_kses_post( preg_replace( '~\[([^\]]*)\]\(([^\)]*)\)~', '<a href="${2}">${1}</a>', $line ) ) . '</p>';
					}

					echo '</div> ';
				}
			}

			// Output Changelog
			$matches = null;
			$regexp = '~==\s*Changelog\s*==\s*=\s*[0-9.]+\s*-(.*)=(.*)(=\s*' . preg_quote( WPCM_VERSION ) . '\s*-(.*)=|$)~Uis';

			if ( preg_match( $regexp, $response['body'], $matches ) ) {
				$changelog = (array) preg_split( '~[\r\n]+~', trim( $matches[2] ) );

				echo __( 'What\'s new:', 'wpclubmanager' ) . '<div style="font-weight: normal;">';

				$ul = false;

				foreach ( $changelog as $index => $line ) {
					if ( preg_match('~^\s*\*\s*~', $line ) ) {
						if ( ! $ul ) {
							echo '<ul style="list-style: disc inside; margin: 9px 0 9px 20px; overflow:hidden; zoom: 1;">';
							$ul = true;
						}
						
						$line = preg_replace( '~^\s*\*\s*~', '', htmlspecialchars( $line ) );
						
						echo '<li style="width: 50%; margin: 0; float: left; ' . ( $index % 2 == 0 ? 'clear: left;' : '' ) . '">' . esc_html( $line ) . '</li>';
					} else {

						$version = trim( current( explode( '-', str_replace( '=', '', $line ) ) ) );

						if ( version_compare( WPCM_VERSION, $version, '>=' ) ) {
							break;
						}

						if ( $ul ) {
							echo '</ul>';
							$ul = false;
						}

						echo '<p style="margin: 9px 0;">' . esc_html( htmlspecialchars( $line ) ) . '</p>';
					}
				}

				if ( $ul ) {
					echo '</ul>';
				}

				echo '</div>';
			}
		}
	}
}

endif;

return new WPCM_Install();