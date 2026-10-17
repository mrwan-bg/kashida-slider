<?php
/**
 * Field schema: single source of truth for defaults, sanitization and the editor UI.
 *
 * @package KashidaSlider
 */

defined( 'ABSPATH' ) || exit;

final class KS_Schema {

	const MAX_SLIDES = 100;

	private static $slide = null;
	private static $glob  = null;

	public static function tabs( $mode ) {
		$tabs = array();
		if ( in_array( $mode, array( 'dynamic', 'carousel' ), true ) ) {
			$tabs['source'] = __( 'Source', 'kashida-slider' );
		}
		if ( 'dynamic' !== $mode ) {
			$tabs['content'] = __( 'Content', 'kashida-slider' );
		}
		$tabs['background'] = __( 'Background', 'kashida-slider' );
		$tabs['typography'] = __( 'Typography', 'kashida-slider' );
		$tabs['buttons']    = __( 'Buttons', 'kashida-slider' );
		$tabs['layout']     = __( 'Layout', 'kashida-slider' );
		$tabs['animation']  = __( 'Animation', 'kashida-slider' );
		$tabs['responsive'] = __( 'Responsive', 'kashida-slider' );
		return $tabs;
	}

	private static function f( $tab, $label, $type, $default, $extra = array() ) {
		return array_merge( array( 'tab' => $tab, 'label' => $label, 'type' => $type, 'default' => $default ), $extra );
	}

