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
