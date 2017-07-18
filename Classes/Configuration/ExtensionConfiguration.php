<?php
namespace MageDeveloper\Dataviewer\Configuration;

use \MageDeveloper\Dataviewer\Utility\LocalizationUtility as Locale;

/**
 * MageDeveloper Dataviewer Extension
 * -----------------------------------
 *
 * @category    TYPO3 Extension
 * @package     MageDeveloper\Dataviewer
 * @author		Bastian Zagar
 * @copyright   Magento Developers / magedeveloper.de <kontakt@magedeveloper.de>
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ExtensionConfiguration
{
	/**
	 * Extension Key
     * 
	 * @var string
	 */
	const EXTENSION_KEY = "dataviewer";

    /**
     * Tables
     * 
     * @var string
     */
	const EXTENSION_RECORD_TABLE 			= "tx_dataviewer_domain_model_record";
	const EXTENSION_RECORD_VALUE_TABLE		= "tx_dataviewer_domain_model_recordvalue";

	/**
	 * Gets the language field for records
	 * 
	 * @return string
	 */
	public static function getRecordsLanguageField()
	{
		return $GLOBALS["TCA"][self::EXTENSION_RECORD_TABLE]["ctrl"]["languageField"];
	}

	/**
	 * Gets the language field for recordValues
	 * 
	 * @return string
	 */
	public static function getRecordValuesLanguageField()
	{
		return $GLOBALS["TCA"][self::EXTENSION_RECORD_VALUE_TABLE]["ctrl"]["languageField"];
	}
}
