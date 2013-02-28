<?php
/*
Plugin Name:  Status Inheritor
Description:  Provides a checkbox on the post editing screen allowing you to specify that children of the current post inherit the status of the current post. @TODO: Describe this more betterer.
Version:      1.0
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
		# (none)

	}

	function submit_box() {

		$pto = get_post_type_object( get_post_type() );

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
