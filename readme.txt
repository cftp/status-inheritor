=== Status Inheritor ===
Contributors: codeforthepeople, johnbillion
Tested up to: 3.5.1
Stable tag: 1.2
Requires at least: 3.5.1

Allows you to publish (or make draft) a post and all it's descendants (children, grandchildren, great-grandchildren, etc, etc) in one go.

== Description ==

Allows you to publish (or make draft) a post and all it's descendants (children, grandchildren, great-grandchildren, etc, etc) in one go.

Provides a checkbox on the post editing screen, just above the publish and update buttons, allowing you to specify that all descendants of the current post inherit the status of the current post.

Why not just use the bulk edit functionality? Sometimes this way is more convenient.

You can hook the `cftp_si_allowed_post_types` filter to control which hierarchical post types are affected by this plugin.

== Installation ==

1. Download and unzip the plugin.
2. Copy the status-inheritor directory into your plugins folder.
3. Visit your Plugins page and activate the plugin.

== Changelog ==

= 1.2 =

* Work with descendants, not just children.
* Change to manual SQL to avoid triggering changes to descendants due to inadvertent save_post actions

= 1.1 =

* Add `cftp_si_allowed_post_types` filter

= 1.0 =

* Work in progress	
