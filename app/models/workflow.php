<?php
require_once 'n_model.php';
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
 * @category   Workflow Model
 * @author     Tim Glen <tim@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 */
class Workflow extends NModel {
	var $_order_by = 'workflow.id DESC';
	function __construct() {
		$this->__table = 'workflow';
		$this->form_ignore_fields = array('page_id', 'page_content_id', 'workflow_group_id', 'asset', 'asset_id', 'action', 'draft', 'submitted', 'parent_workflow', 'completed');
		$this->form_elements['approved'] = array('select', 'workflow_approve', 'Approval', array('1'=>'Approve', '0'=>'Decline'));
		$this->form_field_options['timed_start'] = array('addEmptyOption'=>true);
		$this->form_field_options['timed_end'] = array('addEmptyOption'=>true);
		parent::__construct();
	}
}
?>