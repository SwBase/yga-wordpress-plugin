<?php
/**
 * SPDX-License-Identifier: GPL-3.0-or-later
 * Copyright (C) 2026 Yielder Group B.V.
 *
 * Test fixture, never installed on a real site: what the Stuurlui theme registers on
 * yielder.com, as far as the plugin depends on it. Post type, taxonomies and the field
 * group's keys and names are copied from the live edit screen of a Knowledge Hub item.
 *
 * Also authenticates REST requests from the X-Test-User header (a login), so the tests
 * need no application passwords.
 */

add_action(
	'init',
	static function (): void {
		register_post_type(
			'knowledge_hub',
			array(
				'label'        => 'Knowledge Hubs',
				'public'       => true,
				'show_in_rest' => true,
				// No 'editor' and no 'excerpt', as on yielder.com.
				'supports'     => array( 'title', 'author', 'thumbnail', 'revisions' ),
			)
		);
		register_taxonomy( 'knowledge_hub_category', 'knowledge_hub', array( 'hierarchical' => true, 'show_in_rest' => true ) );
		register_taxonomy( 'knowledge_hub_cat', 'knowledge_hub', array( 'hierarchical' => true, 'show_in_rest' => true ) );
	}
);

add_action(
	'acf/include_fields',
	static function (): void {
		acf_add_local_field_group(
			array(
				'key'      => 'group_knowledge_hub_content',
				'title'    => 'Knowledge Hubs',
				// No show_in_rest, as on yielder.com.
				'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'knowledge_hub' ) ) ),
				'fields'   => array(
					array(
						'key'   => 'field_knowledge_hub_content',
						'name'  => 'knowledge_hub_content',
						'label' => 'Artikel content',
						'type'  => 'wysiwyg',
					),
					array(
						'key'        => 'field_general_settings',
						'name'       => 'settings-group',
						'label'      => 'Instellingen',
						'type'       => 'group',
						'sub_fields' => array(
							array(
								'key'   => 'field_page_excerpt_content',
								'name'  => 'post-excerpt-content',
								'label' => 'Samenvatting',
								'type'  => 'textarea',
							),
							array(
								'key'   => 'field_breadcrumbs_toggle',
								'name'  => 'breadcrumbs',
								'label' => 'Wil je de broodkruimels tonen?',
								'type'  => 'true_false',
							),
						),
					),
				),
			)
		);
	}
);

// Sets and reads a sibling of the excerpt in settings-group, so a test can check that
// writing the excerpt leaves it alone. Sets it the way the edit screen saves the group.
add_action(
	'rest_api_init',
	static function (): void {
		register_rest_route(
			'stuurlui-test/v1',
			'/breadcrumbs',
			array(
				array(
					'methods'             => 'POST',
					'permission_callback' => '__return_true',
					'callback'            => static function ( WP_REST_Request $request ): array {
						update_field(
							'field_general_settings',
							array(
								'field_page_excerpt_content' => 'Oud.',
								'field_breadcrumbs_toggle'   => $request['on'] ? 1 : 0,
							),
							(int) $request['id']
						);
						return array( 'ok' => true );
					},
				),
				array(
					'methods'             => 'GET',
					'permission_callback' => '__return_true',
					'callback'            => static function ( WP_REST_Request $request ): array {
						$group = get_field( 'field_general_settings', (int) $request['id'], false );
						return array( 'on' => (bool) ( $group['field_breadcrumbs_toggle'] ?? false ) );
					},
				),
			)
		);
	}
);

// Reads the fields by name, the way the theme's templates do.
add_action(
	'rest_api_init',
	static function (): void {
		register_rest_route(
			'stuurlui-test/v1',
			'/template',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => static function ( WP_REST_Request $request ): array {
					$id = (int) $request['id'];
					return array(
						'content' => get_field( 'knowledge_hub_content', $id ),
						'excerpt' => get_field( 'settings-group', $id )['post-excerpt-content'] ?? null,
					);
				},
			)
		);
	}
);

add_filter(
	'determine_current_user',
	static function ( $user_id ) {
		$login = $_SERVER['HTTP_X_TEST_USER'] ?? '';
		if ( '' === $login ) {
			return $user_id;
		}
		$user = get_user_by( 'login', $login );
		return $user ? $user->ID : $user_id;
	},
	30
);
