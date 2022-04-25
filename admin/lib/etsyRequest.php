<?php

class Ced_Etsy_Request {

	function __construct() {
		$this->loadDependency();
		$this->base_url      = $this->config->base_url;
		$this->client_id     = $this->config->client_id;
		$this->client_secret = $this->config->client_secret;
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

	public function loadDependency() {

		$fileProducts = CED_ETSY_DIRPATH . 'admin/lib/etsyConfig.php';
		if ( file_exists( $fileProducts ) ) {
			require_once $fileProducts;
		}

		$this->config = new Ced_Etsy_Config();
	}

	public function get_access_token( $shop_name ) {
		$user_details     = get_option( 'ced_etsy_details', array() );
			$access_token = isset( $user_details[ $shop_name ]['details']['token']['access_token'] ) ? $user_details[ $shop_name ]['details']['token']['access_token'] : '';
		return ! empty( $access_token ) ? $access_token : '';
	}
}
