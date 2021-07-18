#!/usr/bin/env python
import math
import re
import os
import json
from copy import copy, deepcopy
import traceback
import argparse
from collections import defaultdict, Counter
import urllib.request
from decimal import Decimal
from enum import Enum

from progressbar import ProgressBar
from validate_email import validate_email
import mysql.connector
import itertools
import mysql.connector
from PIL import Image
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


ATTRIBUTE_GROUP_COLOR = 1
ATTRIBUTE_GROUP_KAZ_SIZE = 2
ATTRIBUTE_GROUP_QUANTITY = 3


class ProductType(Enum):
    COLORED_DESIGN = 1
    BLACK_AND_WHITE_TINTABLE = 2
    MONOCHROME_KAZ_PACK = 3


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
    write_model(original_model, 'model_original.json')
    converted_model = convert_model(original_model)
    download_images(converted_model['tables'])
    configure_stickaz_site(converted_model['tables'])
    write_model(converted_model, 'model_converted.json')


def download_images(tables):
    if args.skip_images:
        print("Skipping images download.")
        return
    download_product_img_data(tables['ps_product'])
    download_category_images(tables['ps_category'])


def export_config():
    return {
        'cookie_key': export_cookie_key(),
        'stripe_public_key': sql_get("SELECT * FROM ps_configuration WHERE name = 'MSSTRIPE_PUBLIC_KEY'")['value'],
        'stripe_private_key': sql_get("SELECT * FROM ps_configuration WHERE name = 'MSSTRIPE_SECRET_KEY'")['value'],
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


def write_model(full_model, filename):
    to_write = deepcopy(full_model)
    for name, table in to_write['tables'].items():
        to_write['tables'][name] = factorize_columns(table)
    destination = os.path.realpath(os.path.join(here, f'../www-share/data/{filename}'))
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
        'ps_carrier',
        'ps_carrier_zone',
        'ps_carrier_group',
        'ps_carrier_lang',
        'ps_delivery',
        'ps_range_weight',
        'ps_range_price',
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


def download_category_images(ps_categories):
    print("Starting download of category images. \n")
    pbar = ProgressBar()
    for c in pbar(ps_categories):
        cid = c['id_category']
        try:
            download_category_image(cid)
        except Exception as e:
            errors.append(str(e))
            pass


def download_product_img_data(ps_products):
    ids_to_keep = {p['id_product'] for p in ps_products}

    product_images = sql_retrieve_raw(f'SELECT id_product, id_image FROM ps_image WHERE id_product <= {max(ids_to_keep)} order by id_product, position asc')
    product_images_dict = defaultdict(list)
    for id_product, id_image in product_images:
        product_images_dict[id_product].append(id_image)

    print("Starting download of product images. \n")

    bar = ProgressBar()
    for product_id in bar(product_images_dict):
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
    download(image_url, destination_path)


def download(source, destination):
        with urllib.request.urlopen(source) as src:
            destination_dir = os.path.dirname(destination)
            os.makedirs(destination_dir, exist_ok=True)
            with open(destination, 'wb') as dest:
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


def configure_stickaz_site(tables):
    remove_unwanted_modules(tables)


def remove_unwanted_modules(tables):
    return 
    # ps_module_shop = tables['ps_module_shop']
    # for row in ps_module_shop:
    #     if row[''] == 25:
    #         ps_module_shop.remove


def create_ps_product_carrier(ps_carrier, ps_product):
    ps_product_carrier = []
    for carrier in ps_carrier:
        for product in ps_product:
            product_carrier = {}
            product_carrier['id_product'] = product['id_product']
            product_carrier['id_carrier_reference'] = carrier['id_carrier']
            product_carrier['id_shop'] = 1
            ps_product_carrier.append(product_carrier)

    return ps_product_carrier



def convert_model(model):
    model = copy(model)
    tables = model['tables']
    # Users etc
    tables['ps_customer'] = convert_customers(tables['ps_customer'])
    tables['ps_currency'], tables['ps_currency_lang'] = convert_currencies(tables['ps_currency'], tables['ps_lang'])
    tables['ps_employee'] = convert_employees(tables['ps_employee'])
    # Attributes
    tables['ps_attribute_group'] = convert_attribute_group(tables['ps_attribute_group'])
    tables['ps_attribute'] = convert_attribute(tables['ps_attribute'], tables['ps_attribute_lang'], tables['ps_attribute_group'])
    # Products
    tables['ps_product'], product_ids_to_keep = convert_products(tables['ps_product'])
    tables['ps_product_shop'] = create_product_shop(tables['ps_product'])
    tables['ps_product_tag'] = [pt for pt in tables['ps_product_tag'] if pt['id_product'] in product_ids_to_keep]
    tables['ps_product_sale'] = [ps for ps in tables['ps_product_sale'] if ps['id_product'] in product_ids_to_keep]
    fix_prices(tables, product_ids_to_keep)
    product_ids_to_keep,\
    tables['ps_product_stickaz'],\
    tables['ps_product_attribute'],\
    tables['ps_product_attribute_combination'] = \
        convert_attribute_combinations(
            product_ids_to_keep,
            tables['ps_product'],
            tables['ps_attribute'],
            tables['ps_attribute_lang'],
            tables['ps_customization'],
            tables['ps_product_stickaz'],
            tables['ps_product_attribute'],
            tables['ps_product_attribute_combination'])
    fix_product_attribute(tables['ps_product_attribute'])
    tables['ps_product_lang'] = convert_product_lang(tables['ps_product_lang'], product_ids_to_keep)
    # Orders
    tables['ps_cart'], tables['ps_cart_product'] = convert_cart_etc(tables['ps_cart'], tables['ps_cart_product'], product_ids_to_keep)
    tables['ps_order_detail'], order_ids_to_keep = convert_order_detail(tables['ps_order_detail'], product_ids_to_keep)
    tables['ps_orders'] = convert_orders(tables['ps_orders'], tables['ps_order_history'], order_ids_to_keep)
    tables['ps_order_history'] = [oh for oh in tables['ps_order_history'] if oh['id_order'] in order_ids_to_keep]
    #
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
    tables['ps_shop'] = [
        {
            'id_shop': 1,
            'id_shop_group': 1,
            'name': 'Stickaz',
            'id_category': 1,
            'theme_name': 'stickaz',
            'active': 1,
            'deleted': 0
        }
    ]
    tables['ps_address'] = convert_addresses(tables['ps_address'])
    tables['ps_lang'] = add_missing_lang_data(tables['ps_lang'])
    tables['ps_carrier'] =  adjust_ps_carrier(tables['ps_carrier'])
    tables['ps_product_carrier'] =  create_ps_product_carrier(tables['ps_carrier'],tables['ps_product'])
    return model


def convert_attribute_combinations(
        product_ids_to_keep,
        ps_product,
        ps_attributes,
        ps_attribute_lang,
        ps_customization,
        ps_product_stickaz,
        ps_product_attribute,
        ps_product_attribute_combination):
    new_product_ids_to_keep = []
    new_ps_product_stickaz = []
    new_ps_product_attribute = []
    new_ps_product_attribute_combination = []
    all_attributes = {
        a['id_attribute']: a
        for a in ps_attributes
    }
    all_colors = [
        a['id_attribute']
        for a in ps_attributes
        if a['id_attribute_group'] == ATTRIBUTE_GROUP_COLOR
    ]
    all_products = {
        p['id_product']: p
        for p in ps_product
    }
    all_combinations = defaultdict(list)  # Combination ID -> list of attributes (values)
    for pac in ps_product_attribute_combination:
        all_combinations[pac['id_product_attribute']].append(pac['id_attribute'])
    next_combination_id = max(pac['id_product_attribute'] for pac in ps_product_attribute_combination) + 1
    for product_id in product_ids_to_keep:
        product = all_products[product_id]
        # ps_product_attributes actually stores combinations available for a product
        # and ps_product_attribute.id_product_attribute gives the combination id
        this_product_combinations = get_all_from_id(ps_product_attribute, 'id_product', product_id)
        if is_corrupt(this_product_combinations, all_combinations, all_attributes):
            # DB is corrupt, ex product 58 has product_attribute 287 with attribute 51 which doesnt exist
            print(f'Warning: ignoring corrupt product {product_id}')
            continue
        existing_entry = maybe_get_one_from_id(ps_product_stickaz, 'id_product', product_id)
        if existing_entry is None:
            print(f'Warning: ignoring product missing in ps_product_stickaz {product_id}')
            continue        
        new_product_ids_to_keep.append(product_id)
        t = get_product_type(product_id, ps_customization, this_product_combinations, all_attributes, all_combinations)
        new_ps_product_stickaz.append({'type': t.name, **existing_entry})
        if t == ProductType.COLORED_DESIGN:
            # Keep the combinations. On each: drop color attributes, keep the kaz size attribute
            for product_combination in this_product_combinations:
                new_ps_product_attribute.append(product_combination)
                id_combination = product_combination['id_product_attribute']
                for attribute_ref in all_combinations[id_combination]:
                    attribute = all_attributes[attribute_ref]
                    if attribute['id_attribute_group'] == ATTRIBUTE_GROUP_KAZ_SIZE:
                        new_ps_product_attribute_combination.append({'id_attribute': attribute_ref, 'id_product_attribute': id_combination})
                        kaz_size_attribute_id = attribute_ref
                product_combination['price'] = price_impact(kaz_size_attribute_id, ps_attribute_lang, product)
        elif t == ProductType.BLACK_AND_WHITE_TINTABLE:
            # The combinations are for black. Add more combinations for all other colors
            for black_combination in this_product_combinations:
                # The black combination for this kaz size. Use it as a model only
                # It will be re-constructed along with the other colors
                black_combination_id = black_combination['id_product_attribute']
                was_default = black_combination['default_on'] == 1
                for attribute_ref in all_combinations[black_combination_id]:
                    attribute = all_attributes[attribute_ref]
                    if attribute['id_attribute_group'] == ATTRIBUTE_GROUP_KAZ_SIZE:
                        kaz_size_attribute_id = attribute['id_attribute']
                    if attribute['id_attribute_group'] == ATTRIBUTE_GROUP_COLOR:
                        black_attribute_id = attribute['id_attribute']
                # Add all colors
                for color_id in all_colors:
                    color_combination_id = next_combination_id
                    next_combination_id += 1
                    color_combination = copy(black_combination)
                    color_combination['id_product_attribute'] = color_combination_id
                    is_black = color_id == black_attribute_id
                    color_combination['default_on'] = 1 if is_black and was_default else None
                    color_combination['price'] = price_impact(kaz_size_attribute_id, ps_attribute_lang, product)
                    new_ps_product_attribute.append(color_combination)
                    new_ps_product_attribute_combination.append({'id_attribute': kaz_size_attribute_id, 'id_product_attribute': color_combination_id})
                    new_ps_product_attribute_combination.append({'id_attribute': color_id, 'id_product_attribute': color_combination_id})
        elif t == ProductType.MONOCHROME_KAZ_PACK:
            # Drop this product. A new product will be created dedicated to all packs
            pass
        else:
            raise ValueError(t)
    # TODO create the pack product
    return new_product_ids_to_keep, new_ps_product_stickaz, new_ps_product_attribute, new_ps_product_attribute_combination


def is_corrupt(this_product_combinations, all_combinations, all_attributes):
    try:
        if len(this_product_combinations) == 0:
            return True
        for pc in this_product_combinations:
            if len(all_combinations[pc['id_product_attribute']]) == 0:
                return True
            for attribute_ref in all_combinations[pc['id_product_attribute']]:
                _ = all_attributes[attribute_ref]
        return False
    except:
        return True


def get_combinations_types(product_combinations, all_attributes, all_combinations):
    types = [get_combination_types(c, all_attributes, all_combinations) for c in product_combinations]
    for t in types:
        # Check all the combinations have the same shapes (ignoring order)
        assert Counter(t) == Counter(types[0]), types
    return types[0]


def get_combination_types(product_combination, all_attributes, all_combinations):
    id_combination = product_combination['id_product_attribute']
    attributes = [
        all_attributes[attribute_ref]
        for attribute_ref in all_combinations[id_combination]
    ]
    return [
        a['id_attribute_group']
        for a in attributes
    ]


def get_product_type(product_id, ps_customization, this_product_combinations, all_attributes, all_combinations) -> ProductType:
    is_customized = any(c['id_product'] == product_id for c in ps_customization)
    combinations_type = get_combinations_types(this_product_combinations, all_attributes, all_combinations)
    has_monochrome_combinations = len([t for t in combinations_type if t == ATTRIBUTE_GROUP_COLOR]) == 1
    if is_customized and \
            has_monochrome_combinations and \
            set(combinations_type) == {ATTRIBUTE_GROUP_KAZ_SIZE, ATTRIBUTE_GROUP_COLOR}:
        return ProductType.BLACK_AND_WHITE_TINTABLE
    elif set(combinations_type) == {ATTRIBUTE_GROUP_KAZ_SIZE, ATTRIBUTE_GROUP_COLOR}:
        return ProductType.COLORED_DESIGN
    elif set(combinations_type) == {ATTRIBUTE_GROUP_KAZ_SIZE, ATTRIBUTE_GROUP_COLOR, ATTRIBUTE_GROUP_QUANTITY}:
        return ProductType.MONOCHROME_KAZ_PACK
    else:
        raise ValueError(f'combinations types {combinations_type} pid {product_id}')


def get_one_from_id(collection, id_field, id_value):
    item = maybe_get_one_from_id(collection, id_field, id_value)
    if item is None:
        raise ValueError(f'not found where {id_field}={id_value}')
    else:
        return item

def maybe_get_one_from_id(collection, id_field, id_value):
    for item in collection:
        if item[id_field] == id_value:
            return item
    return None


def get_all_from_id(collection, id_field, id_value):
    result = []
    for item in collection:
        if item[id_field] == id_value:
            result.append(item)
    return result


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


def create_product_shop(ps_product):
    product_shop = deepcopy(ps_product)
    for p in product_shop:
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
    return product_shop


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


def convert_attribute(ps_attribute, ps_attribute_lang, ps_attribute_group):
    ps_attribute = sort_attributes(ps_attribute, ps_attribute_lang, ps_attribute_group)

    for row in ps_attribute:
        if row['color'] is None:
            row['color'] = ''
    return ps_attribute


# x represents size or color as possible attribute, id_attribute_group defines if it is color or size
def sort_attributes(ps_attribute, ps_attribute_lang, ps_attribute_group):
    groups = [row['id_attribute_group'] for row in ps_attribute_group]
    for id_attribute_group in groups:

        attribute_x_ids = [ row['id_attribute'] for row in ps_attribute if row['id_attribute_group'] == id_attribute_group]
        ps_attribute_lang_x_dict = {row['id_attribute']: row['name'] for row in ps_attribute_lang if (row['id_attribute'] in attribute_x_ids and row['id_lang'] == 1) }

        x_list = [v for k,v in ps_attribute_lang_x_dict.items() ]
        x_list_sorted = sorted(x_list)

        for row in ps_attribute:
            if row['id_attribute'] in attribute_x_ids:
                x_of_this_one = ps_attribute_lang_x_dict[row['id_attribute']]
                index = x_list_sorted.index(x_of_this_one)
                row['position'] = index + 1

    return ps_attribute


def fix_product_attribute(ps_product_attribute):
    for row in ps_product_attribute:
        if row['default_on'] == 0:
            row['default_on'] = None


def get_max_id_product_attribute(ps_product_attribute):
    ids = [a['id_product_attribute'] for a in ps_product_attribute]
    return max(ids)


def convert_ps_image(ps_image, ps_products, identifier = 'id_product'):
    products_to_keep = first(args.limit_products, ps_products)
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


def get_kaz_sizes_and_attribute_ids(ps_attribute, ps_attribute_lang):
    size_id_attributes = {row['id_attribute'] for row in ps_attribute if row['id_attribute_group'] == ATTRIBUTE_GROUP_KAZ_SIZE}
    kaz_sizes_dict = {}
    for row in ps_attribute_lang:
        if row['id_lang'] == 1 and row['id_attribute'] in size_id_attributes:
            kaz_sizes_dict[row['id_attribute']] = row['name']
    return kaz_sizes_dict


def color_id_attributes(ps_attribute, ps_attribute_lang):
    color_id_attributes = {row['id_attribute'] for row in ps_attribute if row['id_attribute_group'] == ATTRIBUTE_GROUP_COLOR}
    color_ids = []
    for row in ps_attribute_lang:
        if row['id_lang'] == 1 and row['id_attribute'] in color_id_attributes:
            color_ids.append(row['id_attribute'])
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


def add_missing_lang_data(ps_lang):
    for lang in ps_lang:
        if lang['iso_code'] == 'en':
            lang['language_code'] = 'en-us'
            lang['locale'] = 'en-US'
        else:
            lang['language_code'] = lang['iso_code'] + "-" + lang['iso_code']
            lang['locale'] = lang['language_code']

        lang['date_format_lite'] = 'Y-m-d'
        lang['date_format_full'] = 'Y-m-d H:i:s'
        lang['is_rtl'] = 0
    return ps_lang


def adjust_ps_carrier(ps_carrier):
    kept_carriers = [c for c in ps_carrier if not_specific_french_carrier_and_active(c)]

    transformed_carriers = [remove_old_carrier_fields(c, position) for position, c in enumerate(kept_carriers)]
    return transformed_carriers

def remove_old_carrier_fields(carrier, position):
    carrier['id_reference'] = carrier['id_carrier']
    carrier['position'] = position
    del carrier['emc_type']
    del carrier['emc_services_id_es']
    if carrier['url'] is None:
        carrier['url'] = ''
    if carrier['external_module_name'] is None:
        carrier['external_module_name'] = ''
    if carrier['id_carrier'] == 124:
        # Hardcoded for 124 but we could search in ps_delivery instead
        carrier['shipping_method'] = 2  # 1 weight, 2 price
    return  carrier

target_prices = {
    # In euros, tax-included
    'base_price': 3,
    'linear_per_kaz': (16 - 3) / 300, # aim for 300 kaz for 16e
    'surcharge_per_color': 0.2,
    'kaz_size_impact_multiplier': {
        '1.5': 0,
        '2': 2 / 1.5 - 1,
        '3': 3 / 1.5 - 1,
        '4': 4 / 1.5 - 1,
        '5': 5 / 1.5 - 1,
    }
}

def not_specific_french_carrier_and_active(carrier):
    return 'Retrait Boutique Stickaz' not in carrier['name'] and 'Colissimo' not in carrier['name'] and 'Suivi' not in carrier['name'] and 'Mondial' not in carrier['name'] and 'FedEx' not in carrier['name'] and 'Lettre' not in carrier['name'] and carrier['active']== 1

def fix_prices(tables, product_ids_to_keep):
    for product_id in product_ids_to_keep:
        kaz_count, number_of_colors = get_design_counts(product_id, tables)
        previous_price = get_price(product_id, tables)
        #print(f'Product {product_id} has {number_of_colors} colors, {kaz_count} kaz')
        #print(f'  previous price: {previous_price:.2f}')
        base_price = target_prices['base_price']
        linear_per_kaz = target_prices['linear_per_kaz']
        multicolor_surcharge = (number_of_colors - 1) * target_prices['surcharge_per_color']
        new_price = base_price + kaz_count * linear_per_kaz + multicolor_surcharge
        # round to 0.5
        new_price_rounded = int(2 * new_price) / 2
        # That was the tax-included price we want, but we have to store prices without tax
        new_price_before_tax = new_price_rounded / 1.2
        #   print(f' suggested price: {new_price_rounded}, before tax: {new_price_before_tax}')
        get_one_from_id(tables['ps_product'], 'id_product', product_id)['price'] = new_price_before_tax
        get_one_from_id(tables['ps_product_shop'], 'id_product', product_id)['price'] = new_price_before_tax


def price_impact(kaz_size_attribute_id, ps_attribute_lang, product):
    product_price = product['price']
    for al in ps_attribute_lang:
        if al['id_attribute'] == kaz_size_attribute_id and al['id_lang'] == 1:
            kaz_size_value = al['name']
            multiplier = target_prices['kaz_size_impact_multiplier'][kaz_size_value]
            raw_impact = product_price * multiplier
            rounded_impact = int(2 * raw_impact) / 2
            return rounded_impact


def get_price(product_id, tables):
    return get_one_from_id(tables['ps_product'], 'id_product', product_id)['price']


def get_design_counts(product_id, tables):
    design_json = get_one_from_id(tables['ps_product_stickaz'], 'id_product', product_id)['json']
    design = json.loads(design_json)
    return _get_design_counts(design)


def _get_design_counts(design):
    kaz_count = 0
    counts = defaultdict(int)
    for row in design['canvas']:
        for color in row:
            if color != '':
                counts[color] += 1
                kaz_count += 1
    return kaz_count, len(counts.keys())


if __name__ == '__main__':
    main()