	/** Per-slide (or dynamic design) fields. */
	public static function slide_fields() {
		if ( null !== self::$slide ) {
			return self::$slide;
		}
		$c      = array( 'scope' => 'custom' );
		$align  = array( 'left' => __( 'Start', 'kashida-slider' ), 'center' => __( 'Center', 'kashida-slider' ), 'right' => __( 'End', 'kashida-slider' ) );
		$ralign = array( 'inherit' => __( 'Same as larger screen', 'kashida-slider' ) ) + $align;
		$weight = array( '400' => __( 'Regular', 'kashida-slider' ), '500' => __( 'Medium', 'kashida-slider' ), '600' => __( 'Semi-bold', 'kashida-slider' ), '700' => __( 'Bold', 'kashida-slider' ), '800' => __( 'Extra bold', 'kashida-slider' ) );
		$gr     = array( 'show' => array( 'bg_type', 'gradient' ) );

		self::$slide = array(
			'heading'   => self::f( 'content', __( 'Heading', 'kashida-slider' ), 'text', __( 'Your headline here', 'kashida-slider' ), $c ),
			'desc'      => self::f( 'content', __( 'Description', 'kashida-slider' ), 'textarea', __( 'A short description for this slide.', 'kashida-slider' ), $c ),
			'btn1_label' => self::f( 'content', __( 'Primary button label', 'kashida-slider' ), 'text', __( 'Learn more', 'kashida-slider' ), $c ),
			'btn1_url'  => self::f( 'content', __( 'Primary button URL', 'kashida-slider' ), 'url', '', $c ),
			'btn1_new'  => self::f( 'content', __( 'Open primary button in a new tab', 'kashida-slider' ), 'toggle', 0, $c ),
			'btn2_label' => self::f( 'content', __( 'Secondary button label (optional)', 'kashida-slider' ), 'text', '', $c ),
			'btn2_url'  => self::f( 'content', __( 'Secondary button URL', 'kashida-slider' ), 'url', '', $c ),
			'btn2_new'  => self::f( 'content', __( 'Open secondary button in a new tab', 'kashida-slider' ), 'toggle', 0, $c ),

			'bg_image'  => self::f( 'background', __( 'Background image', 'kashida-slider' ), 'image', '' ),
			'bg_size'   => self::f( 'background', __( 'Image sizing', 'kashida-slider' ), 'select', 'cover', array( 'choices' => array( 'cover' => __( 'Cover', 'kashida-slider' ), 'contain' => __( 'Contain', 'kashida-slider' ), 'auto' => __( 'Original size', 'kashida-slider' ) ) ) ),
			'pos_x'     => self::f( 'background', __( 'Focal point X (%)', 'kashida-slider' ), 'range', 50, array( 'min' => 0, 'max' => 100 ) ),
			'pos_y'     => self::f( 'background', __( 'Focal point Y (%)', 'kashida-slider' ), 'range', 50, array( 'min' => 0, 'max' => 100 ) ),
			'bg_type'   => self::f( 'background', __( 'Background fill', 'kashida-slider' ), 'select', 'color', array( 'choices' => array( 'color' => __( 'Solid color', 'kashida-slider' ), 'gradient' => __( 'Gradient', 'kashida-slider' ) ) ) ),
			'bg_color'  => self::f( 'background', __( 'Color', 'kashida-slider' ), 'color', '#1d2327' ),
			'bg_color2' => self::f( 'background', __( 'Gradient end color', 'kashida-slider' ), 'color', '#F05C41', $gr ),
			'bg_angle'  => self::f( 'background', __( 'Gradient angle', 'kashida-slider' ), 'range', 135, array( 'min' => 0, 'max' => 360 ) + $gr ),
			'overlay_mode'    => self::f( 'background', __( 'Text overlay', 'kashida-slider' ), 'select', 'content', array( 'choices' => array( 'content' => __( 'Behind text area only', 'kashida-slider' ), 'full' => __( 'Full background', 'kashida-slider' ), 'none' => __( 'None', 'kashida-slider' ) ) ) ),
			'overlay_color'   => self::f( 'background', __( 'Overlay color', 'kashida-slider' ), 'color', '#000000' ),
			'overlay_opacity' => self::f( 'background', __( 'Overlay opacity (%)', 'kashida-slider' ), 'range', 45, array( 'min' => 0, 'max' => 100 ) ),

			'h_color'   => self::f( 'typography', __( 'Heading color', 'kashida-slider' ), 'color', '#ffffff' ),
			'h_weight'  => self::f( 'typography', __( 'Heading weight', 'kashida-slider' ), 'select', '700', array( 'choices' => $weight ) ),
			'h_size_d'  => self::f( 'typography', __( 'Heading size (px)', 'kashida-slider' ), 'number', 48, array( 'min' => 12, 'max' => 140 ) ),
			'd_color'   => self::f( 'typography', __( 'Description color', 'kashida-slider' ), 'color', '#ffffff' ),
			'd_weight'  => self::f( 'typography', __( 'Description weight', 'kashida-slider' ), 'select', '400', array( 'choices' => $weight ) ),
			'd_size_d'  => self::f( 'typography', __( 'Description size (px)', 'kashida-slider' ), 'number', 18, array( 'min' => 10, 'max' => 60 ) ),

			'b1_bg'     => self::f( 'buttons', __( 'Primary background', 'kashida-slider' ), 'color', '#F05C41' ),
			'b1_color'  => self::f( 'buttons', __( 'Primary text color', 'kashida-slider' ), 'color', '#ffffff' ),
			'b2_style'  => self::f( 'buttons', __( 'Secondary style', 'kashida-slider' ), 'select', 'outline', array( 'choices' => array( 'outline' => __( 'Outline', 'kashida-slider' ), 'solid' => __( 'Solid', 'kashida-slider' ) ) ) ),
			'b2_bg'     => self::f( 'buttons', __( 'Secondary border / background', 'kashida-slider' ), 'color', '#ffffff' ),
			'b2_color'  => self::f( 'buttons', __( 'Secondary text color', 'kashida-slider' ), 'color', '#ffffff' ),
			'b_radius'  => self::f( 'buttons', __( 'Border radius (px)', 'kashida-slider' ), 'range', 4, array( 'min' => 0, 'max' => 50 ) ),
			'b_size_d'  => self::f( 'buttons', __( 'Button text size (px)', 'kashida-slider' ), 'number', 16, array( 'min' => 10, 'max' => 40 ) ),

			'layout'    => self::f( 'layout', __( 'Layout preset', 'kashida-slider' ), 'select', 'hero', array( 'choices' => array( 'hero' => __( 'Hero Overlay', 'kashida-slider' ), 'split' => __( 'Split Layout', 'kashida-slider' ), 'minimal' => __( 'Minimal', 'kashida-slider' ) ) ) ),
			'split_side' => self::f( 'layout', __( 'Image side (Split)', 'kashida-slider' ), 'select', 'end', array( 'choices' => array( 'start' => __( 'Start', 'kashida-slider' ), 'end' => __( 'End', 'kashida-slider' ) ), 'show' => array( 'layout', 'split' ) ) ),
			'align'     => self::f( 'layout', __( 'Text alignment', 'kashida-slider' ), 'select', 'left', array( 'choices' => $align ) ),
			'valign'    => self::f( 'layout', __( 'Vertical position', 'kashida-slider' ), 'select', 'center', array( 'choices' => array( 'top' => __( 'Top', 'kashida-slider' ), 'center' => __( 'Middle', 'kashida-slider' ), 'bottom' => __( 'Bottom', 'kashida-slider' ) ) ) ),
			'max_width' => self::f( 'layout', __( 'Maximum text width (px)', 'kashida-slider' ), 'number', 560, array( 'min' => 200, 'max' => 1400 ) ),
			'spacing'   => self::f( 'layout', __( 'Content spacing (px)', 'kashida-slider' ), 'number', 48, array( 'min' => 0, 'max' => 200 ) ),

			'align_t'   => self::f( 'responsive', __( 'Tablet: alignment', 'kashida-slider' ), 'select', 'inherit', array( 'choices' => $ralign ) ),
			'align_m'   => self::f( 'responsive', __( 'Mobile: alignment', 'kashida-slider' ), 'select', 'inherit', array( 'choices' => $ralign ) ),
			'h_size_t'  => self::f( 'responsive', __( 'Tablet: heading size (px)', 'kashida-slider' ), 'number', 36, array( 'min' => 12, 'max' => 140 ) ),
			'h_size_m'  => self::f( 'responsive', __( 'Mobile: heading size (px)', 'kashida-slider' ), 'number', 26, array( 'min' => 12, 'max' => 140 ) ),
			'd_size_t'  => self::f( 'responsive', __( 'Tablet: description size (px)', 'kashida-slider' ), 'number', 16, array( 'min' => 10, 'max' => 60 ) ),
			'd_size_m'  => self::f( 'responsive', __( 'Mobile: description size (px)', 'kashida-slider' ), 'number', 14, array( 'min' => 10, 'max' => 60 ) ),
			'b_size_t'  => self::f( 'responsive', __( 'Tablet: button text size (px)', 'kashida-slider' ), 'number', 15, array( 'min' => 10, 'max' => 40 ) ),
			'b_size_m'  => self::f( 'responsive', __( 'Mobile: button text size (px)', 'kashida-slider' ), 'number', 14, array( 'min' => 10, 'max' => 40 ) ),
			'spacing_t' => self::f( 'responsive', __( 'Tablet: content spacing (px)', 'kashida-slider' ), 'number', 32, array( 'min' => 0, 'max' => 200 ) ),
			'spacing_m' => self::f( 'responsive', __( 'Mobile: content spacing (px)', 'kashida-slider' ), 'number', 20, array( 'min' => 0, 'max' => 200 ) ),
			'bg_image_m' => self::f( 'responsive', __( 'Mobile background image (optional)', 'kashida-slider' ), 'image', '' ),
			'split_stack' => self::f( 'responsive', __( 'Split layout on small screens', 'kashida-slider' ), 'select', 'top', array( 'choices' => array( 'top' => __( 'Image on top', 'kashida-slider' ), 'bottom' => __( 'Image at bottom', 'kashida-slider' ) ) ) ),
		);
		return self::$slide;
	}

