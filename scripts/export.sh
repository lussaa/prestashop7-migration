#!/bin/bash

set -e

HERE=`dirname $0`
HERE=`cd $HERE; pwd`
DEST_DIR=`cd $HERE/../www-share/data; pwd`

DB_CONTAINER=${DB_CONTAINER:-old_stickaz_db}
DB_NAME=${DB_NAME:-old}

export_one() {
    QUERY=$1
    DEST=$2
    DB_CONTAINER=$3
    DB_NAME=$4
    docker exec -it $DB_CONTAINER mysql $DB_NAME --batch  --default_character_set utf8 -e "$QUERY" | tail +1 > $DEST.tsv
    # csvify  TODO commmas, quotes ...
    cat $DEST.tsv | tr '	' ';' > $DEST.csv
    echo "Generated: $DEST.csv"
}

CATEGORIES_QUERY="
    SELECT
        c.id_category,
        id_parent,
        cl1.name,
        cl1.description,
        cl1.link_rewrite,
        cl1.meta_keywords,
        cl2.name,
        cl2.description,
        cl2.link_rewrite,
        cl2.meta_keywords
    FROM
        ps_category c,
        ps_category_lang cl1,
        ps_category_lang cl2
    WHERE
        c.id_category=cl1.id_category
        AND c.id_category=cl2.id_category
        AND active=1
        AND cl1.id_lang=1
        AND cl2.id_lang=2"

export_one "$CATEGORIES_QUERY" $DEST_DIR/categories $DB_CONTAINER $DB_NAME
