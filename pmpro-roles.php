<?php
/**
 * Plugin Name: Paid Memberships Pro - Roles Add On
 * Description: Adds a WordPress Role for each Membership Level.
 * Plugin URI: https://www.paidmembershipspro.com/add-ons/pmpro-roles/
 * Author: Paid Memberships Pro
 * Author URI: https://www.paidmembershipspro.com
 * Version: 1.2
 * License: GPL2
 * Text Domain: pmpro-roles
 * Domain Path: /pmpro-roles
 */

class PMPRO_Roles {

	static $role_key = 'pmpro_role_';
	static $plugin_slug = 'pmpro-roles';
	static $plugin_prefix = 'pmpro_roles_';
	static $ajaction = 'pmpro_roles_repair';
	
	function __construct(){
		add_action( 'pmpro_save_membership_level', array( $this, 'edit_level' ) );
		add_action( 'admin_head-toplevel_page_pmpro-membershiplevels', array( $this, 'delete_level' ) );
		add_action("pmpro_after_change_membership_level", array($this, 'user_change_level'), 10, 2);
		add_action( 'admin_enqueue_scripts', array($this, 'enqueue_admin_scripts' ) );
		add_action('wp_ajax_'.PMPRO_Roles::$ajaction, array( $this, 'install' ) );
		add_filter('plugin_action_links_' . plugin_basename(__FILE__), array('PMPRO_Roles', 'add_action_links'));
		add_filter( 'plugin_row_meta', array( 'PMPRO_Roles', 'plugin_row_meta' ), 10, 2 );
		add_action('admin_init', array('PMPRO_Roles', 'delete_and_deactivate'));
		add_action( 'pmpro_membership_level_after_other_settings', array( 'PMPRO_Roles', 'level_settings' ) );
		add_filter( 'editable_roles', array( 'PMPRO_Roles', 'remove_list_roles' ), 10, 1 );
	}
	
	function enqueue_admin_scripts($hook) {
		if( 'toplevel_page_pmpro-membershiplevels' != $hook )
		return;
		wp_enqueue_script( PMPRO_Roles::$plugin_prefix.'admin', plugin_dir_url( __FILE__ ) . '/admin.js' );
		wp_enqueue_style( PMPRO_Roles::$plugin_prefix.'admin', plugin_dir_url( __FILE__ ) . '/admin.css' );
		$nonce = wp_create_nonce( PMPRO_Roles::$ajaction );
		$vars = array(
			'desc' => __('Levels not matching up, or missing?', PMPRO_Roles::$plugin_slug),
			'repair' => __('Repair', PMPRO_Roles::$plugin_slug),
			'working' => __('Working...', PMPRO_Roles::$plugin_slug),
			'done' => __('Done!', PMPRO_Roles::$plugin_slug),
			'fixed'=> __(' role connections were needed/repaired.', PMPRO_Roles::$plugin_slug),
			'failed'=> __('An error occurred while repairing roles.', PMPRO_Roles::$plugin_slug),
			'ajaction'=>PMPRO_Roles::$ajaction,
			'nonce'=>$nonce,
			);
		$key = PMPRO_Roles::$plugin_prefix.'vars';
		wp_localize_script( PMPRO_Roles::$plugin_prefix . 'admin', 'key', array( 'key'=>$key ) );
		wp_localize_script( PMPRO_Roles::$plugin_prefix . 'admin', $key, $vars );
	}
	
	function edit_level( $saveid ) {
		//by being here, we know we already have the $_REQUEST we need, so no need to check.
		
		$capabilities = PMPRO_Roles::capabilities();

		if( !empty( $_REQUEST['pmpro_roles_level'] ) ){

			$level_roles = $_REQUEST['pmpro_roles_level'];

			//created a new level
			if( $_REQUEST['edit'] < 0 ) {
				
				foreach( $level_roles as $role_key => $role_name ){
					$capabilities = PMPRO_Roles::capabilities( $role_key[$role_key] );
					add_role( $role_key, $role_name, $capabilities );	
				}
			} else {

				global $wpdb;
				//have to get all roles and find ours because get_role() doesn't yield the role's "pretty" name, only its index.
				$roles = get_option( $wpdb->get_blog_prefix() . 'user_roles' );

				if(!is_array( $roles ) ) return;

				foreach( $level_roles as $role_key => $role_name ){

					$capabilities = PMPRO_Roles::capabilities( $role_key );

					if(!isset( $roles[$role_key] ) ){
						add_role( $role_key, $_REQUEST['name'], $capabilities[$role_key] );
						return;
					}

					remove_role( $role_key );

					add_role( $role_key, $role_name, $capabilities );

				}
			}

			update_option( 'pmpro_roles_'.$saveid, $level_roles );

		}
		
	}
	
