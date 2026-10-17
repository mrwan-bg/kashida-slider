<?php
/**
 * Dynamic content: posts, WooCommerce products and portfolio-type custom post types.
 *
 * @package KashidaSlider
 */

defined( 'ABSPATH' ) || exit;

final class KS_Source {

	public static function hooks() {
		add_action( 'save_post', array( __CLASS__, 'on_post' ), 10, 2 );
		add_action( 'transition_post_status', array( __CLASS__, 'on_transition' ), 10, 3 );
		add_action( 'deleted_post', array( __CLASS__, 'bump' ) );
		add_action( 'set_object_terms', array( __CLASS__, 'bump' ) );
		add_action( 'edited_term', array( __CLASS__, 'bump' ) );
		add_action( 'delete_term', array( __CLASS__, 'bump' ) );
	}

	public static function on_post( $post_id, $post ) {
		if ( ! wp_is_post_revision( $post_id ) && ! wp_is_post_autosave( $post_id ) && KS_Storage::CPT !== $post->post_type ) {
			self::bump();
		}
	}

	public static function on_transition( $new, $old, $post ) {
		if ( $new !== $old && ( 'publish' === $new || 'publish' === $old ) && KS_Storage::CPT !== $post->post_type ) {
			self::bump();
		}
	}

	/** Invalidate every cached dynamic query by changing the version token. */
	public static function bump() {
		update_option( 'ks_content_ver', (string) microtime( true ), false );
	}

	public static function woo_active() {
		return class_exists( 'WooCommerce' ) && function_exists( 'wc_get_product' );
	}

	public static function excluded_types() {
		return apply_filters( 'ks_excluded_post_types', array( 'post', 'page', 'attachment', 'product', KS_Storage::CPT, 'wp_block', 'wp_template', 'wp_template_part', 'wp_navigation' ) );
	}

	/** Public custom post types that could act as a portfolio. */
	public static function portfolio_types() {
		$out = array();
		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $slug => $obj ) {
			if ( ! in_array( $slug, self::excluded_types(), true ) ) {
				$out[ $slug ] = $obj;
			}
		}
		return $out;
	}

	public static function taxonomies_for( $post_type, $limit = 200 ) {
		$out = array();
		foreach ( get_object_taxonomies( $post_type, 'objects' ) as $tax => $obj ) {
			if ( ! $obj->public && ! $obj->show_ui ) {
				continue;
			}
			$terms = get_terms( array( 'taxonomy' => $tax, 'hide_empty' => false, 'number' => $limit, 'orderby' => 'name', 'fields' => 'id=>name' ) );
			if ( is_wp_error( $terms ) || ! $terms ) {
				continue;
			}
			$list = array();
			foreach ( $terms as $id => $name ) {
				$list[] = array( (int) $id, wp_specialchars_decode( $name ) );
			}
			$out[ $tax ] = array( 'label' => $obj->labels->name, 'terms' => $list );
		}
		return $out;
	}

	/** Data the editor needs to build the Source tab. */
	public static function editor_data() {
		$data = array(
			'post'      => array( 'available' => true, 'taxes' => self::taxonomies_for( 'post' ) ),
			'product'   => array( 'available' => self::woo_active(), 'taxes' => self::woo_active() ? self::taxonomies_for( 'product' ) : array() ),
			'portfolio' => array( 'available' => false, 'types' => array(), 'suggested' => '' ),
		);
		foreach ( self::portfolio_types() as $slug => $obj ) {
			$data['portfolio']['types'][ $slug ] = array( 'label' => $obj->labels->name, 'taxes' => self::taxonomies_for( $slug ) );
			if ( ! $data['portfolio']['suggested'] && preg_match( '/portfolio|project|work|case/i', $slug . ' ' . $obj->label ) ) {
				$data['portfolio']['suggested'] = $slug;
			}
		}
		$data['portfolio']['available'] = ! empty( $data['portfolio']['types'] );
		if ( ! $data['portfolio']['suggested'] && $data['portfolio']['types'] ) {
			$data['portfolio']['suggested'] = (string) key( $data['portfolio']['types'] );
		}
		return $data;
	}

	private static function post_type_for( $source ) {
		switch ( $source['type'] ) {
			case 'product':
				return self::woo_active() ? 'product' : '';
			case 'portfolio':
				$pt = $source['post_type'];
				return $pt && isset( self::portfolio_types()[ $pt ] ) ? $pt : '';
			default:
				return 'post';
		}
	}

	/** @return array[] Normalised items (cached as data, never as HTML). */
	public static function items( $source, $slider_id = 0, $use_cache = true ) {
		$pt = self::post_type_for( $source );
		if ( '' === $pt ) {
			return array();
		}
		$ttl = (int) $source['cache_min'] * MINUTE_IN_SECONDS;
		$key = 'ks_' . md5( wp_json_encode( array( $slider_id, $source, get_option( 'ks_content_ver', '0' ), get_locale() ) ) );
		if ( $use_cache && $ttl > 0 ) {
			$hit = get_transient( $key );
			if ( is_array( $hit ) ) {
				return $hit;
			}
		}
		$items = self::fetch( $pt, $source );
		if ( $use_cache && $ttl > 0 ) {
			set_transient( $key, $items, $ttl );
		}
		return $items;
	}

	private static function fetch( $pt, $source ) {
		$args = array(
			'post_type'              => $pt,
			'post_status'            => 'publish',
			'posts_per_page'         => (int) $source['count'],
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => true,
			'has_password'           => false,
			'order'                  => $source['order'],
			'update_post_term_cache' => false,
		);
		if ( 'price' === $source['orderby'] && 'product' === $pt ) {
			$args['meta_key'] = '_price';
			$args['orderby']  = 'meta_value_num';
		} elseif ( 'menu_order' === $source['orderby'] ) {
			$args['orderby'] = 'menu_order date';
		} else {
			$args['orderby'] = 'title' === $source['orderby'] ? 'title' : 'date';
		}

		$tax_query = array( 'relation' => 'AND' );
		foreach ( $source['terms'] as $tax => $ids ) {
			if ( $ids && is_object_in_taxonomy( $pt, $tax ) ) {
				$tax_query[] = array( 'taxonomy' => $tax, 'field' => 'term_id', 'terms' => $ids, 'operator' => 'IN' );
			}
		}
		if ( 'product' === $pt ) {
			$tax_query[] = array( 'taxonomy' => 'product_visibility', 'field' => 'name', 'terms' => array( 'exclude-from-catalog' ), 'operator' => 'NOT IN' );
		}
		if ( count( $tax_query ) > 1 ) {
			$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		$query = new WP_Query( $args );
		$items = array();
		foreach ( $query->posts as $post ) {
			$item = array(
				'id'      => $post->ID,
				'title'   => wp_strip_all_tags( get_the_title( $post ) ),
				'url'     => get_permalink( $post ),
				'image'   => (string) get_the_post_thumbnail_url( $post, 'large' ),
				'excerpt' => '',
				'date'    => get_the_date( '', $post ),
				'iso'     => get_the_date( 'c', $post ),
				'price'   => '',
			);
			$text            = $post->post_excerpt ? $post->post_excerpt : $post->post_content;
			$item['excerpt'] = wp_trim_words( wp_strip_all_tags( strip_shortcodes( $text ) ), (int) $source['excerpt_words'], '…' );
			if ( 'product' === $pt ) {
				$product = wc_get_product( $post->ID );
				if ( ! $product ) {
					continue;
				}
				$item['price'] = $product->get_price_html();
			}
			$items[] = $item;
		}
		return $items;
	}
}
