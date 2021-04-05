#!/bin/bash

set -e


HERE=`dirname $0`
HERE=`cd $HERE; pwd`


time $HERE/export.py --limit_products 100 --skip_images

