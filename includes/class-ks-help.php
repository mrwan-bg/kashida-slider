<?php
/**
 * Help Center: local, translatable documentation (no external requests).
 *
 * @package KashidaSlider
 */

defined( 'ABSPATH' ) || exit;

final class KS_Help {

	private static function p( $t ) {
		return array( 'p', $t );
	}

	private static function ol( $a ) {
		return array( 'ol', $a );
	}

	private static function ul( $a ) {
		return array( 'ul', $a );
	}

	/** @return array[] sections => id, title, items => [title, blocks[]] */
	public static function sections() {
		return array(
			array(
				'id'    => 'start',
				'title' => __( 'Getting started', 'kashida-slider' ),
				'items' => array(
					array( __( 'What Kashida Slider does', 'kashida-slider' ), array(
						self::p( __( 'Kashida Slider is a lightweight visual builder for hero banners and content carousels. You design in a live preview and show the result anywhere on your site with a shortcode.', 'kashida-slider' ) ),
					) ),
					array( __( 'Custom vs. Dynamic sliders', 'kashida-slider' ), array(
						self::p( __( 'A Custom slider is built slide by slide: you choose every image, heading, text and button yourself.', 'kashida-slider' ) ),
						self::p( __( 'A Dynamic slider has one design that is applied to your latest published posts, WooCommerce products or portfolio projects. New content appears automatically; you never rebuild slides by hand.', 'kashida-slider' ) ),
						self::p( __( 'A Carousel slider displays multiple cards at once. Choose custom items or retrieve items from Blog, WooCommerce or Portfolio, then set how many items are shown on desktop, tablet and mobile.', 'kashida-slider' ) ),
						self::p( __( 'The mode is chosen when the slider is created and cannot be changed afterwards.', 'kashida-slider' ) ),
					) ),
					array( __( 'Create a new slider', 'kashida-slider' ), array(
						self::ol( array(
							__( 'Open Kashida Slider → Create New Slider.', 'kashida-slider' ),
							__( 'Type a name and choose Custom, Dynamic or Carousel.', 'kashida-slider' ),
							__( 'Click "Create and open editor".', 'kashida-slider' ),
						) ),
					) ),
					array( __( 'Save and preview', 'kashida-slider' ), array(
						self::p( __( 'The preview in the editor updates as you change settings. Use the Desktop, Tablet and Mobile buttons to check each screen size. Click Save (or press Ctrl/Cmd+S) to store your work; the status next to the button shows whether you have unsaved changes.', 'kashida-slider' ) ),
						self::p( __( 'The Preview button in the toolbar opens the saved slider on its own page. Save first so that page shows your latest changes.', 'kashida-slider' ) ),
					) ),
					array( __( 'Insert a slider into a page', 'kashida-slider' ), array(
						self::ol( array(
							__( 'Open the Slider Library and click the shortcode button on your slider card to copy the shortcode.', 'kashida-slider' ),
							__( 'Edit a page or post and add a Shortcode block.', 'kashida-slider' ),
							__( 'Paste the shortcode and update the page.', 'kashida-slider' ),
						) ),
					) ),
				),
			),
			array(
				'id'    => 'custom',
				'title' => __( 'Custom slider guide', 'kashida-slider' ),
				'items' => array(
					array( __( 'Add, duplicate, delete and reorder slides', 'kashida-slider' ), array(
						self::ul( array(
							__( 'Click "Add slide" in the toolbar to add a slide.', 'kashida-slider' ),
							__( 'Each thumbnail below the preview has buttons to move it earlier or later, duplicate it, or delete it. You can also drag thumbnails to reorder them.', 'kashida-slider' ),
							__( 'A slider must keep at least one slide.', 'kashida-slider' ),
						) ),
					) ),
					array( __( 'Background images', 'kashida-slider' ), array(
						self::p( __( 'Select the slide, open the Background tab and click "Select image" to pick a file from the media library. Use the focal point sliders to choose which part of the image stays visible when it is cropped, and set sizing to Cover, Contain or Original size.', 'kashida-slider' ) ),
						self::p( __( 'Without an image you can use a solid color or a two-color gradient. The Text overlay setting darkens only the area behind the text by default; choose "Full background" to cover the whole image.', 'kashida-slider' ) ),
					) ),
					array( __( 'Headings, descriptions and buttons', 'kashida-slider' ), array(
						self::p( __( 'Open the Content tab to edit the heading and description, and to set the label and destination URL of the primary button and an optional secondary button. You can also click any text or button inside the preview to jump to its setting.', 'kashida-slider' ) ),
						self::p( __( 'Button URLs must start with http://, https://, mailto: or tel:, or be a relative address such as /contact.', 'kashida-slider' ) ),
					) ),
					array( __( 'Colors, typography and layouts', 'kashida-slider' ), array(
						self::ul( array(
							__( 'Typography: heading and description color, weight and size.', 'kashida-slider' ),
							__( 'Buttons: colors, outline or solid secondary style, corner radius and text size.', 'kashida-slider' ),
							__( 'Layout: Hero Overlay (text over a full image), Split (text on one side, image on the other) or Minimal (typography only). Switching layouts never deletes your content.', 'kashida-slider' ),
							__( 'Layout also controls text alignment, vertical position, maximum text width and content spacing.', 'kashida-slider' ),
						) ),
					) ),
					array( __( 'Transitions, autoplay and navigation', 'kashida-slider' ), array(
						self::p( __( 'In the Animation tab choose a transition: Fade, Crossfade, Slide, Zoom In, Zoom Out, Push or Wipe / Reveal. Slide, Push and Wipe also have a direction. Set the duration in milliseconds and click Preview Animation to see the effect without saving.', 'kashida-slider' ) ),
						self::p( __( 'You can switch autoplay on or off, set the interval, pause on hover, loop infinitely, and show or hide the arrows and pagination dots. Visitors who prefer reduced motion get instant slide changes and autoplay starts paused.', 'kashida-slider' ) ),
						self::p( __( 'On right-to-left sites horizontal directions are mirrored automatically, and the arrow keys and swipe gestures follow the reading direction.', 'kashida-slider' ) ),
					) ),
					array( __( 'Desktop, tablet and mobile settings', 'kashida-slider' ), array(
						self::p( __( 'The Responsive tab sets the slider height for each device, plus heading, description, button and spacing sizes for tablet and mobile. You can also set a different alignment and an optional mobile background image. Use the device buttons above the preview to check the result.', 'kashida-slider' ) ),
					) ),
				),
			),
			array(
				'id'    => 'dynamic',
				'title' => __( 'Dynamic content sliders', 'kashida-slider' ),
				'items' => array(
					array( __( 'How dynamic sliders work', 'kashida-slider' ), array(
						self::p( __( 'Each time the slider is displayed it fetches published content that matches its saved filters. Drafts, private and password-protected items are never shown. Results are cached for a short time (set in Source → Cache duration) and the cache is cleared automatically when content is published, updated or deleted.', 'kashida-slider' ) ),
					) ),
					array( __( 'Blog posts', 'kashida-slider' ), array(
						self::ul( array(
							__( 'Choose "Blog posts" under Content source.', 'kashida-slider' ),
							__( 'Tick one or more categories (and tags) to limit the posts. Leave everything unticked to use all posts.', 'kashida-slider' ),
							__( 'Set the number of posts (1–20), then sort by date, title or menu order, ascending or descending.', 'kashida-slider' ),
							__( 'Show or hide the image, title, excerpt, publication date and button. You can change the button label and the excerpt length.', 'kashida-slider' ),
							__( 'Posts without a featured image use the fallback image if you set one.', 'kashida-slider' ),
						) ),
					) ),
					array( __( 'Selected WordPress pages', 'kashida-slider' ), array(
						self::p( __( 'Choose Selected pages as the content source to build a slider from specific published WordPress pages instead of automatically fetching the latest posts.', 'kashida-slider' ) ),
						self::ul( array(
							__( 'Search pages by title, then tick the pages you want to include. The page picker displays parent and child pages with indentation to reflect the WordPress page hierarchy.', 'kashida-slider' ),
							__( 'Use the up and down arrows in the selected-pages list to control the display order. This order is saved with the slider.', 'kashida-slider' ),
							__( 'Only published pages are offered. Pages in different languages are separate WordPress pages; select the language-specific page IDs you want to display.', 'kashida-slider' ),
							__( 'Page titles, featured images, links and text excerpts are taken from the selected pages. The original page content is not modified.', 'kashida-slider' ),
						) ),
					) ),
					array( __( 'WooCommerce products', 'kashida-slider' ), array(
						self::p( __( 'WooCommerce must be installed and active; otherwise the product source shows a notice and no products are displayed.', 'kashida-slider' ) ),
						self::ul( array(
							__( 'Pick product categories and tags to filter the products.', 'kashida-slider' ),
							__( 'Show the product image, name, price (sale prices are shown with the regular price) and button. The button links to the product page.', 'kashida-slider' ),
							__( 'Products can also be sorted by price. Products hidden from the catalog are skipped.', 'kashida-slider' ),
						) ),
					) ),
					array( __( 'Portfolio projects', 'kashida-slider' ), array(
						self::p( __( 'Portfolios are not part of WordPress itself: each theme or plugin registers its own content type and taxonomies. Kashida Slider therefore lists every public custom post type on your site (except posts, pages and products) so you can choose the one that holds your projects.', 'kashida-slider' ) ),
						self::ul( array(
							__( 'Choose "Portfolio projects", then select the portfolio post type.', 'kashida-slider' ),
							__( 'Its taxonomies and terms (for example project categories) appear as checkboxes; tick the ones to include.', 'kashida-slider' ),
							__( 'Set the number of projects, sorting and which fields to show (image, title, excerpt, button).', 'kashida-slider' ),
							__( 'If no suitable post type exists, the editor says so and you can choose another source.', 'kashida-slider' ),
						) ),
					) ),
				),
			),
			array(
				'id'    => 'carousel',
				'title' => __( 'Carousel sliders', 'kashida-slider' ),
				'items' => array(
					array( __( 'Create and configure a carousel', 'kashida-slider' ), array(
						self::p( __( 'Choose Carousel Slider when creating a new slider. In the Source tab, select Custom images and items to add your own cards, or select Blog, WooCommerce or Portfolio to retrieve published content automatically.', 'kashida-slider' ) ),
						self::p( __( 'Use the Layout tab to select a card style, set the total number of dynamic items in Source, and configure how many cards appear on desktop, tablet and mobile. The gap setting controls spacing between cards.', 'kashida-slider' ) ),
						self::p( __( 'Arrows, pagination dots, autoplay, swipe and keyboard focus are supported where applicable. The carousel adapts to the available width and keeps each item independently linked.', 'kashida-slider' ) ),
						self::p( __( 'In Layout, set outer padding to add space inside the carousel container. Enable or disable the outer border independently from card borders, and configure each border color and width. Set card corner radius separately.', 'kashida-slider' ) ),
						self::p( __( 'Carousel image shape supports Rectangle, Square (1:1) and Circle. Use Image to text spacing to control the gap between each image and its title or text. Set the gap between cards and the number of visible cards independently for desktop, tablet and mobile.', 'kashida-slider' ) ),
						self::p( __( 'In Layout, set outer padding to add space inside the carousel container. Enable or disable the outer border independently from card borders, and configure each border color and width. Set card corner radius separately.', 'kashida-slider' ) ),
						self::p( __( 'Carousel image shape supports Rectangle, Square (1:1) and Circle. Use Image to text spacing to control the gap between each image and its title or text. Set the gap between cards and the number of visible cards independently for desktop, tablet and mobile.', 'kashida-slider' ) ),
					) ),
				),
			),
			array(
				'id'    => 'shortcodes',
				'title' => __( 'Using shortcodes', 'kashida-slider' ),
				'items' => array(
					array( __( 'Find and copy your shortcode', 'kashida-slider' ), array(
						self::p( __( 'Every slider has its own shortcode, shown on its card in the Slider Library and in the editor toolbar. Click the shortcode button to copy it.', 'kashida-slider' ) ),
						array( 'code', '[kashida_slider id="123"]' ),
						self::p( __( 'Replace 123 with your slider\'s ID. The id attribute is the only attribute the shortcode supports.', 'kashida-slider' ) ),
					) ),
					array( __( 'Block editor', 'kashida-slider' ), array(
						self::p( __( 'Add a Shortcode block to your page, paste the shortcode, and update the page.', 'kashida-slider' ) ),
					) ),
					array( __( 'WPBakery and other page builders', 'kashida-slider' ), array(
						self::p( __( 'Add a Raw HTML or Text Block element to the page and paste the shortcode into it. Any builder element that processes WordPress shortcodes will work. The slider fills the width of its container.', 'kashida-slider' ) ),
					) ),
				),
			),
			array(
				'id'    => 'troubleshooting',
				'title' => __( 'Troubleshooting', 'kashida-slider' ),
				'items' => array(
					array( __( 'The slider does not appear', 'kashida-slider' ), array(
						self::ul( array(
							__( 'Check that the shortcode ID matches a slider in the Slider Library. If the slider was deleted, administrators see a notice and visitors see nothing.', 'kashida-slider' ),
							__( 'Make sure the shortcode is in a block or element that processes shortcodes, and that the page was updated and any cache plugin was cleared.', 'kashida-slider' ),
							__( 'Dynamic sliders show nothing (to visitors) when no published content matches their filters.', 'kashida-slider' ),
						) ),
					) ),
					array( __( 'Images do not display', 'kashida-slider' ), array(
						self::p( __( 'Re-select the image in the Background tab. If the image was deleted from the media library, or the site address changed (for example from http to https) after the slider was saved, the stored address no longer works. Also check that the slide height is large enough to show the image.', 'kashida-slider' ) ),
					) ),
					array( __( 'A dynamic slider is empty', 'kashida-slider' ), array(
						self::p( __( 'Remove category or tag filters to see whether any content is returned. Confirm that the content is published (not draft or private) and, for products, visible in the catalog. Several filters narrow the result: items must match every selected taxonomy.', 'kashida-slider' ) ),
					) ),
					array( __( 'WooCommerce options are missing', 'kashida-slider' ), array(
						self::p( __( 'The product source needs WooCommerce to be installed and active. Activate it and reload the editor.', 'kashida-slider' ) ),
					) ),
					array( __( 'Portfolio categories are missing', 'kashida-slider' ), array(
						self::p( __( 'Select the correct portfolio post type first; the list of taxonomies depends on it. Only taxonomies that have at least one term are listed, and the theme or plugin that provides the portfolio must be active.', 'kashida-slider' ) ),
					) ),
					array( __( 'Changes do not appear immediately', 'kashida-slider' ), array(
						self::p( __( 'Click Save before checking the site. Then clear any page-cache, CDN or browser cache. Dynamic sliders refresh automatically when content changes, but cache plugins may serve an older copy of the page.', 'kashida-slider' ) ),
					) ),
					array( __( 'Responsive layout problems', 'kashida-slider' ), array(
						self::p( __( 'Use the Tablet and Mobile preview and the Responsive tab: raise the mobile height if text is cut off, reduce the font sizes, or reduce the content spacing. The Split layout stacks the image and text on screens narrower than 900 px.', 'kashida-slider' ) ),
					) ),
					array( __( 'Shortcode placement problems', 'kashida-slider' ), array(
						self::p( __( 'If the shortcode appears as plain text, it was probably pasted into a field that does not process shortcodes. Use a Shortcode block or a builder element that supports them. Keep the straight quotes exactly as copied.', 'kashida-slider' ) ),
					) ),
					array( __( 'Plugin or theme conflicts', 'kashida-slider' ), array(
						self::ol( array(
							__( 'Temporarily switch to a default theme such as Twenty Twenty-Four, or deactivate other plugins one at a time.', 'kashida-slider' ),
							__( 'If the slider works, the conflict comes from the theme or plugin you disabled; note its name.', 'kashida-slider' ),
							__( 'Enable WP_DEBUG and open your browser console to look for errors from other scripts.', 'kashida-slider' ),
						) ),
						self::p( __( 'Problems that go away when you change a setting or content are configuration issues. A PHP error or a JavaScript error that mentions kashida-slider is a likely plugin defect: report it with the error text and your WordPress and PHP versions.', 'kashida-slider' ) ),
					) ),
				),
			),
			array(
				'id'    => 'faq',
				'title' => __( 'FAQ', 'kashida-slider' ),
				'items' => array(
					array( __( 'Can I turn a Custom slider into a Dynamic one?', 'kashida-slider' ), array( self::p( __( 'No. The mode is fixed when the slider is created. Create a new slider in the other mode; you can keep the original.', 'kashida-slider' ) ) ) ),
					array( __( 'Which content sources are supported?', 'kashida-slider' ), array( self::p( __( 'Blog posts, WooCommerce products and portfolio-type custom post types.', 'kashida-slider' ) ) ) ),
					array( __( 'Can I edit or delete a slider later?', 'kashida-slider' ), array( self::p( __( 'Yes. Use the Slider Library to edit, duplicate or delete any slider. Deleting asks for confirmation and cannot be undone. A page that still contains the shortcode of a deleted slider shows nothing to visitors.', 'kashida-slider' ) ) ) ),
					array( __( 'Does duplicating share settings with the original?', 'kashida-slider' ), array( self::p( __( 'No. A duplicate is an independent copy with its own ID and shortcode.', 'kashida-slider' ) ) ) ),
					array( __( 'Is the slider responsive?', 'kashida-slider' ), array( self::p( __( 'Yes. Height, text sizes, spacing and alignment can be set separately for desktop, tablet and mobile, and the slider never overflows its container.', 'kashida-slider' ) ) ) ),
					array( __( 'Are Arabic and right-to-left layouts supported?', 'kashida-slider' ), array( self::p( __( 'Yes. The interface and the slider follow the site language direction. The plugin is translation-ready; it only appears in a given language if a translation file for that language is installed.', 'kashida-slider' ) ) ) ),
					array( __( 'What do products and portfolios require?', 'kashida-slider' ), array( self::p( __( 'Products require an active WooCommerce. Portfolios require a theme or plugin that registers a public portfolio-type content type.', 'kashida-slider' ) ) ) ),
				),
			),
		);
	}

