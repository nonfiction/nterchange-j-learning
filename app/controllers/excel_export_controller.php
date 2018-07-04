<?php
require_once 'admin_controller.php';
/**
 * Excel Export for Assets - Automatically allowing an Excel export of any asset.
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Excel Export for Assets
 * @author     Darron Froese <darron@nonfiction.ca>
 * @copyright  2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 */
class ExcelExportController extends AdminController {
	var $default_foreign_keys = array('cms_modified_by_user'=>array('cms_auth', 'real_name'));
	var $default_field_exclusions = array('id'=>true, 'cms_active'=>true, 'cms_draft'=>true, 'cms_deleted'=>true, 'cms_headline'=>true);

	function __construct() {
		$this->name = 'excel_export';
		// set user level allowed to access the actions with required login
		$this->user_level_required = N_USER_EDITOR;
		$this->login_required = true;
		$this->page_title = 'Excel Export';
		parent::__construct();
	}

	function export($model_name) {
		if (isset($model_name)) {
			$model = NModel::factory($model_name);
			// Foreign Key Lookup Support
			if (isset($model->excel_export)) {
				$model_foreign_keys = $model->excel_export;
				// Default standard foreign keys get added and merged here.
				$foreign_keys = array_merge($this->default_foreign_keys, $model_foreign_keys);
			} else {
				$foreign_keys = $this->default_foreign_keys;
			}
			// Field Inclusion and Exclusion Support
			if (isset($model->excel_exclude_fields)) {
				$model_excel_inclusions = $model->excel_exclude_fields;
				$field_exclusions = array_merge($this->default_field_exclusions, $model_excel_inclusions);
			} else {
				$field_exclusions = $this->default_field_exclusions;
			}
			// If $_GET['search'] is set, only export those items.
			$search = isset($_GET['search'])?$_GET['search']:null;
			$search_field = isset($_GET['search_field'])?$_GET['search_field']:null;
			if (isset($search) && $search != null) {
				if (!$search_field && $search_field != null) {
					$acon = NController::factory('asset');
					$search_field = isset($model->search_field)?$model->search_field:$acon->search_field;
					unset($acon);
				}
			}
			$options = $search?array('conditions'=>"$search_field LIKE '%$search%'"):array();
			// Can set options in the model about items exported to the Excel.
			// Only export items that meet a certain criteria - not everything in the list.
			// For example: $this->viewlist_options = array('conditions'=>"cms_modified_by_user = '4'");
			if (isset($model->viewlist_options)) {
				foreach ($model->viewlist_options as $key => $val) {
					if (isset($options[$key])) {
						$options[$key] .= ' AND ' . $val;
					} else {
						$options[$key] = "$val";
					}
				}
			}

			if ($model->find($options)) {
				$fields = $model->fields();
				// Add additional custom fields here from the model file.
				if (isset($model->excel_extra_fields)) {
					foreach ($model->excel_extra_fields as $key => $value) {
						$fields[] = $key;
					}
				}
				require_once 'Spreadsheet/Excel/Writer.php';

				// Creating a workbook
				$workbook = new Spreadsheet_Excel_Writer();
				$worksheet =& $workbook->addWorksheet(ucwords(str_replace('_', ' ', $model_name)));
				$worksheet->setColumn(2, 4, 20);
				$worksheet->setColumn(7, 7, 15);
				$worksheet->setColumn(10, 28, 20);

				// Make the title line look a little different
				$title =& $workbook->addFormat();
				$title->setBold();
				$title->setAlign('center');
				$title->setBottom(2);

				// Let's add the field names to the title line.
				// Leave out a few.
				$x = 0;
				$worksheet->setRow(0, 18.75);
				foreach ($fields as $field) {
					$exclude_this = array_key_exists($field, $field_exclusions);
					if ($exclude_this && $field_exclusions[$field] == true) {
						// do nothing
					} else {
						$worksheet->write(0, $x, ucwords(str_replace('_', ' ', $field)), $title);
						$x++;
					}
				}

				// Now here comes the data.
				$y = 1;
				while ($model->fetch()) {
					$item = $model->toArray();
					// For reference while we're working with things.
					$original_item = array();
					$original_item = $item;
					$x = 0;
					$worksheet->setRow($y, 18.75);
					foreach ($fields as $field) {
						$exclude_this = array_key_exists($field, $field_exclusions);
						if ($exclude_this && $field_exclusions[$field] == true) {
							// do nothing
						} else {
							// Look for foreign keys and replace if assigned.
							foreach($foreign_keys as $foreign_key => $foreign_key_value) {
								if ($field == $foreign_key) {
									$fk_model_name = $foreign_key_value[0];
									$fk_model_headline = $foreign_key_value[1];
									$fk_model = NModel::factory($fk_model_name);
									if ($fk_model && ($fk_model->get($item[$field]))) {
										$item[$field] = $fk_model->{$fk_model_headline};
									}
									unset($fk_model);
								}
							}

							//Look for bitmask fields and replace with string value instead of numeric total
							if (is_array($model->bitmask_fields) && count($model->bitmask_fields)) {
								$bitmask_keys = array_keys($model->bitmask_fields);
								if (in_array($field, $bitmask_keys)) {
									$bitmask_total = $item[$field];
									$value_str = '';
									$i = 0;
									foreach($model->bitmask_fields[$field] as $bit=>$val) {
										if($bit & $bitmask_total) {
											if($i > 0) {
												$value_str .= ', ';
											}
											$value_str .= $val;
											$i ++;
										}
									}
									$item[$field] = $value_str;
								}
							}

							// Any extra fields get dealt with here.
							if (isset($model->excel_extra_fields)) {
								foreach ($model->excel_extra_fields as $key => $value) {
									if ($field == $key) {
										$extra_name = $value[0];
										$extra_attribute = $value[1];
										$extra_key = $value[2];
										$extra_info = NModel::factory($extra_name);
										if (method_exists($extra_info, $extra_attribute)) {
											$item[$field] = $extra_info->$extra_attribute($original_item["$extra_key"]);
										} else {
											$extra_info->get($original_item["$extra_key"]);
											$item[$field] = $extra_info->$extra_attribute;
										}
										unset($extra_info);
									}
								}
							}
							// If it's an uploaded file, put the address in the conf.php before it so that it
							// turns into a link in Excel.
							if (eregi(UPLOAD_DIR, $item[$field])) {
								$item[$field] = PUBLIC_SITE . ereg_replace("^/", "", $item[$field]);
							}
							$worksheet->write($y, $x, $this->convert_characters($item[$field]));
							$x++;
						}
					}
					$y++;
					unset($original_item);
					unset($item);
				}
				// sending HTTP headers
				$xls_filename = $model_name . '_entries.xls';
				$workbook->send($xls_filename);
				$workbook->close();
			}
		}
	}

