Panegyric
=========

> _Panegyric_, n. a public speech or published text in praise of someone or something

Panegyric is a plugin for displaying Github Pull Requests created by a list of organisations and/or users. It's main usage is for an organisation or user to show off all the cool stuff they've done for open source projects.

Usage
-----
Simplest form is `[github_prs orgs="lshift"]` (replace `lshift` with your Github organisation name).

Additional parameters are supported as follows:
* `orgs`: Comma-separated list of organisations. Optional, but if you omit this and users, it won't do anything. Default is `""`
* `users`: Comma-separated list of users. Optional, but as per `orgs`, it's kinda recommended. Default is `""`.
* `limit`: Number of Pull Requests to show. Default is 10.
* `format`: Format of items. Default is `{$updated_at}: "<a href="{$pr_url}">{$pr_title}</a>" was done by <a href="{$user_url}">{$name}</a> for <a href="{$repo_url}">{$repo_name}</a>`. The various `${variables}` in the default format are all the ones supported currently.

The list of pull requests is determined as follows:
1. For every organisation, get all their public users.
2. Add that to the list of users.
3. For each user, get all their merged pull requests.
4. List in reverse date order every Pull Request that's not in the user list or the `orgs` list. Stop when you hit the `limit`.

These requests are done once per day (or when someone clicks the relevant date field on the admin page), via the magic of WP Cron (the standard Wordpress update mechanism)

Development
-----------

You can do manual Wordpress development in other ways, but here's a sensible way to do things.

1. [Install Docker](https://docs.docker.com/engine/installation/) and [Docker Compose](https://docs.docker.com/compose/install/)
2. Run `docker-compose up`
3. Goto [http://localhost:8080/](http://localhost:8080/) and run through the "setup a Wordpress site" wizard
4. Goto [http://localhost:8080/wp-admin/plugins.php](http://localhost:8080/wp-admin/plugins.php) and click "activate" on Panegyric
5. Goto [http://localhost:8080/wp-admin/edit.php](http://localhost:8080/wp-admin/edit.php) and edit the "Hello World" page. Try adding `[github_prs org="lshift"]` to the page, saving then going to the "Panegyric Admin" tab under "Tools"

Because of an [upstream bug](https://github.com/docker-library/wordpress/issues/200) you should probably enable Wordpress debug manually as follows.

1. In a new terminal (not the one running `docker-compose up`), run `docker-compose exec wordpress bash`
2. In that new session, run `apt-get update && apt-get install -y vim`
3. Then `vim /var/www/html/wp-config.php`
4. Add `define('WP_DEBUG', true);` to the bottom of that page