<?php

class Capsule_CRM_API {

	const EXPIRATION = 300;

	private function __construct() {
		$this->url = '';
		$this->key = '';
	}

	public static function instance() {
		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new Capsule_CRM_API();
		}

		return $instance;
	}


	public function get( $endpoint ) {
		$cache_key = 'cap_' . md5( serialize( func_get_args() ) );

		// if ( ! $response = get_transient( $cache_key ) ) {
			$url = trailingslashit( $this->url ) . $endpoint;

			$response = wp_remote_get( $url, array(
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( $this->key . ':x' ),
					'Accept' => 'application/json',
				) )
			);

			// set_transient( $cache_key, $response, self::EXPIRATION );
		// }

		return $response;
	}

	public function post( $endpoint, $data ) {
		// $cache_key = 'cap_' . md5( serialize( func_get_args() ) );

		// if ( ! $response = get_transient( $cache_key ) ) {
			$url = trailingslashit( $this->url ) . $endpoint;

			$response = wp_remote_post( $url, array(
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( $this->key . ':x' ),
					'Content-type' => 'application/json',
				),
				'body' => json_encode( $data ),
			) );

			// set_transient( $cache_key, $response, self::EXPIRATION );
		// }

		return $response;
	}

	public function put( $url, $data ) {
		error_log( $url );
		$response = wp_remote_request( $url, array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $this->key . ':x' ),
				'Content-type' => 'application/json',
				'Accept' => 'application/json',
			),
			'body' => json_encode( $data ),
			'method' => 'PUT',
		) );

		return $response;
	}

	public function set_url( $url ) {
		$this->url = $url;
		return $this;
	}

	public function set_key( $key ) {
		$this->key = $key;
		return $this;
	}
}