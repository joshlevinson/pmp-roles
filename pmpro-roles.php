<?php
/*
Plugin Name: PMPro Roles
Description: Adds a WordPress Role for each Membership Level with Display Name = Membership Level Name and Role Name = 'pmpro_role_X' (where X is the Membership Level's ID).
Plugin URI: http://joshlevinson.me
Author: Josh Levinson
Author URI: http://joshlevinson.me
Version: 1.0
License: GPL2
Text Domain: pmpro-roles
Domain Path: /pmpro-roles
*/

/*
    Copyright (C) 2013  Josh Levinson  josh@joshlevinson.me

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
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
		$role_key = PMPRO_Roles::$role_key . $saveid;
		//created a new level
		if( $_REQUEST['edit'] < 0 ) {
			add_role( $role_key, $_REQUEST['name'] );
		}
		//edited a level
		else {
			global $wpdb;
			//have to get all roles and find ours because get_role() doesn't yield the role's "pretty" name, only its index.
			$roles = get_option( $wpdb->get_blog_prefix() . 'user_roles' );
			//can't get the roles, die
			if(!is_array( $roles ) ) return;
			//the role doesn't exist - create it, then we are done.
			if(!isset( $roles[$role_key] ) ){
				add_role( $role_key, $_REQUEST['name'] );
				return;
			}
			$role = $roles[$role_key];
			$role_name = $role['name'];
			//we only need to update if the role's name has changed.
			if( $role_name !== $_REQUEST['name'] ) {
				//delete the role (because update_role() doesn't exist...)
				remove_role( $role_key );
				//then recreate it
				add_role( $role_key, $_REQUEST['name'] );
			}
		}
	}
	
	function delete_level() {
		//if there is no action, or if there is an action but it isn't deleting, return.
		if( !isset( $_REQUEST['action'] ) || ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] !== 'delete_membership_level' ) ) { return; }
		$ml_id = $_REQUEST['deleteid'];
		if($ml_id > 0){
			$role_key = PMPRO_Roles::$role_key . $ml_id;
			//this will silently fail if the role doesn't exist, so we're fine.
			remove_role( $role_key );
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
			$wp_user_object->set_role( PMPRO_Roles::$role_key . $level_id );
		}
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
		$i = 0;
		foreach ( $levels as $level ) {
			$role_key = PMPRO_Roles::$role_key . $level->id;
			//the role doesn't exist for this level
			if( !get_role( $role_key ) ) {
				$i++;
				add_role( $role_key, $level->name );
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
}
new PMPRO_Roles;
register_activation_hook( __FILE__, array( 'PMPRO_Roles', 'install' ) );