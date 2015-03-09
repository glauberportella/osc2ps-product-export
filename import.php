<?php
/**
 *	The MIT License (MIT)
 *
 *	Copyright (c) 2015 Glauber Portella
 *
 *	Permission is hereby granted, free of charge, to any person obtaining a copy
 *	of this software and associated documentation files (the "Software"), to deal
 *	in the Software without restriction, including without limitation the rights
 *	to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *	copies of the Software, and to permit persons to whom the Software is
 *	furnished to do so, subject to the following conditions:
 *
 *	The above copyright notice and this permission notice shall be included in all
 *	copies or substantial portions of the Software.
 *
 *	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *	IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *	FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *	AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *	LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *	OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 *	SOFTWARE.
 */

// Prestashop Config requirement
require_once dirname(__FILE__).'/../config/config.inc.php';

// Configuration
$csvToImport 		= dirname(__FILE__).'/products.csv';
$firstLineIsHeader 	= true;
$delimiter 			= ';';
$enclosure 			= '"';
$escape 			= "\\";
$oscProductImageUrl = 'http://www.seanauticamg.com.br/images';

// CSV data indexes
define('COL_MANUFACTURER_NAME', 	0);
define('COL_PRODUCT_ID', 			1);
define('COL_PRODUCT_CODE', 			2);
define('COL_PRODUCT_NAME', 			3);
define('COL_PRODUCT_DESCRIPTION', 	4);
define('COL_PRODUCT_PRICE', 		5);
define('COL_PRODUCT_QUANTITY', 		6);
define('COL_PRODUCT_IMAGE', 		7);

//
$fp = fopen($csvToImport, 'r');
if (!$fp)
	die("\nERROR >> Error when try to open CSV to import.\n");

// Create export array
$export = array();

echo "\n1. READING EXPORT DATA...";
while (($data = fgetcsv($fp, 0, $delimiter, $enclosure, $escape)) !== false) {
	if ($firstLineIsHeader)
		continue;

	// threats NULL column value
	foreach ($data as $k => &$value) {
		if ($value == 'NULL' || $value == 'null') {
			$value = null;
		}
	}

	$export[$data[COL_MANUFACTURER_NAME]][] = array(
			'product_id' 			=> $data[COL_PRODUCT_ID],
			'product_code' 			=> $data[COL_PRODUCT_CODE],
			'product_name' 			=> $data[COL_PRODUCT_ID],
			'product_description' 	=> $data[COL_PRODUCT_ID],
			'product_price' 		=> $data[COL_PRODUCT_ID],
			'product_quantity' 		=> $data[COL_PRODUCT_ID],
			'product_image' 		=> $data[COL_PRODUCT_ID],
		);
}

fclose($fp);
echo "DONE.\n";

// import
echo "2. IMPORTING DATA...";
$prevManufacturerName = null;
$manufacturerId = null
$errors = array();
$errorCsvData = array();
foreach ($export as $manufacturerName => $products) {
	echo sprintf("\n\tIMPORTING '%d' PRODUCTS OF MANUFACTURER '%s'...", count($products), $manufacturerName);
	// manufacturer
	if ($prevManufacturerName != $manufacturerName) {
		// get manufacturer id
		$manufacturerId = Manufacturer::getIdByName($manufacturerName);
	}
	// product
	foreach ($products as $productData) {
		// verify if product already exists on PS store
		Db::getInstance(_PS_USE_SQL_SLAVE_)->execute('SELECT id_product FROM '._DB_PREFIX_.'product WHERE reference = '.$productData['product_code']);
		if (Db::getInstance(_PS_USE_SQL_SLAVE_)->numRows() > 0) {
			continue;
		}

		$product = new Product();
		$product->reference = $productData['product_code'];
		// name
		$link = Tools::link_rewrite($productData['product_name']);
		$product->name = array();
		$product->link_rewrite = array();
		foreach (Language::getLanguages(false) as $lang){
		  $product->name[$lang['id_lang']] = $productData['product_name'];
		  $product->link_rewrite[$lang['id_lang']] = $link;
		}
		// description
		$product->description = array();
		foreach (Language::getLanguages(false) as $lang){
			$product->description[$lang['id_lang']] = $productData['product_description'];
		}
		// price
		$product->price = $productData['product_price'];
		// stock quantity
		$product->quantity = $productData['quantity'];

		// manufacturer
		if ($manufacturerId) {
			$product->id_manufacturer = $manufacturerId;
		}

		// persist product		
		if (!$product->add()) {
			// saves add error product to restore csv
			// add to an array
			$errorCsvData[] = array_merge(array($manufacturerName), $productData);
			continue;
		}
		$id_product = $product->id;

		// image
		// copy external image and upload it
		$productImageUrl = $oscProductImageUrl.'/'.$productData['product_image'];
		$image = new Image();
		if (!$new_path = $image->getPathForCreation()) {
			$err_str = 'An error occurred while attempting to create a new folder.';
			$errors[] = $err_str;
		}
		if (!($tmpName = tempnam(_PS_TMP_IMG_DIR_, 'PS')) || !copy($productImageUrl, $tmpName)) {
			$err_str = 'An error occurred during the image upload process.';
			$errors[] = $err_str;
		} else {
			$imagesTypes = ImageType::getImagesTypes('products');
			foreach ($imagesTypes as $k => $image_type)
			{
				if (!ImageManager::resize($tmpName, $new_path.'-'.stripslashes($image_type['name']).'.'.$image->image_format, $image_type['width'], $image_type['height'], $image->image_format)) {
					$err_str = 'An error occurred while copying this image: '.stripslashes($image_type['name']);
					$errors[] = $err_str;
				}
			}
		}
		@unlink($tmpName);
		Hook::exec('actionWatermark', array('id_image' => $image->id, 'id_product' => $id_product));
		echo "DONE.";
	}

	$prevManufacturerName = $manufacturerName;
}

// generate error csv data
$fp = fopen(dirname(__FILE__).'import-errors.csv', 'w');
if ($fp) {
	echo "\nSAVING ERROR CSV FOR RETRY...";
	foreach ($errorCsvData as $data) {
		fputcsv($fp, $errorCsvData, ';', '"');
	}
	echo "DONE.";
	fclose($fp);
}

if (count($errors) > 0) {
	echo "\nIMAGE ERRORS\n";
	echo implode("\n", $errors);
	echo "\n";
}

echo "\nDONE.\n";