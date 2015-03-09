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

while (($data = fgetcsv($fp, 0, $delimiter, $enclosure, $escape)) !== false) {
	
}
