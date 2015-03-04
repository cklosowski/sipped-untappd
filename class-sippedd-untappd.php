<?php


class Sippedd_Untapped_API {

	private $api_url = 'https://api.untappd.com/v4/';

	private $api_key    = '';
	private $api_secret = '';


	public function __construct( $api_key, $api_secret ) {
		$this->api_key    = $api_key;
		$this->api_secret = $api_secret;
	}

	public function get_users_checkins( $username ) {
		$method      = '/user/checkins/' . $username;
		$request_url = $this->get_request_url( $method );

		if ( get_transient( 'sippedd-checkins-' . $username ) ) {
			return get_transient( 'sippedd-checkins-' . $username );
		}

		$results = $this->request( $request_url );
		$results = $results['response']['checkins']['items'];

		set_transient( 'sippedd-checkins-' . $username, $results, HOUR_IN_SECONDS * 2 );

		return $results;
	}

	private function get_count() {
		$settings = get_option( 'sippedd_settings' );

		return isset( $settings['count'] ) ? $settings['count'] : 25;
	}

	private function get_request_url( $method ) {
		$endpoint = $this->api_url . $method . '?limit=' . $this->get_count() . '&client_id=' . $this->api_key . '&client_secret=' . $this->api_secret;

		return $endpoint;
	}

	private function request( $url ) {
		return json_decode( wp_remote_retrieve_body( wp_remote_get( $url ) ), true );
	}
}
