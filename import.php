<?php

// Prestashop Config requirement
require_once dirname(__FILE__).'/../config/config.inc.php';

// CSV data indexes
define('COL_MANUFACTURER_NAME', 	0);
define('COL_PRODUCT_ID', 			1);
define('COL_PRODUCT_CODE', 			2);
define('COL_PRODUCT_NAME', 			3);
define('COL_PRODUCT_DESCRIPTION', 	4);
define('COL_PRODUCT_PRICE', 		5);
define('COL_PRODUCT_QUANTITY', 		6);
define('COL_PRODUCT_IMAGE', 		7);

// Configuration
$csvToImport = dirname(__FILE__).'/products.csv';
$firstLineIsHeader = true;
$delimiter = ';';
$enclosure = '"';
$escape = "\\";

//
$fp = fopen($csvToImport, 'r');
if (!$fp)
	die('Error when try to open CSV to import.');

// Create export array
$export = array();

while (($data = fgetcsv($fp, 0, $delimiter, $enclosure, $escape)) !== false) {
	// threats NULL column value
	foreach ($data as $k => &$value) {
		if ($value == 'NULL' || $value == 'null') {
			$value = null;
		}
	}

	$export[strtolower($data[COL_MANUFACTURER_NAME])][] = array(
			'product_id' 			=> $data[COL_PRODUCT_ID],
			'product_code' 			=> $data[COL_PRODUCT_CODE],
			'product_name' 			=> $data[COL_PRODUCT_ID],
			'product_description' 	=> $data[COL_PRODUCT_ID],
			'product_price' 		=> $data[COL_PRODUCT_ID],
			'product_quantity' 		=> $data[COL_PRODUCT_ID],
			'product_image' 		=> $data[COL_PRODUCT_ID],
		);
}

