<?php
/**
 * SPDX-License-Identifier: GPL-3.0-or-later
 * Copyright (C) 2026 Yielder Group B.V.
 *
 * A `geo` field on the knowledge_hub REST endpoint, for the article body and summary.
 *
 * The Stuurlui theme stores a Knowledge Hub article in ACF fields it registers in PHP,
 * without `show_in_rest`, and the post type has no editor. So the REST API creates a
 * Knowledge Hub item but cannot fill it: `content` and `acf` are dropped without a word.
 * This field reads and writes the two ACF fields geo-contentmotor fills, by the theme's
 * field keys, and leaves the rest of the theme's field group alone.
 *
 *     POST /wp-json/wp/v2/knowledge_hub
 *     { "title": "...", "status": "draft", "geo": { "content": "<p>...</p>", "excerpt": "..." } }
 *
 * The field is only in the `edit` context: the create response carries it, so a client
 * can check its write landed, and anonymous readers never see it.
 */

namespace Yielder\Yga\KnowledgeHubRest;

use WP_Error;
use WP_Post;

defined( 'ABSPATH' ) || exit;

const POST_TYPE  = 'knowledge_hub';
const REST_FIELD = 'geo';

// The theme's field keys. If Stuurlui renames one, writes fail with 500 instead of
// silently dropping the article — see fields_missing().
const CONTENT_FIELD  = 'field_knowledge_hub_content';  // wysiwyg, "Artikel content"
const SETTINGS_GROUP = 'field_general_settings';       // group "settings-group"
const EXCERPT_FIELD  = 'field_page_excerpt_content';   // textarea "Samenvatting", in that group

add_action( 'rest_api_init', __NAMESPACE__ . '\\register' );

function register(): void {
	register_rest_field(
		POST_TYPE,
		REST_FIELD,
		array(
			'get_callback'    => __NAMESPACE__ . '\\read',
			'update_callback' => __NAMESPACE__ . '\\update',
			'schema'          => array(
				'description'          => 'Article body and summary, stored in the theme\'s ACF fields.',
				'type'                 => 'object',
				'context'              => array( 'edit' ),
				'additionalProperties' => false,
				'properties'           => array(
					'content' => array(
						'description' => 'Article body as HTML.',
						'type'        => 'string',
					),
					'excerpt' => array(
						'description' => 'Plain-text summary shown on overview cards.',
						'type'        => 'string',
					),
				),
			),
		)
	);
}

/**
 * @param array $post The post as the controller prepared it so far.
 */
function read( array $post ): ?array {
	if ( fields_missing() ) {
		return null;
	}
	$settings = get_field( SETTINGS_GROUP, $post['id'], false );
	return array(
		'content' => (string) get_field( CONTENT_FIELD, $post['id'], false ),
		'excerpt' => (string) ( is_array( $settings ) ? ( $settings[ EXCERPT_FIELD ] ?? '' ) : '' ),
	);
}

/**
 * Runs after the post is inserted or updated, so $post always has an ID.
 *
 * @return true|WP_Error
 */
function update( array $value, WP_Post $post ) {
	$missing = fields_missing();
	if ( $missing ) {
		return new WP_Error(
			'yielder_acf_fields_missing',
			sprintf( 'The theme no longer registers the ACF field(s) %s.', implode( ', ', $missing ) ),
			array( 'status' => 500 )
		);
	}

	if ( array_key_exists( 'content', $value ) ) {
		// post_content goes through kses for users without unfiltered_html; an ACF
		// field does not, so do it here.
		$content = current_user_can( 'unfiltered_html' ) ? $value['content'] : wp_kses_post( $value['content'] );
		update_field( CONTENT_FIELD, $content, $post->ID );
	}

	if ( array_key_exists( 'excerpt', $value ) ) {
		// The summary lives in a group with the page's other settings (breadcrumbs, menu
		// colour, CTA buttons), and update_field on a group blanks every sub field it is
		// not given. So write back what is there, with only the summary changed.
		$settings = get_field( SETTINGS_GROUP, $post->ID, false );
		$settings = is_array( $settings ) ? $settings : array();

		$settings[ EXCERPT_FIELD ] = sanitize_textarea_field( $value['excerpt'] );
		update_field( SETTINGS_GROUP, $settings, $post->ID );
	}

	return true;
}

/**
 * @return string[] The field keys ACF does not know. Empty when all is well.
 */
function fields_missing(): array {
	if ( ! function_exists( 'acf_get_field' ) ) {
		return array( 'ACF itself' );
	}
	return array_values(
		array_filter(
			array( CONTENT_FIELD, SETTINGS_GROUP, EXCERPT_FIELD ),
			static fn ( string $key ): bool => ! acf_get_field( $key )
		)
	);
}