	/** Slider-wide fields. */
	public static function global_fields() {
		if ( null !== self::$glob ) {
			return self::$glob;
		}
		self::$glob = array(
			'transition' => self::f( 'animation', __( 'Transition', 'kashida-slider' ), 'select', 'fade', array( 'choices' => array(
				'fade'      => __( 'Fade', 'kashida-slider' ),
				'crossfade' => __( 'Crossfade', 'kashida-slider' ),
				'slide'     => __( 'Slide', 'kashida-slider' ),
				'zoom_in'   => __( 'Zoom In', 'kashida-slider' ),
				'zoom_out'  => __( 'Zoom Out', 'kashida-slider' ),
				'push'      => __( 'Push', 'kashida-slider' ),
				'wipe'      => __( 'Wipe / Reveal', 'kashida-slider' ),
			) ) ),
			'direction'  => self::f( 'animation', __( 'Direction', 'kashida-slider' ), 'select', 'left', array(
				'choices' => array( 'left' => __( 'Left', 'kashida-slider' ), 'right' => __( 'Right', 'kashida-slider' ), 'up' => __( 'Up', 'kashida-slider' ), 'down' => __( 'Down', 'kashida-slider' ) ),
				'show'    => array( 'transition', array( 'slide', 'push', 'wipe' ) ),
			) ),
			'duration'   => self::f( 'animation', __( 'Transition duration (ms)', 'kashida-slider' ), 'number', 600, array( 'min' => 0, 'max' => 3000, 'step' => 50 ) ),
			'autoplay'   => self::f( 'animation', __( 'Autoplay', 'kashida-slider' ), 'toggle', 1 ),
			'interval'   => self::f( 'animation', __( 'Autoplay interval (ms)', 'kashida-slider' ), 'number', 5000, array( 'min' => 1500, 'max' => 30000, 'step' => 100 ) ),
			'pause_hover' => self::f( 'animation', __( 'Pause on hover', 'kashida-slider' ), 'toggle', 1 ),
			'loop'       => self::f( 'animation', __( 'Infinite loop', 'kashida-slider' ), 'toggle', 1 ),
			'arrows'     => self::f( 'animation', __( 'Show navigation arrows', 'kashida-slider' ), 'toggle', 1 ),
			'dots'       => self::f( 'animation', __( 'Show pagination dots', 'kashida-slider' ), 'toggle', 1 ),
			'carousel_layout' => self::f( 'layout', __( 'Carousel layout', 'kashida-slider' ), 'select', 'standard', array( 'choices' => array( 'standard' => __( 'Standard cards', 'kashida-slider' ), 'centered' => __( 'Centered active card', 'kashida-slider' ), 'image' => __( 'Image carousel', 'kashida-slider' ), 'product' => __( 'Product cards', 'kashida-slider' ), 'compact' => __( 'Compact cards', 'kashida-slider' ) ) ) ),
			'slider_radius' => self::f( 'layout', __( 'Slider container corner radius (px)', 'kashida-slider' ), 'number', 0, array( 'min' => 0, 'max' => 40 ) ),
			'carousel_visible_d' => self::f( 'layout', __( 'Carousel columns: desktop', 'kashida-slider' ), 'number', 4, array( 'min' => 1, 'max' => 8 ) ),
			'carousel_visible_t' => self::f( 'layout', __( 'Carousel columns: tablet', 'kashida-slider' ), 'number', 2, array( 'min' => 1, 'max' => 6 ) ),
			'carousel_visible_m' => self::f( 'layout', __( 'Carousel columns: mobile', 'kashida-slider' ), 'number', 1, array( 'min' => 1, 'max' => 3 ) ),
			'carousel_gap' => self::f( 'layout', __( 'Gap between items (px)', 'kashida-slider' ), 'number', 18, array( 'min' => 0, 'max' => 60 ) ),
			'carousel_card_radius' => self::f( 'layout', __( 'Carousel card corner radius (px)', 'kashida-slider' ), 'number', 10, array( 'min' => 0, 'max' => 80 ) ),
			'carousel_image_shape' => self::f( 'layout', __( 'Carousel image shape', 'kashida-slider' ), 'select', 'rectangle', array( 'choices' => array( 'rectangle' => __( 'Rectangle', 'kashida-slider' ), 'circle' => __( 'Circle', 'kashida-slider' ) ) ) ),
			'carousel_move' => self::f( 'layout', __( 'Items per navigation', 'kashida-slider' ), 'number', 1, array( 'min' => 1, 'max' => 8 ) ),
			'h_d'        => self::f( 'responsive', __( 'Slider height: desktop (px)', 'kashida-slider' ), 'number', 560, array( 'min' => 160, 'max' => 1400 ) ),
			'h_t'        => self::f( 'responsive', __( 'Slider height: tablet (px)', 'kashida-slider' ), 'number', 480, array( 'min' => 160, 'max' => 1400 ) ),
			'h_m'        => self::f( 'responsive', __( 'Slider height: mobile (px)', 'kashida-slider' ), 'number', 440, array( 'min' => 160, 'max' => 1400 ) ),
		);
		return self::$glob;
	}

