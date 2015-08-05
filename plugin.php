<?php

/*
Plugin Name: WP JSON v2 Custom Endpoints
Plugin URI:
Description: Shows an example of a plugin that creates a custom post type and custom endpoints for the v2 wp-json api
Version: 1.0
Author: Will Haley, Boddie Noell Enterprise
Author URI: https://surc.us
License: None
*/

class BNE_Registration {


	function __construct() {

		add_action( 'init', array( $this, 'init' ) );

		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );

	}

	function init() {

		/**
		 * Registering a custom post type called Units.  The "show_in_rest" option means that it will not be
		 * available in via the rest api...
		 */
		register_post_type( 'units',
			array(
				'labels'       => array(
					'name'          => __( 'Units' ),
					'singular_name' => __( 'Unit' )
				),
				'public'       => true,
				'has_archive'  => false,
				'show_in_rest' => false, //Excludes the custom post type to the rest api
			)
		);

	}

	function rest_api_init() {

		register_rest_route(
			'bne/v1',
			'preregister/(?P<id>\d+)',
			array(
				'method'   => WP_REST_Server::READABLE,
				'callback' => array( $this, 'preregister' )
			)
		);

		register_rest_route(
			'bne/v1',
			'register/(?P<id>\d+)/(?P<lat>\d+\.\d+)/(?P<long>[+-]\d+\.\d+)/(?P<nonce>\w+)',
			array(
				'method'   => WP_REST_Server::READABLE,
				'callback' => array( $this, 'register' )
			)
		);

		register_rest_route(
			'bne/v1',
			'unit/(?P<post_id>\d+)/(?P<number>\d+)/(?P<nonce>\w+)',
			array(
				'method'   => WP_REST_Server::READABLE,
				'callback' => array( $this, 'unit_number' )
			)
		);

		register_rest_route(
			'bne/v1',
			'push/(?P<post_id>\d+)/(?P<key>\d+)/(?P<nonce>\w+)',
			array(
				'method'   => WP_REST_Server::READABLE,
				'callback' => array( $this, 'push_key' )
			)
		);

	}

	function preregister( WP_REST_Request $request ) {

		$id = $request->get_param( 'id' );

		return wp_create_nonce( 'register_' . $id );

	}


	function register( WP_REST_Request $request ) {

		$id    = $request->get_param( 'id' );
		$nonce = $request->get_param( 'nonce' );
		$lat   = $request->get_param( 'lat' );
		$long  = $request->get_param( 'long' );

		if ( ! wp_verify_nonce( $nonce, 'register_' . $id ) ) {
			return new WP_Error( 'registration', 'Validation Fail', array( 'status' => 404 ) );
		}

		$geo = array( 'address' => '', 'lat' => $lat, 'lng' => $long );

		$post = get_page_by_title( $id, OBJECT, 'units' );

		$post_id = ( $post ) ? $post->ID : wp_insert_post( array( 'post_title' => $id, 'post_type' => 'units' ) );

		update_post_meta( $post_id, 'location', $geo );

		return $post_id;

	}

	function unit_number( WP_REST_Request $request ) {

		$post_id = $request->get_param( 'post_id' );
		$nonce   = $request->get_param( 'nonce' );
		$number  = $request->get_param( 'number' );

		if ( ! wp_verify_nonce( $nonce, 'register_' . $number ) ) {
			return new WP_Error( 'registration', 'Validation Fail', array( 'status' => 404 ) );
		}

		return ( update_post_meta( $post_id, 'unit_number', $number ) );

	}

	function push_key( WP_REST_Request $request ) {

		$post_id = $request->get_param( 'post_id' );
		$nonce   = $request->get_param( 'nonce' );
		$key     = $request->get_param( 'key' );

		if ( ! wp_verify_nonce( $nonce, 'register_' . $key ) ) {
			return new WP_Error( 'registration', 'Validation Fail', array( 'status' => 404 ) );
		}

		return ( update_post_meta( $post_id, 'push_key', $key ) );

	}

}

$bne_registration = new BNE_Registration();