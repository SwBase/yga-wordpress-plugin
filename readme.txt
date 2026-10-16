=== Yielder – Stuurlui site extensions ===
Tags: rest-api, acf
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 0.2.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Yielder's additions to the Stuurlui theme on yielder.com. Lets geo-contentmotor fill Knowledge Hub articles over the REST API.

== Description ==

A Knowledge Hub article (`knowledge_hub`) on yielder.com keeps its body and summary in ACF fields the Stuurlui theme registers without `show_in_rest`. This plugin adds a `geo` REST field to the `knowledge_hub` endpoint that reads and writes those two fields, so the REST API can create a complete article.

The plugin is built for yielder.com and the Stuurlui theme; on any other site it does nothing useful.

= Updates =

The plugin updates itself from the releases at https://github.com/swbase/yga-wordpress-plugin, and turns on automatic updates for itself. To check for a new release, WordPress fetches `manifest.json` from the latest release on github.com; it sends no data about the site beyond what any HTTP request carries.

= Source code =

The complete source, with its tests, is at https://github.com/swbase/yga-wordpress-plugin.

== Installation ==

1. Download `yga-wordpress-plugin.zip` from the latest release on GitHub.
2. Upload it through Plugins → Add New Plugin → Upload Plugin, and activate it.

Later versions install automatically.

== Changelog ==

= 0.2.0 =
* Renamed to yga-wordpress-plugin. Remove 0.1.0 (stuurlui-wp-plugin) before installing.
* Updates itself from the GitHub releases, automatically.
* Licensed under the GPL v3 or later.

= 0.1.0 =
* A `geo` REST field on `knowledge_hub` for the article body and summary.