	public static function source_defaults() {
		return array(
			'type'          => 'post',
			'post_type'     => '',
			'count'         => 5,
			'terms'         => array(),
			'orderby'       => 'date',
			'order'         => 'DESC',
			'show_image'    => 1,
			'show_title'    => 1,
			'show_excerpt'  => 1,
			'show_date'     => 0,
			'show_price'    => 1,
			'show_button'   => 1,
			'button_label'  => '',
			'excerpt_words' => 25,
			'fallback_image' => '',
			'cache_min'     => 10,
		);
	}

	public static function defaults( $mode = 'custom' ) {
		$slide = array();
		foreach ( self::slide_fields() as $k => $d ) {
			$slide[ $k ] = $d['default'];
		}
		$settings = array();
		foreach ( self::global_fields() as $k => $d ) {
			$settings[ $k ] = $d['default'];
		}
		return array(
			'schema'   => KS_SCHEMA_VERSION,
			'name'     => __( 'Untitled slider', 'kashida-slider' ),
			'mode'     => in_array( $mode, array( 'dynamic', 'carousel' ), true ) ? $mode : 'custom',
			'settings' => $settings,
			'slides'   => array( $slide ),
			'design'   => $slide,
			'source'   => array_merge( self::source_defaults(), array( 'type' => 'carousel' === $mode ? 'custom' : 'post' ) ),
		);
	}

