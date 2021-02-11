To start:

 # docker-compose up -d

First time (after start)

 # ./install.sh




To stuff current stickaz db into a mysql container:

    docker run -d --name old_stickaz_db -p 33306:3306 -e MYSQL_ALLOW_EMPTY_PASSWORD=1 mariadb:5.5
    docker exec -it old_stickaz_db mysql -e "create database old"
    cat data/stickaz_com1.sql | docker exec -i old_stickaz_db mysql old


Query data out of the mysql example:


    docker exec -it old_stickaz_db mysql old -e "select count(*) from ps_product_stickaz where json is null"




Interactive php shell and example of product creation:

    docker exec -it presta7_new_apache_1 bash
    # php -a
    php > require_once('./config/config.inc.php');
    php > $p = new Product();
    php > $p->name = [ 1 => "The name", 2 => "Le nom" ];
    php > $p->add();
    php > 





LANG=1
SKIP=1
SHIFT=1
QUERY="select c.id_category+$SHIFT,id_parent+$SHIFT,name,description,link_rewrite from ps_category c, ps_category_lang cl where c.id_category=cl.id_category and active=1 and c.id_category > $SKIP  and id_lang=$LANG"
OUTFILE=categories_$LANG

docker exec -it old_stickaz_db mysql old --batch -e "$QUERY" | tail +2 > $OUTFILE.tsv

# csvify  TODO commmas, quotes ...
cat $OUTFILE.tsv | tr '	' ';' > $OUTFILE.csv






Import categories csv:

cat categories_1.csv | docker exec -i presta7_new_apache_1 php /scripts/import-categories.php

