<?php
/**
 * WooCommerce Admin Sanitization Helper
 *
 * @package WooCommerce\Admin\Helper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Helper_Sanitization Class
 *
 * Provides sanitization functions for admin content.
 */
class WC_Helper_Sanitization {

	/**
	 * Sanitize CSS markup from API responses for safe rendering in admin pages.
	 *
	 * @param string $css The raw CSS to sanitize.
	 *
	 * @return string Sanitized CSS safe for inclusion in style blocks.
	 */
	public static function sanitize_css( $css ) {
		// Handle non-string inputs (return empty string)
		if ( ! is_string( $css ) ) {
			return '';
		}

		// Remove potentially harmful constructs
		$css = preg_replace( '/@import\s+[^;]+;?/', '', $css );

		// Block all data URIs
		$css = preg_replace( '/url\s*\(\s*([\'"]?)data:/i', 'url($1invalid:', $css );

		// Only allow URLs from specific trusted domains and their subdomains
		$css = preg_replace_callback(
			'/url\s*\(\s*([\'"]?)(https?:\/\/[^)]+)\1\s*\)/i',
			function ( $matches ) {
				$url   = $matches[2];
				$quote = $matches[1];

				// Check if URL belongs to allowed domains
				if ( preg_match( '/^https?:\/\/(([\w-]+\.)*woocommerce\.com|
											 ([\w-]+\.)*woocommerce\.test|
											 ([\w-]+\.)*wordpress\.com|
											 ([\w-]+\.)*wp\.com)/ix', $url ) ) {
					// URL is from a trusted domain, keep it
					return "url({$quote}{$url}{$quote})";
				} else {
					// URL is not from a trusted domain, make it ineffective
					return "url({$quote}#blocked-url{$quote})";
				}
			},
			$css
		);

		// Remove HTML tags and PHP
		$css = wp_strip_all_tags( $css );

		// Remove any JavaScript events
		$css = preg_replace( '/\s*expression\s*\(.*?\)/', '', $css );
		$css = preg_replace( '/\s*javascript\s*:/', '', $css );

		// Block other potentially dangerous protocols
		$css = preg_replace( '/(behavior|eval|calc|mocha)(\s*:|\s*\()/i', 'blocked', $css );

		// We assume relative and root-relative URLs are safe because they point to resources on the same domain.

		// Limit size of CSS to prevent DoS
		$css = substr( $css, 0, 100000 );

		return $css;
	}
}
