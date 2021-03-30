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
kaz_sizes = {}

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
        'ps_attribute_impact',
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
        #'ps_image_type',
        'ps_image_lang',
        'ps_customization_field',
        'ps_customization',
        'ps_customization_field_lang',
        'ps_product',
        'ps_product_lang',
        'ps_product_attribute',
        'ps_product_attribute_combination',
        'ps_tag',
        'ps_product_tag',
        'ps_product_stickaz',
        'ps_product_sale',

        'ps_group',
        'ps_category_group',
        'ps_customer_group',
        'ps_group_lang',
    ]
    return {
        table: sql_retrieve(f'SELECT * FROM {table}')
        for table in tables
    }


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
        image = Image.open(destination_path)
        image = image.resize((730, 800))
        image.save(destination_path)
        print("Img {0} resized.".format(destination_path))
        
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
    tables['ps_currency'], tables['ps_currency_lang'] = convert_currencies(tables['ps_currency'], tables['ps_lang'])
    tables['ps_employee'] = convert_employees(tables['ps_employee'])
    tables['ps_attribute_group'] = convert_attribute_group(tables['ps_attribute_group'])
    tables['ps_attribute'] = convert_attribute(tables['ps_attribute'])
    tables['ps_product'], product_ids_to_keep = convert_products(tables['ps_product'])
    tables['ps_cart'], tables['ps_cart_product'] = convert_cart_etc(tables['ps_cart'], tables['ps_cart_product'], product_ids_to_keep)
    tables['ps_order_detail'], order_ids_to_keep = convert_order_detail(tables['ps_order_detail'], product_ids_to_keep)
    tables['ps_orders'] = convert_orders(tables['ps_orders'], tables['ps_order_history'], order_ids_to_keep)
    tables['ps_order_history'] = [oh for oh in tables['ps_order_history'] if oh['id_order'] in order_ids_to_keep]
    tables['ps_product_tag'] = [pt for pt in tables['ps_product_tag'] if pt['id_product'] in product_ids_to_keep]
    tables['ps_product_stickaz'] = [ps for ps in tables['ps_product_stickaz'] if ps['id_product'] in product_ids_to_keep]
    tables['ps_product_sale'] = [ps for ps in tables['ps_product_sale'] if ps['id_product'] in product_ids_to_keep]
    tables['ps_product_lang'] = convert_product_lang(tables['ps_product_lang'], product_ids_to_keep)
    tables['ps_product_attribute'] = convert_product_attribute(tables['ps_product_attribute'], product_ids_to_keep)
    tables['ps_product_attribute'], tables['ps_product_attribute_combination'] = \
        convert_ps_customiztion_to_attributes(
            product_ids_to_keep,
            tables['ps_customization'],
            tables['ps_product_attribute'],
            tables['ps_attribute'],
            tables['ps_attribute_lang'],
            tables['ps_product_attribute_combination'])
    tables['ps_product_shop'] = deepcopy(tables['ps_product'])
    for p in tables['ps_product_shop']:
        p['id_shop'] = 1
        del p['id_supplier']
        del p['id_manufacturer']
        del p['cache_has_attachments']
        del p['cache_is_pack']
        del p['depth']
        del p['ean13']
        del p['location']
        del p['out_of_stock']
        del p['quantity']
        del p['quantity_discount']
        del p['reference']
        del p['upc']
        del p['supplier_reference']
        del p['height']
        del p['weight']
        del p['width']
    tables['ps_image'] = convert_ps_image(tables['ps_image'], tables['ps_product'])
    tables['ps_image_shop'] = deepcopy(tables['ps_image'])
    for p in tables['ps_image_shop']:
        p['id_shop'] = 1
        del p['position']
    tables['ps_image_lang'] = convert_ps_image_lang(tables['ps_image_lang'], tables['ps_image'], 'id_image')
    tables['ps_category_product'] = dedupe(tables['ps_category_product'], {'id_category', 'id_product'})
    tables['ps_category_product'] = [cp for cp in tables['ps_category_product'] if cp['id_product'] in product_ids_to_keep]
    tables['ps_category_shop'] = [{'id_category': c['id_category'], 'id_shop': 1} for c in tables['ps_category']]
    tables['ps_group_shop'] = [{'id_group': g['id_group'], 'id_shop': 1} for g in tables['ps_group']]
    tables['ps_shop'] = [{'id_shop': 1, 'id_shop_group': 1, 'name': 'Stickaz', 'id_category': 1, 'theme_name': 'classic', 'active': 1, 'deleted': 0}]
    tables['ps_address'] = convert_addresses(tables['ps_address'])
    return model


