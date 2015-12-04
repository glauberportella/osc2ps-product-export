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
require_once dirname(__FILE__).'/controllers/admin/Osc2PsAdminImportController.php';

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

function normalizeReference($ref, $sep = '/', $padChar = '0', $padLen = 5, $padPos = STR_PAD_LEFT)
{
	if (empty($ref))
		return '';

	$ref = preg_replace('/[^0-9]/', $sep, $ref);
	list($prefix, $sufix) = explode($sep, $ref);
	$prefix = str_pad($prefix, $padLen, $padChar, $padPos);
	$ref = implode($sep, array($prefix, $sufix));
	return $ref;
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
				<h3 class="text-center">Importação de Produtos - Confirmar</h3>
			</div>

			<?php
			//
			$fp = fopen($csvToImport, 'r');
			if (!$fp)
				die('<div class="alert alert-danger">ERROR: Error when try to open CSV to import.</div>');

			// Create export array
			$export = array();

			while (($data = fgetcsv($fp, 0, $delimiter, $enclosure, $escape)) !== false) {
				if ($firstLineIsHeader) {
					$firstLineIsHeader = false;
					continue;
				}

				//echo sprintf('<pre>Marca: %s >> %s</pre>', $data[COL_MANUFACTURER_NAME], $data[COL_PRODUCT_NAME]);

				// threats NULL column value
				foreach ($data as $k => &$value) {
					if ($value == 'NULL' || $value == 'null') {
						$value = null;
					}
				}

				$export[$data[COL_MANUFACTURER_NAME]][] = array(
						'product_id' 			=> $data[COL_PRODUCT_ID],
						'product_code' 			=> $data[COL_PRODUCT_CODE],
						'product_name' 			=> $data[COL_PRODUCT_NAME],
						'product_description' 	=> $data[COL_PRODUCT_DESCRIPTION],
						'product_price' 		=> $data[COL_PRODUCT_PRICE],
						'product_quantity' 		=> $data[COL_PRODUCT_QUANTITY],
						'product_image' 		=> $data[COL_PRODUCT_IMAGE],
					);
			}

			fclose($fp);
?>
			<table class="table table-bordered">
				<thead>
					<tr>
						<th>Marca</th>
						<th>Código</th>
						<th>Imagem</th>
						<th>Descrição</th>
						<th>Preço</th>
					</tr>
				</thead>
				<tbody>
<?php
			// import
			$prevManufacturerName = null;
			$manufacturerId = null;
			$errors = array();
			$errorCsvData = array();
			foreach ($export as $manufacturerName => $products) {
				// manufacturer
				if ($prevManufacturerName != $manufacturerName) {
					// get manufacturer id
					$manufacturerId = Manufacturer::getIdByName($manufacturerName);
				}
				// product
				foreach ($products as $productData) {
					$reference = normalizeReference($productData['product_code']);
					if (!empty($reference)) {
						// verify if product already exists on PS store
						$sql = 'SELECT id_product FROM '._DB_PREFIX_.'product WHERE reference = "'.$reference.'"';
						Db::getInstance(_PS_USE_SQL_SLAVE_)->execute($sql);
						if (Db::getInstance(_PS_USE_SQL_SLAVE_)->numRows() > 0) {
							continue;
						}
					}

					echo '<tr><td>'.$manufacturerName.'</td><td>'.$reference.'</td><td><img width="100" src="'.$oscProductImageUrl.'/'.$productData['product_image'].'" alt=""></td><td>'.$productData['product_name'].'</td><td>R$ '.number_format($productData['product_price'], 2, ',', '.').'</td></tr>';
				}

				$prevManufacturerName = $manufacturerName;
			}
?>
				</tbody>
			</table>
<?php
			// generate error csv data
			$fp = fopen(dirname(__FILE__).'/import-errors.csv', 'w');
			if ($fp) {
				foreach ($errorCsvData as $data) {
					fputcsv($fp, $data, ';', '"');
				}
				fclose($fp);
			}

			if (count($errors) > 0) {
				echo "<h4>ERROS DE IMAGEM</h4>";
				echo '<div class="alert alert-danger">';
				echo implode("<br>", $errors);
				echo '</div>';
			}
			?>
			</div>

		<!-- jQuery -->
		<script src="//code.jquery.com/jquery.js"></script>
		<!-- Bootstrap JavaScript -->
		<script src="//netdna.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
	</body>
</html>