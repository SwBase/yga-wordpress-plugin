<?php
/**
 * SPDX-License-Identifier: GPL-3.0-or-later
 * Copyright (C) 2026 Yielder Group B.V.
 *
 * Test fixture, never installed on a real site: runs WordPress's plugin update check
 * with GitHub and api.wordpress.org faked, so a test can offer any manifest.json.
 *
 *     POST /yga-test/v1/update-check  { "manifest": "<body>" }   (null: a 404)
 */

add_action(
	'rest_api_init',
	static function (): void {
		register_rest_route(
			'yga-test/v1',
			'/update-check',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => static function ( WP_REST_Request $request ): array {
					$manifest  = $request['manifest'];
					$requested = array();
					$respond   = static fn ( int $code, string $body ): array => array(
						'headers'  => array(),
						'body'     => $body,
						'response' => array( 'code' => $code, 'message' => '' ),
						'cookies'  => array(),
						'filename' => null,
					);
					add_filter(
						'pre_http_request',
						static function ( $pre, $args, $url ) use ( $manifest, &$requested, $respond ) {
							$requested[] = $url;
							$host        = wp_parse_url( $url, PHP_URL_HOST );
							if ( 'github.com' === $host ) {
								return null === $manifest ? $respond( 404, 'Not Found' ) : $respond( 200, $manifest );
							}
							if ( 'api.wordpress.org' === $host ) {
								return $respond( 200, wp_json_encode( array( 'plugins' => array(), 'translations' => array(), 'no_update' => array() ) ) );
							}
							return $pre;
						},
						10,
						3
					);

					delete_site_transient( 'update_plugins' );
					delete_site_transient( 'yga_wordpress_plugin_release' );
					wp_update_plugins();

					require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
					$file    = 'yga-wordpress-plugin/yga-wordpress-plugin.php';
					$updates = get_site_transient( 'update_plugins' );
					$details = plugins_api( 'plugin_information', array( 'slug' => 'yga-wordpress-plugin' ) );
					return array(
						'update'      => $updates->response[ $file ] ?? null,
						'details'     => is_wp_error( $details ) ? null : $details,
						'auto_update' => apply_filters( 'auto_update_plugin', false, (object) array( 'plugin' => $file ) ),
						'requested'   => $requested,
					);
				},
			)
		);
	}
);