def dedupe(rows, key_columns):
    def key(row):
        return tuple(row[column] for column in key_columns)
    by_key = {
        key(r): r
        for r in rows
    }
    return list(by_key.values())


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


def convert_currencies(currencies, langs):
    converted_currencies = copy(currencies)
    currency_lang = []
    for c in converted_currencies:
        for lang in langs:
            currency_lang.append({
                'id_currency': c['id_currency'],
                'id_lang': lang['id_lang'],
                'name': c['name'],
                'symbol': c['sign']
            })
        c['numeric_iso_code'] = c['iso_code_num'];
        del c['iso_code_num']
        del c['sign']
        del c['blank']
        del c['format']
        del c['decimals']
    return converted_currencies, currency_lang


def convert_employees(employees):
    converted = copy(employees)
    for e in converted:
        del e['bo_uimode']
    return converted


def convert_orders(orders, history, order_ids_to_keep):
    return list(_convert_orders(orders, history, order_ids_to_keep))


def _convert_orders(orders, history, order_ids_to_keep):
    for order in orders:
        order = copy(order)
        if order['id_order'] not in order_ids_to_keep:
            continue
        current_state = most_recent_state(order['id_order'], history)
        order['current_state'] = current_state
        if order['delivery_date'] is None:
            # Column is NOT NULL but some rows have NULL in the database somehow
            order['delivery_date'] = '1970-01-01 00:00:00'
        if order['invoice_date'] is None:
            # Column is NOT NULL but some rows have NULL in the database somehow
            order['invoice_date'] = '1970-01-01 00:00:00'
        order['reference'] = f'M{order["id_order"]}'
        yield order


def convert_order_detail(ps_order_detail, product_ids_to_keep):
    ps_order_detail = [od for od in ps_order_detail if od['product_id'] in product_ids_to_keep]
    order_ids_to_keep = {od['id_order'] for od in ps_order_detail}
    for od in ps_order_detail:
        for field in ['product_supplier_reference']:
            if od[field] is None:
                od[field] = ''
    return ps_order_detail, order_ids_to_keep


def convert_attribute_group(ps_attriute_group):
    for row in ps_attriute_group:
        if row['is_color_group'] == 1:
            row['group_type'] = 'color'
        else:
            row['group_type'] = 'radio'
        row['position'] = 0
    return ps_attriute_group


def convert_attribute(ps_attriute):
    for row in ps_attriute:
        if row['color'] is None:
            row['color'] = ''
    return ps_attriute


def convert_product_attribute(ps_product_attribute, product_ids_to_keep):
    ps_product_attribute = [pa for pa in ps_product_attribute if pa['id_product'] in product_ids_to_keep]
    for row in ps_product_attribute:
        if row['default_on'] == 0:
            row['default_on'] = None
    return ps_product_attribute


def get_max_id_product_attribute(ps_product_attribute):
    ids = [a['id_product_attribute'] for a in ps_product_attribute]
    return max(ids)


def delete_cutomized_products_from_tables(product_ids_to_keep, table_ps_product_attribute, table_product_attribute_combination , table_customizations):
    customized_product_ids = {customization['id_product'] for customization in table_customizations if customization['id_product'] in product_ids_to_keep}
    table_product_attribute_without_customized = [
        row for row in table_ps_product_attribute
        if row['id_product'] not in customized_product_ids and row['id_product'] in product_ids_to_keep
    ]
    product_attributes_only_black_customizable = {
        row['reference']: row for row in table_ps_product_attribute
        if row['id_product'] in customized_product_ids and row['id_product'] in product_ids_to_keep
    }
    id_product_attributes_without_customized = {row['id_product_attribute'] for row in table_product_attribute_without_customized}
    new_table_product_attribute_combination = [row for row in table_product_attribute_combination if row['id_product_attribute'] in id_product_attributes_without_customized]
    return product_attributes_only_black_customizable, table_product_attribute_without_customized, new_table_product_attribute_combination, customized_product_ids


def convert_ps_image(ps_image, products, identifier = 'id_product'):
    products_to_keep = first(args.limit_products, products)
    ids_to_keep = {p[identifier] for p in products_to_keep}
    new_table = [
        row for row in ps_image
        if row[identifier] in ids_to_keep
    ]
    for row in new_table:
        if row['cover'] == 0 : row['cover'] = None
    return new_table


def convert_ps_image_lang(ps_image_lang, ps_image, identifier = 'id_image'):
    imgs_to_keep = {p[identifier] for p in ps_image}
    new_table = [
        row for row in ps_image_lang
        if row[identifier] in imgs_to_keep
    ]
    return new_table


