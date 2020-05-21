=== Paid Memberships Pro - Roles Add On ===
Contributors: strangerstudios, joshlevinson
Tags: pmpro, paid memberships pro, membership, roles
Requires at least: 3.0
Tested up to: 5.4.1
Stable tag: 1.2

Adds a WordPress Role for each Membership Level.

== Description ==

Adds a new WordPress Role for each Membership Level. A member's role will be set to the role for their membership level after checkout.

Custom roles will set the `display_name` as the membership level's name and the `role` as `pmpro_role_x`, where `x` is the membership level's ID. 

Custom role capabilities are equivalent to WordPress `subscriber` role capabilities. You can adjust the role's capabilities using the `pmpro_roles_default_caps` filter in a custom function. For a list of all WordPress core capabilities, visit (https://wordpress.org/support/article/roles-and-capabilities/](https://wordpress.org/support/article/roles-and-capabilities/).

Alternately, you can use a plugin like [User Role Editor](https://wordpress.org/plugins/user-role-editor/) to add or remove custom capabilities via a settings page in the WordPress dashboard.

The plugin will immediately create a new role for every existing or newly added membership level. 

* New members will have their role set after completing checkout. 
* Existing members will keep their previous role. You will need to perform a custom database query to bulk update the roles for existing members, if desired.

If you no longer want to use this plugin, click the "Delete and Deactivate" link in the plugin's action links on the Plugins page in the WordPress dashboard. This will update all users with a custom membership role and set their "role" back to the WordPress "subscriber" default.

This plugin currently requires Paid Memberships Pro. 

Based on the original PMPro Roles plugin by Josh Levinson (josh@joshlevinson.me) in 2013.

== Installation ==

1. Upload the `pmpro-roles` directory to the `/wp-content/plugins/` directory of your site.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. That's it. No settings.

== Frequently Asked Questions ==

= I found a bug in the plugin. =

Please post it in the issues section of GitHub and we'll fix it as soon as we can. Thanks for helping. https://github.com/strangerstudios/pmpro-roles/issues

= I need help installing, configuring, or customizing the plugin. =

Please visit our premium support site at https://www.paidmembershipspro.com for more documentation and our support forums.

== Changelog ==

= 1.2 - 2020-05-21 =
* BUG FIX/ENHANCEMENT: Adding the "read" capability to all custom level roles by default. You can filter custom role capabilities using the `pmpro_roles_default_caps` filter.
* BUG FIX: Repaired issue where the "Delete Roles and Deactivate" link would show when the plugin was inactive.

= 1.1 = 
* Added a "Delete Roles and Deactivate" link to the plugins page to deactivate with a bit more cleanup. Users are given the "Subscriber" role if they had a membership level based role before.

= 1.0 =
* FEATURE: This is the initial version of the plugin.
