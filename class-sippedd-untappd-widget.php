<?php
class Sippedd_Untappd_Widget extends WP_Widget {

	function Sippedd_Untappd_Widget() {
		// Instantiate the parent object
		parent::__construct( false, 'SippEDD Untappd' );
	}

	function widget( $args, $instance ) {
		$settings   = get_option( 'sippedd_settings' );
		$api_key    = trim( $settings['api_key'] );
		$api_secret = trim( $settings['api_secret'] );
		$usernames  = trim( $settings['usernames'] );
		$count      = trim( $settings['count'] );
		?>
		<div style="text-align: center;">
		<h3>Latest Check-In</h3>
		<?php
		$usernames = explode( ',', $usernames );
		$api       = new Sippedd_Untapped_API( $api_key, $api_secret );
		foreach ( $usernames as $username ) {
			$username     = trim( $username );
			$checkins     = $api->get_users_checkins( $username );
			$last_checkin = ! empty( $checkins[0] ) ? $checkins[0] : false;
			?>
			<p>
				<h6><?php echo $username; ?></h6>
				<div class="sippedd-latest-wrapper">
					<div class="sippedd-latest-beer">
						<?php if ( ! empty( $last_checkin ) ) : ?>
							<img height="100px" width="100px" src="<?php echo $last_checkin['beer']['beer_label']; ?>" /><br />
							<?php echo $last_checkin['beer']['beer_name']; ?>
						<?php else: ?>
							<em><?php _e( 'No Checkins Found', 'sippedd-untappd' ); ?></em>
						<?php endif; ?>
					</div>
				</div>
			</p>
			<?
		}
		?>
		</div>
		<?php
	}

}