	public static function value( $v, $def ) {
		switch ( $def['type'] ) {
			case 'toggle':
				return empty( $v ) || '0' === $v ? 0 : 1;
			case 'number':
			case 'range':
				if ( ! is_numeric( $v ) ) {
					return $def['default'];
				}
				return max( $def['min'], min( $def['max'], (int) $v ) );
			case 'select':
				$v = is_scalar( $v ) ? (string) $v : '';
				return isset( $def['choices'][ $v ] ) ? $v : $def['default'];
			case 'color':
				$c = is_string( $v ) ? sanitize_hex_color( $v ) : '';
				if ( ! $c ) {
					return $def['default'];
				}
				return 4 === strlen( $c ) ? '#' . $c[1] . $c[1] . $c[2] . $c[2] . $c[3] . $c[3] : strtolower( $c );
			case 'url':
			case 'image':
				return is_string( $v ) ? esc_url_raw( trim( $v ), array( 'http', 'https', 'mailto', 'tel' ) ) : '';
			case 'textarea':
				return is_string( $v ) ? sanitize_textarea_field( $v ) : '';
			default:
				return is_string( $v ) ? sanitize_text_field( $v ) : '';
		}
	}

	private static function obj( $raw, $fields ) {
		$raw = is_array( $raw ) ? $raw : array();
		$out = array();
		foreach ( $fields as $k => $def ) {
			$out[ $k ] = array_key_exists( $k, $raw ) ? self::value( $raw[ $k ], $def ) : $def['default'];
		}
		return $out;
	}

