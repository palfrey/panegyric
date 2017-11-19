docker-compose up
http://localhost:8080/ make site
http://localhost:8080/wp-admin/plugins.php - click "activate" on Panegyric

Because https://github.com/docker-library/wordpress/issues/200
docker-compose exec wordpress bash
apt-get update && apt-get install -y vim
vim /var/www/html/wp-config.php
define('WP_DEBUG', true);

Add the following to a page
[github_prs org="lshift"]