	function delete_level() {
		//if there is no action, or if there is an action but it isn't deleting, return.
		if( !isset( $_REQUEST['action'] ) || ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] !== 'delete_membership_level' ) ) { return; }
		$ml_id = $_REQUEST['deleteid'];
		if($ml_id > 0){

			//this will silently fail if the role doesn't exist, so we're fine.
			$role_key = PMPRO_Roles::$role_key . $ml_id;
			if( !empty( $role_key ) ){
				remove_role( $role_key );
			}
			// ^Backwards compat. New delete loop below too
			$roles = get_option( 'pmpro_roles_'.$ml_id );

			if( !empty( $roles ) ){
				foreach( $roles as $role_key => $role_name ){
					remove_role( $role_key );
				}
			}			
			
		}
	}
	
	function user_change_level($level_id, $user_id){
		//get user object
		$wp_user_object = new WP_User($user_id);
		//ignore admins
		if( in_array( 'administrator', $wp_user_object->roles ) )
			return;
		//downgrade time!
		if( $level_id == 0 ) {
			$wp_user_object->set_role('subscriber');
		}
		//set the role to our key
		else {
			$roles = get_option( 'pmpro_roles_'.$level_id );
			if( !empty( $roles ) ){
				foreach( $roles as $role_key => $role_name ){
					$wp_user_object->set_role( $role_key );
				}
			} else {
				$wp_user_object->set_role( PMPRO_Roles::$role_key . $level_id );
			}
		}
	}

	public static function level_settings() {
		?>
		<hr />
		<h3><?php esc_html_e( 'Paid Memberships Pro - Roles', 'pmpro-roles' ); ?></h3>
		<p><?php _e( "Choose which roles should be applied to this level.", 'pmpro-roles' ); ?></p>
		<table>
			<tbody class="form-table">
				<?php
				
				$level_id = absint( filter_input( INPUT_GET, 'edit', FILTER_DEFAULT ) );

				global $wp_roles;

			    $all_roles = $wp_roles->roles;

			    $editable_roles = apply_filters('editable_roles', $all_roles);

			    $saved_roles = get_option( 'pmpro_roles_'.$level_id );			    
			    
				if( !empty( $editable_roles ) ){
					?>
					<tr>
						<th scope="row" valign="top"><label><?php _e('Select Roles For This Level', 'pmpro-roles'); ?>:</label></th>
						<td>
							<ul>
							<?php
							foreach( $editable_roles as $key => $role ){

								$checked = '';
								//Backwards compat here, if $saved_roles is empty, set the default level's role as checked
								if( empty( $saved_roles ) ){
									if( PMPRO_Roles::$role_key.$level_id == $key ){
										$checked = 'checked=true';
									}
								}

								if( isset( $saved_roles[$key] ) ){ 
									$checked = 'checked=true';
								}

								
								?>
								<li>
									<input type='checkbox' name='pmpro_roles_level[<?php echo $key; ?>]' value='<?php echo stripslashes( $role["name"] ); ?>' id='<?php echo $key; ?>' <?php echo $checked; ?> /> <label for='<?php echo $key; ?>'><?php echo stripslashes( $role['name'] ); ?></label>
								</li>
								<?php
							}
							?>
							</ul>
						</td>
					<?php
				}
				?>
			</tbody>
		</table>
		<?php
	}

	public static function remove_list_roles( $roles ){

		//Take admins out of the array first 
		unset( $roles['administrator'] );

		if( !function_exists( 'pmpro_getAllLevels' ) ){
			return $roles;
		}

		if( !empty( $_REQUEST['edit'] ) ){

			$edit_level = intval( $_REQUEST['edit'] );

			$all_levels = pmpro_getAllLevels( true, false );

			foreach( $all_levels as $level_key => $level ){
				if( $level_key !== $edit_level ){
					if( isset( $roles[PMPRO_Roles::$role_key.$level_key] ) ){
						unset( $roles[PMPRO_Roles::$role_key.$level_key] ); 
					}
				}
			}
			
		}

		return $roles;

	}

	//activation function
	public static function install() {
		
		global $wpdb;
		if( defined( 'DOING_AJAX' ) && DOING_AJAX ){
			check_ajax_referer( PMPRO_Roles::$ajaction );
		}
		
		$levels = $wpdb->get_results( "SELECT * FROM $wpdb->pmpro_membership_levels" );
		
		if( !$levels ) {
			if( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				die( 'failed' );
			}
			else {
				return;
			}
		}

		$capabilities = PMPRO_Roles::capabilities();

		$i = 0;
		foreach ( $levels as $level ) {
			$role_key = PMPRO_Roles::$role_key . $level->id;
			//the role doesn't exist for this level
			if( !get_role( $role_key ) ) {
				$i++;
				add_role( $role_key, $level->name, $capabilities[$level->id] );
			}
		}
		if( defined( 'DOING_AJAX' ) && DOING_AJAX ){
			if($i > 0){
				echo $i;
			}
			else{
				echo __('No', PMPRO_Roles::$plugin_slug);
			}
			die();
		}
	}

	public static function capabilities( $role_key = null ) {
		$all_levels = pmpro_getAllLevels( true, false );
		$capabilities = array();

		if( !empty( $role_key ) ){
			$capabilities[$role_key] = array( 'read' => true );
		} else {
			foreach ( $all_levels as $key => $value ) {
				$capabilities[$key] = array( 'read' => true );
			}
		}

		$capabilities = apply_filters( 'pmpro_roles_default_caps', $capabilities );

		return $capabilities;
	}

	/**
	 * Add "Delete Roles and Deactivate" link to plugins page
	 */
	public static function add_action_links($links) {	
		// Only add this if plugin is active.
		if( is_plugin_active( 'pmpro-roles/pmpro-roles.php' ) ) {
			$new_links = array(
				'<a href="' . wp_nonce_url(get_admin_url(NULL, 'plugins.php?pmpro_roles_delete_and_deactivate=1'), 'pmpro_roles_delete_and_deactivate') . '">' . __('Delete Roles and Deactivate', 'pmpro-roles') . '</a>',
			);
			return array_merge($new_links, $links);
		}

		return $links;
	}

	/**
	 * Add links to the plugin row meta
	 */
	public static function plugin_row_meta( $links, $file ) {
		if ( strpos( $file, 'pmpro-roles' ) !== false ) {
			$new_links = array(
				'<a href="' . esc_url( 'https://www.paidmembershipspro.com/add-ons/pmpro-roles/' ) . '" title="' . esc_attr( __( 'View Documentation', 'pmpro-roles' ) ) . '">' . __( 'Docs', 'pmpro-roles' ) . '</a>',
				'<a href="' . esc_url( 'https://paidmembershipspro.com/support/' ) . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro-roles' ) ) . '">' . __( 'Support', 'pmpro-roles' ) . '</a>',
			);
			$links     = array_merge( $links, $new_links );
		}
		return $links;
	}

	/**
	 * Process delete and deactivate if clicked.
	 */
	public static function delete_and_deactivate() {
		//see if our param was passed
		if(empty($_REQUEST['pmpro_roles_delete_and_deactivate']))
			return;

		//check nonce
		check_admin_referer('pmpro_roles_delete_and_deactivate');
		
		//find roles based on levels
		global $wpdb;
		$roles = get_option( $wpdb->get_blog_prefix() . 'user_roles' );
		
		foreach($roles as $key => $role) {
			//is this a pmpro role?
			if(strpos($key, PMPRO_Roles::$role_key) === 0) {				
				//change all users with those roles to have the default role			
				$users = get_users(array('role'=>$key));
				foreach($users as $user) {
					$user->set_role('subscriber');
				}

				//delete the roles
				remove_role($key);
			}
		}

		//deactivate the plugin
		deactivate_plugins( plugin_basename( __FILE__ ) );

		//output deactivated notice:
		?>
		<div id="message" class="updated notice is-dismissible">
			<p><?php _e('Plugin <strong>deactivated</strong>');?>.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php _e('Dismiss this notice.');?></span></button>
		</div>
		<?php
	}
}
new PMPRO_Roles;
register_activation_hook( __FILE__, array( 'PMPRO_Roles', 'install' ) );
