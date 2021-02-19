#!/usr/bin/env python

import re
import os
import json
import traceback
from subprocess import call
import argparse
from collections import defaultdict
import urllib.request
from decimal import Decimal
from progressbar import ProgressBar
from validate_email import validate_email
import mysql.connector
import itertools
import mysql.connector 


here = os.path.dirname(os.path.realpath(__file__))


args = None
warnings = []
errors = []


def main():
    global args
    parser = argparse.ArgumentParser(description='Export shop data')
    parser.add_argument('--database', action='store', default='root@localhost:33306/old')
    parser.add_argument('--skip_images', action='store_true')
    parser.add_argument('--images_dir', action='store', default='https://www.stickaz.com/img')
    parser.add_argument('--skip_config', action='store_true')
    parser.add_argument('--www_root', action='store', default='/run/media/seb/share_seb/old_prestashop_copy/www-root')
    parser.add_argument('--limit_products', action='store', type=int, default=None)
    parser.add_argument('--limit_users', action='store', type=int, default=None)
    args = parser.parse_args()
    run_export()


db = None


def connect_to_database():
    global db
    m = re.match('(.*)@(.*):(.*)/(.*)', args.database)
    assert m
    db = mysql.connector.connect(
        host=m.group(2),
        port=m.group(3),
        user=m.group(1),
        database=m.group(4))


def run_export():
    connect_to_database()
    full_model = {
        'langs': export_langs(),
        'categories': export_categories(),
        'products': export_products(),
        'users': export_users(),
        'config': {
            'cookie_key': export_cookie_key(),
        },
        'warnings': warnings,
        'errors': errors,
    }
    write_model(full_model)


def export_cookie_key():
    config_file = os.path.join(args.www_root, 'html', 'config', 'settings.inc.php')
    regex = r"^define\('_COOKIE_KEY_', '(.*)'\);$"
    with open(config_file) as f:
        content = f.read()
    m = re.search(regex, content, re.MULTILINE)
    assert m
    return m.group(1)


def write_model(full_model):
    destination = os.path.realpath(os.path.join(here, '../www-share/data/model.json'))
    with open(destination, 'wb') as dest:
        j = json.dumps(full_model, indent=4, cls=DecimalEncoder )
        dest.write(j.encode('utf-8'))


def sql_retrieve(query):
    c = db.cursor()
    c.execute(query)
    num_fields = len(c.description)
    field_names = [i[0] for i in c.description]
    rows = c.fetchall()
    return [dict(zip(field_names, row)) for row in rows]


def sql_retrieve_raw(query):
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


def export_langs():
    return sql_retrieve('SELECT id_lang, iso_code FROM ps_lang')


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
    if not args.skip_images:
        download_images(categories)
    return categories


def export_users():
    # ?   case id_gender when 9 then 0 else id_gender end as gender, 
    users = sql_retrieve('SELECT id_customer, id_gender, firstname, lastname, username, passwd, email, newsletter FROM ps_customer')
    valid_users = []
    for user in users:
        if looks_spammy(user):
            continue  # Drop user without warning
        for field in ['lastname', 'firstname']:
            user[field] = sanitize_name(user[field])
        if validate_email(user['email']):
            valid_users.append(user)
        else:
            warnings.append(f'Dropped user with invalid email {user["email"]}')
    return first(args.limit_users, valid_users)


def looks_spammy(user):
    return user['lastname'].startswith('www.')


def first(limit, collection):
    if limit is not None:
        return list(itertools.islice(collection, limit))
    else:
        return collection


def sanitize_name(name):
    name = re.sub(r'[/]', r' ', name)
    name = re.sub(r'\.\.', r'. .', name)
    name = re.sub(r'\.([^ ])', r'. \1', name)
    name = name.strip()
    if name == '':
        name = '.'
    return name


def export_products():
    products = sql_retrieve('SELECT id_product, price, id_category_default, active FROM ps_product')
    products = first(args.limit_products, products)
    text_fields = ['name', 'meta_description', 'link_rewrite', 'meta_keywords']
    img_field = 'image_ids'
    text_fields_list = ', '.join(text_fields)
    text_table = 'ps_product_lang'
    item_id_field = 'id_product'
    flat_texts = sql_retrieve_raw(f"""
        SELECT {item_id_field}, id_lang, {text_fields_list}
        FROM {text_table}""")
    indexed_texts = defaultdict(empty_texts(text_fields))
    images = download_product_img_data(products)
    for item_id, id_lang, *text_values in flat_texts:
        for field, value in zip(text_fields, text_values):
            indexed_texts[item_id][field][id_lang] = value
    for product in products:
        for field in text_fields:
            product[field] = indexed_texts[product['id_product']][field]
        product[img_field] = images[product['id_product']]
    return products


def download_images(categories):
    print("Starting download of category images. \n")
    pbar = ProgressBar()
    for c in pbar(categories):
        cid = c['id_category']
        try:
            download_category_image(cid)
        except Exception as e:
            errors.append(str(e))
            pass


def download_product_img_data(products):
    product_images = sql_retrieve_raw('SELECT id_product, id_image FROM ps_image order by id_product, position asc')
    product_images_dict = defaultdict(list)
    for id_product, id_image in product_images:
        product_images_dict[id_product].append(id_image)

    if args.skip_images:
        return product_images_dict

    print("Starting download of product images. \n")
    for product_id in pbar(product_images_dict):
        for image_id in product_images_dict[product_id]:
            try:
                download_product_image(product_id, image_id)
            except:
                traceback.print_exc()

    return product_images_dict


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

class DecimalEncoder (json.JSONEncoder):
    def default (self, obj):
       if isinstance (obj, Decimal):
           return int (obj)
       return json.JSONEncoder.default (self, obj)


if __name__ == '__main__':
    main()
