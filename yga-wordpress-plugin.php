<?php
/**
 * Plugin Name:       Yielder – Stuurlui site extensions
 * Plugin URI:        https://github.com/swbase/yga-wordpress-plugin
 * Description:       Yielder's additions to the Stuurlui theme on yielder.com. Lets geo-contentmotor fill Knowledge Hub articles over the REST API.
 * Version:           0.2.1
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            Yielder Group B.V.
 * Author URI:        https://yielder.com
 * License:           GPL v3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Update URI:        https://github.com/swbase/yga-wordpress-plugin
 *
 * Kept in yielder-platforms/yga-wordpress-plugin and released on
 * github.com/swbase/yga-wordpress-plugin; the plugin updates itself from those
 * releases (includes/updates.php). Do not edit it on the site.
 *
 * Copyright (C) 2026 Yielder Group B.V.
 *
 * This program is free software: you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
 * PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this
 * program. If not, see <https://www.gnu.org/licenses/>.
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

defined( 'ABSPATH' ) || exit;

const YGA_WORDPRESS_PLUGIN_FILE = __FILE__;

require_once __DIR__ . '/includes/knowledge-hub-rest.php';
require_once __DIR__ . '/includes/updates.php';
