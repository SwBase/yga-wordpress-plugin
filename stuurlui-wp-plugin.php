<?php
/**
 * Plugin Name:       Yielder – Stuurlui site extensions
 * Description:       Yielder's additions to the Stuurlui theme on yielder.com. Lets geo-contentmotor fill Knowledge Hub articles over the REST API.
 * Version:           0.1.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            Yielder Group
 * Update URI:        false
 *
 * Kept in yielder-platforms/yielder-generic-apps, packages/stuurlui-wp-plugin. Install
 * the zip `just stuurlui-wp-plugin::zip` builds; do not edit it on the site.
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/includes/knowledge-hub-rest.php';
