<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 alterNET Internet BV (support@alternet.nl)
 *  All rights reserved
 *
 *  This script is part of the Typo3 project. The Typo3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Update script for the 'automaketemplate' extension.
 *
 * @author	alterNET Internet BV <support@alternet.nl>
 */

/**
 * Update class - called by the Extension manager
 *
 * @author	alterNET Internet BV <support@alternet.nl>
 * @package TYPO3
 * @subpackage tx_automaketemplate
 */
class ext_update {

	private $ll = 'LLL:EXT:automaketemplate/locallang.xlf:updater.';

	/**
	 * Calculates if there is a potential reason for displaying an update warning
	 * @return bool
	 */
	public function access() {
		$result = FALSE;
		$selectFields = '*';
		$fromTable = 'sys_template';
		$whereClause = 'config LIKE \'%ereg_replace%\'' . BackendUtility::BEenableFields('sys_template') .
			BackendUtility::deleteClause('sys_template');

		$res = $this->getDatabaseConnection()->exec_SELECTquery($selectFields, $fromTable, $whereClause);
		if ($this->getDatabaseConnection()->sql_num_rows($res) > 0) {
			$result = TRUE;
		}
		return $result;
	}

	/**
	 * Main function to generate output
	 *
	 * @return string
	 */
	public function main() {
		$out = '<h3>' . $this->getLanguageObject()->sL($this->ll . 'heading') . '</h3>';
		$out .= '<p>' . $this->getLanguageObject()->sL($this->ll . 'message') . '</p>';

		return $out;
	}

	/**
	 * Get database connection object
	 *
	 * @return \TYPO3\CMS\Dbal\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

	/**
	 * @return \TYPO3\CMS\Lang\LanguageService
	 */
	protected function getLanguageObject() {
		return $GLOBALS['LANG'];
	}
}
