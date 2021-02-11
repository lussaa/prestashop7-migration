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





SKIP=1
SHIFT=1
QUERY="
    SELECT
        c.id_category+$SHIFT,
        id_parent+$SHIFT,
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
        AND c.id_category > $SKIP
        AND cl1.id_lang=1
        AND cl2.id_lang=2"
OUTFILE=categories_all
docker exec -it old_stickaz_db mysql old --batch  --default_character_set utf8 -e "$QUERY" | tail +2 > $OUTFILE.tsv

# csvify  TODO commmas, quotes ...
cat $OUTFILE.tsv | tr '	' ';' > $OUTFILE.csv






Import categories csv:

cat categories_all.csv | docker exec -i presta7_new_apache_1 php /scripts/import-categories.php




## TODO ##
1 - delete existing categories
2 - copy category images into img/c/<id>.jpg
3 - run the import