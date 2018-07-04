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
 * @category   Page Template Helpers
 * @author     Darron Froese <darron@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 */
class PageTemplateHelper{
	function function_check_template($params) {
		$filename = $params['filename'];
		$template_check = &NController::factory('page_template');
		$result = $template_check->doesPageTemplateExist($filename);
		if (!$result) print '<span class="notfound">&nbsp;File not found&nbsp;</span>';
		unset($template_check);
	}	
}
?>