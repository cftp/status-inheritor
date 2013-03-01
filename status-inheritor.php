<?php
/*
Plugin Name:  Status Inheritor
Description:  Provides a checkbox on the post editing screen allowing you to specify that children of the current post inherit the status of the current post. @TODO: Describe this more betterer.
Version:      1.1
Plugin URI:   http://github.com/cftp/status-inheritor
Author:       Code For The People Ltd
Author URI:   http://codeforthepeople.com/
Text Domain:  status_inheritor
Domain Path:  /languages/
License:      GPL v2 or later

Copyright © 2013 Code For The People Ltd

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
		add_filter( 'euapi_plugin_handler',        array( $this, 'update_handler' ), 10, 2 );

	}

	function update_handler( EUAPI_Handler $handler = null, EUAPI_Item $item ) {

		if ( 'status-inheritor/status-inheritor.php' == $item->file ) {

			$handler = new EUAPI_Handler_GitHub( array(
				'type'       => $item->type,
				'file'       => $item->file,
				'github_url' => 'https://github.com/cftp/status-inheritor',
				'sslverify'  => false
			) );

		}

		return $handler;

	}

	function submit_box() {

		$pto = get_post_type_object( get_post_type() );

		if ( ! $this->allowed_post_type( $GLOBALS[ 'post' ] ) )
			return;

		if ( !$pto->hierarchical )
			return;

		echo '<div class="misc-pub-section">';
		printf( '<label><input type="checkbox" name="bestow_status" value="1" /> %s</label>', __( 'Apply this status to all children', 'status_inheritor' ) );
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

		$children = get_pages( array(
			'post_type'   => $pto->name,
			'child_of'    => $post->ID,
			'post_status' => $post_status,
		) );

		if ( empty( $children ) )
			return;

		$this->no_recursion = true;

		foreach ( $children as $child ) {
			wp_update_post( array(
				'ID'          => $child->ID,
				'post_status' => $post->post_status
			) );
		}

		$this->no_recursion = false;

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
