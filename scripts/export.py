#!/usr/bin/env python

import os
import json
from subprocess import call
import mysql.connector 
import urllib.request

here = os.path.dirname(os.path.realpath(__file__))


def main():
    run_export()


def run_export():
    destination_dir = os.path.realpath(os.path.join(here, '../www-share/data'))
    categories_query = """
        SELECT
            c.id_category,
            id_parent,
            cl1.name as name_en,
            cl1.description as description_en,
            cl1.link_rewrite as link_rewrite_en,
            cl1.meta_keywords as meta_keywords_en,
            cl2.name as name_fr,
            cl2.description as description_fr,
            cl2.link_rewrite as link_rewrite_fr,
            cl2.meta_keywords as meta_keywords_fr
        FROM
            ps_category c,
            ps_category_lang cl1,
            ps_category_lang cl2
        WHERE
            c.id_category=cl1.id_category
            AND c.id_category=cl2.id_category
            AND active=1
            AND cl1.id_lang=1
            AND cl2.id_lang=2"""
    categories = export_one(categories_query, os.path.join(destination_dir, 'categories'))
    download_images(categories)


def export_one(query, file_base):
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
    result = {
        'categories': [dict(zip(field_names, row)) for row in rows]
    }
    destination = file_base + '.json'
    with open(destination, 'wb') as dest:
        j = json.dumps(result, indent=4)
        dest.write(j.encode('utf-8'))
    return result['categories']


def download_images(categories):
    for c in categories:
        cid = c['id_category']
        try:
            download_category_image(cid)
        except:
            pass
            
def download_category_image(cid):
        image_url = f'https://www.stickaz.com/img/c/{cid}.jpg'
        destination_dir = os.path.realpath(os.path.join(here, '../www-share/data/img/c'))
        destination_path = os.path.join(destination_dir, f'{cid}.jpg')
        with urllib.request.urlopen(image_url) as src:
            os.makedirs(destination_dir, exist_ok=True)
            with open(destination_path, 'wb') as dest:
                dest.write(src.read())


if __name__ == '__main__':
    main()
