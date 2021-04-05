#!/bin/bash

set -e

HERE=`dirname $0`
HERE=`cd $HERE; pwd`
DATA_DIR=`cd $HERE/../www-share/data; pwd`

REPO_ROOT=`cd $HERE/..; pwd`
HERE_DIR_NAME=`basename $REPO_ROOT`
PRESTA_CONTAINER=${HERE_DIR_NAME}_apache_1

docker exec -i -u www-data $PRESTA_CONTAINER php -d memory_limit=1024M /www-share/scripts/import-all.php
# + 2 separate scripts for now, as they take longer to run due to image manipulations
#docker exec -i -u www-data $PRESTA_CONTAINER php -d memory_limit=1024M www-share/scripts/import-images.php
