<?php
/*
 * Plugin Name: Sippedd-Untappd
 * Description: Pulls in data for the EDD team to show Untappd checkins
 * Author: Chris Klosowski
 * Version: 1.0
 */

define( 'SIPPEDD_FILE', plugin_basename( __FILE__ ) );
define( 'SIPPEDD_URL', plugins_url( '/', SIPPEDD_FILE ) );

class Sippedd_Untappd {

	private static $su_instance;

	private function __construct() {
		add_action( 'admin_init', array( $this, 'sippedd_settings_init' ), 10 );
		add_action( 'admin_menu', array( $this, 'sippedd_add_admin_menu' ), 1000, 0 );
		add_shortcode( 'show_checkins', array( $this, 'sipped_checkins' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'load_dashicons' ) );
		include_once( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'class-sippedd-untappd.php' );
		include_once( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'class-sippedd-untappd-widget.php' );
		include_once( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'metaboxes.php' );
		add_action( 'widgets_init', array( $this, 'register_widget' ) );
		add_shortcode( 'untappd-url', array( $this, 'show_beer_url' ) );
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
			<div class="sippedd-checkin-wrapper">
				<p class="sippedd-checkin-beer-label">
					<img src="<?php echo $checkin['beer_label']; ?>" />
				</p>
				<p class="sippedd-meta-wrapper">
					<span class="sippedd-meta">
						<span class="sippedd-meta-user"><?php echo $checkin['username']; ?></span>
						<span class="sippedd-meta-beer"><?php echo $checkin['beer']; ?></span>
						<span class="sippedd-meta-brewery"> by <?php echo $checkin['brewery']; ?></span>
						<?php echo $this->generate_stars( $checkin['rating'] ); ?>
					</span>
				</p>
			</div>
			<?
		}
	}

	public function show_beer_url( $atts ) {
		if ( ! is_single() && ! is_page() ) {
			return;
		}

		global $post;

		$attributes = shortcode_atts( array(
			'post_id' => $post->ID
		), $atts );

		$url     = get_post_meta( $attributes['post_id'], '_untappd_beer_url', true );

		if ( empty( $url ) ) {
			return;
		}

		$output  = '<p class="sippedd-beer-link-wrapper">';
		$output .= '<a class="sippedd-beer-link" href="' . $url . '">';
		$output .= apply_filters( 'sippedd_view_beer_text', __( 'View This Beer on Untappd', 'sippedd' ) );
		$output .= '</a>';
		$output .= '</p>';

		echo $output;
	}

	public function load_dashicons() {
		wp_enqueue_style( 'dashicons' );

		wp_register_style( 'sippedd_styles', SIPPEDD_URL . 'assets/css/style.css', false );
		wp_enqueue_style( 'sippedd_styles' );
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
