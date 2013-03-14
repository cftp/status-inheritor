<?php
/*
Plugin Name:  Status Inheritor
Description:  Allows you to publish (or make draft) a post and all it's descendants (children, grandchildren, great-grandchildren, etc, etc) in one go.
Version:      1.2
Plugin URI:   http://github.com/cftp/status-inheritor
Author:       Code For The People Ltd
Author URI:   http://codeforthepeople.com/
Text Domain:  status_inheritor
Domain Path:  /languages/
License:      GPL v2 or later

Copyright 2013 Code for the People Ltd
				_____________
			   /      ____   \
		 _____/       \   \   \
		/\    \        \___\   \
	   /  \    \                \
	  /   /    /          _______\
	 /   /    /          \       /
	/   /    /            \     /
	\   \    \ _____    ___\   /
	 \   \    /\    \  /       \
	  \   \  /  \____\/    _____\
	   \   \/        /    /    / \
		\           /____/    /___\
		 \                        /
		  \______________________/

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

class status_inheritor {

	public $no_recursion = false;

	/**
	 * Class constructor. Set up some filters and actions.
	 *
	 * @return null
	 */
	function __construct() {

		# Actions
		add_action( 'init',                        array( $this, 'load' ) );
		add_action( 'post_submitbox_misc_actions', array( $this, 'submit_box' ) );
		add_action( 'save_post',                   array( $this, 'save_post' ), 10, 2 );

		# Filters
		# none yet

	}

	function submit_box() {

		$pto = get_post_type_object( get_post_type() );

		if ( ! $this->allowed_post_type( $GLOBALS[ 'post' ] ) )
			return;

		if ( !$pto->hierarchical )
			return;

		echo '<div class="misc-pub-section">';
		printf( '<label><input type="checkbox" name="bestow_status" value="1" /> %s</label>', __( 'Apply this status to all descendants', 'status_inheritor' ) );
		echo '</div>';

	}

	function save_post( $post_id, $post ) {

		if ( $this->no_recursion )
			return;

		$pto = get_post_type_object( get_post_type( $post ) );

		if ( !$pto->hierarchical )
			return;
		if ( wp_is_post_revision( $post ) )
			return;
		if ( wp_is_post_autosave( $post ) )
			return;
		if ( !isset( $_POST['bestow_status'] ) )
			return;

		$post_status = get_post_stati();
		unset(
		#	$post_status['private'], # @TODO this?
			$post_status['trash'],
			$post_status['auto-draft'],
			$post_status['inherit']
		);

		$descendants = $this->get_post_descendants( $post );

		if ( empty( $descendants ) )
			return;

		$this->no_recursion = true;

		// To avoid triggering `save_post` on descendant posts, with attendant issues
		// where other plugins getting confused by seeing POST/GET variables, we access 
		// the DB directly (and remember the transition post actions and 
		// cache clearance).
		global $wpdb;
		$sql = " UPDATE $wpdb->posts SET post_status = %s WHERE ID = %d ";
		foreach ( $descendants as $descendant_id ) {
			$descendant = get_post( $descendant_id );
			$wpdb->query( $wpdb->prepare( $sql, $post->post_status, $descendant->ID ) );
			do_action('transition_post_status', $post->post_status, $descendant->post_status, $descendant);
			do_action("{$descendant->post_status}_to_{$post->post_status}", $descendant);
			do_action("{$post->post_status}_{$descendant->post_type}", $descendant->ID, $descendant);
			clean_post_cache( $descendant->ID );
		}

		$this->no_recursion = false;

	}

	/**
	 * Retrieve direct children of a post.
	 *
	 * @param int|object $post Post ID or post object
	 * @return array Child post IDs or empty array if none are found.
	 */
	function get_post_children( $post ) {

		if ( ! $post = get_post( $post ) )
			return array();

		return get_posts( array( 'post_type' => 'any', 'post_status' => 'any', 'post_parent' => $post->ID, 'fields' => 'ids' ) );
	}

	/**
	 * Retrieve all descendants of a post as IDs.
	 *
	 * @param int|object $post Post ID or post object
	 * @param array $descendants An array of IDs to which we are adding recursively
	 * @return array Descendant IDs or empty array if none are found.
	 */
	function get_post_descendants( $post, $descendants = null ) {

		if ( ! $post = get_post( $post ) )
			return array();

		if ( is_null( $descendants ) )
			$descendants = array();
		
		$children = $this->get_post_children( $post );
		$descendants = array_merge( $descendants, $children );
		
		foreach( $children as $child )
			$descendants = $this->get_post_descendants( $child, $descendants );

		return $descendants;
	}

	/**
	 * Determines whether to allow status inheritance on
	 * the post type of a given post.
	 *
	 * @param int|object $post Either a Post ID or object
	 * @return bool True if the status inheritor is allowed for this post type
	 * @author 
	 **/
	function allowed_post_type( $post ){
		$post = get_post( $post );
		$allowed_post_types = get_post_types( array( 'hierarchical' => true, 'public' => true ) );
		$allowed_post_types = apply_filters( 'cftp_si_allowed_post_types', $allowed_post_types );
		return in_array( $post->post_type, $allowed_post_types );
	}

	function load() {

		load_plugin_textdomain( 'status_inheritor', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	}

	/**
	 * Singleton stuff.
	 * 
	 * @return status_inheritor
	 */
	static public function init() {

		static $instance = null;

		if ( !$instance )
			$instance = new status_inheritor;

		return $instance;

	}
	
}

status_inheritor::init();