def convert_products(products):
    products_to_keep = first(args.limit_products, products)
    product_ids_to_keep = {p['id_product'] for p in products_to_keep}
    for p in products_to_keep:
        del p['id_color_default']
        for field in ['supplier_reference']:
            if p[field] is None:
                p[field] = ''
    return products_to_keep, product_ids_to_keep


def convert_ps_customiztion_to_attributes(product_ids_to_keep, customizations, ps_product_attribute, ps_attribute, ps_attribute_lang, product_attribute_combination):

    product_attributes_only_black_customizable, ps_product_attribute, product_attribute_combination, customized_product_ids = \
        delete_cutomized_products_from_tables(product_ids_to_keep, ps_product_attribute, product_attribute_combination, customizations)

    for id_product in customized_product_ids:
        id_product_attribute = get_max_id_product_attribute(ps_product_attribute)
        _kaz_sizes_dict = kaz_sizes_dict(ps_attribute, ps_attribute_lang)

        for key_idsize in _kaz_sizes_dict:
            reference = str(id_product) + "-s" + str(_kaz_sizes_dict[key_idsize])
            black_product_attributes = product_attributes_only_black_customizable[reference]

            for color in color_id_attributes(ps_attribute, ps_attribute_lang):
                id_product_attribute = id_product_attribute + 1
                ps_product_attribute.append({
                    'id_product_attribute': id_product_attribute,
                    'id_product': id_product,
                    'reference': black_product_attributes['reference'],
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
                    'price': black_product_attributes['price'],
                    'ecotax': 0,
                    'quantity': 0,
                    'weight': black_product_attributes['weight'],
                    'unit_price_impact':0,
                    'minimal_quantity':0
                })
                product_attribute_combination.append( { 'id_product_attribute': id_product_attribute, 'id_attribute': color} )
                product_attribute_combination.append( { 'id_product_attribute': id_product_attribute, 'id_attribute': key_idsize} )
    for pac in product_attribute_combination:
        if 'stickaz_qty' in pac:
            del pac['stickaz_qty']
    # product 991 has dupes
    ps_product_attribute = [pa for pa in ps_product_attribute if (pa['id_product'] != 991 or pa['id_product_attribute'] < 4793) and pa['id_product'] in product_ids_to_keep]

    return ps_product_attribute, product_attribute_combination


def generate_size_and_color_attribute_list(ps_attribute, ps_attribute_lang):
    size_ids = { row['id_attribute'] for row in ps_attribute if row['color'] is None or row['color']  == ''}
    for row in ps_attribute_lang:
        if row['id_lang'] == 1 and row['id_attribute'] in size_ids:
            kaz_sizes[row['id_attribute']] = row['name']
        else:
            color_ids.append(row['id_attribute'])


def kaz_sizes_dict(ps_attribute, ps_attribute_lang):
    global kaz_sizes
    if kaz_sizes is None or kaz_sizes == {}:
        generate_size_and_color_attribute_list(ps_attribute, ps_attribute_lang)
        return kaz_sizes
    else:
        return kaz_sizes


def color_id_attributes(ps_attribute, ps_attribute_lang):
    if color_ids is None:
        generate_size_and_color_attribute_list(ps_attribute, ps_attribute_lang)
        return color_ids
    else:
        return color_ids


def convert_product_lang(ps_product_lang, product_ids_to_keep):
    ps_product_lang = [pl for pl in ps_product_lang if pl['id_product'] in product_ids_to_keep]
    for row in ps_product_lang:
        row['id_shop'] = 1
    return ps_product_lang


def most_recent_state(id_order, history):
    history_for_this_order = filter(lambda h: h['id_order'] == id_order, history)
    history_for_this_order = sorted(history_for_this_order, key=itemgetter('date_add'), reverse=True)
    if history_for_this_order:
        return history_for_this_order[0]['id_order_state']
    else:
        return None


def convert_cart_etc(ps_cart, ps_cart_product, product_ids_to_keep):
    ps_cart_product = [cp for cp in ps_cart_product if cp['id_product'] in product_ids_to_keep]
    cart_ids_to_keep = {cp['id_cart'] for cp in ps_cart_product}
    ps_cart = [c for c in ps_cart if c['id_cart'] in cart_ids_to_keep]
    return ps_cart, ps_cart_product


def convert_addresses(ps_address):
    converted = deepcopy(ps_address)
    for a in converted:
        for field in ['company', 'address2', 'phone', 'phone_mobile']:
            if a[field] is None:
                a[field] = ''
    return converted


if __name__ == '__main__':
    main()