	function convert_characters($text) {
		$dict  = array(chr(138) => '�', chr(141) => '�', chr(142) => '�', chr(145) => "'", chr(146) => "'",
										chr(147) => '"', chr(148) => '"', chr(150) => '-', chr(151) => '-', chr(154) => '�',
										chr(157) => '�', chr(158) => '�', chr(160) => ' ', chr(161) => '�', chr(173) => '�',
										chr(188) => '�', chr(189) => '�', chr(190) => '�', chr(191) => '�', chr(192) => '�',
										chr(193) => '�', chr(194) => '�', chr(195) => '�', chr(196) => '�', chr(197) => '�',
										chr(198) => '�', chr(199) => '�', chr(200) => '�', chr(201) => '�', chr(202) => '�',
										chr(203) => '�', chr(204) => '�', chr(205) => '�', chr(206) => '�', chr(207) => '�',
										chr(209) => '�', chr(210) => '�', chr(211) => '�', chr(212) => '�', chr(213) => '�',
										chr(214) => '�', chr(216) => '�', chr(217) => '�', chr(218) => '�', chr(219) => '�',
										chr(220) => '�', chr(221) => '�', chr(223) => '�', chr(224) => '�', chr(225) => '�',
										chr(226) => '�', chr(227) => '�', chr(228) => '�', chr(229) => '�', chr(230) => '�',
										chr(231) => '�', chr(232) => '�', chr(233) => '�', chr(234) => '�', chr(235) => '�',
										chr(236) => '�', chr(237) => '�', chr(238) => '�', chr(239) => '�', chr(241) => '�',
										chr(242) => '�', chr(243) => '�', chr(244) => '�', chr(245) => '�', chr(246) => '�',
										chr(248) => '�', chr(249) => '�', chr(250) => '�', chr(251) => '�', chr(252) => '�',
										chr(253) => '�', chr(255) => '�',

										'&#138;' => '�', '&#141;' => '�', '&#142;' => '�', '&#145;' => "'", '&#146;' => "'",
										'&#147;' => '"', '&#148;' => '"', '&#150;' => '-', '&#151;' => '-', '&#154;' => '�',
										'&#157;' => '�', '&#158;' => '�', '&#160;' => ' ', '&#161;' => '�', '&#173;' => '�',
										'&#188;' => '�', '&#189;' => '�', '&#190;' => '�', '&#191;' => '�', '&#192;' => '�',
										'&#193;' => '�', '&#194;' => '�', '&#195;' => '�', '&#196;' => '�', '&#197;' => '�',
										'&#198;' => '�', '&#199;' => '�', '&#200;' => '�', '&#201;' => '�', '&#202;' => '�',
										'&#203;' => '�', '&#204;' => '�', '&#205;' => '�', '&#206;' => '�', '&#207;' => '�',
										'&#209;' => '�', '&#210;' => '�', '&#211;' => '�', '&#212;' => '�', '&#213;' => '�',
										'&#214;' => '�', '&#216;' => '�', '&#217;' => '�', '&#218;' => '�', '&#219;' => '�',
										'&#220;' => '�', '&#221;' => '�', '&#223;' => '�', '&#224;' => '�', '&#225;' => '�',
										'&#226;' => '�', '&#227;' => '�', '&#228;' => '�', '&#229;' => '�', '&#230;' => '�',
										'&#231;' => '�', '&#232;' => '�', '&#233;' => '�', '&#234;' => '�', '&#235;' => '�',
										'&#236;' => '�', '&#237;' => '�', '&#238;' => '�', '&#239;' => '�', '&#241;' => '�',
										'&#242;' => '�', '&#243;' => '�', '&#244;' => '�', '&#245;' => '�', '&#246;' => '�',
										'&#248;' => '�', '&#249;' => '�', '&#250;' => '�', '&#251;' => '�', '&#252;' => '�',
										'&#253;' => '�', '&#255;' => '�',

										'� ' => '�', 'Œ' => '�', 'Ž' => '�', 'š' => '�', 'œ' => '�', 'ž' => '�', 'Ÿ' => '�',
										'¥' => '�', 'µ' => '�', 'À' => '�', 'Á' => '�', 'Â' => '�', 'Ã' => '�', 'Ä' => '�',
										'Å' => '�', 'Æ' => '�', 'Ç' => '�', 'È' => '�', 'É' => '�', 'Ê' => '�', 'Ë' => '�',
										'Ì' => '�', 'Í' => '�', 'Î' => '�', 'Ï' => '�', 'Ð' => '�', 'Ñ' => '�', 'Ò' => '�',
										'Ó' => '�', 'Ô' => '�', 'Õ' => '�', 'Ö' => '�', 'Ø' => '�', 'Ù' => '�', 'Ú' => '�',
										'Û' => '�', 'Ü' => '�', 'Ý' => '�', 'ß' => '�', '� ' => '�', 'á' => '�', 'â' => '�',
										'ã' => '�', 'ä' => '�', 'å' => '�', 'æ' => '�', 'ç' => '�', 'è' => '�', 'é' => '�',
										'ê' => '�', 'ë' => '�', 'ì' => '�', 'í' => '�', 'î' => '�', 'ï' => '�', 'ð' => '�',
										'ñ' => '�', 'ò' => '�', 'ó' => '�', 'ô' => '�', 'õ' => '�', 'ö' => '�', 'ø' => '�',
										'ù' => '�', 'ú' => '�', 'û' => '�', 'ü' => '�', 'ý' => '�', 'ÿ' => '�', "¿" => '�',
										'¼' => '�', '½' => '�', '¾' => '�', '� ' => '�', '' => '�', '' => '�', '¡' => '�', '­' => '-',
										'“' => '"', '”' => '"', '–' => '-', "\n" => ' ', "\r" => ' ', '’' => "'", '�' => '"',

										'&iquest;' => '�', '&AElig;' => '�', '&Aacute;' => '�', '&Acirc;' => '�', '&Agrave;' => '�',
										'&Aring;' => '�', '&Atilde;' => '�', '&Auml;' => '�', '&Ccedil;' => '�', '&ETH;' => '�',
										'&Eacute;' => '�', '&Ecirc;' => '�', '&Egrave;' => '�', '&Euml;' => '�', '&Iacute;' => '�',
										'&Icirc;' => '�', '&Igrave;' => '�', '&Iuml;' => '�', '&Ntilde;' => '�', '&Oacute;' => '�',
										'&Ocirc;' => '�', '&Ograve;' => '�', '&Oslash;' => '�', '&Otilde;' => '�', '&Ouml;' => '�',
										'&Uacute;' => '�', '&Ucirc;' => '�', '&Ugrave;' => '�', '&Uuml;' => '�', '&Yacute;' => '�',
										'&aacute;' => '�', '&acirc;' => '�', '&aelig;' => '�', '&agrave;' => '�', '&aring;' => '�',
										'&atilde;' => '�', '&auml;' => '�', '&ccedil;' => '�', '&eacute;' => '�', '&ecirc;' => '�',
										'&egrave;' => '�', '&eth;' => '�', '&euml;' => '�', '&frac12;' => '�', '&frac14;' => '�',
										'&frac34;' => '�', '&iacute;' => '�', '&icirc;' => '�', '&iexcl;' => '�', '&igrave;' => '�',
										'&iquest;' => '�', '&iuml;' => '�', '&mdash;' => '�', '&micro;' => '�', '&ndash;' => '�',
										'&ntilde;' => '�', '&oacute;' => '�', '&ocirc;' => '�', '&ograve;' => '�', '&oslash;' => '�',
										'&otilde;' => '�', '&ouml;' => '�', '&quot;' => '"', '&shy;' => '�', '&szlig;' => '�',
										'&uacute;' => '�', '&ucirc;' => '�', '&ugrave;' => '�', '&uuml;' => '�', '&yacute;' => '�',
										'&yen;' => '�', '&yuml;' => '�', '&#8212;' => '-', "\n" => ' ', "\r" => ' ');

		return strtr($text, $dict);
	}

}
?>