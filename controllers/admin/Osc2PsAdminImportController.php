<?php

require_once dirname(__FILE__).'/../../../config/config.inc.php';

class Osc2PsAdminImportController extends AdminImportController
{
	public static function copyImg($id_entity, $id_image = null, $url, $entity = 'products', $regenerate = true)
	{
		return parent::copyImg($id_entity, $id_image, $url, $entity, $regenerate);
	}
}