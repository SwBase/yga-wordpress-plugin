<?php
/**
 * SPDX-License-Identifier: GPL-3.0-or-later
 * Copyright (C) 2026 Yielder Group B.V.
 *
 * Updates from the GitHub releases of swbase/yga-wordpress-plugin, installed automatically.
 *
 * The `Update URI` header points at github.com, so WordPress never asks wordpress.org
 * about this plugin and calls the `update_plugins_github.com` filter instead. That filter
 * reads manifest.json from the latest release:
 *
 *     https://github.com/swbase/yga-wordpress-plugin/releases/latest/download/manifest.json
 *     { "version": "0.2.0", "requires": "6.5", "requires_php": "8.1", "tested": "7.1" }
 *
 * A release download, not the GitHub API, so many sites behind one IP do not run into
 * the API's rate limit. The zip's URL is built here from the version, never taken from
 * the manifest: the manifest can only say which release, not where code comes from.
 * WordPress compares the version with the installed one itself.
 */

namespace Yielder\Yga\Updates;

defined( 'ABSPATH' ) || exit;

const SLUG         = 'yga-wordpress-plugin';
const REPOSITORY   = 'https://github.com/swbase/yga-wordpress-plugin';
const MANIFEST_URL = REPOSITORY . '/releases/latest/download/manifest.json';
const CACHE_KEY    = 'yga_wordpress_plugin_release';

add_filter( 'update_plugins_github.com', __NAMESPACE__ . '\\check', 10, 3 );
add_filter( 'plugins_api', __NAMESPACE__ . '\\details', 10, 3 );
add_filter( 'auto_update_plugin', __NAMESPACE__ . '\\auto_update', 10, 2 );
add_action( 'upgrader_process_complete', __NAMESPACE__ . '\\forget', 10, 0 );

/**
 * @param array|false $update      What an earlier filter found, or false.
 * @param array       $plugin_data The plugin's headers.
 * @param string      $plugin_file The plugin's basename.
 * @return array|false
 */
function check( $update, array $plugin_data, string $plugin_file ) {
	if ( plugin_basename( \YGA_WORDPRESS_PLUGIN_FILE ) !== $plugin_file ) {
		return $update;
	}
	$release = latest_release();
	if ( ! $release ) {
		return $update;
	}
	return array(
		'slug'         => SLUG,
		'version'      => $release['version'],
		'url'          => REPOSITORY,
		'package'      => package_url( $release['version'] ),
		'requires'     => $release['requires'],
		'requires_php' => $release['requires_php'],
		'tested'       => $release['tested'],
	);
}

/**
 * The "View details" dialog on the Plugins and Updates screens.
 *
 * @param false|object|array $result What an earlier filter found.
 * @return false|object|array
 */
function details( $result, string $action, object $args ) {
	if ( 'plugin_information' !== $action || SLUG !== ( $args->slug ?? '' ) ) {
		return $result;
	}
	$release = latest_release();
	if ( ! $release ) {
		return $result;
	}
	$notes = REPOSITORY . '/releases/tag/v' . $release['version'];
	return (object) array(
		'name'          => 'Yielder – Stuurlui site extensions',
		'slug'          => SLUG,
		'version'       => $release['version'],
		'author'        => '<a href="https://yielder.com">Yielder Group B.V.</a>',
		'homepage'      => REPOSITORY,
		'requires'      => $release['requires'],
		'requires_php'  => $release['requires_php'],
		'tested'        => $release['tested'],
		'download_link' => package_url( $release['version'] ),
		'sections'      => array(
			'description' => '<p>Yielder\'s additions to the Stuurlui theme on yielder.com. Source code under the GPL v3 or later at <a href="' . esc_url( REPOSITORY ) . '">' . esc_html( REPOSITORY ) . '</a>.</p>',
			'changelog'   => '<p><a href="' . esc_url( $notes ) . '">Release notes for ' . esc_html( $release['version'] ) . '</a></p>',
		),
	);
}

/**
 * Always auto-update this plugin, whatever the toggle on the Plugins screen says.
 * WordPress still honours AUTOMATIC_UPDATER_DISABLED and DISALLOW_FILE_MODS.
 *
 * @param bool|null $update
 * @param object    $item The update offer, with the plugin's basename in `plugin`.
 * @return bool|null
 */
function auto_update( $update, $item ) {
	if ( isset( $item->plugin ) && plugin_basename( \YGA_WORDPRESS_PLUGIN_FILE ) === $item->plugin ) {
		return true;
	}
	return $update;
}

/**
 * Drops the cached manifest after any update, so the next check sees the new state.
 */
function forget(): void {
	delete_site_transient( CACHE_KEY );
}

/**
 * The latest release's manifest, cached for a few hours; a failed fetch is cached for
 * an hour, so a GitHub outage does not slow every admin page.
 *
 * @return array{version: string, requires: string, requires_php: string, tested: string}|null
 */
function latest_release(): ?array {
	$cached = get_site_transient( CACHE_KEY );
	if ( is_array( $cached ) ) {
		return $cached['release'];
	}

	$release  = null;
	$response = wp_remote_get( MANIFEST_URL, array( 'timeout' => 10 ) );
	if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
		$release = parse_manifest( json_decode( wp_remote_retrieve_body( $response ), true ) );
	}

	set_site_transient( CACHE_KEY, array( 'release' => $release ), $release ? 6 * HOUR_IN_SECONDS : HOUR_IN_SECONDS );
	return $release;
}

/**
 * @param mixed $manifest The decoded manifest.json.
 * @return array{version: string, requires: string, requires_php: string, tested: string}|null
 */
function parse_manifest( $manifest ): ?array {
	if ( ! is_array( $manifest ) || ! is_string( $manifest['version'] ?? null ) ) {
		return null;
	}
	// Only a plain x.y.z goes into the package URL.
	if ( ! preg_match( '/^\d+\.\d+\.\d+$/', $manifest['version'] ) ) {
		return null;
	}
	return array(
		'version'      => $manifest['version'],
		'requires'     => (string) ( $manifest['requires'] ?? '' ),
		'requires_php' => (string) ( $manifest['requires_php'] ?? '' ),
		'tested'       => (string) ( $manifest['tested'] ?? '' ),
	);
}

function package_url( string $version ): string {
	return REPOSITORY . '/releases/download/v' . $version . '/' . SLUG . '.zip';
}
