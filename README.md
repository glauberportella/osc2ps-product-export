# Export OSCommerce 2.2 Catalog to CSV and import to Prestashop 1.6+

## Requirements

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

## SQL for export data

The data is ordered by Manufacturer name, product code and product name columns in Ascending order.

    SELECT m.manufacturers_name, p.products_id, p.products_code, pd.products_name, pd.products_description, p.products_price, p.products_quantity, p.products_image
    FROM products p
    LEFT JOIN products_description pd ON pd.products_id = p.products_id
    LEFT JOIN manufacturers m ON m.manufacturers_id = p.manufacturers_id
    WHERE p.products_status <> 0
    ORDER BY m.manufacturers_name, p.products_code, pd.products_name ASC

## Script to import exported data

Import steps:

1. Creates a multi dimensional array with key = manufacturer name and value as an array with all products from that manufacturer;
2. Traverse the created array, find the Manufacturer from Prestashop base, save its ID for futher use
3. For each product of the manufacturer found
   Create the Prestashop Product instance, persist it;
   Load the image from OSCommerce url that points to products_image value;
   Persist the image to Prestashop base relative to the persisted product;

### TODO LIST

None to add in the time it was created.