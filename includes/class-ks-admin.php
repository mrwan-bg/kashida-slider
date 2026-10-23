<?php
/**
 * Admin: Slider Library, "new" screen, editor shell, Help Center, branded header/footer and secured handlers.
 *
 * @package KashidaSlider
 */

defined( 'ABSPATH' ) || exit;

final class KS_Admin {

	const SLUG = 'kashida-slider';

	/** @var string[] Hook suffixes of this plugin's pages. */
	private static $hooks = array();

	public static function hooks() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_ks_create', array( __CLASS__, 'handle_create' ) );
		add_action( 'admin_post_ks_duplicate', array( __CLASS__, 'handle_duplicate' ) );
		add_action( 'admin_post_ks_delete', array( __CLASS__, 'handle_delete' ) );
		add_action( 'wp_ajax_ks_save', array( __CLASS__, 'ajax_save' ) );
		add_action( 'wp_ajax_ks_preview', array( __CLASS__, 'ajax_preview' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( KS_FILE ), array( __CLASS__, 'action_links' ) );
	}

	public static function action_links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( self::url() ) . '">' . esc_html__( 'Slider Library', 'kashida-slider' ) . '</a>' );
		return $links;
	}

	/** URL of the Slider Library / editor (page=kashida-slider). */
	public static function url( $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => self::SLUG ), $args ), admin_url( 'admin.php' ) );
	}

	public static function new_url() {
		return add_query_arg( array( 'page' => self::SLUG . '-new' ), admin_url( 'admin.php' ) );
	}

	public static function help_url() {
		return add_query_arg( array( 'page' => self::SLUG . '-help' ), admin_url( 'admin.php' ) );
	}

	public static function menu() {
		$cap   = ks_capability();
		$icon  = 'dashicons-images-alt2'; // Use a native WordPress Dashicon for consistent admin-menu sizing and contrast.
		self::$hooks[] = add_menu_page( __( 'Kashida Slider', 'kashida-slider' ), __( 'Kashida Slider', 'kashida-slider' ), $cap, self::SLUG, array( __CLASS__, 'page' ), $icon, 58 );
		self::$hooks[] = add_submenu_page( self::SLUG, __( 'Slider Library', 'kashida-slider' ), __( 'Slider Library', 'kashida-slider' ), $cap, self::SLUG, array( __CLASS__, 'page' ) );
		self::$hooks[] = add_submenu_page( self::SLUG, __( 'Create New Slider', 'kashida-slider' ), __( 'Create New Slider', 'kashida-slider' ), $cap, self::SLUG . '-new', array( __CLASS__, 'page' ) );
		self::$hooks[] = add_submenu_page( self::SLUG, __( 'Help', 'kashida-slider' ), __( 'Help', 'kashida-slider' ), $cap, self::SLUG . '-help', array( __CLASS__, 'page' ) );
	}

	/** @return string list|new|edit|help */
	private static function screen() {
		$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		if ( self::SLUG . '-help' === $page ) {
			return 'help';
		}
		if ( self::SLUG . '-new' === $page ) {
			return 'new';
		}
		$view = isset( $_GET['view'] ) ? sanitize_key( $_GET['view'] ) : 'list'; // phpcs:ignore WordPress.Security.NonceVerification
		return in_array( $view, array( 'new', 'edit' ), true ) ? $view : 'list';
	}

	private static function editing() {
		return isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
	}

	/* ---------- Assets (own pages only) ---------- */

	public static function assets( $hook ) {
		if ( ! in_array( $hook, self::$hooks, true ) ) {
			return;
		}
		wp_enqueue_style( 'kashida-slider-admin', KS_URL . 'assets/css/admin.css', array(), KS_VERSION );
		$screen = self::screen();
		if ( 'list' === $screen ) {
			wp_enqueue_script( 'kashida-slider-list', KS_URL . 'assets/js/admin-list.js', array(), KS_VERSION, true );
			wp_localize_script( 'kashida-slider-list', 'KS_LIST', array(
				'dlgTitle' => __( 'Delete slider?', 'kashida-slider' ),
				/* translators: %s: slider name */
				'dlgBody'  => __( 'Delete "%s" permanently? This cannot be undone.', 'kashida-slider' ),
				'dlgOk'    => __( 'Delete', 'kashida-slider' ),
				'dlgNo'    => __( 'Cancel', 'kashida-slider' ),
				'copied'   => __( 'Shortcode copied', 'kashida-slider' ),
				'copyFail' => __( 'Copy failed. Select the shortcode and copy it manually.', 'kashida-slider' ),
			) );
			return;
		}
		if ( 'edit' !== $screen ) {
			return;
		}
		$slider = KS_Storage::get( self::editing() );
		if ( ! $slider ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_script( 'kashida-slider-admin', KS_URL . 'assets/js/admin.js', array(), KS_VERSION, true );
		wp_localize_script( 'kashida-slider-admin', 'KS_ADMIN', array(
			'ajax'     => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'ks_admin' ),
			'id'       => $slider['id'],
			'config'   => $slider['config'],
			'slideFields'  => self::export( KS_Schema::slide_fields() ),
			'globalFields' => self::export( KS_Schema::global_fields() ),
			'tabs'     => KS_Schema::tabs( $slider['config']['mode'] ),
			'defaults' => KS_Schema::defaults(),
			'sources'  => in_array( $slider['config']['mode'], array( 'dynamic', 'carousel' ), true ) ? KS_Source::editor_data() : null,
			'previewUrl' => add_query_arg( array( 'ks_preview' => $slider['id'], '_wpnonce' => wp_create_nonce( 'ks_preview_' . $slider['id'] ) ), home_url( '/' ) ),
			'listUrl'  => self::url(),
			'maxSlides' => KS_Schema::MAX_SLIDES,
			'i18n'     => self::strings(),
		) );
	}

	private static function export( $fields ) {
		$out = array();
		foreach ( $fields as $k => $f ) {
			$out[] = array_merge( array( 'key' => $k ), $f );
		}
		return $out;
	}

	private static function strings() {
		return array(
			'save' => __( 'Save', 'kashida-slider' ), 'saving' => __( 'Saving…', 'kashida-slider' ), 'saved' => __( 'All changes saved', 'kashida-slider' ),
			'unsaved' => __( 'Unsaved changes', 'kashida-slider' ), 'saveFailed' => __( 'Save failed', 'kashida-slider' ),
			'preview' => __( 'Preview', 'kashida-slider' ), 'back' => __( 'All sliders', 'kashida-slider' ), 'name' => __( 'Slider name', 'kashida-slider' ),
			'desktop' => __( 'Desktop', 'kashida-slider' ), 'tablet' => __( 'Tablet', 'kashida-slider' ), 'mobile' => __( 'Mobile', 'kashida-slider' ),
			'addSlide' => __( 'Add slide', 'kashida-slider' ), 'duplicate' => __( 'Duplicate slide', 'kashida-slider' ), 'delete' => __( 'Delete slide', 'kashida-slider' ),
			'moveBack' => __( 'Move earlier', 'kashida-slider' ), 'moveFwd' => __( 'Move later', 'kashida-slider' ),
			'confirmDelete' => __( 'Delete this slide?', 'kashida-slider' ), 'lastSlide' => __( 'A slider needs at least one slide.', 'kashida-slider' ),
			'maxSlides' => __( 'Maximum number of slides reached.', 'kashida-slider' ),
			'slide' => __( 'Slide', 'kashida-slider' ), 'select' => __( 'Select image', 'kashida-slider' ), 'change' => __( 'Change', 'kashida-slider' ), 'remove' => __( 'Remove', 'kashida-slider' ),
			'mediaTitle' => __( 'Choose an image', 'kashida-slider' ), 'mediaButton' => __( 'Use this image', 'kashida-slider' ),
			'addImages' => __( 'Add multiple images', 'kashida-slider' ), 'customItemCount' => __( 'Custom items', 'kashida-slider' ), 'mediaUnavailable' => __( 'The WordPress Media Library is unavailable.', 'kashida-slider' ),
			'dynNote' => __( 'Dynamic slides use these design settings for every item. Text and images come from your content automatically.', 'kashida-slider' ),
			'sourceType' => __( 'Content source', 'kashida-slider' ), 'customItems' => __( 'Custom images and items', 'kashida-slider' ), 'customItemsHelp' => __( 'Add and manage carousel items in the Content tab.', 'kashida-slider' ), 'post' => __( 'Blog posts', 'kashida-slider' ), 'pages' => __( 'Selected pages', 'kashida-slider' ), 'pageSearch' => __( 'Search pages by title', 'kashida-slider' ), 'pageHelp' => __( 'Select pages and use the arrows to set their display order.', 'kashida-slider' ), 'pageNone' => __( 'No published pages match your search.', 'kashida-slider' ), 'pageSelected' => __( 'Selected pages', 'kashida-slider' ), 'moveUp' => __( 'Move up', 'kashida-slider' ), 'moveDown' => __( 'Move down', 'kashida-slider' ), 'product' => __( 'WooCommerce products', 'kashida-slider' ), 'portfolio' => __( 'Portfolio projects', 'kashida-slider' ),
			'wooMissing' => __( 'WooCommerce is not active on this site. Activate it to use products.', 'kashida-slider' ),
			'portfolioMissing' => __( 'No portfolio-type content was found. Your theme or plugin must register a public custom post type (for example "portfolio"). Please choose another source.', 'kashida-slider' ),
			'postType' => __( 'Portfolio post type', 'kashida-slider' ), 'count' => __( 'Number of items', 'kashida-slider' ),
			'filters' => __( 'Filter by terms', 'kashida-slider' ), 'noTerms' => __( 'No categories or tags available for this source.', 'kashida-slider' ),
			'orderby' => __( 'Sort by', 'kashida-slider' ), 'order' => __( 'Order', 'kashida-slider' ), 'asc' => __( 'Ascending', 'kashida-slider' ), 'desc' => __( 'Descending', 'kashida-slider' ),
			'date' => __( 'Date', 'kashida-slider' ), 'title' => __( 'Title', 'kashida-slider' ), 'menuOrder' => __( 'Menu order', 'kashida-slider' ), 'price' => __( 'Price', 'kashida-slider' ),
			'fields' => __( 'Fields to show', 'kashida-slider' ), 'fImage' => __( 'Image', 'kashida-slider' ), 'fTitle' => __( 'Title', 'kashida-slider' ), 'fExcerpt' => __( 'Excerpt', 'kashida-slider' ),
			'fDate' => __( 'Publication date', 'kashida-slider' ), 'fPrice' => __( 'Price (with sale price)', 'kashida-slider' ), 'fButton' => __( 'Button', 'kashida-slider' ),
			'btnLabel' => __( 'Button label (blank = default)', 'kashida-slider' ), 'words' => __( 'Excerpt length (words)', 'kashida-slider' ),
			'fallback' => __( 'Fallback image (used when an item has no featured image)', 'kashida-slider' ),
			'cache' => __( 'Cache duration (minutes, 0 = off)', 'kashida-slider' ),
			'cacheHelp' => __( 'Results refresh automatically when content changes.', 'kashida-slider' ),
			'previewLoading' => __( 'Updating preview…', 'kashida-slider' ), 'previewFailed' => __( 'Preview could not be loaded.', 'kashida-slider' ),
			'shortcode' => __( 'Shortcode', 'kashida-slider' ), 'copied' => __( 'Copied!', 'kashida-slider' ), 'copy' => __( 'Copy', 'kashida-slider' ),
			'leave' => __( 'You have unsaved changes.', 'kashida-slider' ), 'clickHint' => __( 'Tip: click text or buttons in the preview to edit them.', 'kashida-slider' ),
			'fxPreview' => __( 'Preview Animation', 'kashida-slider' ), 'fxPlaying' => __( 'Playing…', 'kashida-slider' ),
			'fxNeedTwo' => __( 'Add at least two slides (or make sure the source returns two or more items) to preview a transition.', 'kashida-slider' ),
			'fxHelp' => __( 'Plays the selected effect on the preview. Nothing is saved and the slider returns to its starting slide.', 'kashida-slider' ),
		);
	}

	/* ---------- Icons ---------- */

	private static function icon( $name, $size = 18 ) {
		$paths = array(
			'edit'    => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4z"/>',
			'eye'     => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/>',
			'copy'    => '<rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/>',
			'code'    => '<path d="M16 18l6-6-6-6"/><path d="M8 6l-6 6 6 6"/>',
			'trash'   => '<path d="M3 6h18"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/>',
			'plus'    => '<path d="M12 5v14M5 12h14"/>',
			'grid'    => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>',
			'help'    => '<circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 015.8 1c0 2-3 3-3 3"/><path d="M12 17h.01"/>',
			'post'    => '<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h8"/>',
			'cart'    => '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 002 1.6h9.7a2 2 0 002-1.6L23 6H6"/>',
			'folder'  => '<path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/>',
			'layers'  => '<path d="M12 2L2 7l10 5 10-5z"/><path d="M2 17l10 5 10-5M2 12l10 5 10-5"/>',
			'chevron' => '<path d="M9 6l6 6-6 6"/>',
		);
		if ( ! isset( $paths[ $name ] ) ) {
			return '';
		}
		return '<svg class="ks-i ks-i-' . esc_attr( $name ) . '" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="' . (int) $size . '" height="' . (int) $size . '" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>'; // phpcs:ignore WordPress.Security.EscapeOutput -- static markup.
	}

	/* ---------- Layout: header, nav, footer ---------- */

	private static function header( $screen ) {
		$items = array(
			'list' => array( self::url(), __( 'Slider Library', 'kashida-slider' ), 'grid' ),
			'new'  => array( self::new_url(), __( 'Create New Slider', 'kashida-slider' ), 'plus' ),
			'help' => array( self::help_url(), __( 'Help', 'kashida-slider' ), 'help' ),
		);
		$active = 'edit' === $screen ? 'list' : $screen;

		echo '<div class="ks-shell"><header class="ks-header"><div class="ks-header-main">';
		echo '<a class="ks-brand" href="https://kashida.ly/" target="_blank" rel="noopener"><img class="ks-logo-img" src="' . esc_url( KS_URL . 'assets/img/kashida-logo.svg' ) . '" width="150" height="30" alt="' . esc_attr__( 'Kashida Media Services', 'kashida-slider' ) . '"></a>';
		echo '<div class="ks-header-text"><p class="ks-product">' . esc_html__( 'Kashida Slider', 'kashida-slider' ) . ' <span class="ks-version" dir="ltr">v' . esc_html( KS_VERSION ) . '</span></p>';
		echo '<p class="ks-tagline">' . esc_html__( 'A lightweight visual slider builder for custom and dynamic content.', 'kashida-slider' ) . '</p></div></div>';
		echo '<nav class="ks-nav" aria-label="' . esc_attr__( 'Kashida Slider navigation', 'kashida-slider' ) . '"><ul>';
		foreach ( $items as $key => $it ) {
			echo '<li><a href="' . esc_url( $it[0] ) . '"' . ( $key === $active ? ' class="is-active" aria-current="page"' : '' ) . '>' . self::icon( $it[2], 16 ) . '<span>' . esc_html( $it[1] ) . '</span></a></li>'; // phpcs:ignore WordPress.Security.EscapeOutput -- icon is static markup.
		}
		echo '</ul></nav></header><div class="ks-content-area">';
	}

	private static function footer() {
		echo '</div><footer class="ks-footer"><img class="ks-footer-mark" src="' . esc_url( KS_URL . 'assets/img/kashida-mark.svg' ) . '" width="18" height="18" alt="">';
		echo '<span dir="ltr"><a href="https://kashida.ly/" target="_blank" rel="noopener">Kashida Media Services</a> by Mrwan elGobee</span></footer></div>';
	}

	public static function page() {
		if ( ! current_user_can( ks_capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'kashida-slider' ) );
		}
		$screen = self::screen();
		echo '<div class="wrap ks-wrap">';
		self::header( $screen );
		switch ( $screen ) {
			case 'new':
				self::page_new();
				break;
			case 'edit':
				self::page_edit();
				break;
			case 'help':
				self::page_help();
				break;
			default:
				self::page_list();
		}
		self::footer();
		echo '</div>';
	}

	private static function page_title( $title, $action = '' ) {
		echo '<div class="ks-pagehead"><h1>' . esc_html( $title ) . '</h1>' . $action . '</div><hr class="wp-header-end">'; // phpcs:ignore WordPress.Security.EscapeOutput -- $action is built from escaped parts by the caller.
	}

	private static function notices() {
		$msg = isset( $_GET['ks_msg'] ) ? sanitize_key( $_GET['ks_msg'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$map = array(
			'created'    => array( 'success', __( 'Slider created.', 'kashida-slider' ) ),
			'duplicated' => array( 'success', __( 'Slider duplicated.', 'kashida-slider' ) ),
			'deleted'    => array( 'success', __( 'Slider deleted.', 'kashida-slider' ) ),
			'error'      => array( 'error', __( 'Something went wrong. Please try again.', 'kashida-slider' ) ),
			'invalid'    => array( 'error', __( 'That slider no longer exists.', 'kashida-slider' ) ),
			'denied'     => array( 'error', __( 'You are not allowed to do that.', 'kashida-slider' ) ),
		);
		if ( isset( $map[ $msg ] ) ) {
			printf( '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>', esc_attr( $map[ $msg ][0] ), esc_html( $map[ $msg ][1] ) );
		}
	}

	/* ---------- Slider Library ---------- */

	private static function source_label( $cfg ) {
		if ( 'dynamic' !== $cfg['mode'] && 'carousel' !== $cfg['mode'] ) {
			return '';
		}
		if ( 'custom' === $cfg['source']['type'] ) { return __( 'Custom items', 'kashida-slider' ); }
		$labels = array(
			'post'      => __( 'Blog', 'kashida-slider' ),
			'product'   => __( 'WooCommerce', 'kashida-slider' ),
			'portfolio' => __( 'Portfolio', 'kashida-slider' ),
		);
		return $labels[ $cfg['source']['type'] ];
	}

	private static function thumb_style( $cfg ) {
		$d = in_array( $cfg['mode'], array( 'dynamic', 'carousel' ), true ) && 'custom' !== $cfg['source']['type'] ? $cfg['design'] : $cfg['slides'][0];
		$bg = 'gradient' === $d['bg_type'] ? sprintf( 'linear-gradient(%ddeg,%s,%s)', $d['bg_angle'], $d['bg_color'], $d['bg_color2'] ) : $d['bg_color'];
		$css = 'background:' . $bg . ';';
		if ( ( 'custom' === $cfg['mode'] || ( 'carousel' === $cfg['mode'] && 'custom' === $cfg['source']['type'] ) ) && $d['bg_image'] ) {
			$css = 'background:url("' . esc_url( $d['bg_image'] ) . '") center/cover no-repeat,' . $bg . ';';
		}
		return $css;
	}

	private static function page_list() {
		$s      = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$mode   = isset( $_GET['mode'] ) && in_array( $_GET['mode'], array( 'custom', 'dynamic', 'carousel' ), true ) ? $_GET['mode'] : ''; // phpcs:ignore WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput
		$source = isset( $_GET['source'] ) && in_array( $_GET['source'], array( 'post', 'product', 'portfolio' ), true ) ? $_GET['source'] : ''; // phpcs:ignore WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput
		$sorts  = array(
			'modified_desc' => array( 'modified', 'DESC', __( 'Newest modified', 'kashida-slider' ) ),
			'modified_asc'  => array( 'modified', 'ASC', __( 'Oldest modified', 'kashida-slider' ) ),
			'name_asc'      => array( 'title', 'ASC', __( 'Name A–Z', 'kashida-slider' ) ),
			'name_desc'     => array( 'title', 'DESC', __( 'Name Z–A', 'kashida-slider' ) ),
		);
		$sort   = isset( $_GET['sort'] ) && isset( $sorts[ $_GET['sort'] ] ) ? $_GET['sort'] : 'modified_desc'; // phpcs:ignore WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput

		$action = '<a href="' . esc_url( self::new_url() ) . '" class="button button-primary ks-btn-primary">' . self::icon( 'plus', 16 ) . esc_html__( 'Create New Slider', 'kashida-slider' ) . '</a>';
		self::page_title( __( 'Slider Library', 'kashida-slider' ), $action );
		self::notices();

		$total = KS_Storage::count();
		if ( 0 === $total ) {
			echo '<div class="ks-empty"><div class="ks-empty-ico" aria-hidden="true">' . self::icon( 'layers', 28 ) . '</div><h2>' . esc_html__( 'Create your first slider', 'kashida-slider' ) . '</h2><p>' . esc_html__( 'Build a custom slider slide by slide, or generate slides automatically from your posts, products or portfolio.', 'kashida-slider' ) . '</p><a class="button button-primary ks-btn-primary" href="' . esc_url( self::new_url() ) . '">' . esc_html__( 'Create New Slider', 'kashida-slider' ) . '</a> <a class="button" href="' . esc_url( self::help_url() ) . '">' . esc_html__( 'Read the Help Center', 'kashida-slider' ) . '</a></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
			return;
		}

		$all  = KS_Storage::all( $s, $sorts[ $sort ][0], $sorts[ $sort ][1] );
		$list = array();
		foreach ( $all as $sl ) {
			$c = $sl['config'];
			if ( $mode && $c['mode'] !== $mode ) {
				continue;
			}
			if ( $source && ( ! in_array( $c['mode'], array( 'dynamic', 'carousel' ), true ) || $c['source']['type'] !== $source ) ) {
				continue;
			}
			$list[] = $sl;
		}

		// Filter bar (read-only GET form).
		echo '<form method="get" class="ks-filters" role="search"><input type="hidden" name="page" value="' . esc_attr( self::SLUG ) . '">';
		echo '<label class="screen-reader-text" for="ks-s">' . esc_html__( 'Search sliders by name', 'kashida-slider' ) . '</label><input type="search" id="ks-s" name="s" value="' . esc_attr( $s ) . '" placeholder="' . esc_attr__( 'Search by name…', 'kashida-slider' ) . '">';
		echo '<label class="screen-reader-text" for="ks-mode">' . esc_html__( 'Creation mode', 'kashida-slider' ) . '</label><select id="ks-mode" name="mode"><option value="">' . esc_html__( 'All modes', 'kashida-slider' ) . '</option><option value="custom"' . selected( $mode, 'custom', false ) . '>' . esc_html__( 'Custom', 'kashida-slider' ) . '</option><option value="dynamic"' . selected( $mode, 'dynamic', false ) . '>' . esc_html__( 'Dynamic', 'kashida-slider' ) . '</option><option value="carousel"' . selected( $mode, 'carousel', false ) . '>' . esc_html__( 'Carousel', 'kashida-slider' ) . '</option></select>';
		echo '<label class="screen-reader-text" for="ks-src">' . esc_html__( 'Content source', 'kashida-slider' ) . '</label><select id="ks-src" name="source"><option value="">' . esc_html__( 'All sources', 'kashida-slider' ) . '</option><option value="post"' . selected( $source, 'post', false ) . '>' . esc_html__( 'Blog', 'kashida-slider' ) . '</option><option value="product"' . selected( $source, 'product', false ) . '>' . esc_html__( 'WooCommerce', 'kashida-slider' ) . '</option><option value="portfolio"' . selected( $source, 'portfolio', false ) . '>' . esc_html__( 'Portfolio', 'kashida-slider' ) . '</option></select>';
		echo '<label class="screen-reader-text" for="ks-sort">' . esc_html__( 'Sort by', 'kashida-slider' ) . '</label><select id="ks-sort" name="sort">';
		foreach ( $sorts as $k => $so ) {
			echo '<option value="' . esc_attr( $k ) . '"' . selected( $sort, $k, false ) . '>' . esc_html( $so[2] ) . '</option>';
		}
		echo '</select><button type="submit" class="button">' . esc_html__( 'Apply', 'kashida-slider' ) . '</button>';
		if ( $s || $mode || $source || 'modified_desc' !== $sort ) {
			echo ' <a href="' . esc_url( self::url() ) . '">' . esc_html__( 'Reset', 'kashida-slider' ) . '</a>';
		}
		/* translators: 1: number of sliders shown, 2: total number of sliders */
		echo '<span class="ks-count" role="status">' . esc_html( sprintf( __( 'Showing %1$d of %2$d', 'kashida-slider' ), count( $list ), $total ) ) . '</span></form>';

		if ( ! $list ) {
			echo '<div class="ks-empty ks-empty-sm"><h2>' . esc_html__( 'No sliders match your search', 'kashida-slider' ) . '</h2><p>' . esc_html__( 'Try a different name or clear the filters.', 'kashida-slider' ) . '</p><a class="button" href="' . esc_url( self::url() ) . '">' . esc_html__( 'Reset', 'kashida-slider' ) . '</a></div>';
			return;
		}

		echo '<ul class="ks-grid">';
		foreach ( $list as $sl ) {
			self::card( $sl );
		}
		echo '</ul>';
	}

	private static function card( $sl ) {
		$cfg   = $sl['config'];
		$id    = (int) $sl['id'];
		$name  = $sl['name'];
		$edit  = self::url( array( 'view' => 'edit', 'id' => $id ) );
		$dup   = wp_nonce_url( admin_url( 'admin-post.php?action=ks_duplicate&id=' . $id ), 'ks_duplicate_' . $id );
		$del   = wp_nonce_url( admin_url( 'admin-post.php?action=ks_delete&id=' . $id ), 'ks_delete_' . $id );
		$prev  = add_query_arg( array( 'ks_preview' => $id, '_wpnonce' => wp_create_nonce( 'ks_preview_' . $id ) ), home_url( '/' ) );
		$code  = '[kashida_slider id="' . $id . '"]';
		$dyn   = 'dynamic' === $cfg['mode'];
		$carousel = 'carousel' === $cfg['mode'];
		$fmt   = get_option( 'date_format' );
		$ico   = array( 'post' => 'post', 'product' => 'cart', 'portfolio' => 'folder' );

		echo '<li class="ks-slider-card"><a class="ks-thumb-box" href="' . esc_url( $edit ) . '" style="' . esc_attr( self::thumb_style( $cfg ) ) . '" aria-label="' . esc_attr( sprintf( /* translators: %s: slider name */ __( 'Edit %s', 'kashida-slider' ), $name ) ) . '">';
		if ( $dyn || ( $carousel && 'custom' !== $cfg['source']['type'] ) ) {
			echo '<span class="ks-thumb-ico">' . self::icon( $ico[ $cfg['source']['type'] ], 30 ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo '<span class="ks-badge ' . ( $dyn || $carousel ? 'ks-badge-dynamic' : 'ks-badge-custom' ) . '">' . esc_html( $carousel ? __( 'Carousel', 'kashida-slider' ) : ( $dyn ? __( 'Dynamic', 'kashida-slider' ) : __( 'Custom', 'kashida-slider' ) ) ) . '</span></a>'; 
		echo '<div class="ks-card-main"><h2 class="ks-card-title"><a href="' . esc_url( $edit ) . '">' . esc_html( $name ) . '</a></h2><dl class="ks-meta">';
		echo '<div><dt>' . esc_html__( 'ID', 'kashida-slider' ) . '</dt><dd>' . esc_html( (string) absint( $id ) ) . '</dd></div>';
		if ( $dyn || ( $carousel && 'custom' !== $cfg['source']['type'] ) ) {
			echo '<div><dt>' . esc_html__( 'Source', 'kashida-slider' ) . '</dt><dd>' . esc_html( self::source_label( $cfg ) ) . '</dd></div>';
			/* translators: %d: maximum number of items */
			echo '<div><dt>' . esc_html__( 'Slides', 'kashida-slider' ) . '</dt><dd>' . esc_html( sprintf( __( 'Auto (up to %d)', 'kashida-slider' ), $cfg['source']['count'] ) ) . '</dd></div>';
		} elseif ( $carousel ) {
			echo '<div><dt>' . esc_html__( 'Source', 'kashida-slider' ) . '</dt><dd>' . esc_html__( 'Custom items', 'kashida-slider' ) . '</dd></div>';
			echo '<div><dt>' . esc_html__( 'Items', 'kashida-slider' ) . '</dt><dd>' . (int) count( $cfg['slides'] ) . '</dd></div>';
		} else {
			echo '<div><dt>' . esc_html__( 'Slides', 'kashida-slider' ) . '</dt><dd>' . (int) count( $cfg['slides'] ) . '</dd></div>';
		}
		echo '<div><dt>' . esc_html__( 'Created', 'kashida-slider' ) . '</dt><dd>' . esc_html( wp_date( $fmt, $sl['created'] ) ) . '</dd></div>';
		echo '<div><dt>' . esc_html__( 'Modified', 'kashida-slider' ) . '</dt><dd>' . esc_html( wp_date( $fmt, $sl['modified'] ) ) . '</dd></div></dl>';
		echo '<p class="ks-sc-line"><code dir="ltr">' . esc_html( $code ) . '</code></p></div>';

		echo '<div class="ks-actions">';
		$act = function ( $href, $label, $icon, $extra = '' ) {
			return '<a class="ks-act' . ( 'trash' === $icon ? ' ks-act-danger ks-delete' : '' ) . '" href="' . esc_url( $href ) . '" title="' . esc_attr( $label ) . '" aria-label="' . esc_attr( $label ) . '"' . $extra . '>' . self::icon( $icon ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput
		};
		echo $act( $edit, __( 'Edit', 'kashida-slider' ), 'edit' ); // phpcs:ignore WordPress.Security.EscapeOutput
		echo $act( $prev, __( 'Preview', 'kashida-slider' ), 'eye', ' target="_blank" rel="noopener"' ); // phpcs:ignore WordPress.Security.EscapeOutput
		echo $act( $dup, __( 'Duplicate', 'kashida-slider' ), 'copy' ); // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<button type="button" class="ks-act ks-copy" data-copy="' . esc_attr( $code ) . '" title="' . esc_attr__( 'Copy Shortcode', 'kashida-slider' ) . '" aria-label="' . esc_attr__( 'Copy Shortcode', 'kashida-slider' ) . '">' . self::icon( 'code' ) . '</button>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo $act( $del, __( 'Delete', 'kashida-slider' ), 'trash', ' data-name="' . esc_attr( $name ) . '"' ); // phpcs:ignore WordPress.Security.EscapeOutput
		echo '</div></li>';
	}

	/* ---------- Create / Edit / Help ---------- */

	private static function page_new() {
		self::page_title( __( 'Create New Slider', 'kashida-slider' ) );
		self::notices();
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="ks-new ks-card-box">';
		wp_nonce_field( 'ks_create' );
		echo '<input type="hidden" name="action" value="ks_create">';
		echo '<p><label for="ks-name"><strong>' . esc_html__( 'Slider name', 'kashida-slider' ) . '</strong></label><br><input type="text" id="ks-name" name="ks_name" class="regular-text" maxlength="100" required placeholder="' . esc_attr__( 'e.g. Home page hero', 'kashida-slider' ) . '"></p>';
		echo '<fieldset class="ks-cards"><legend class="screen-reader-text">' . esc_html__( 'Slider type', 'kashida-slider' ) . '</legend>';
		$cards = array(
			'custom'  => array( __( 'Custom slider', 'kashida-slider' ), __( 'Design each slide manually: images, text, buttons and layouts.', 'kashida-slider' ), 'layers' ),
			'dynamic' => array( __( 'Dynamic content slider', 'kashida-slider' ), __( 'Slides are generated automatically from blog posts, WooCommerce products or portfolio projects.', 'kashida-slider' ), 'post' ),
			'carousel' => array( __( 'Carousel slider', 'kashida-slider' ), __( 'Show multiple cards at once using images, blog posts, products, or portfolio projects.', 'kashida-slider' ), 'grid' ),
		);
		foreach ( $cards as $val => $c ) {
			echo '<label class="ks-card"><input type="radio" name="ks_mode" value="' . esc_attr( $val ) . '"' . checked( 'custom', $val, false ) . '><span class="ks-card-body">' . self::icon( $c[2], 22 ) . '<strong>' . esc_html( $c[0] ) . '</strong><span>' . esc_html( $c[1] ) . '</span></span></label>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo '</fieldset><p class="ks-form-actions"><button type="submit" class="button button-primary ks-btn-primary">' . esc_html__( 'Create and open editor', 'kashida-slider' ) . '</button> <a class="button" href="' . esc_url( self::url() ) . '">' . esc_html__( 'Cancel', 'kashida-slider' ) . '</a></p></form>';
	}

	private static function page_edit() {
		$slider = KS_Storage::get( self::editing() );
		if ( ! $slider ) {
			self::page_title( __( 'Edit Slider', 'kashida-slider' ) );
			echo '<div class="notice notice-error inline"><p>' . esc_html__( 'This slider does not exist or was deleted.', 'kashida-slider' ) . ' <a href="' . esc_url( self::url() ) . '">' . esc_html__( 'Back to the Slider Library', 'kashida-slider' ) . '</a></p></div>';
			return;
		}
		echo '<nav class="ks-crumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'kashida-slider' ) . '"><a href="' . esc_url( self::url() ) . '">' . esc_html__( 'Slider Library', 'kashida-slider' ) . '</a><span class="ks-sep" aria-hidden="true">' . self::icon( 'chevron', 14 ) . '</span><span aria-current="page">' . esc_html( $slider['name'] ) . '</span></nav>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<h1 class="screen-reader-text">' . esc_html__( 'Edit Slider', 'kashida-slider' ) . '</h1><hr class="wp-header-end">';
		self::notices();
		echo '<div id="ks-app" class="ks-app" data-id="' . (int) $slider['id'] . '"><p class="ks-loading">' . esc_html__( 'Loading editor…', 'kashida-slider' ) . '</p></div>';
		echo '<noscript><p>' . esc_html__( 'JavaScript is required to use the slider editor.', 'kashida-slider' ) . '</p></noscript>';
	}

	private static function page_help() {
		self::page_title( __( 'Help Center', 'kashida-slider' ) );
		KS_Help::render();
	}

	/* ---------- Handlers ---------- */

	private static function back( $msg ) {
		wp_safe_redirect( self::url( array( 'ks_msg' => $msg ) ) );
		exit;
	}

	public static function handle_create() {
		if ( ! current_user_can( ks_capability() ) ) {
			self::back( 'denied' );
		}
		check_admin_referer( 'ks_create' );
		$mode = isset( $_POST['ks_mode'] ) && in_array( $_POST['ks_mode'], array( 'dynamic', 'carousel' ), true ) ? sanitize_key( $_POST['ks_mode'] ) : 'custom';
		$name = isset( $_POST['ks_name'] ) ? sanitize_text_field( wp_unslash( $_POST['ks_name'] ) ) : '';
		$id   = KS_Storage::create( $name, $mode );
		if ( is_wp_error( $id ) ) {
			self::back( 'error' );
		}
		wp_safe_redirect( self::url( array( 'view' => 'edit', 'id' => $id, 'ks_msg' => 'created' ) ) );
		exit;
	}

	public static function handle_duplicate() {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		if ( ! current_user_can( ks_capability() ) ) {
			self::back( 'denied' );
		}
		check_admin_referer( 'ks_duplicate_' . $id );
		if ( ! KS_Storage::get( $id ) ) {
			self::back( 'invalid' );
		}
		$new = KS_Storage::duplicate( $id );
		self::back( is_wp_error( $new ) ? 'error' : 'duplicated' );
	}

	public static function handle_delete() {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		if ( ! current_user_can( ks_capability() ) ) {
			self::back( 'denied' );
		}
		check_admin_referer( 'ks_delete_' . $id );
		if ( ! KS_Storage::get( $id ) ) {
			self::back( 'invalid' );
		}
		self::back( KS_Storage::delete( $id ) ? 'deleted' : 'error' );
	}

	private static function ajax_guard() {
		check_ajax_referer( 'ks_admin', 'nonce' );
		if ( ! current_user_can( ks_capability() ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to do that.', 'kashida-slider' ) ), 403 );
		}
	}

	private static function posted_config() {
		$json = isset( $_POST['config'] ) ? wp_unslash( $_POST['config'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput
		$data = is_string( $json ) && strlen( $json ) < 1500000 ? json_decode( $json, true ) : null;
		if ( ! is_array( $data ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid data received.', 'kashida-slider' ) ), 400 );
		}
		return $data;
	}

	public static function ajax_save() {
		self::ajax_guard();
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- ajax_guard() verifies the AJAX nonce before input is read.
		if ( ! KS_Storage::get( $id ) ) {
			wp_send_json_error( array( 'message' => __( 'Slider not found.', 'kashida-slider' ) ), 404 );
		}
		$res = KS_Storage::save( $id, self::posted_config() );
		if ( is_wp_error( $res ) ) {
			wp_send_json_error( array( 'message' => $res->get_error_message() ), 500 );
		}
		wp_send_json_success( array( 'name' => $res['name'], 'slides' => count( $res['slides'] ) ) );
	}

	public static function ajax_preview() {
		self::ajax_guard();
		$id     = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- ajax_guard() verifies the AJAX nonce before input is read.
		$stored = KS_Storage::get( $id );
		if ( ! $stored ) {
			wp_send_json_error( array( 'message' => __( 'Slider not found.', 'kashida-slider' ) ), 404 );
		}
		$raw         = self::posted_config();
		$raw['mode'] = $stored['config']['mode'];
		$cfg         = KS_Schema::sanitize( $raw );
		$start       = isset( $_POST['start'] ) ? absint( $_POST['start'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- ajax_guard() verifies the AJAX nonce before input is read.
		$html        = KS_Renderer::render( $id, $cfg, array( 'preview' => true, 'start' => $start ) );
		wp_send_json_success( array( 'html' => KS_Renderer::document( $html, true ) ) );
	}
}
