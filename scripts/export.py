#!/usr/bin/env python

import re
import os
import json
from copy import copy, deepcopy
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
from PIL import Image;
from datetime import datetime, date
from operator import itemgetter


here = os.path.dirname(os.path.realpath(__file__))


"""
TODO
 - import orders
 - solve question with customers gender/title/roles
 - taxes, manufacturers
 """

args = None
warnings = []
errors = []

color_ids = []
size_ids = []

next_id_product_attribute = None


def main():
    global args
    parser = argparse.ArgumentParser(description='Export shop data')
    parser.add_argument('--database', action='store', default='root@localhost:33306/old')
    parser.add_argument('--skip_images', action='store_true')
    parser.add_argument('--images_dir', action='store', default='https://www.stickaz.com/img')
    parser.add_argument('--skip_config', action='store_true')
    parser.add_argument('--www_root', action='store', default='./data/original_www_root')
    parser.add_argument('--limit_products', action='store', type=int, default=None)
    parser.add_argument('--limit_users', action='store', type=int, default=None)
    parser.add_argument('--resize_images',  action='store_true')
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
    original_model = {
        'categories': export_categories(),
        'products': export_products(),
        'config': export_config(),
        'tables': export_tables(),
        'warnings': warnings,
        'errors': errors,
    }
    write_model(original_model, 'original')
    converted_model = convert_model(original_model)
    write_model(converted_model, 'converted')


def export_config():
    return {
        'cookie_key': export_cookie_key(),
    }


def export_cookie_key():
    if args.skip_config:
        return '???'
    config_file = os.path.join(args.www_root, 'html', 'config', 'settings.inc.php')
    regex = r"^define\('_COOKIE_KEY_', '(.*)'\);$"
    with open(config_file) as f:
        content = f.read()
    m = re.search(regex, content, re.MULTILINE)
    assert m
    return m.group(1)


def write_model(full_model, suffix):
    to_write = deepcopy(full_model)
    for name, table in to_write['tables'].items():
        to_write['tables'][name] = factorize_columns(table)
    destination = os.path.realpath(os.path.join(here, f'../www-share/data/model_{suffix}.json'))
    with open(destination, 'wb') as dest:
        j = json.dumps(to_write, indent=4, cls=OurJsonEncoder)
        dest.write(j.encode('utf-8'))

def factorize_columns(table):
    columns = list(table[0].keys())
    rows = [destructure(row, columns) for row in table]
    return {
        'columns': columns,
        'rows': rows,
    }


def destructure(row_dict, columns):
    try:
        return [row_dict[c] for c in columns]
    except KeyError as e:
        print(e)
        print(row_dict)
        print(columns)
        raise

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


def export_tables():
    simple = export_tables_simple()
    special = {
        'ps_cart_product': sql_retrieve(cart_product_query)
    }
    return {
        **simple,
        **special
    }


# De-duplicate rows by merging, as new prestashop has a primary key on these
cart_product_query = """
SELECT
    id_cart,
    id_product,
    id_product_attribute,
    SUM(quantity) as quantity,
    MAX(date_add) as date_add
FROM
    ps_cart_product
GROUP BY
    id_cart,
    id_product,
    id_product_attribute
"""


def export_tables_simple():
    tables = [
        'ps_orders',
        'ps_cart',
        'ps_address',
        'ps_currency',
        'ps_country',
        'ps_country_lang',
        'ps_customer',
        'ps_state',
        'ps_zone',
        'ps_attribute_group',
        'ps_attribute_group_lang',
        'ps_attribute',
        'ps_attribute_lang',
        'ps_product_attribute',
        'ps_product_attribute_combination',
        'ps_lang',
        'ps_order_detail',
        'ps_order_state',
        'ps_order_state_lang',
        'ps_order_history',
        'ps_employee',
        'ps_profile',
        'ps_profile_lang',
        'ps_category',
        'ps_category_group',
        'ps_category_lang',
        'ps_category_product',
        'ps_image',
        'ps_image_type',
        'ps_image_lang',
        'ps_customization_field',
        'ps_customization',
        'ps_customization_field_lang'
    ]
    return {
        table: sql_retrieve(f'SELECT * FROM {table}')
        for table in tables
    }


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
    max_product_id = max(int(product['id_product']) for product in products)

    images = download_product_img_data(max_product_id)
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


def download_product_img_data(max_product_id):
    product_images = sql_retrieve_raw(f'SELECT id_product, id_image FROM ps_image WHERE id_product <= {max_product_id} order by id_product, position asc')
    product_images_dict = defaultdict(list)
    for id_product, id_image in product_images:
        product_images_dict[id_product].append(id_image)

    if args.skip_images:
        return product_images_dict

    pbar = ProgressBar()
    print("Starting download of product images. \n")
    pbar = ProgressBar()
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
    if args.resize_images:
        image = Image.open(destination_path);
        image = image.resize((730,800));
        image.save(destination_path);
        print ("Img {0} resized.".format(destination_path));

class OurJsonEncoder(json.JSONEncoder):
    def default(self, obj):
        if isinstance(obj, Decimal):
            return float(obj)
        elif isinstance(obj, datetime):
            return str(obj)
        elif isinstance(obj, date):
            return str(obj)
        else:
            return json.JSONEncoder.default(self, obj)


