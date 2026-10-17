<?php
/**
 * Storage: sliders are saved in a private custom post type; the configuration is JSON in post meta.
 *
 * @package KashidaSlider
 */

defined( 'ABSPATH' ) || exit;

final class KS_Storage {

	const CPT  = 'ks_slider';
	const META = '_ks_config';

	public static function register() {
		register_post_type(
			self::CPT,
			array(
				'labels'              => array( 'name' => __( 'Sliders', 'kashida-slider' ), 'singular_name' => __( 'Slider', 'kashida-slider' ) ),
				'public'              => false,
				'show_ui'             => false,
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => array( 'title' ),
				'capability_type'     => 'post',
			)
		);
	}

	private static function decode( $post_id ) {
		$json = get_post_meta( $post_id, self::META, true );
		$data = is_string( $json ) ? json_decode( $json, true ) : null;
		return KS_Schema::sanitize( is_array( $data ) ? $data : array() );
	}

	/** @return array|null */
	public static function get( $id ) {
		$post = get_post( absint( $id ) );
		if ( ! $post || self::CPT !== $post->post_type || 'publish' !== $post->post_status ) {
			return null;
		}
		$config         = self::decode( $post->ID );
		$config['name'] = $post->post_title ? $post->post_title : $config['name'];
		return array(
			'id'       => $post->ID,
			'name'     => $config['name'],
			'config'   => $config,
			'created'  => (int) get_post_time( 'U', true, $post ),
			'modified' => (int) get_post_modified_time( 'U', true, $post ),
		);
	}

	/**
	 * All saved sliders (decoded). Search and ordering use WP_Query; mode/source filtering is done by the caller.
	 *
	 * @param string $search  Name search.
	 * @param string $orderby 'modified' or 'title'.
	 * @param string $order   ASC|DESC.
	 * @return array[]
	 */
	public static function all( $search = '', $orderby = 'modified', $order = 'DESC' ) {
		$posts = get_posts( array(
			'post_type'   => self::CPT,
			'post_status' => 'publish',
			'numberposts' => 500,
			's'           => $search,
			'orderby'     => 'title' === $orderby ? 'title' : 'modified',
			'order'       => 'ASC' === $order ? 'ASC' : 'DESC',
		) );
		$out = array();
		foreach ( $posts as $p ) {
			$s = self::get( $p->ID );
			if ( $s ) {
				$out[] = $s;
			}
		}
		return $out;
	}

	public static function count() {
		$c = wp_count_posts( self::CPT );
		return isset( $c->publish ) ? (int) $c->publish : 0;
	}

	public static function create( $name, $mode ) {
		$config         = KS_Schema::defaults( $mode );
		$name           = trim( sanitize_text_field( $name ) );
		$config['name'] = '' !== $name ? $name : $config['name'];
		if ( 'dynamic' === $config['mode'] || ( 'carousel' === $config['mode'] && 'custom' !== $config['source']['type'] ) ) {
			$config['slides'] = array();
			$config['design']['layout'] = 'hero';
		} else {
			$config['slides'][0]['bg_image'] = '';
		}
		$id = wp_insert_post( array( 'post_type' => self::CPT, 'post_status' => 'publish', 'post_title' => $config['name'] ), true );
		if ( is_wp_error( $id ) ) {
			return $id;
		}
		update_post_meta( $id, self::META, wp_slash( wp_json_encode( $config ) ) );
		return $id;
	}

	public static function save( $id, $raw ) {
		$slider = self::get( $id );
		if ( ! $slider ) {
			return new WP_Error( 'ks_missing', __( 'Slider not found.', 'kashida-slider' ) );
		}
		$raw         = is_array( $raw ) ? $raw : array();
		$raw['mode'] = $slider['config']['mode'];
		$config      = KS_Schema::sanitize( $raw );
		$res         = wp_update_post( array( 'ID' => $slider['id'], 'post_title' => $config['name'] ), true );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		update_post_meta( $slider['id'], self::META, wp_slash( wp_json_encode( $config ) ) );
		return $config;
	}

	/** Creates an independent copy (new ID, new JSON string; nothing is shared with the original). */
	public static function duplicate( $id ) {
		$slider = self::get( $id );
		if ( ! $slider ) {
			return new WP_Error( 'ks_missing', __( 'Slider not found.', 'kashida-slider' ) );
		}
		$config         = $slider['config'];
		/* translators: %s: slider name */
		$config['name'] = mb_substr( sprintf( __( '%s (copy)', 'kashida-slider' ), $slider['name'] ), 0, 100 );
		$new            = wp_insert_post( array( 'post_type' => self::CPT, 'post_status' => 'publish', 'post_title' => $config['name'] ), true );
		if ( is_wp_error( $new ) ) {
			return $new;
		}
		update_post_meta( $new, self::META, wp_slash( wp_json_encode( $config ) ) );
		return $new;
	}

	public static function delete( $id ) {
		$id = absint( $id );
		return $id && self::get( $id ) && wp_delete_post( $id, true );
	}
}
