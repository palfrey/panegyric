=== Panegyric ===
Contributors: palfrey
Tags: github
Requires PHP: 5.6
Requires at least: 4.9
Tested up to: 4.9
Stable tag: 1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Panegyric is a Wordpress plugin for displaying Github Pull Requests created by a list of organisations and/or users.

== Description ==

Panegyric is a Wordpress plugin for displaying Github Pull Requests created by a list of organisations and/or users. 
It's main usage is for an organisation or user to show off all the cool stuff they've done for open source projects.

To use it, add the `github_prs` shortcode to a page. Simplest form is `[github_prs orgs="lshift"]` (replace `lshift` with your Github organisation name).

Additional parameters are supported as follows:

* `orgs`: Comma-separated list of organisations. Optional, but if you omit this and `users`, it won't do anything. Default is `""`
* `users`: Comma-separated list of users. Optional, but as per `orgs`, it's kinda recommended. Default is `""`.
* `limit`: Number of Pull Requests to show. Default is 10.
* `format`: Format of items. Default is `{$updated_at}: "<a href="{$pr_url}">{$pr_title}</a>" was done by <a href="{$user_url}">{$name}</a> for <a href="{$repo_url}">{$repo_name}</a>`. The various `${variables}` in the default format are all the ones supported currently.

For `format`, if you want to include `"` characters, you should write it with single quotes on the outside, and not do escaping. This is actually [a Wordpress problem](https://core.trac.wordpress.org/ticket/15434), but it doesn't unfortunately do a nice error message, it just fails!

The list of pull requests is determined as follows:

1. For every organisation, get all their public users.
2. Add that to the list of users.
3. For each user, get all their merged pull requests.
4. List in reverse date order every Pull Request that's not in the user list or the `orgs` list. Stop when you hit the `limit`.

The data for these requests are updated once per day (or when someone clicks the relevant date field on the admin page), via the magic of WP Cron (the standard Wordpress update mechanism). You will need to manually update people when you first add them, but the plugin will bug you about that.

Lists have the CSS class `panegyric-list` and the items have the class `panegyric-item`.

== Installation ==

1. install the plugin through the WordPress plugins screen

== Frequently Asked Questions ==

= Where is development for this plugin done? =

At [https://github.com/palfrey/panegyric](https://github.com/palfrey/panegyric)

== Changelog ==

= 1.0 =
* Initial version
