To start:

 # docker-compose up -d

First time (after start)

 # ./install.sh


To stuff current(old) stickaz db into a mysql container:

    docker exec -it old_stickaz_db mysql -e "create database old"
    (echo "SET FOREIGN_KEY_CHECKS=0;" ; cat data/stickaz_com1.sql) | docker exec -i old_stickaz_db mysql old


Query data out of the mysql example:


    docker exec -it old_stickaz_db mysql old -e "select count(*) from ps_product_stickaz where json is null"


Export / Import:

# ./scripts/export.sh
# ./scripts/import.sh


Cleanup (before reinstalling):
 # docker-compose down
 # docker volume rm presta7_new_database
 # git clean -xd (or remove individual folders, see a list with: git clean -xdn)





Interactive php shell and example of product creation:

    docker exec -it presta7_new_apache_1 bash
    # php -a
    php > require_once('./config/config.inc.php');
    php > $p = new Product();
    php > $p->name = [ 1 => "The name", 2 => "Le nom" ];
    php > $p->add();
    php > 


