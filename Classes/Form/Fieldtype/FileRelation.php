<?php
namespace MageDeveloper\Dataviewer\Form\Fieldtype;

use MageDeveloper\Dataviewer\Domain\Model\Field;
use MageDeveloper\Dataviewer\Domain\Model\RecordValue;

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
class FileRelation extends Inline
{
	/**
	 * Initializes all form data providers to
	 * $this->formDataProviders
	 *
	 * Will be executed in order of the added providers!
	 *
	 * @return void
	 */
	public function initializeFormDataProviders()
	{
		$this->formDataProviders[] = \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems::class;
		$this->formDataProviders[] = \MageDeveloper\Dataviewer\Form\FormDataProvider\TcaInlineFile::class;

		parent::initializeFormDataProviders();
	}

	/**
	 * Gets built tca array
	 *
	 * @return array
	 */
	public function buildTca()
	{
		$fieldName 					= $this->getField()->getUid();
		$tableName 					= "tx_dataviewer_domain_model_record";
		$value 						= $this->getValue(false, true);
		$databaseRow 				= $this->getDatabaseRow();
		$databaseRow[$fieldName] 	= $value;
		
		$tca = [
			"command" => "edit",
			"tableName" => $tableName,
			"databaseRow" => $databaseRow,
			"fieldName" => $fieldName,
			"processedTca" => [
				"ctrl" => [
					"label" => $this->getField()->getFrontendLabel(),
				],
				"columns" => [
					$fieldName => [
						"exclude" => (int)$this->getField()->isExclude(),
						"label" => $this->getField()->getFrontendLabel(),
						"config" => [
							"type" => "inline",
							"foreign_table" => "sys_file_reference",
							"foreign_field" => "uid_foreign",
							"foreign_sortby" => "sorting_foreign",
							"foreign_table_field" => "tablenames",
							"foreign_match_fields" => [
								"fieldname" => $fieldName
							],
							"foreign_label" => "uid_local",
							"foreign_selector" => "uid_local",
							"foreign_selector_fieldTcaOverride" => [
								"config" => [
									"appearance" => [
										"elementBrowserType" => "file",
										"elementBrowserAllowed" => $this->getField()->getConfig("allowed"),
									]
								]
							],
							"filter" => [
								[
									"userFunc" => "TYPO3\\CMS\\Core\\Resource\\Filter\\FileExtensionFilter->filterInlineChildren",
									"parameters" => [
										"allowedFileExtensions" => $this->getField()->getConfig("allowed"),
										"disallowedFileExtensions" => $this->getField()->getConfig("disallowed"),
									]
								]
							],
							"appearance" => [
								"useSortable" => TRUE,
								"headerThumbnail" => [
									"field" => "uid_local",
									"width" => "45",
									"height" => "45c",
								],
								"showPossibleLocalizationRecords" => FALSE,
								"showRemovedLocalizationRecords" => FALSE,
								"showSynchronizationLink" => FALSE,
								"showAllLocalizationLink" => FALSE,
								"fileUploadAllowed" => (bool)$this->getField()->getConfig("fileUploadAllowed"), 
								"enabledControls" => [
									"info" => true,
									"new" => false,
									"dragdrop" => true,
									"sort" => true,
									"hide" => true,
									"delete" => true,
									"localize" => true,
								],
							],
							"behaviour" => [
								"localizationMode" => "select",
								"localizeChildrenAtParentLocalization" => TRUE,
							],
						],
					],
				],
			],
			"inlineStructure" => [],
			"inlineFirstPid" => $this->getInlineFirstPid(),
			"inlineResolveExistingChildren" => true,
			"inlineCompileExistingChildren"=> true,
		];
		
		$this->prepareTca($tca);
		
		$this->tca = $tca;
		return $this->tca;
	}

}
