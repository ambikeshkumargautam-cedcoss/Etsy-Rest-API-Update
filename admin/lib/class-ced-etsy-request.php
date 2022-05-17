<?php

class Ced_Etsy_Request {
	
	public $client_id;
	public $base_url;
	public $client_secret;

	function __construct() {
		$this->client_id     = 'b2pa8bczfrwnuccpevnql8eh';
		$this->client_secret = 'hznh7z8xkb';
		$this->base_url      = 'https://api.etsy.com/v3/';
	}

	public function delete( $action ='', $shop_name='', $query_args=array(),$method='DELETE'  ){
		$api_url = $this->base_url . $action;
		if ( ! empty( $query_args ) ) {
			$api_url = $api_url . '?' . http_build_query( $query_args );
		}

		$header = array(
			'Content-Type: application/json',
			'Accept: application/json',
			'x-api-key: ' . $this->client_id,
		);

		$access_token = $this->get_access_token( $shop_name );
		if ( ! empty( $access_token ) ) {
			$header[] = 'Authorization: Bearer ' . $access_token;
		}
		$curl = curl_init();
		curl_setopt_array(
			$curl,
			array(
				CURLOPT_URL            => $api_url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST  => 'DELETE',
				CURLOPT_HTTPHEADER     => $header,
			)
		);

		$response = curl_exec( $curl );
		$response = $this->parse_reponse( $response );
		curl_close( $curl );
		return $response;

	}
	public function put( $action = '', $parameters = array(), $shop_name = '', $query_args = array(), $request_type = 'PUT' ){
		$api_url = $this->base_url . $action;
		// var_dump( $api_url );
		if ( ! empty( $query_args ) ) {
			$api_url = $api_url . '?' . http_build_query( $query_args );
		}

		$header = array(
			'Content-Type: application/json',
			'Accept: application/json',
			'x-api-key: ' . $this->client_id,
		);

		$access_token = $this->get_access_token( $shop_name );
		// var_dump($access_token);
		if ( ! empty( $access_token ) && $action != 'public/oauth/token' ) {
			$header[] = 'Authorization: Bearer ' . $access_token;
		}
		// var_dump( $header );
		$curl = curl_init();
		curl_setopt_array(
			$curl,
			array(
				CURLOPT_URL            => $api_url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST  => 'PUT',
				CURLOPT_POSTFIELDS     => json_encode($parameters),
				CURLOPT_HTTPHEADER     => $header,
			)
		);
		$response = curl_exec( $curl );
		// var_dump( $response );
		$response = $this->parse_reponse( $response );
		curl_close( $curl );
		return $response;
	}

	public function post( $action = '', $parameters = array(), $shop_name = '', $query_args = array(), $request_type = 'POST' ) {
		$api_url = $this->base_url . $action;
		if ( ! empty( $query_args ) ) {
			$api_url = $api_url . '?' . http_build_query( $query_args );
		}

		$header = array(
			'Content-Type: application/json',
			'Accept: application/json',
			'x-api-key: ' . $this->client_id,
		);

		$access_token = $this->get_access_token( $shop_name );
		// var_dump($access_token);
		if ( ! empty( $access_token ) && $action != 'public/oauth/token' ) {
			$header[] = 'Authorization: Bearer ' . $access_token;
		}

		$curl = curl_init();
		curl_setopt_array(
			$curl,
			array(
				CURLOPT_URL            => $api_url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST  => $request_type,
				CURLOPT_POSTFIELDS     => json_encode( $parameters ),
				CURLOPT_HTTPHEADER     => $header,
			)
		);

		$response = curl_exec( $curl );
		$response = $this->parse_reponse( $response );
		curl_close( $curl );
		return $response;

	}

	public function get( $action = '', $shop_name = '', $query_args = array() ) {

		$api_url = $this->base_url . $action;
		if ( ! empty( $query_args ) ) {
			$api_url = $api_url . '?' . http_build_query( $query_args );
		}

		$header = array(
			'Content-Type: application/json',
			'Accept: application/json',
			'x-api-key: ' . $this->client_id,
		);

		$access_token = $this->get_access_token( $shop_name );
		if ( ! empty( $access_token ) ) {
			$header[] = 'Authorization: Bearer ' . $access_token;
		}
		
		$curl = curl_init();
		curl_setopt_array(
			$curl,
			array(
				CURLOPT_URL            => $api_url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST  => 'GET',
				CURLOPT_HTTPHEADER     => $header,
			)
		);

		$response = curl_exec( $curl );
		$response = $this->parse_reponse( $response );
		curl_close( $curl );
		return $response;

	}

	public function parse_reponse( $response ) {
		return json_decode( $response, true );
	}

	public function get_access_token( $shop_name ) {
		$user_details     = get_option( 'ced_etsy_details', array() );
			$access_token = isset( $user_details[ $shop_name ]['details']['token']['access_token'] ) ? $user_details[ $shop_name ]['details']['token']['access_token'] : '';
		return ! empty( $access_token ) ? $access_token : '';
	}
}