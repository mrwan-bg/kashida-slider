<?php
/**
 * Frontend: shortcode, assets, HTML rendering and standalone preview documents.
 *
 * @package KashidaSlider
 */

defined( 'ABSPATH' ) || exit;

final class KS_Renderer {

	private static $count = 0;

	public static function hooks() {
		add_shortcode( 'kashida_slider', array( __CLASS__, 'shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'maybe_enqueue' ), 20 );
		add_action( 'template_redirect', array( __CLASS__, 'preview_page' ) );
	}

	public static function register_assets() {
		wp_register_style( 'kashida-slider', KS_URL . 'assets/css/frontend.css', array(), KS_VERSION );
		wp_register_script( 'kashida-slider', KS_URL . 'assets/js/frontend.js', array(), KS_VERSION, true );
	}

	/** Enqueue early when the shortcode is present in the main content (avoids a style flash). */
	public static function maybe_enqueue() {
		$post = is_singular() ? get_post() : null;
		if ( $post && has_shortcode( (string) $post->post_content, 'kashida_slider' ) ) {
			self::enqueue();
		}
	}

	public static function enqueue() {
		wp_enqueue_style( 'kashida-slider' );
		wp_enqueue_script( 'kashida-slider' );
	}

	public static function shortcode( $atts ) {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'kashida_slider' );
		$id   = absint( $atts['id'] );
		$sl   = $id ? KS_Storage::get( $id ) : null;
		if ( ! $sl ) {
			return current_user_can( ks_capability() )
				? '<p class="ks-notice">' . esc_html__( 'Kashida Slider: this slider does not exist or was deleted.', 'kashida-slider' ) . '</p>'
				: '';
		}
		self::enqueue();
		return self::render( $sl['id'], $sl['config'] );
	}

	/** Build the slide list: custom slides, or the design template combined with live content. */
	private static function build_slides( $id, $cfg, $preview ) {
		if ( 'custom' === $cfg['mode'] || ( 'carousel' === $cfg['mode'] && 'custom' === $cfg['source']['type'] ) ) {
			return $cfg['slides'];
		}
		$src    = $cfg['source'];
		$design = $cfg['design'];
		$label  = '' !== $src['button_label'] ? $src['button_label'] : ( 'product' === $src['type'] ? __( 'View product', 'kashida-slider' ) : ( 'post' === $src['type'] ? __( 'Read more', 'kashida-slider' ) : __( 'View project', 'kashida-slider' ) ) );
		$out    = array();
		foreach ( KS_Source::items( $src, $id, ! $preview ) as $it ) {
			$s               = $design;
			$image           = $it['image'] ? $it['image'] : ( $src['fallback_image'] ? $src['fallback_image'] : $design['bg_image'] );
			$s['heading']    = $src['show_title'] ? $it['title'] : '';
			$s['desc']       = $src['show_excerpt'] ? $it['excerpt'] : '';
			$s['bg_image']   = $src['show_image'] ? $image : '';
			$s['bg_image_m'] = '';
			$s['btn1_label'] = $src['show_button'] ? $label : '';
			$s['btn1_url']   = $it['url'];
			$s['btn1_new']   = 0;
			$s['btn2_label'] = '';
			$s['btn2_url']   = '';
			$s['date']       = $src['show_date'] ? $it['date'] : '';
			$s['iso']        = $it['iso'];
			$s['price']      = $src['show_price'] ? $it['price'] : '';
			$s['link']       = $it['url'];
			$out[]           = $s;
		}
		return $out;
	}

	private static function rgba( $hex, $opacity ) {
		$hex = ltrim( $hex, '#' );
		return sprintf( 'rgba(%d,%d,%d,%s)', hexdec( substr( $hex, 0, 2 ) ), hexdec( substr( $hex, 2, 2 ) ), hexdec( substr( $hex, 4, 2 ) ), round( $opacity / 100, 2 ) );
	}

	private static function jc( $a ) {
		return array( 'left' => 'flex-start', 'center' => 'center', 'right' => 'flex-end' )[ $a ];
	}

	private static function ta( $a ) {
		return array( 'left' => 'start', 'center' => 'center', 'right' => 'end' )[ $a ];
	}

	private static function slide_vars( $s ) {
		$bg = 'gradient' === $s['bg_type'] ? sprintf( 'linear-gradient(%ddeg,%s,%s)', $s['bg_angle'], $s['bg_color'], $s['bg_color2'] ) : $s['bg_color'];
		$v  = array(
			'--ks-sbg'  => $bg,
			'--ks-size' => $s['bg_size'],
			'--ks-px'   => $s['pos_x'] . '%',
			'--ks-py'   => $s['pos_y'] . '%',
			'--ks-ov'   => self::rgba( $s['overlay_color'], $s['overlay_opacity'] ),
			'--ks-hc'   => $s['h_color'],
			'--ks-hw'   => $s['h_weight'],
			'--ks-dc'   => $s['d_color'],
			'--ks-dw'   => $s['d_weight'],
			'--ks-b1bg' => $s['b1_bg'],
			'--ks-b1c'  => $s['b1_color'],
			'--ks-b2bg' => 'solid' === $s['b2_style'] ? $s['b2_bg'] : 'transparent',
			'--ks-b2bd' => $s['b2_bg'],
			'--ks-b2c'  => $s['b2_color'],
			'--ks-br'   => $s['b_radius'] . 'px',
			'--ks-va'   => array( 'top' => 'flex-start', 'center' => 'center', 'bottom' => 'flex-end' )[ $s['valign'] ],
			'--ks-mw'   => $s['max_width'] . 'px',
			'--ks-hs'   => $s['h_size_d'] . 'px',
			'--ks-hs-t' => $s['h_size_t'] . 'px',
			'--ks-hs-m' => $s['h_size_m'] . 'px',
			'--ks-ds'   => $s['d_size_d'] . 'px',
			'--ks-ds-t' => $s['d_size_t'] . 'px',
			'--ks-ds-m' => $s['d_size_m'] . 'px',
			'--ks-bs'   => $s['b_size_d'] . 'px',
			'--ks-bs-t' => $s['b_size_t'] . 'px',
			'--ks-bs-m' => $s['b_size_m'] . 'px',
			'--ks-sp'   => $s['spacing'] . 'px',
			'--ks-sp-t' => $s['spacing_t'] . 'px',
			'--ks-sp-m' => $s['spacing_m'] . 'px',
			'--ks-j'    => self::jc( $s['align'] ),
			'--ks-ta'   => self::ta( $s['align'] ),
		);
		if ( 'inherit' !== $s['align_t'] ) {
			$v['--ks-j-t']  = self::jc( $s['align_t'] );
			$v['--ks-ta-t'] = self::ta( $s['align_t'] );
		}
		if ( 'inherit' !== $s['align_m'] ) {
			$v['--ks-j-m']  = self::jc( $s['align_m'] );
			$v['--ks-ta-m'] = self::ta( $s['align_m'] );
		}
		if ( $s['bg_image'] ) {
			$v['--ks-bg'] = 'url("' . esc_url( $s['bg_image'] ) . '")';
		}
		if ( $s['bg_image_m'] ) {
			$v['--ks-bg-m'] = 'url("' . esc_url( $s['bg_image_m'] ) . '")';
		}
		$css = '';
		foreach ( $v as $k => $val ) {
			$css .= $k . ':' . $val . ';';
		}
		return $css;
	}

	private static function button( $cls, $label, $url, $new ) {
		if ( '' === $label ) {
			return '';
		}
		$url  = $url ? esc_url( $url, array( 'http', 'https', 'mailto', 'tel' ) ) : '';
		$attr = $new ? ' target="_blank" rel="noopener noreferrer"' : '';
		return '<a class="ks-btn ' . esc_attr( $cls ) . '" href="' . ( $url ? $url : '#' ) . '"' . $attr . ' data-ks-el="' . ( 'ks-btn1' === $cls ? 'btn1' : 'btn2' ) . '">' . esc_html( $label ) . '</a>';
	}

	private static function slide_html( $s, $i, $n ) {
		$layout = $s['layout'];
		$cls    = 'ks-slide ks-layout-' . $layout . ' ks-ov-' . $s['overlay_mode'];
		if ( 'split' === $layout ) {
			$cls .= ' ks-img-' . $s['split_side'] . ' ks-stack-' . $s['split_stack'];
		}
		$has_img = 'minimal' !== $layout && ( $s['bg_image'] || $s['bg_image_m'] );
		$media   = '';
		if ( 'minimal' !== $layout ) {
			$media = '<div class="ks-media' . ( $has_img ? '' : ' ks-noimg' ) . '" aria-hidden="true">' . ( 'full' === $s['overlay_mode'] ? '<span class="ks-overlay"></span>' : '' ) . '</div>';
		}
		$meta = '';
		if ( ! empty( $s['date'] ) ) {
			$meta .= '<time class="ks-meta" datetime="' . esc_attr( $s['iso'] ) . '">' . esc_html( $s['date'] ) . '</time>';
		}
		$body = $meta;
		if ( '' !== $s['heading'] ) {
			$body .= '<h2 class="ks-h" data-ks-el="heading">' . esc_html( $s['heading'] ) . '</h2>';
		}
		if ( ! empty( $s['price'] ) ) {
			$body .= '<div class="ks-price">' . wp_kses_post( $s['price'] ) . '</div>';
		}
		if ( '' !== $s['desc'] ) {
			$body .= '<p class="ks-d" data-ks-el="desc">' . nl2br( esc_html( $s['desc'] ) ) . '</p>';
		}
		$btns = self::button( 'ks-btn1', $s['btn1_label'], $s['btn1_url'], $s['btn1_new'] ) . self::button( 'ks-btn2', $s['btn2_label'], $s['btn2_url'], $s['btn2_new'] );
		if ( $btns ) {
			$body .= '<div class="ks-btns">' . $btns . '</div>';
		}
		/* translators: 1: current slide number, 2: total slides */
		$label = sprintf( __( 'Slide %1$d of %2$d', 'kashida-slider' ), $i + 1, $n );
		return '<div class="' . esc_attr( $cls ) . '" role="group" aria-roledescription="' . esc_attr__( 'slide', 'kashida-slider' ) . '" aria-label="' . esc_attr( $label ) . '" dir="' . ( is_rtl() ? 'rtl' : 'ltr' ) . '" style="' . esc_attr( self::slide_vars( $s ) ) . '">'
			. $media . '<div class="ks-inner"><div class="ks-content">' . $body . '</div></div></div>';
	}

	/** Build safe CSS variables for one carousel card from the shared design schema. */
	private static function carousel_item_style( $s ) {
		$bg = 'gradient' === $s['bg_type'] ? sprintf( 'linear-gradient(%ddeg,%s,%s)', (int) $s['bg_angle'], $s['bg_color'], $s['bg_color2'] ) : $s['bg_color'];
		$vars = array(
			'--ks-card-bg'       => $bg,
			'--ks-card-size'     => $s['bg_size'],
			'--ks-card-px'       => (int) $s['pos_x'] . '%',
			'--ks-card-py'       => (int) $s['pos_y'] . '%',
			'--ks-card-overlay'  => self::rgba( $s['overlay_color'], $s['overlay_opacity'] ),
			'--ks-card-hc'       => $s['h_color'],
			'--ks-card-hw'       => (int) $s['h_weight'],
			'--ks-card-hs'       => (int) $s['h_size_d'] . 'px',
			'--ks-card-hs-t'     => (int) $s['h_size_t'] . 'px',
			'--ks-card-hs-m'     => (int) $s['h_size_m'] . 'px',
			'--ks-card-dc'       => $s['d_color'],
			'--ks-card-dw'       => (int) $s['d_weight'],
			'--ks-card-ds'       => (int) $s['d_size_d'] . 'px',
			'--ks-card-ds-t'     => (int) $s['d_size_t'] . 'px',
			'--ks-card-ds-m'     => (int) $s['d_size_m'] . 'px',
			'--ks-card-btn-bg'   => $s['b1_bg'],
			'--ks-card-btn-color'=> $s['b1_color'],
			'--ks-card-btn-radius' => (int) $s['b_radius'] . 'px',
			'--ks-card-btn-size' => (int) $s['b_size_d'] . 'px',
			'--ks-card-btn-size-t' => (int) $s['b_size_t'] . 'px',
			'--ks-card-btn-size-m' => (int) $s['b_size_m'] . 'px',
			'--ks-card-pad'      => (int) $s['spacing'] . 'px',
			'--ks-card-pad-t'    => (int) $s['spacing_t'] . 'px',
			'--ks-card-pad-m'    => (int) $s['spacing_m'] . 'px',
			'--ks-card-max'      => (int) $s['max_width'] . 'px',
			'--ks-card-align'    => 'left' === $s['align'] ? 'start' : ( 'right' === $s['align'] ? 'end' : 'center' ),
			'--ks-card-valign'   => 'top' === $s['valign'] ? 'flex-start' : ( 'bottom' === $s['valign'] ? 'flex-end' : 'center' ),
		);
		$css = '';
		foreach ( $vars as $key => $value ) {
			$css .= $key . ':' . $value . ';';
		}
		return $css;
	}

	/** Render a responsive multi-item carousel using the same saved slider and source configuration. */
	private static function render_carousel( $id, $cfg, $items, $preview = false ) {
		$n = count( $items );
		if ( ! $n ) {
			return ( $preview || current_user_can( ks_capability() ) ) ? '<p class="ks-notice">' . esc_html__( 'No items are available for this carousel.', 'kashida-slider' ) . '</p>' : '';
		}
		++self::$count;
		$st = $cfg['settings'];
		$layout = isset( $st['carousel_layout'] ) ? $st['carousel_layout'] : 'standard';
		$src = isset( $cfg['source'] ) && is_array( $cfg['source'] ) ? $cfg['source'] : array();
		$is_custom = isset( $src['type'] ) && 'custom' === $src['type'];
		$show_image = $is_custom || ! array_key_exists( 'show_image', $src ) || ! empty( $src['show_image'] );
		$show_title = $is_custom || ! array_key_exists( 'show_title', $src ) || ! empty( $src['show_title'] );
		$show_excerpt = $is_custom || ! array_key_exists( 'show_excerpt', $src ) || ! empty( $src['show_excerpt'] );
		$show_date = ! $is_custom && ! empty( $src['show_date'] );
		$show_price = $is_custom || ! array_key_exists( 'show_price', $src ) || ! empty( $src['show_price'] );
		$show_button = $is_custom || ! array_key_exists( 'show_button', $src ) || ! empty( $src['show_button'] );
		$vd = max( 1, min( 8, (int) $st['carousel_visible_d'] ) );
		$vt = max( 1, min( 6, (int) $st['carousel_visible_t'] ) );
		$vm = max( 1, min( 3, (int) $st['carousel_visible_m'] ) );
		$gap = max( 0, min( 60, (int) $st['carousel_gap'] ) );
		$dom = 'ks-carousel-' . (int) $id . '-' . self::$count;
		$card_radius = isset( $st['carousel_card_radius'] ) ? max( 0, min( 80, (int) $st['carousel_card_radius'] ) ) : 10;
		$image_shape = isset( $st['carousel_image_shape'] ) && in_array( $st['carousel_image_shape'], array( 'rectangle', 'square', 'circle' ), true ) ? $st['carousel_image_shape'] : 'rectangle';
		$container_radius = isset( $st['slider_radius'] ) ? max( 0, min( 40, (int) $st['slider_radius'] ) ) : 0;
		$html = '<section id="' . esc_attr( $dom ) . '" class="ks-carousel ks-carousel-' . esc_attr( $layout ) . ' ks-carousel-shape-' . esc_attr( $image_shape ) . '" dir="' . ( is_rtl() ? 'rtl' : 'ltr' ) . '" aria-label="' . esc_attr( $cfg['name'] ) . '" data-autoplay="' . ( ! $preview && ! empty( $st['autoplay'] ) ? '1' : '0' ) . '" data-interval="' . max( 1500, (int) $st['interval'] ) . '" data-pause-hover="' . ( ! empty( $st['pause_hover'] ) ? '1' : '0' ) . '" data-move="' . max( 1, min( 8, (int) $st['carousel_move'] ) ) . '" data-loop="' . ( ! empty( $st['loop'] ) ? '1' : '0' ) . '" style="--ks-cv-d:' . $vd . ';--ks-cv-t:' . $vt . ';--ks-cv-m:' . $vm . ';--ks-cgap:' . $gap . 'px;--ks-carousel-radius:' . $card_radius . 'px;--ks-slider-radius:' . $container_radius . 'px;--ks-image-text-gap:' . max( 0, min( 60, (int) ( $st['carousel_image_text_gap'] ?? 8 ) ) ) . 'px;--ks-slider-padding:' . max( 0, min( 80, (int) ( $st['slider_padding'] ?? 0 ) ) ) . 'px;--ks-slider-border-width:' . ( ! empty( $st['slider_border'] ) ? max( 0, min( 8, (int) ( $st['slider_border_width'] ?? 1 ) ) ) : 0 ) . 'px;--ks-slider-border-color:' . esc_attr( $st['slider_border_color'] ?? '#dddddd' ) . ';--ks-card-border-width:' . ( ! empty( $st['carousel_card_border'] ) ? max( 0, min( 8, (int) ( $st['carousel_card_border_width'] ?? 1 ) ) ) : 0 ) . 'px;--ks-card-border-color:' . esc_attr( $st['carousel_card_border_color'] ?? '#e2e4e7' ) . ';">';
		$html .= '<div class="ks-carousel-toolbar">';
		if ( $n > min( $vd, $vt, $vm ) && ! empty( $st['arrows'] ) ) {
			$html .= '<button type="button" class="ks-carousel-prev" aria-label="' . esc_attr__( 'Previous items', 'kashida-slider' ) . '"><span aria-hidden="true">' . ( is_rtl() ? '→' : '←' ) . '</span></button>';
			$html .= '<button type="button" class="ks-carousel-next" aria-label="' . esc_attr__( 'Next items', 'kashida-slider' ) . '"><span aria-hidden="true">' . ( is_rtl() ? '←' : '→' ) . '</span></button>';
		}
		$html .= '</div><div class="ks-carousel-track" tabindex="0" role="list">';
		foreach ( $items as $item ) {
			$image = ! empty( $item['bg_image'] ) ? $item['bg_image'] : '';
			$title = isset( $item['heading'] ) ? $item['heading'] : '';
			$desc = isset( $item['desc'] ) ? $item['desc'] : '';
			$url = ! empty( $item['link'] ) ? $item['link'] : ( ! empty( $item['btn1_url'] ) ? $item['btn1_url'] : '' );
			$style = self::carousel_item_style( $item );
			$html .= '<article class="ks-carousel-item" role="listitem" style="' . esc_attr( $style ) . '">';
			if ( $show_image && $image ) {
				$fit = in_array( $item['bg_size'], array( 'cover', 'contain', 'auto' ), true ) ? $item['bg_size'] : 'cover';
				$pos = (int) $item['pos_x'] . '% ' . (int) $item['pos_y'] . '%';
				$img_attrs = ' class="ks-carousel-card-image" alt="' . esc_attr( $title ) . '" loading="lazy" decoding="async" style="object-fit:' . esc_attr( $fit ) . ';object-position:' . esc_attr( $pos ) . ';"';
				$img = ! empty( $item['bg_image_m'] )
					? '<picture><source media="(max-width: 600px)" srcset="' . esc_url( $item['bg_image_m'] ) . '"><img src="' . esc_url( $image ) . '"' . $img_attrs . '></picture>'
					: '<img src="' . esc_url( $image ) . '"' . $img_attrs . '>';
				$html .= '<div class="ks-carousel-media">' . ( $url ? '<a class="ks-carousel-image-link" href="' . esc_url( $url ) . '">' . $img . '</a>' : $img );
				if ( 'full' === $item['overlay_mode'] ) { $html .= '<span class="ks-carousel-image-overlay" aria-hidden="true"></span>'; }
				$html .= '</div>';
			} elseif ( $show_image ) {
				$html .= '<div class="ks-carousel-media ks-carousel-no-image" aria-hidden="true"></div>';
			}
			$body_class = 'ks-carousel-body ks-ov-' . esc_attr( $item['overlay_mode'] );
			$html .= '<div class="' . $body_class . '">';
			if ( $show_date && ! empty( $item['date'] ) ) { $html .= '<time class="ks-carousel-date"' . ( ! empty( $item['iso'] ) ? ' datetime="' . esc_attr( $item['iso'] ) . '"' : '' ) . '>' . esc_html( $item['date'] ) . '</time>'; }
			if ( $show_title && $title ) { $html .= '<h3 class="ks-carousel-title">' . esc_html( $title ) . '</h3>'; }
			if ( $show_excerpt && $desc ) { $html .= '<p class="ks-carousel-desc">' . esc_html( $desc ) . '</p>'; }
			if ( $show_price && ! empty( $item['price'] ) ) { $html .= '<div class="ks-carousel-price">' . wp_kses_post( $item['price'] ) . '</div>'; }
			if ( $show_button && $url && ! empty( $item['btn1_label'] ) && 'image' !== $layout ) { $html .= '<a class="ks-carousel-link" href="' . esc_url( $url ) . '"' . ( ! empty( $item['btn1_new'] ) ? ' target="_blank" rel="noopener noreferrer"' : '' ) . '>' . esc_html( $item['btn1_label'] ) . '</a>'; }
			$html .= '</div></article>';
		}
		$html .= '</div>';
		if ( ! empty( $st['dots'] ) && $n > min( $vd, $vt, $vm ) ) {
			$html .= '<div class="ks-carousel-dots" aria-label="' . esc_attr__( 'Carousel pages', 'kashida-slider' ) . '">';
			for ( $dot = 0; $dot < $n; $dot++ ) {
				/* translators: %d: one-based carousel item number. */
				$html .= '<button type="button" class="ks-carousel-dot" data-index="' . (int) $dot . '" aria-label="' . esc_attr( sprintf( __( 'Go to item %d', 'kashida-slider' ), $dot + 1 ) ) . '"></button>';
			}
			$html .= '</div>';
		}
		$html .= '</section>';
		return $html;
	}

	/**
	 * @param int   $id   Slider ID.
	 * @param array $cfg  Sanitised configuration.
	 * @param array $args preview (bool), start (int).
	 */
	public static function render( $id, $cfg, $args = array() ) {
		$preview = ! empty( $args['preview'] );
		$slides  = self::build_slides( $id, $cfg, $preview );
		if ( 'carousel' === $cfg['mode'] ) {
			return self::render_carousel( $id, $cfg, $slides, $preview );
		}
		$n       = count( $slides );
		if ( ! $n ) {
			if ( $preview || current_user_can( ks_capability() ) ) {
				return '<p class="ks-notice">' . esc_html__( 'Kashida Slider: no published content matches this slider\'s source settings.', 'kashida-slider' ) . '</p>';
			}
			return '';
		}
		++self::$count;
		$st    = $cfg['settings'];
		$rtl   = is_rtl();
		$dom   = 'ks-slider-' . (int) $id . '-' . self::$count;
		$data  = array(
			'transition' => $st['transition'],
			'direction'  => $st['direction'],
			'duration'   => (int) $st['duration'],
			'autoplay'   => $preview ? false : (bool) $st['autoplay'],
			'interval'   => (int) $st['interval'],
			'pauseHover' => (bool) $st['pause_hover'],
			'loop'       => (bool) $st['loop'],
			'rtl'        => $rtl,
			'start'      => isset( $args['start'] ) ? max( 0, min( $n - 1, (int) $args['start'] ) ) : 0,
			'i18n'       => array( 'pause' => __( 'Pause autoplay', 'kashida-slider' ), 'play' => __( 'Start autoplay', 'kashida-slider' ) ),
		);
		$container_radius = isset( $st['slider_radius'] ) ? max( 0, min( 40, (int) $st['slider_radius'] ) ) : 0;
		$style = sprintf( '--ks-h:%dpx;--ks-h-t:%dpx;--ks-h-m:%dpx;--ks-dur:%dms;--ks-slider-radius:%dpx;', $st['h_d'], $st['h_t'], $st['h_m'], $st['duration'], $container_radius );
		$fxcls = ( 'fade' === $st['transition'] ? '' : ' ks-fx' ) . ( $rtl ? ' ks-rtl' : '' );

		$html  = '<div id="' . esc_attr( $dom ) . '" class="ks-slider ks-t-' . esc_attr( $st['transition'] ) . $fxcls . '" role="region" aria-roledescription="' . esc_attr__( 'carousel', 'kashida-slider' ) . '" aria-label="' . esc_attr( $cfg['name'] ) . '" data-ks="' . esc_attr( wp_json_encode( $data ) ) . '" style="' . esc_attr( $style ) . '">';
		$html .= '<div class="ks-track" aria-live="off">';
		foreach ( $slides as $i => $s ) {
			$html .= self::slide_html( $s, $i, $n );
		}
		$html .= '</div>';
		if ( $n > 1 ) {
			if ( $st['arrows'] ) {
				$html .= '<button type="button" class="ks-arrow ks-prev" aria-label="' . esc_attr__( 'Previous slide', 'kashida-slider' ) . '"><svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg></button>';
				$html .= '<button type="button" class="ks-arrow ks-next" aria-label="' . esc_attr__( 'Next slide', 'kashida-slider' ) . '"><svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg></button>';
			}
			if ( $st['dots'] || $st['autoplay'] ) {
				$html .= '<div class="ks-controls">';
				if ( $st['autoplay'] && ! $preview ) {
					$html .= '<button type="button" class="ks-pause" aria-label="' . esc_attr__( 'Pause autoplay', 'kashida-slider' ) . '"><span class="ks-ico" aria-hidden="true"></span></button>';
				}
				if ( $st['dots'] ) {
					$html .= '<div class="ks-dots">';
					for ( $i = 0; $i < $n; $i++ ) {
						/* translators: %d: slide number */
						$html .= '<button type="button" class="ks-dot" data-i="' . (int) $i . '" aria-label="' . esc_attr( sprintf( __( 'Go to slide %d', 'kashida-slider' ), $i + 1 ) ) . '"></button>';
					}
					$html .= '</div>';
				}
				$html .= '</div>';
			}
		}
		return $html . '</div>';
	}

	/** Complete HTML document used by the editor iframe and the full-page preview. */
	public static function document( $inner, $editor = false ) {
		$lang = esc_attr( get_bloginfo( 'language' ) );
		$dir  = is_rtl() ? 'rtl' : 'ltr';
		$out  = '<!doctype html><html lang="' . $lang . '" dir="' . $dir . '"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex">';
		// This is a standalone iframe document, so enqueue APIs cannot add assets to its head.
		// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- Required in the standalone preview document.
		$out .= '<link rel="stylesheet" href="' . esc_url( KS_URL . 'assets/css/frontend.css?ver=' . KS_VERSION ) . '">';
		$out .= '<style>html,body{margin:0;padding:0;background:#f0f0f1;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Tahoma,sans-serif}.ks-notice{padding:2rem;text-align:center;color:#50575e}';
		if ( $editor ) {
			$out .= '[data-ks-el]{cursor:pointer}[data-ks-el]:hover{outline:2px dashed #F05C41;outline-offset:3px}';
		}
		$out .= '</style><style>html,body{margin:0!important;padding:0!important;overflow:hidden!important;scrollbar-width:none!important}html::-webkit-scrollbar,body::-webkit-scrollbar{display:none!important}</style></head><body>' . $inner;
		// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- Required in the standalone preview document.
		$out .= '<script src="' . esc_url( KS_URL . 'assets/js/frontend.js?ver=' . KS_VERSION ) . '"></script>'; 
		if ( $editor ) {
			// Editor-only: relays element clicks to the editor and runs the "Preview Animation" request from the parent window.
			$out .= '<script>window.addEventListener("message",function(e){var d=e.data;if(e.source!==parent||!d||d.ks!=="fx")return;var s=document.querySelector(".ks-slider");if(!s||!window.KashidaSlider){parent.postMessage({ks:"fx-done",ok:false},"*");return;}KashidaSlider.demo(s,{fx:d.fx,direction:d.direction,dur:d.dur},function(r){parent.postMessage({ks:"fx-done",ok:r},"*");});});';
			$out .= 'document.addEventListener("click",function(e){var t=e.target.closest("[data-ks-el]");if(t){e.preventDefault();parent.postMessage({ks:"select",el:t.getAttribute("data-ks-el")},"*");}else if(e.target.closest("a")){e.preventDefault();}});</script>';
		}
		return $out . '</body></html>';
	}

	/** Full-page preview: /?ks_preview=ID&_wpnonce=… (admins only). */
	public static function preview_page() {
		if ( empty( $_GET['ks_preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		$id = absint( $_GET['ks_preview'] ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! current_user_can( ks_capability() ) || ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'ks_preview_' . $id ) ) {
			wp_die( esc_html__( 'You are not allowed to preview this slider.', 'kashida-slider' ), '', array( 'response' => 403 ) );
		}
		$sl = KS_Storage::get( $id );
		if ( ! $sl ) {
			wp_die( esc_html__( 'Slider not found.', 'kashida-slider' ), '', array( 'response' => 404 ) );
		}
		nocache_headers();
		header( 'Content-Type: text/html; charset=utf-8' );
		echo self::document( self::render( $id, $sl['config'], array( 'preview' => false ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
		exit;
	}
}
