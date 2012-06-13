<?php
/*                                                                        *
 * This script belongs to the TYPO3 extension "tinyurls".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Hooks for the TYPO3 core engine
 */
class Tx_Tinyurls_Hooks_Tce {

	/**
	 * When a user stores a tinyurl record in the Backend the urlkey and the target_url_hash will be updated
	 *
	 * @param string $status (reference) Status of the current operation, 'new' or 'update
	 * @param string $table (refrence) The table currently processing data for
	 * @param string $id (reference) The record uid currently processing data for, [integer] or [string] (like 'NEW...')
	 * @param array $fieldArray (reference) The field array of a record
	 * @param t3lib_TCEmain $tcemain Reference to the TCEmain object that calls this hook
	 * @see t3lib_TCEmain::hook_processDatamap_afterDatabaseOperations()
	 */
	public function processDatamap_afterDatabaseOperations($status, $table, $id, $fieldArray, $tcemain) {

		if ($table != 'tx_tinyurls_urls') {
			return;
		}

		if (t3lib_div::isFirstPartOfStr($id, 'NEW')) {
			$id = $tcemain->substNEWwithIDs[$id];
		}

		$tinyUrlData = t3lib_BEfunc::getRecord('tx_tinyurls_urls', $id);
		$extensionConfiguration = Tx_Tinyurls_Utils_ConfigUtils::getExtensionConfiguration();

		$updateArray = array(
			'urlkey' => Tx_Tinyurls_Utils_UrlUtils::generateTinyurlKeyForUid($id, $extensionConfiguration),
			'target_url_hash' => Tx_Tinyurls_Utils_UrlUtils::generateTinyurlHash($tinyUrlData['target_url']),
		);

		$fieldArray = array_merge($fieldArray, $updateArray);

		/**
		 * @var t3lib_db $db
		 */
		$db = $GLOBALS['TYPO3_DB'];
		$db->exec_UPDATEquery('tx_tinyurls_urls', 'uid=' . $id, $updateArray);
	}

}