	/** Echo the Help Center. */
	public static function render() {
		$sections = self::sections();
		$nav      = $sections;
		$nav[]    = array( 'id' => 'info', 'title' => __( 'Plugin information', 'kashida-slider' ) );

		echo '<div class="ks-help">';
		echo '<nav class="ks-help-nav" aria-label="' . esc_attr__( 'Help sections', 'kashida-slider' ) . '"><ul>';
		foreach ( $nav as $s ) {
			echo '<li><a href="#ks-' . esc_attr( $s['id'] ) . '">' . esc_html( $s['title'] ) . '</a></li>';
		}
		echo '</ul></nav><div class="ks-help-body">';

		foreach ( $sections as $si => $s ) {
			echo '<section class="ks-card-box" id="ks-' . esc_attr( $s['id'] ) . '" aria-labelledby="ks-h-' . esc_attr( $s['id'] ) . '"><h2 id="ks-h-' . esc_attr( $s['id'] ) . '">' . esc_html( $s['title'] ) . '</h2>';
			foreach ( $s['items'] as $ii => $item ) {
				echo '<details class="ks-acc"' . ( 0 === $si && 0 === $ii ? ' open' : '' ) . '><summary>' . esc_html( $item[0] ) . '</summary><div class="ks-acc-body">';
				foreach ( $item[1] as $b ) {
					self::block( $b );
				}
				echo '</div></details>';
			}
			echo '</section>';
		}

		echo '<section class="ks-card-box" id="ks-info" aria-labelledby="ks-h-info"><h2 id="ks-h-info">' . esc_html__( 'Plugin information', 'kashida-slider' ) . '</h2><table class="ks-info"><tbody>';
		$rows = array(
			array( __( 'Plugin name', 'kashida-slider' ), 'Kashida Slider' ),
			array( __( 'Installed version', 'kashida-slider' ), KS_VERSION ),
			array( __( 'Developer', 'kashida-slider' ), 'Kashida Media Services' ),
			array( __( 'Author', 'kashida-slider' ), 'Mrwan elGobee' ),
			array( __( 'License', 'kashida-slider' ), 'GPL-2.0-or-later' ),
		);
		foreach ( $rows as $r ) {
			echo '<tr><th scope="row">' . esc_html( $r[0] ) . '</th><td>' . esc_html( $r[1] ) . '</td></tr>';
		}
		echo '<tr><th scope="row">' . esc_html__( 'Website', 'kashida-slider' ) . '</th><td dir="ltr"><a href="https://kashida.ly/" target="_blank" rel="noopener">https://kashida.ly/</a></td></tr>';
		echo '</tbody></table></section></div></div>';
	}

	private static function block( $b ) {
		switch ( $b[0] ) {
			case 'ol':
			case 'ul':
				echo '<' . $b[0] . '>'; // phpcs:ignore WordPress.Security.EscapeOutput -- fixed tag name.
				foreach ( $b[1] as $li ) {
					echo '<li>' . esc_html( $li ) . '</li>';
				}
				echo '</' . $b[0] . '>'; // phpcs:ignore WordPress.Security.EscapeOutput
				break;
			case 'code':
				echo '<p><code class="ks-code" dir="ltr">' . esc_html( $b[1] ) . '</code></p>';
				break;
			default:
				echo '<p>' . esc_html( $b[1] ) . '</p>';
		}
	}
}
