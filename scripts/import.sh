#!/bin/bash

set -e

HERE=`dirname $0`
HERE=`cd $HERE; pwd`
DATA_DIR=`cd $HERE/../www-share/data; pwd`

REPO_ROOT=`cd $HERE/..; pwd`
HERE_DIR_NAME=`basename $REPO_ROOT`
PRESTA_CONTAINER=${HERE_DIR_NAME}_apache_1

docker exec -i -u www-data $PRESTA_CONTAINER php -d memory_limit=1024M bin/console cache:clear

docker exec -i -u www-data $PRESTA_CONTAINER rm -R /var/www/html/themes/stickaz
docker exec -i -u www-data $PRESTA_CONTAINER ln -s /www-share/themes/stickaz /var/www/html/themes/stickaz
docker exec -i -u www-data $PRESTA_CONTAINER ln -fs /www-share/themes/stickaz/logo-stickaz.png /var/www/html/img/logo-stickaz.png
docker exec -i -u www-data $PRESTA_CONTAINER ln -fs /www-share/themes/stickaz/favicon.ico /var/www/html/img/favicon.ico

docker exec -i -u www-data $PRESTA_CONTAINER php -d memory_limit=1024M /www-share/scripts/import-all.php


docker exec -i -u www-data $PRESTA_CONTAINER ln -Tfs /www-share/modules/stickaz /var/www/html/modules/stickaz
docker exec -i -u www-data $PRESTA_CONTAINER ln -Tfs /www-share/modules/productvariationswidget /var/www/html/modules/productvariationswidget
docker exec -i -u www-data $PRESTA_CONTAINER ln -Tfs /www-share/modules/howitworks /var/www/html/modules/howitworks
docker exec -i -u www-data $PRESTA_CONTAINER ln -Tfs /www-share/modules/infopage /var/www/html/modules/infopage

docker exec -i -u www-data $PRESTA_CONTAINER php bin/console prestashop:module install stickaz
docker exec -i -u www-data $PRESTA_CONTAINER php bin/console prestashop:module install productvariationswidget
docker exec -i -u www-data $PRESTA_CONTAINER php bin/console prestashop:module install howitworks
docker exec -i -u www-data $PRESTA_CONTAINER php bin/console prestashop:module install infopage

