<?php
/** Server-side inclusion/exclusion rules. */

namespace Hamgoo\LiveChat;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rules {
	public static function context_labels() {
		return array(
			'front'           => __( 'Front page', 'hamgoo-live-chat' ),
			'home'            => __( 'Blog index', 'hamgoo-live-chat' ),
			'archive'         => __( 'Archives', 'hamgoo-live-chat' ),
			'search'          => __( 'Search results', 'hamgoo-live-chat' ),
			'404'             => __( '404 pages', 'hamgoo-live-chat' ),
			'shop'            => __( 'WooCommerce shop', 'hamgoo-live-chat' ),
			'product'         => __( 'WooCommerce products', 'hamgoo-live-chat' ),
			'product_archive' => __( 'WooCommerce product categories and tags', 'hamgoo-live-chat' ),
			'cart'            => __( 'WooCommerce cart', 'hamgoo-live-chat' ),
			'checkout'        => __( 'WooCommerce checkout', 'hamgoo-live-chat' ),
			'account'         => __( 'WooCommerce account', 'hamgoo-live-chat' ),
		);
	}

	public static function matches_path( $path, $patterns ) {
		$path = '/' . ltrim( rawurldecode( $path ), '/' );
		$path = '/' === $path ? $path : rtrim( $path, '/' );
		foreach ( explode( "\n", $patterns ) as $pattern ) {
			$pattern = trim( $pattern );
			if ( '' === $pattern ) {
				continue;
			}
			$pattern = rawurldecode( $pattern );
			$pattern = '/' === $pattern ? $pattern : rtrim( $pattern, '/' );
			$regex   = str_replace( '\\*', '.*', preg_quote( $pattern, '~' ) );
			if ( 1 === preg_match( '~\A' . $regex . '\z~u', $path ) ) {
				return true;
			}
		}
		return false;
	}

	/** Pure evaluator; all exclusions take precedence over any inclusion. */
	public static function allows( array $settings, array $context ) {
		if ( ! $settings['enabled'] || ! $settings['service_consent'] || ! Settings::valid_key( $settings['widget_key'] ) || $context['private'] ) {
			return false;
		}
		$id = (int) $context['id'];
		if ( in_array( $id, $settings['exclude_ids'], true ) || array_intersect( $context['contexts'], $settings['exclude_contexts'] ) || self::matches_path( $context['path'], $settings['exclude_paths'] ) ) {
			return false;
		}
		if ( 'all' === $settings['mode'] ) {
			return true;
		}
		return in_array( $id, $settings['include_ids'], true ) || in_array( $context['post_type'], $settings['post_types'], true ) || (bool) array_intersect( $context['contexts'], $settings['contexts'] ) || self::matches_path( $context['path'], $settings['include_paths'] );
	}

	public static function current_context() {
		$contexts = array();
		foreach ( array(
			'front'           => 'is_front_page',
			'home'            => 'is_home',
			'archive'         => 'is_archive',
			'search'          => 'is_search',
			'404'             => 'is_404',
			'shop'            => 'is_shop',
			'product'         => 'is_product',
			'product_archive' => 'is_product_taxonomy',
			'cart'            => 'is_cart',
			'checkout'        => 'is_checkout',
			'account'         => 'is_account_page',
		) as $name => $function ) {
			if ( function_exists( $function ) && $function() ) {
				$contexts[] = $name;
			}
		}
		// Read the request path only; never save URLs or visitor information.
		$uri       = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
		$path      = (string) wp_parse_url( $uri, PHP_URL_PATH );
		$home_path = rtrim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
		if ( '' !== $home_path && ( $path === $home_path || 0 === strpos( $path, $home_path . '/' ) ) ) {
			$path = substr( $path, strlen( $home_path ) );
		}
		return array(
			'id'        => is_singular() || is_home() ? (int) get_queried_object_id() : 0,
			'post_type' => is_singular() ? (string) get_post_type( get_queried_object_id() ) : '',
			'contexts'  => $contexts,
			'path'      => $path ? $path : '/',
			'private'   => is_admin() || wp_doing_ajax() || wp_doing_cron() || is_feed() || is_embed() || is_preview() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ),
		);
	}
}
