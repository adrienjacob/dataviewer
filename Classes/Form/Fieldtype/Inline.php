<?php
namespace MageDeveloper\Dataviewer\Form\Fieldtype;

use MageDeveloper\Dataviewer\Domain\Model\Field;
use MageDeveloper\Dataviewer\Domain\Model\RecordValue;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
class Inline extends AbstractFieldtype implements FieldtypeInterface
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

		$this->formDataProviders[] = \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsOverrides::class;
		$this->formDataProviders[] = \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessCommon::class;
		$this->formDataProviders[] = \TYPO3\CMS\Backend\Form\FormDataProvider\InlineOverrideChildTca::class;
		$this->formDataProviders[] = \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems::class;
		$this->formDataProviders[] = \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems::class;
		$this->formDataProviders[] = \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineExpandCollapseState::class;
		$this->formDataProviders[] = \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineConfiguration::class;
		$this->formDataProviders[] = \TYPO3\CMS\Backend\Form\FormDataProvider\TcaRecordTitle::class;
		
		// We need to add TcaInline to the data providers, if we are not in ajax context
		if (!$_SERVER['HTTP_X_REQUESTED_WITH'] || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest' ) {
			$this->formDataProviders[] = \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInline::class;
		}
		
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
		$value 						= $this->getValue();
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
							"foreign_table" => $this->getField()->getConfig("foreign_table"),
							"overrideChildTca" => $this->getField()->getForeignRecordDefaults(),
							"maxitems"      => 9999,
							"appearance" => [
								"collapseAll" => 1,
								"levelLinksPosition" => "top",
								"showSynchronizationLink" => 1,
								"showPossibleLocalizationRecords" => 1,
								"useSortable" => 1,
								"showAllLocalizationLink" => 0,
							],
							"behaviour" => [
								"localizationMode" => "none",
								"localizeChildrenAtParentLocalization" => false,
							],
						],
					],
				],
			],
			"inlineStructure" => [],
			"inlineFirstPid" => $this->getInlineFirstPid(),
			"inlineResolveExistingChildren" => true,
			"inlineCompileExistingChildren"=> true,
			//"defaultLanguageRow" => $databaseRow,
			"defaultLanguageRow" => null,
		];

		$this->prepareTca($tca);
		return $tca;
	}

	/**
	 * Gets the inline first pid setting
	 * for determe the pid of which the
	 * records shall be stored
	 * 
	 * @return int
	 */
	protected function getInlineFirstPid()
	{
		if($this->getField()->getConfig("pid_config") > 0)
			return (int)$this->getField()->getConfig("pid_config");
	
		if($this->getRecord()->getPid() > 0)
			return $this->getRecord()->getPid();
	
		$edit = GeneralUtility::_GET("edit");
		if(isset($edit["tx_dataviewer_domain_model_record"]))
			return (int)key($edit["tx_dataviewer_domain_model_record"]);
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
