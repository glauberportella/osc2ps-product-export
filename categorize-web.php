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
?>
<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Importar Produtos</title>

        <!-- Bootstrap CSS -->
        <link href="//netdna.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css" rel="stylesheet">

        <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
            <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
            <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
        <![endif]-->
    </head>
    <body>
        <div class="container">
            <div class="page-header">
                <h3 class="text-center">Categorize From OSCommerce to Prestashop</h3>
            </div>

<?php
$fp = fopen($csvCategories, 'r');
if (!$fp)
	die("<div class=\"alert alert-danger\">ERROR >> Error when try to open CSV to categorize.</div>");

// Create export array
$import = array();
$categoryNotExistData = array();
echo "<h4>1. READING CATEGORIES DATA...</h4>";
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
echo "<pre>DONE.</pre>";

// generate error 'category not exist' csv data
$fp = fopen(dirname(__FILE__).'/categorize-not-exists.csv', 'w');
if ($fp) {
	echo "<pre>SAVING CATEGORY NOT EXIST CSV FOR RETRY...</pre>";
	foreach ($categoryNotExistData as $data) {
		fputcsv($fp, $data, ';', '"');
	}
	echo "<pre>DONE.</pre>";
	fclose($fp);
}

echo "<h4>2. CATEGORIZING PRODUCTS...</h4>";
$errors = array();
$errorCsvData = array();
foreach ($import as $catId => $productRefs) {
    echo "<pre><strong>CAT #$catId:</strong></pre>";
    foreach ($productRefs as $ref) {
        echo "<pre>    [$ref]</pre>";
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
    echo "<pre>DONE.</pre>";
}

// generate error csv data
$fp = fopen(dirname(__FILE__).'/categorize-errors.csv', 'w');
if ($fp) {
	echo "<pre>SAVING ERROR CSV FOR RETRY...</pre>";
	foreach ($errorCsvData as $data) {
		fputcsv($fp, $data, ';', '"');
	}
	echo "<pre>DONE.</pre>";
	fclose($fp);
}

echo "<p><strong>ALL DONE.</strong></p>";
?>
        </div>

        <!-- jQuery -->
        <script src="//code.jquery.com/jquery.js"></script>
        <!-- Bootstrap JavaScript -->
        <script src="//netdna.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
    </body>
</html>