<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register the metaboxes for our Untappd options
 * @return void
 */
function sippedd_register_meta_boxes() {
	global $post;
	$post_types = apply_filters( 'sipped_post_types', array( 'post' ) );

	if ( ! in_array( $post->post_type, $post_types ) ) {
		return;
	}

	foreach ( $post_types as $post_type => $value ) {
		add_meta_box( 'sippedd_metabox', 'Untappd Options', 'sippedd_metabox', $post_type, 'normal', 'high' );
	}
}
add_action( 'add_meta_boxes', 'sippedd_register_meta_boxes', 12 );

/**
 * Display the Metabox for our Untappd options
 * @return void
 */
function sippedd_metabox() {

	global $post;
	$current = get_post_meta( $post->ID, '_untappd_beer_url', true );
	?>
	<label for="untappd-beer-url"><?php _e( 'Untappd Beer URL', 'sippedd' ); ?></label>
	<input id="untappd-beer-url" type="text" size="50" name="_untappd_beer_url" value="<?php echo esc_attr( $current ); ?>" />
	<?php
}

/**
 * Save the URL when added to the post meta
 * @param  int $post_id The post ID being edited
 * @return [type]          [description]
 */
function sippedd_save_metabox( $post_id ) {
	global $post;
	if ( empty( $post ) ) { return; }

	$post_types = apply_filters( 'sipped_post_types', array( 'post' ) );

	if ( ! in_array( $post->post_type, $post_types ) ) {
		return;
	}

	if ( ! empty( $_POST['_untappd_beer_url'] ) ) {
		update_post_meta( $post->ID, '_untappd_beer_url', esc_url( $_POST['_untappd_beer_url'] ) );
	} else {
		delete_post_meta( $post->ID, '_untappd_beer_url' );
	}

}
add_action( 'save_post', 'sippedd_save_metabox', 10, 1 );

