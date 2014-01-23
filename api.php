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
					'Accept' => 'application/json',
				),
				'body' => json_encode( $data ),
			) );

			// set_transient( $cache_key, $response, self::EXPIRATION );
		// }

		return $response;
	}

	public function put( $url, $data ) {
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

	/**
	 *
	 */
	public static function add_party( $data ) {
		return self::instance()->post( 'api/person', $data );
	}

	/**
	 *
	 */
	public static function update_party( $party_id, $data ) {
		return self::instance()->put( trailingslashit( self::instance()->url ) . 'api/person/' . $party_id, $data );
	}

	/**
	 *
	 */
	public static function get_party( $party_id ) {
		$response = self::instance()->get( 'api/party/' . (int)$party_id );
		$data = json_decode( $response['body'] );

		if ( isset( $data->person ) ) {
			return $data->person;
		}

		return array();
	}

	/**
	 * Get the custom field definitions for the specified type.
	 * @param $type
	 * @return bool|array
	 */
	public static function get_custom_fields( $type = 'party' ) {
		$response = self::instance()->get( 'api/' . $type . '/customfield/definitions' );
		$data = json_decode( $response['body'] );

		if ( isset( $data->customFieldDefinitions->{'@size'} ) ) {
			if ( 0 == $data->customFieldDefinitions->{'@size'} ) {
				return array();
			}

			if ( 1 == $data->customFieldDefinitions->{'@size'} ) {
				return array( $data->customFieldDefinitions->customFieldDefinition );
			}

			return $data->customFieldDefinitions->customFieldDefinition;
		}

		return false;
	}

	public static function get_custom_fields_for( $id, $type = 'party' ) {
		$response = self::instance()->get( '/api/' . $type . '/' . $id . '/customfields' );
		$data = json_decode( $response['body'] );


		if ( isset( $data->customFields->{'@size'} ) ) {
			if ( 0 == $data->customFields->{'@size'} ) {
				return array();
			}

			if ( 1 == $data->customFields->{'@size'} ) {
				return array( $data->customFields->customField );
			}

			return $data->customFields->customField;
		}

		return false;
	}

	public static function get_opportunities( $party_id ) {
		$response = self::instance()->get( 'api/party/' . $party_id . '/opportunity' );
		$data = json_decode( $response['body'] );

		if ( isset( $data->opportunities->{'@size'} ) ) {
			if ( 0 == $data->opportunities->{'@size'} ) {
				return array();
			}

			if ( 1 == $data->opportunities->{'@size'} ) {
				return array( $data->opportunities->opportunity );
			}

			return $data->opportunities->opportunity;
		}

		return false;
	}

	public static function get_opportunity_tags( $opportunity_id ) {
		$response = self::instance()->get( 'api/opportunity/' . $opportunity_id . '/tag' );
		$data = json_decode( $response['body'] );

		if ( isset( $data->tags->{'@size'} ) ) {

			if ( 0 == $data->tags->{'@size'} ) {
				return array();
			}

			if ( 1 == $data->tags->{'@size'} ) {
				return array( $data->tags->tag );
			}

			return $data->tags->tag;
		}

		return false;
	}
}