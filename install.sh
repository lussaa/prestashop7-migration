#!/bin/bash
set -e
docker exec -i presta7_new_apache_1 php /var/www/html/install/index_cli.php --domain=localhost:8090 --db_server=database --db_name=stickaz --db_user=stickaz --db_password=stickaz
docker exec -i presta7_new_apache_1 chown -R www-data: /var/www/html/
echo "Admin URL (pub@prestashop.com/0123456789):"
docker exec -i presta7_new_apache_1 ls -1 /var/www/html | grep admin
docker exec -i presta7_new_apache_1 rm -r /var/www/html/install