def convert_model(model):
    model = copy(model)
    tables = model['tables']
    tables['ps_customer'] = convert_customers(tables['ps_customer'])
    tables['ps_order'] = convert_orders(tables['ps_orders'], tables['ps_order_history'])
    tables['ps_product_attribute'], tables['ps_product_attribute_combination'] = \
        convert_ps_customiztion_to_attributes(
            tables['ps_customization'],
            tables['ps_product_attribute'],
            tables['ps_attribute'],
            tables['ps_product_attribute_combination'])
    #tables['ps_cart'] = convert_cart(tables['ps_cart'])
    return model


def convert_customers(customers):
    # ?   case id_gender when 9 then 0 else id_gender end as gender, 
    valid_users = []
    for user in customers:
        if looks_spammy(user):
            continue  # Drop user without warning
        for field in ['lastname', 'firstname']:
            user[field] = sanitize_name(user[field])
        if validate_email(user['email']):
            del user['username']
            valid_users.append(user)
        else:
            warnings.append(f'Dropped user with invalid email {user["email"]}')
    return first(args.limit_users, valid_users)


def convert_orders(orders, history):
    return list(_convert_orders(orders, history))


def _convert_orders(orders, history):
    for order in orders:
        current_state = most_recent_state(order['id_order'], history)
        order['current_state'] = current_state
        if order['delivery_date'] is None:
            # Column is NOT NULL but some rows have NULL in the database somehow
            order['delivery_date'] = '1970-01-01 00:00:00'
        if order['invoice_date'] is None:
            # Column is NOT NULL but some rows have NULL in the database somehow
            order['invoice_date'] = '1970-01-01 00:00:00'
        yield order

def get_max_id_product_attribute(ps_product_attribute):
    ids = [a['id_product_attribute'] for a in ps_product_attribute]
    return max(ids)


def  delete_cutomized_products_from_tables(table_ps_product_attribute, table_product_attribute_combination , table_customizations):
    customized_product_ids = {customization['id_product'] for customization in table_customizations}
    new_table_product_attribute = [row for row in table_ps_product_attribute if row['id_product'] not in customized_product_ids]
    id_product_attributes_to_keep  = {row['id_product_attribute'] for row in new_table_product_attribute}
    new_table_product_attribute_combination = [row for row in table_product_attribute_combination if row['id_product_attribute'] in id_product_attributes_to_keep]
    return new_table_product_attribute, new_table_product_attribute_combination, customized_product_ids


def convert_ps_customiztion_to_attributes(customizations, ps_product_attribute, ps_attribute, product_attribute_combination):
    ps_product_attribute, product_attribute_combination, customized_product_ids = \
        delete_cutomized_products_from_tables(ps_product_attribute, product_attribute_combination, customizations)

    for id_product in customized_product_ids:
        id_product_attribute = get_max_id_product_attribute(ps_product_attribute)

        for size in size_attributes(ps_attribute):
            id_product_attribute = id_product_attribute + 1
            for color in color_attributes(ps_attribute):
                ps_product_attribute.append({
                    'id_product_attribute': id_product_attribute,
                    'id_product': id_product,
                    'reference': None,
                    'supplier_reference': None,
                    'location': None,
                    'ean13': None,
                    'isbn': None,
                    'upc': None,
                    'mpn': None,
                    'default_on': None,
                    'low_stock_threshold': None,
                    'low_stock_alert': None,
                    'available_date': None,
                    'wholesale_price':0,
                    'price': 0,
                    'ecotax': 0,
                    'quantity': 0,
                    'weight': 0,
                    'unit_price_impact':0,
                    'minimal_quantity':0
                })

                product_attribute_combination.append( { 'id_product_attribute': id_product_attribute, 'id_attribute': color, 'stickaz_qty': None } )
                product_attribute_combination.append( { 'id_product_attribute': id_product_attribute, 'id_attribute': size, 'stickaz_qty': None } )
    return ps_product_attribute, product_attribute_combination


def generate_size_and_color_attribute_list(ps_attribute):
    for row in ps_attribute:
        if(row['color'] is None or ''):
            size_ids.append(row['id_attribute'])
        else:
            color_ids.append(row['id_attribute'])

def size_attributes(ps_attribute):
    global size_ids
    if (size_ids is None or size_ids == []):
        generate_size_and_color_attribute_list(ps_attribute)
        return size_ids
    else:
        return size_ids

def color_attributes(ps_attribute):
    if (color_ids is None):
        generate_size_and_color_attribute_list(ps_attribute)
        return color_ids
    else:
        return color_ids



def most_recent_state(id_order, history):
    history_for_this_order = filter(lambda h: h['id_order'] == id_order, history)
    history_for_this_order = sorted(history_for_this_order, key=itemgetter('date_add'), reverse=True)
    if history_for_this_order:
        return history_for_this_order[0]['id_order_state']
    else:
        return None


def convert_cart(carts):
    carts = copy(carts)
    for cart in carts:
        if cart['secure_key'] == '':
            cart['secure_key'] = {'type': 'sql', 'value': "''"}
    return carts

if __name__ == '__main__':
    main()
