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

$csvCategories = dirname(__FILE__).'/categories.csv';
$firstLineIsHeader 	= true;
$delimiter 			= ';';
$enclosure 			= '"';
$escape 			= "\\";

define('COL_CATEGORY_ID', 0);
define('COL_PRODUCT_REF', 1);

function normalizeProductReference($ref, $padChar = '0', $padLen = 5, $padPos = STR_PAD_LEFT)
{
	if (empty($ref))
		return '';

	$normalized = str_pad($ref, $padLen, $padChar, $padPos);
	return $normalized;
}

$fp = fopen($csvCategories, 'r');
if (!$fp)
	die("\nERROR >> Error when try to open CSV to categorize.\n");

// Create export array
$import = array();
$categoryNotExistData = array();
echo "\n1. READING CATEGORIES DATA...";
while (($data = fgetcsv($fp, 0, $delimiter, $enclosure, $escape)) !== false) {
	if ($firstLineIsHeader) {
		$firstLineIsHeader = false;
		continue;
	}

	// threats NULL column value
	foreach ($data as $k => &$value) {
		if ($value == 'NULL' || $value == 'null') {
			$value = null;
		}
	}
    
    if (empty($data[COL_CATEGORY_ID])) {
        continue;
    }
    
    // verify if category exists
    $category = new Category((int)$data[COL_CATEGORY_ID]);
    if (!Validate::isLoadedObject($category)) {
        $categoryNotExistData[] = $data;
        continue;
    }

	$import[$data[COL_CATEGORY_ID]][] = normalizeProductReference($data[COL_PRODUCT_REF]);
}

fclose($fp);
echo "DONE.\n";

// generate error 'category not exist' csv data
$fp = fopen(dirname(__FILE__).'/categorize-not-exists.csv', 'w');
if ($fp) {
	echo "\nSAVING CATEGORY NOT EXIST CSV FOR RETRY...";
	foreach ($categoryNotExistData as $data) {
		fputcsv($fp, $data, ';', '"');
	}
	echo "DONE.";
	fclose($fp);
}

echo "\n2. CATEGORIZING PRODUCTS...\n";
$errors = array();
$errorCsvData = array();
foreach ($import as $catId => $productRefs) {
    echo "CAT #$catId:\n";
    foreach ($productRefs as $ref) {
        echo "\t[$ref]\n";
        // get product by code ref
        $sql = sprintf('SELECT id_product FROM '._DB_PREFIX_.'product WHERE SUBSTR(reference, 1, 5) = "%s"', $ref);
        $product_id = Db::getInstance()->getValue($sql);
        if (empty($product_id)) {
            $errorCsvData[] = array($catId, $ref);
            continue;
        }
        $product = new Product($product_id);
        // associate to category
        $category = new Category($catId);
        if (!Validate::isLoadedObject($category)) {
            $errorCsvData[] = array($catId, $ref);
            continue;
        }
        if (false === $product->addToCategories($catId)) {
            $errorCsvData[] = array($catId, $ref);
            continue;
        }
    }
    echo "\nDONE.\n";
}

// generate error csv data
$fp = fopen(dirname(__FILE__).'/categorize-errors.csv', 'w');
if ($fp) {
	echo "\nSAVING ERROR CSV FOR RETRY...";
	foreach ($errorCsvData as $data) {
		fputcsv($fp, $data, ';', '"');
	}
	echo "DONE.";
	fclose($fp);
}

echo "\nDONE.\n";