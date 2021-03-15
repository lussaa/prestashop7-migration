#!/bin/bash

set -e

HERE=`dirname $0`
HERE=`cd $HERE; pwd`
DATA_DIR=`cd $HERE/../www-share/data; pwd`

REPO_ROOT=`cd $HERE/..; pwd`
HERE_DIR_NAME=`basename $REPO_ROOT`
PRESTA_CONTAINER=${HERE_DIR_NAME}_apache_1

docker exec -i presta7_new_database_1 mysqldump --skip-extended-insert -B stickaz --user stickaz --password=stickaz
