#!/bin/bash

HERE=`dirname $0`
HERE=`cd $HERE; pwd`

REPO_ROOT=$HERE
HERE_DIR_NAME=`basename $REPO_ROOT`
PRESTA_CONTAINER=${HERE_DIR_NAME}_apache_1

set -e
docker exec -i -u www-data $PRESTA_CONTAINER php /var/www/html/install/index_cli.php --domain=localhost:8090 --db_server=database --db_name=stickaz --db_user=stickaz --db_password=stickaz
docker exec -i $PRESTA_CONTAINER rm -r /var/www/html/install
docker exec -i $PRESTA_CONTAINER mv /var/www/html/admin /var/www/html/bo

docker exec -i $PRESTA_CONTAINER ln -s /www-share/custom_pages /var/www/html/custom_pages
docker exec -i $PRESTA_CONTAINER ln -s /www-share/modules/stickaz /var/www/html/modules/stickaz

docker exec -i $PRESTA_CONTAINER ln -s /www-share/modules/productvariationswidget /var/www/html/modules/productvariationswidget
docker exec -i $PRESTA_CONTAINER ln -s /www-share/themes/stickaz /var/www/html/themes/stickaz


docker exec -i -u www-data $PRESTA_CONTAINER php bin/console prestashop:module install stickaz
docker exec -i -u www-data $PRESTA_CONTAINER php bin/console prestashop:module install productvariationswidget
