<?php
/**
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Asset Administration Helpers
 * @author     Darron Froese <darron@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 */
class CmsAssetInfoHelper{
	
	function function_check_asset_model($params) {
		$asset = $params['asset'];
		$model_check = &NController::factory('cms_asset_info');
		$result = $model_check->doesAssetModelFileExist($asset);
		if (!$result) print '<span class="notfound">&nbsp;Model File not found&nbsp;</span>';
		unset($model_check);
	}

	function function_check_asset_controller($params) {
		$asset = $params['asset'];
		$controller_check = &NController::factory('cms_asset_info');
		$result = $controller_check->doesAssetControllerFileExist($asset);
		if (!$result) print '<span class="notfound">&nbsp;Controller File not found&nbsp;</span>';
		unset($controller_check);
	}
	
	function function_check_asset_table($params) {
		$asset = $params['asset'];
		$table_check = &NController::factory('cms_asset_info');
		$result = $table_check->doesAssetDatabaseTableExist($asset);
		if (!$result) print '<span class="notfound">&nbsp;Database table not found&nbsp;</span>';
		unset($table_check);
	}
	
	function function_check_asset_template_use($params) {
		$asset = $params['asset'];
		$container_id = $params['container_id'];
		$check = &NController::factory('page_content');
		$result = $check->checkAssetContainerUsage($asset, $container_id);
		if ($result > 0) print '&nbsp;(' . $result . ' uses)';
	}
}
?>