#!/bin/bash
set -e
docker exec -i presta7_new_apache_1 php /var/www/html/install/index_cli.php --domain=localhost:8090 --db_server=database --db_name=stickaz --db_user=stickaz --db_password=stickaz
docker exec -i presta7_new_apache_1 chown -R www-data: /var/www/html/

