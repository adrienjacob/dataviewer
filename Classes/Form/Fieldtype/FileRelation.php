<?php
namespace MageDeveloper\Dataviewer\Form\Fieldtype;

use MageDeveloper\Dataviewer\Domain\Model\Field;
use MageDeveloper\Dataviewer\Domain\Model\RecordValue;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

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
class FileRelation extends AbstractFieldtype implements FieldtypeInterface
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
		$this->formDataProviders[] = \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew::class;
		$this->formDataProviders[] = \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineIsOnSymmetricSide::class;

		$this->formDataProviders[] = \TYPO3\CMS\Backend\Form\FormDataProvider\InlineOverrideChildTca::class;
		$this->formDataProviders[] = \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems::class;
		$this->formDataProviders[] = \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineExpandCollapseState::class;
		$this->formDataProviders[] = \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineConfiguration::class;
		$this->formDataProviders[] = \TYPO3\CMS\Backend\Form\FormDataProvider\TcaRecordTitle::class;
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
							// New
							"overrideChildTca" => [
								"columns" => [
									"uid_local" => [
										"config" => [
											"appearance" => [
												"elementBrowserType" => "file",
												"elementBrowserAllowed" => $this->getField()->getConfig("allowed"),
											],
										],
									],
								],
							],
							// Old
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
	
	/**
	 * Prepares the TCA Array with
	 * the form data providers
	 *
	 * @param array $tca
	 */
	public function prepareTca(array &$tca)
	{
		$fieldName = $tca["fieldName"];

		//maxitems
		if($maxitems = $this->getField()->getConfig("maxitems"))
			$tca["processedTca"]["columns"][$fieldName]["config"]["maxitems"] = $maxitems;

		//minitems
		if($minitems = $this->getField()->getConfig("minitems"))
			$tca["processedTca"]["columns"][$fieldName]["config"]["minitems"] = $minitems;

		//size
		if($size = $this->getField()->getConfig("size"))
			$tca["processedTca"]["columns"][$fieldName]["config"]["size"] = $size;

		//show_thumbs
		if($showThumbs = $this->getField()->getConfig("show_thumbs"))
			$tca["processedTca"]["columns"][$fieldName]["config"]["show_thumbs"] = $showThumbs;

		//multiple
		if($multiple = $this->getField()->getConfig("multiple"))
			$tca["processedTca"]["columns"][$fieldName]["config"]["multiple"] = (int)$multiple;

		//selectedListStyle
		if($selectedListStyle = $this->getField()->getConfig("selectedListStyle"))
			$tca["processedTca"]["columns"][$fieldName]["config"]["selectedListStyle"] = $selectedListStyle;

		//allowed
		if($allowed = $this->getField()->getConfig("allowed"))
			$tca["processedTca"]["columns"][$fieldName]["config"]["allowed"] = $allowed;

		//disallowed
		if($disallowed = $this->getField()->getConfig("disallowed"))
			$tca["processedTca"]["columns"][$fieldName]["config"]["disallowed"] = $disallowed;

		//max_size
		if($max_size = $this->getField()->getConfig("max_size"))
			$tca["processedTca"]["columns"][$fieldName]["config"]["max_size"] = $max_size;

		//hideMoveIcons
		if($hideMoveIcons = $this->getField()->getConfig("hideMoveIcons"))
			$tca["processedTca"]["columns"][$fieldName]["config"]["hideMoveIcons"] = (int)$hideMoveIcons;

		//disable_controls
		if($disable_controls = $this->getField()->getConfig("disable_controls"))
			$tca["processedTca"]["columns"][$fieldName]["config"]["disable_controls"] = $disable_controls;

		parent::prepareTca($tca);
	}

}
