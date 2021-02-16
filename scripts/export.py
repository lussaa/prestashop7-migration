#!/usr/bin/env python

import os
import json
from subprocess import call
import mysql.connector 
import urllib.request
from collections import defaultdict

here = os.path.dirname(os.path.realpath(__file__))


def main():
    # ---- run_export()
    products = export_products()
    download_images(products)


def run_export():
    destination = os.path.realpath(os.path.join(here, '../www-share/data/model.json'))
    categories = export_categories()
    langs = sql_retrieve('SELECT id_lang, iso_code FROM ps_lang')
    download_images(categories)
    full_model = {'categories': categories, 'langs': langs}
    with open(destination, 'wb') as dest:
        j = json.dumps(full_model, indent=4)
        dest.write(j.encode('utf-8'))


def sql_retrieve(query):
    db = mysql.connector.connect(
        host='localhost',
        port='33306',
        user='root',
        database='old')
    c = db.cursor()
    c.execute(query)
    num_fields = len(c.description)
    field_names = [i[0] for i in c.description]
    rows = c.fetchall()
    return [dict(zip(field_names, row)) for row in rows]


def sql_retrieve_raw(query):
    db = mysql.connector.connect(
        host='localhost',
        port='33306',
        user='root',
        database='old')
    c = db.cursor()
    c.execute(query)
    rows = c.fetchall()
    return list(rows)


def sql_get(query):
    res = sql_retrieve(query)
    assert len(res) == 1
    return res[0]


def empty_texts(fields):
    def create():
        return {
            f: {}
            for f in fields
        }
    return create


def export_categories():
    categories = sql_retrieve('SELECT id_category, id_parent FROM ps_category')
    text_fields = ['name', 'description', 'link_rewrite', 'meta_keywords']
    text_fields_list = ', '.join(text_fields)
    text_table = 'ps_category_lang'
    item_id_field = 'id_category'
    flat_texts = sql_retrieve_raw(f"""
        SELECT {item_id_field}, id_lang, {text_fields_list}
        FROM {text_table}""")
    indexed_texts = defaultdict(empty_texts(text_fields))
    for item_id, id_lang, *text_values in flat_texts:
        for field, value in zip(text_fields, text_values):
            indexed_texts[item_id][field][id_lang] = value
    for category in categories:
        for field in text_fields:
            category[field] = indexed_texts[category['id_category']][field]
    return categories


def export_products():
    products = sql_retrieve('SELECT id_product, price, id_category_default, active FROM ps_product')
    text_fields = ['name', 'meta_description', 'link_rewrite', 'meta_keywords']
    img_field = 'image_ids'
    text_fields_list = ', '.join(text_fields)
    text_table = 'ps_product_lang'
    item_id_field = 'id_product'
    flat_texts = sql_retrieve_raw(f"""
        SELECT {item_id_field}, id_lang, {text_fields_list}
        FROM {text_table}""")
    indexed_texts = defaultdict(empty_texts(text_fields))
    images = download_prod_img_data(products)
    for item_id, id_lang, *text_values in flat_texts:
        for field, value in zip(text_fields, text_values):
            indexed_texts[item_id][field][id_lang] = value
    for product in products:
        for field in text_fields:
            product[field] = indexed_texts[product['id_product']][field]
        product[img_field] = images[product['id_product']]
    return products


def download_images(categories):
    for c in categories:
        cid = c['id_category']
        try:
            download_category_image(cid)
        except:
            pass


def download_prod_img_data(products):
    product_images = sql_retrieve_raw('SELECT id_product, id_image FROM ps_image')
    product_images_dict = defaultdict(list)
    for id_product, id_image in product_images:
        product_images_dict[id_product].append(id_image)

    for product_id in product_images_dict:
        for image_id in product_images_dict[product_id]:
            try:
                print("here would be the download_product_image, id: %d" , product_id)
                #download_product_image(pid, image_id)
            except:
                pass

    return product_images_dict;


def download_category_image(cid):
        image_url = f'https://www.stickaz.com/img/c/{cid}.jpg'
        destination_dir = os.path.realpath(os.path.join(here, '../www-share/data/img/c'))
        destination_path = os.path.join(destination_dir, f'{cid}.jpg')
        with urllib.request.urlopen(image_url) as src:
            os.makedirs(destination_dir, exist_ok=True)
            with open(destination_path, 'wb') as dest:
                dest.write(src.read())


def download_product_image(pid, iid):
    image_url = f'https://www.stickaz.com/img/p/{pid}-{iid}.png'
    destination_dir = os.path.realpath(os.path.join(here, '../www-share/data/img/p'))
    destination_path = os.path.join(destination_dir, f'{pid}-{iid}.png')
    with urllib.request.urlopen(image_url) as src:
        os.makedirs(destination_dir, exist_ok=True)
        with open(destination_path, 'wb') as dest:
            dest.write(src.read())

if __name__ == '__main__':
    main()
