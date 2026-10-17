<?php
/**
 * Removes plugin options. Saved sliders are kept unless KS_REMOVE_DATA is defined as true.
 *
 * @package KashidaSlider
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'ks_schema_version' );
delete_option( 'ks_content_ver' );

if ( defined( 'KS_REMOVE_DATA' ) && KS_REMOVE_DATA ) {
	$ids = get_posts( array( 'post_type' => 'ks_slider', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ) );
	foreach ( $ids as $id ) {
		wp_delete_post( $id, true );
	}
}
