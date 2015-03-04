<?php
/*
 * Plugin Name: Sippedd-Untappd
 * Description: Pulls in data for the EDD team to show Untappd checkins
 * Author: Chris Klosowski
 * Version: 1.0
 */

class Sippedd_Untappd {

	private static $su_instance;

	private function __construct() {
		add_action( 'admin_init', array( $this, 'sippedd_settings_init' ), 10 );
		add_action( 'admin_menu', array( $this, 'sippedd_add_admin_menu' ), 1000, 0 );
		add_shortcode( 'show_checkins', array( $this, 'sipped_checkins' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'load_dashicons' ) );
		include_once( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'class-sippedd-untappd.php' );
		include_once( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'class-sippedd-untappd-widget.php' );
		add_action( 'widgets_init', array( $this, 'register_widget' ) );
	}

	/**
	 * Get the singleton instance of our plugin
	 * @return class The Instance
	 * @access public
	 */
	public static function getInstance() {
		if ( !self::$su_instance ) {
			self::$su_instance = new Sippedd_Untappd();
		}

		return self::$su_instance;
	}

	function sippedd_add_admin_menu() {

		add_options_page( 'Untappd', 'Untappd', 'manage_options', 'sippedd-untappd', array( $this, 'sippedd_options_page' ) );

	}


	function sippedd_settings_init() {

		register_setting( 'pluginPage', 'sippedd_settings' );

		add_settings_section(
			'sippedd_pluginPage_section',
			__( 'SippEDD Settings', 'sippedd' ),
			array( $this, 'sippedd_settings_section_callback' ),
			'pluginPage'
		);

		add_settings_field(
			'sippedd_api_key',
			__( 'Untappd API Key', 'sippedd' ),
			array( $this, 'render_api_key_field' ),
			'pluginPage',
			'sippedd_pluginPage_section'
		);

		add_settings_field(
			'sippedd_api_secret',
			__( 'Untappd API Secret', 'sippedd' ),
			array( $this, 'render_api_secret_field' ),
			'pluginPage',
			'sippedd_pluginPage_section'
		);

		add_settings_field(
			'sipped_usernames',
			__( 'Untappd Usernames', 'sippedd' ),
			array( $this, 'render_usernames_field' ),
			'pluginPage',
			'sippedd_pluginPage_section'
		);

		add_settings_field(
			'sipped_number',
			__( 'Number of checkins to show', 'sippedd' ),
			array( $this, 'render_number_field' ),
			'pluginPage',
			'sippedd_pluginPage_section'
		);


	}


	function render_api_key_field() {

		$options = get_option( 'sippedd_settings' );
		?>
		<input size="50" type='text' name='sippedd_settings[api_key]' value='<?php echo $options['api_key']; ?>'>
		<?php

	}


	function render_api_secret_field() {

		$options = get_option( 'sippedd_settings' );
		?>
		<input size="50" type='text' name='sippedd_settings[api_secret]' value='<?php echo $options['api_secret']; ?>'>
		<?php

	}


	function render_usernames_field() {

		$options = get_option( 'sippedd_settings' );
		?>
		<input size="50" type='text' name='sippedd_settings[usernames]' value='<?php echo $options['usernames']; ?>'>
		<?php

	}

	function render_number_field() {

		$options = get_option( 'sippedd_settings' );
		?>
		<input size="10" type='text' name='sippedd_settings[count]' value='<?php echo $options['count']; ?>'>
		<?php

	}


	function sippedd_settings_section_callback() {
	}


	function sippedd_options_page() {

		?>
		<form action='options.php' method='post'>

			<h2>Untappd Settings for SippEDD</h2>

			<?php
			settings_fields( 'pluginPage' );
			do_settings_sections( 'pluginPage' );
			submit_button();
			?>

		</form>
		<?php

	}

	public function register_widget() {
		register_widget( 'Sippedd_Untappd_Widget' );
	}

	public function sipped_checkins() {
		$settings = get_option( 'sippedd_settings', true );

		$api_key    = trim( $settings['api_key'] );
		$api_secret = trim( $settings['api_secret'] );
		$usernames  = trim( $settings['usernames'] );
		$count      = trim( $settings['count'] );

		$usernames = explode( ',', $usernames );

		if ( empty( $usernames ) ) {
			return;
		}

		$api            = new Sippedd_Untapped_API( $api_key, $api_secret );
		$found_checkins = array();
		$checkins       = array();

		foreach ( $usernames as $username ) {
			$username = trim( $username );

			$found_checkins = $api->get_users_checkins( $username );

			foreach ( $found_checkins as $checkin ) {
				$checkins[$checkin['checkin_id']] = array(
					'time'       => $checkin['created_at'],
					'rating'     => $checkin['rating_score'],
					'username'   => $checkin['user']['user_name'],
					'avatar'     => $checkin['user']['user_avatar'],
					'beer'       => $checkin['beer']['beer_name'],
					'brewery'    => $checkin['brewery']['brewery_name'],
					'beer_label' => $checkin['beer']['beer_label']
				);
			}
		}

		krsort( $checkins );
		$checkins = array_slice( $checkins, 0, $count );

		foreach ( $checkins as $checkin ) {
			?>
			<div class="sippedd-checkin-wrapper" style="font-family: 'Noto Sans', sans-serif; width: 48%; display: inline-block; min-height: 125px;">
				<p class="sippedd-checkin-beer-label" style="float: left; padding-right: 10px;">
					<img height="100px" width="100px" src="<?php echo $checkin['beer_label']; ?>" />
				</p>
				<p class="sippedd-meta-wrapper">
					<span class="sippedd-meta" style="width: 100% text-align: left;">
						<span style="display: block; font-size: 14px;"><?php echo $checkin['username']; ?></span>
						<span style="display: block; font-size: 12px;"><?php echo $checkin['beer']; ?></span>
						<span style="display: block; font-size: 10px;"> by <?php echo $checkin['brewery']; ?></span>
						<?php echo $this->generate_stars( $checkin['rating'] ); ?>
					</span>
				</p>
			</div>
			<?
		}
	}

	public function load_dashicons() {
		wp_enqueue_style( 'dashicons' );
	}

	public function generate_stars( $number ) {
	// Get the whole number
	$whole = floor( $number );

	// Find out if our number contains a decimal
	$fraction = $number - $whole;

	$i = 0;
	// This is the total number of stars to generate.
	$total = 5;
	$output = '';

	// Generate the filled stars
	while( $i < $whole ) {
		$output .= '<span class="ratings dashicons dashicons-star-filled"></span>';
		$i++;
	}

	// Generate the half star, if needed
	if ( $fraction > 0 ) {
		$output .= '<span class="ratings dashicons dashicons-star-half"></span>';
		$i++;
	}

	// Until total is met, generate empty stars
	if ( $i < $total ) {
		while ( $i < $total ) {
			$output .= '<span class="ratings dashicons dashicons-star-empty"></span>';
			$i++;
		}
	}

	return $output;
}


}

function load_sippedd_untapped() {
	global $su_loaded;

	$su_loaded = Sippedd_Untappd::getInstance();
}
add_action( 'plugins_loaded', 'load_sippedd_untapped' );
