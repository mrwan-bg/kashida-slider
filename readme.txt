=== Kashida Slider ===
Contributors: mrwanbg
Tags: slider, carousel, hero, woocommerce, portfolio
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.4.9
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create custom sliders and responsive carousels from images, posts, WooCommerce products and portfolio projects.

== Description ==
* Three creation modes: Custom Slider, Dynamic Content Slider, and Carousel Slider.
* Responsive carousel layouts with configurable desktop, tablet, and mobile item counts.
* Carousel items can be manually created or generated from Blog, WooCommerce, and Portfolio content.
* Navigation arrows, pagination dots, touch swipe, and autoplay for carousels.
* Slider Library with search, filters, sorting and quick actions (edit, preview, duplicate, copy shortcode, delete).
* Built-in Help Center (works offline, no external requests).
* Visual editor with live desktop / tablet / mobile preview.
* Layout presets: Hero Overlay, Split, Minimal.
* Seven transition effects with direction control and an in-editor "Preview Animation" button.
* No external libraries. Frontend assets load only on pages that use the shortcode.
* Translation-ready (text domain `kashida-slider`) with RTL support. A partial Arabic (ar) translation is included; the POT file is the template for other languages.

Usage: `[kashida_slider id="123"]`

== Installation ==
1. Upload the `kashida-slider` folder to `/wp-content/plugins/` (or upload the ZIP in Plugins > Add New).
2. Activate the plugin.
3. Open Kashida Slider in the admin menu, create a slider, then paste its shortcode into a page, post or WPBakery Text Block.

== Branding assets ==
`assets/img/kashida-logo.svg` (header) and `assets/img/kashida-mark.svg` (footer and admin menu icon). Replace these files to update the branding.

== Changelog ==

= 1.4.9 =
* Addressed Plugin Check findings for escaping, translator comments, preview asset loading, AJAX nonce annotations, and text-domain loading.


= 1.4.8 =
* Completed additional Arabic translations for Help Center sections and creation instructions.
* Updated contributor metadata to mrwanbg and synchronized the internal plugin version.

= 1.4.6 =
* Restore the custom carousel item strip and Add Slide control when switching back to custom images.
* Make the preview wrapper follow the rendered slider/carousel height and re-fit after preview updates.

= 1.4.4 =
* Fixed preview height to follow the configured device height and removed excessive blank space.
* Clear custom carousel image assignments when switching to a dynamic content source.

= 1.4.4 =
* Fix editor preview height by measuring rendered iframe content and allowing the WordPress page to scroll naturally.



= 1.4.2 =
* Use a native WordPress Dashicon for the plugin menu icon.
* Adjust the editor preview layout so the scaled preview is not clipped at the bottom.


= 1.4.1 =
* Removed nested vertical scrolling from the editor settings panel.
* Hid the document scrollbar inside the live preview frame.
* Replaced the WordPress admin menu mark with a dedicated slider icon.
* Reviewed Arabic translation catalog and Help Center terminology.


= 1.3.9 =
* Clarified Carousel column controls for desktop, tablet, and mobile.
* Opening multiple custom images now switches directly to the editable slide Content tab.
* Removed the fixed-height inner admin panel scrollbar so the editor expands with the WordPress page.



= 1.3.8 =
* Added a slider container corner-radius setting for custom, dynamic, and carousel sliders.
* Added multi-select image import for custom carousel items using the WordPress Media Library.
* Increased the maximum number of saved slides/items from 40 to 100.
* Improved editor minimum height to use more of the available WordPress viewport.


= 1.3.7 =
* Added configurable carousel card corner radius.
* Added circular image presentation for carousel items.
* Added responsive side-positioned carousel navigation arrows.
* Clarified carousel columns and layout controls.


= 1.3.6 =
* Apply background, typography, button, and responsive design settings to carousel cards.
* Respect image sizing, focal point, overlay mode, and optional mobile image.
* Hide hero-only layout controls when editing a carousel.


= 1.3.5 =
* Fixed reduced-motion handling so slide transitions remain visible with a short duration.
* Fixed autoplay being treated as user-paused when the operating system requests reduced motion.
* Allowed carousel autoplay under reduced-motion settings while using non-smooth scrolling.
* Synchronized the internal asset version with the plugin version to invalidate stale browser caches.



= 1.3.4 =
* Fixed autoplay callback index tracking.
* Prevented carousel navigation from scrolling the entire page.
* Allowed autoplay to resume after pointer navigation while retaining hover pause.
* Added a safe default transition duration when settings are missing.


= 1.3.3 =
* Fixed transition stacking and animation start-state handling.
* Fixed carousel target index tracking during smooth scrolling and autoplay.
* Improved carousel RTL navigation and scroll synchronization.



= 1.3.0 =
* Fixed outgoing-slide states for Crossfade, Zoom, and Wipe transitions.
* Added a responsive Carousel Slider mode with custom items or dynamic Blog, WooCommerce, and Portfolio sources.
* Added carousel layouts, responsive visible-item counts, navigation, dots, swipe, and autoplay behavior.
* Expanded administration content to use the available WordPress admin width.

= 1.2.0 =
* New Slider Library: card grid with thumbnail, mode, source, slide count, ID, dates, shortcode, search, mode/source filters and sorting.
* Quick actions with icons: Edit, Preview, Duplicate, Copy Shortcode, Delete (accessible confirmation dialog).
* New Help Center (Getting started, Custom guide, Dynamic sliders, Shortcodes, Troubleshooting, FAQ, Plugin information).
* Branded admin header and footer on all plugin pages; menu now has Slider Library, Create New Slider and Help.
* Admin interface refresh using logical CSS properties for RTL; breadcrumbs, icons and focus styles.
* RTL sites: slider arrows, arrow keys, swipe and horizontal transition direction follow the reading direction.
* Partial Arabic translation and updated POT file.
* Existing sliders, IDs, shortcodes and settings are unchanged; no database changes.

= 1.1.0 =
* New transition effects: Crossfade, Zoom In, Zoom Out, Push and Wipe / Reveal, direction setting and Preview Animation button.

= 1.0.0 =
* Initial release.

== 1.3.2 ==
* Fixed non-Fade transition visibility so inactive slides remain hidden and transitions can render correctly.
* Hid the native horizontal carousel scrollbar while preserving touch and programmatic scrolling.
* Improved carousel navigation state, responsive pagination dots, and autoplay progression at the end of a non-looping carousel.
* Kept existing slider settings and shortcode compatibility.

== 1.3.1 ==
* Improved transition handling so all supported effects use a consistent animation path.
* Fixed carousel navigation wrapping, RTL-aware item navigation, and autoplay rescheduling.
* Applied dynamic source visibility settings to carousel images, titles, excerpts, dates, prices, and buttons.
