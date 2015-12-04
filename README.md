# Export OSCommerce 2.2 Catalog to CSV and import to Prestashop 1.6+

# 1. Requirements

First of all your manufacturers must be already in your Prestashop store, with the same name as exists in your OSCommerce store.

## Data export format

Data columns to export:

*'p' is products table alias, 'pd' is products_description alias*

    - p.products_id
    - p.products_code
    - pd.products_name
    - pd.products_description
    - p.products_price
    - p.products_quantity
    - p.products_image

# 2. SQL for export data from OSCommerce

## Product data

The data is ordered by Manufacturer name, product code and product name columns in Ascending order.

1. Execute the query on your OSCommerce Database

    SELECT m.manufacturers_name, p.products_id, p.products_code, pd.products_name, pd.products_description, p.products_price, p.products_quantity, p.products_image
    FROM products p
    LEFT JOIN products_description pd ON pd.products_id = p.products_id
    LEFT JOIN manufacturers m ON m.manufacturers_id = p.manufacturers_id
    WHERE p.products_status <> 0
    ORDER BY m.manufacturers_name, p.products_code, pd.products_name ASC

2. Save the result as CSV with name `products.csv` inside the import_from_oscommerce script folder.
    
    **IMPORTANT** Export in CSV with the colum names on first row, use (options from phpmyadmin):

        Columns separated with: ;
        Columns enclosed with:  "
        Columns escaped with:   \
        Replace NULL with:      NULL

# 3. Import process

Import steps executed by script import.php or import-web.php:

1. Creates a multi dimensional array with key = manufacturer name and value as an array with all products from that manufacturer;
2. Traverse the created array, find the Manufacturer from Prestashop base, save its ID for futher use
3. For each product of the manufacturer found
   Create the Prestashop Product instance, persist it;
   Load the image from OSCommerce url that points to products_image value;
   Persist the image to Prestashop base relative to the persisted product;

## 3.1 How to Import the data

1. Run `import.php` via SSH or, if no SSH session, run `import-web.php` from your browser
2. Run `categorize.php` via SSH or, if no SSJ session, run `categorize-web.php` from your browser

## 3.2 Steps to rerun the import (if needed)

1. Remove all products using the prestashop backend
2. Truncate poducts table in MySQL DB
    TRUNCATE `ps_product`;
    TRUNCATE `ps_product_attachment`;
    TRUNCATE `ps_product_attribute`;
    TRUNCATE `ps_product_attribute_combination`;
    TRUNCATE `ps_product_attribute_image`;
    TRUNCATE `ps_product_attribute_shop`;
    TRUNCATE `ps_product_carrier`;
    TRUNCATE `ps_product_comment`;
    TRUNCATE `ps_product_comment_criterion_product`;
    TRUNCATE `ps_product_comment_grade`;
    TRUNCATE `ps_product_comment_report`;
    TRUNCATE `ps_product_comment_usefulness`;
    TRUNCATE `ps_product_country_tax`;
    TRUNCATE `ps_product_download`;
    TRUNCATE `ps_product_group_reduction_cache`;
    TRUNCATE `ps_product_lang`;
    TRUNCATE `ps_product_sale`;
    TRUNCATE `ps_product_shop`;
    TRUNCATE `ps_product_supplier`;
    TRUNCATE `ps_product_tag`;
3. Delete only the folders on /img/p (folders named with numbers)

# 4. Generate a list of products to be imported

This will only generate a list with all products to import, no data will be imported.

1. Run `import-confirmar.php` in your browser.