	public static function sanitize_source( $raw ) {
		$d   = self::source_defaults();
		$raw = is_array( $raw ) ? $raw : array();
		$o   = $d;

		$type         = isset( $raw['type'] ) ? $raw['type'] : '';
		$o['type']    = in_array( $type, array( 'custom', 'post', 'product', 'portfolio' ), true ) ? $type : 'post';
		$o['post_type'] = 'portfolio' === $o['type'] && isset( $raw['post_type'] ) ? sanitize_key( $raw['post_type'] ) : '';
		$o['count']   = isset( $raw['count'] ) ? max( 1, min( 20, (int) $raw['count'] ) ) : $d['count'];
		$o['order']   = isset( $raw['order'] ) && 'ASC' === strtoupper( (string) $raw['order'] ) ? 'ASC' : 'DESC';
		$ob           = isset( $raw['orderby'] ) ? $raw['orderby'] : 'date';
		$allowed      = array( 'date', 'title', 'menu_order' );
		if ( 'product' === $o['type'] ) {
			$allowed[] = 'price';
		}
		$o['orderby'] = in_array( $ob, $allowed, true ) ? $ob : 'date';

		if ( ! empty( $raw['terms'] ) && is_array( $raw['terms'] ) ) {
			foreach ( $raw['terms'] as $tax => $ids ) {
				$tax = sanitize_key( $tax );
				if ( $tax && taxonomy_exists( $tax ) && is_array( $ids ) ) {
					$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
					if ( $ids ) {
						$o['terms'][ $tax ] = $ids;
					}
				}
			}
		}
		foreach ( array( 'show_image', 'show_title', 'show_excerpt', 'show_date', 'show_price', 'show_button' ) as $k ) {
			$o[ $k ] = isset( $raw[ $k ] ) ? ( empty( $raw[ $k ] ) ? 0 : 1 ) : $d[ $k ];
		}
		$o['button_label']   = isset( $raw['button_label'] ) ? sanitize_text_field( $raw['button_label'] ) : '';
		$o['excerpt_words']  = isset( $raw['excerpt_words'] ) ? max( 5, min( 80, (int) $raw['excerpt_words'] ) ) : $d['excerpt_words'];
		$o['fallback_image'] = isset( $raw['fallback_image'] ) ? esc_url_raw( (string) $raw['fallback_image'] ) : '';
		$o['cache_min']      = isset( $raw['cache_min'] ) ? max( 0, min( 1440, (int) $raw['cache_min'] ) ) : $d['cache_min'];
		return $o;
	}

	/** Validate and normalise a complete slider configuration. */
	public static function sanitize( $raw ) {
		$raw  = is_array( $raw ) ? $raw : array();
		$mode = isset( $raw['mode'] ) && in_array( $raw['mode'], array( 'dynamic', 'carousel' ), true ) ? $raw['mode'] : 'custom';
		$out  = self::defaults( $mode );

		$name        = isset( $raw['name'] ) ? trim( sanitize_text_field( $raw['name'] ) ) : '';
		$out['name'] = '' !== $name ? mb_substr( $name, 0, 100 ) : $out['name'];
		$out['settings'] = self::obj( isset( $raw['settings'] ) ? $raw['settings'] : array(), self::global_fields() );
		$out['design']   = self::obj( isset( $raw['design'] ) ? $raw['design'] : array(), self::slide_fields() );
		$out['source']   = self::sanitize_source( isset( $raw['source'] ) ? $raw['source'] : array() );

		$slides = array();
		if ( ! empty( $raw['slides'] ) && is_array( $raw['slides'] ) ) {
			foreach ( array_slice( array_values( $raw['slides'] ), 0, self::MAX_SLIDES ) as $s ) {
				$slides[] = self::obj( $s, self::slide_fields() );
			}
		}
		$out['slides'] = $slides ? $slides : ( ( 'custom' === $mode || ( 'carousel' === $mode && 'custom' === $out['source']['type'] ) ) ? array( self::defaults()['design'] ) : array() );
		return $out;
	}
}
