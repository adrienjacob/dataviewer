<?php
namespace MageDeveloper\Dataviewer\UserFunc;

use MageDeveloper\Dataviewer\Utility\LocalizationUtility;
use MageDeveloper\Dataviewer\Utility\StringUtility;
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
class Field
{
	/**
	 * Object Manager
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 * @inject
	 */
	protected $objectManager;

	/**
	 * Field Repository
	 *
	 * @var \MageDeveloper\Dataviewer\Domain\Repository\FieldRepository
	 * @inject
	 */
	protected $fieldRepository;

	/**
	 * Record Repository
	 *
	 * @var \MageDeveloper\Dataviewer\Domain\Repository\RecordRepository
	 * @inject
	 */
	protected $recordRepository;

	/**
	 * FlexForm Service
	 *
	 * @var \MageDeveloper\Dataviewer\Service\FlexFormService
	 * @inject
	 */
	protected $flexFormService;

	/**
	 * Plugin Settings Service
	 *
	 * @var \MageDeveloper\Dataviewer\Service\Settings\Plugin\PluginSettingsService
	 * @inject
	 */
	protected $pluginSettingsService;

	/**
	 * Constructor
	 *
	 * @return Field
	 */
	public function __construct()
	{
		$this->objectManager 			= \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
		$this->fieldRepository			= $this->objectManager->get(\MageDeveloper\Dataviewer\Domain\Repository\FieldRepository::class);
		$this->recordRepository			= $this->objectManager->get(\MageDeveloper\Dataviewer\Domain\Repository\RecordRepository::class);
		$this->flexFormService			= $this->objectManager->get(\MageDeveloper\Dataviewer\Service\FlexFormService::class);
		$this->pluginSettingsService	= $this->objectManager->get(\MageDeveloper\Dataviewer\Service\Settings\Plugin\PluginSettingsService::class);
	}

	/**
	 * Displays the generated field identifier for frontend identification
	 *
	 * @param array $config
	 * @param array $parentObject
	 * @return string
	 */
	public function displayGeneratedFieldIdentifier(array &$config, &$parentObject)
	{
		$row = $config["row"];
		$text = ($row["variable_name"] != "")?$row["variable_name"]:$row["frontend_label"];
		$code = StringUtility::createCodeFromString($text);
		$title = LocalizationUtility::translate("formvalue_access_to_hidden_field");
		$recordName = $this->pluginSettingsService->getRecordVarName();

		if (!$code) $code = "<em>generated on save</em>";

		$table = $config["table"];
		$uid = $row["uid"];
		
		$frontendLabelFieldName = "input[data-formengine-input-name=\"data[{$table}][{$uid}][frontend_label]\"]";
		$variableNameFieldName 	= "input[data-formengine-input-name=\"data[{$table}][{$uid}][variable_name]\"]";
		$variableValueFieldName = "input[name=\"data[{$table}][{$uid}][variable_name]\"]";
		$generatedCodeFieldName = "div#generated_code";

		$script = "
		<script type=\"text/javascript\">
			var currentFrontendLabel;
			var currentVariableName;
			var canChangeVariableName = false;
			TYPO3.jQuery(function($) {
			
				var slug = function(str) {
					str = str.replace(/^\s+|\s+$/g, ''); // trim
					str = str.toLowerCase();
					
					// remove accents, swap ñ for n, etc
					var from = \"ãàáäâẽèéëêìíïîõòóöôùúüûñç·/_,:;\";
					var to   = \"aaaaaeeeeeiiiiooooouuuunc------\";
					for (var i=0, l=from.length ; i<l ; i++) {
						str = str.replace(new RegExp(from.charAt(i), 'g'), to.charAt(i));
					}
						
					str = str.replace(/[^a-z -]/g, '') // remove invalid chars
							 .replace(/\s+/g, '') // collapse whitespace
							 .replace(/-+/g, ''); // collapse dashes
							 
					return str;	     
				};
				
				var fetchFrontendLabel = function() {
					return $('{$frontendLabelFieldName}').val();
				};
				
				var fetchVariableName = function() {
					return $('{$variableValueFieldName}').val();
				};
				
				currentFrontendLabel = fetchFrontendLabel();
				currentVariableName = fetchVariableName();
				
				if (currentVariableName == '')
					canChangeVariableName = true;
	
				// Changing the value when the frontend label field is changed
				$('{$frontendLabelFieldName}').keyup(function(){
					var str = $(this).val();
					str = slug(str);
					
					// We can only change the variable name if it is empty
					if (canChangeVariableName)
					{
						$('{$variableNameFieldName}').val(str);
						$('{$variableValueFieldName}').val(str);
						
						var code = str;
						if (code == '') {
							code = '<em>generated on save</em>';
						}
						
						$('{$generatedCodeFieldName}').html(code);
					}
				});
					
				// Changing the value when the variable_name field is changed
				$('{$variableNameFieldName}').keyup(function(){
					var str = $(this).val();
					str = slug(str);
					
					if (str == '')
						canChangeVariableName = true;
					else
						canChangeVariableName = false;
					
					var code = str;
					if (code == '') {
						code = '<em>generated on save</em>';
					}
						
					$('{$generatedCodeFieldName}').html(code);
					
				});
			
			});
		</script>
		";

		$html = "";
		$html .= $script;
		$html .= "<strong>{$title}</strong>";
		$html .= "<span style=\"font-family: Courier, Courier new, monospace; font-size:16px; float:left; width:100%;\" class='callout callout-info'>";
		$html .= '{'.$recordName.'.<div id="generated_code" style="display:inline; color:#F05C00; font-weight:700;"><strong>'.$code.'</strong></div>}';
		$html .= "</span><br />";

		return $html;
	}

	/**
	 * Displays all available field ids for helping while
	 * using display conditions
	 *
	 * @param array $config
	 * @param array $parentObject
	 * @return string
	 */
	public function displayAvailableFieldIds(array &$config, &$parentObject)
	{
		$row = $config["row"];
		$pid = $row["pid"];
		$title = LocalizationUtility::translate("available_field_ids");
		$fields = $this->fieldRepository->findAllOnPid($pid);


		$html = "";
		$html .= "<strong>{$title}</strong>";
		$html .= "<span style=\"font-family: Courier, Courier new, monospace; font-size:12px; float:left; width:100%;\" class='callout callout-info'>";

		if(count($fields))
		{
			foreach($fields as $_field)
			{
				/* @var \MageDeveloper\Dataviewer\Domain\Model\Field $_field */
				$html .= "[ID: {$_field->getUid()}]\t\t";
				$html .= "{$_field->getFrontendLabel()}";
				$html .= "<br />";

			}

			$html .= "<br />";
			$html .= "Example: FIELD:2:=:Selected Value";

		}
		else
		{
			$html .= "---<br />";
		}


		$html .= "</span><br />";

		return $html;
	}

	/**
	 * Populate fields
	 *
	 * @param array $config Configuration Array
	 * @param array $parentObject Parent Object
	 * @return array
	 */
	public function populateFields(array &$config, &$parentObject)
	{
		$options = [];

		$fields = $this->fieldRepository->findAll(false);

		$sorted = [];
		foreach($fields as $_field)
		{
			$pid = $_field->getPid();
			$sorted[$pid][] = $_field;
		}

		ksort($sorted);

		foreach($sorted as $pid)
		{
			foreach($pid as $_field)
			{
				$label = $this->_getFieldLabel($_field);
				$options[] = [$label, $_field->getUid()];
			}
		}

		$config["items"] = array_merge($config["items"], $options);
	}

	/**
	 * Populate fields
	 *
	 * @param array $config Configuration Array
	 * @param array $parentObject Parent Object
	 * @return array
	 */
	public function populateFieldsOnCurrentPid(array &$config, &$parentObject)
	{
		$pid = $config["flexParentDatabaseRow"]["pid"];

		$options = [];
		$fields = $this->fieldRepository->findAllOnPids([$pid], true);

		$sortedFields = [];

		foreach($fields as $_field)
		{
			$type = $_field->getType();
			$sortedFields[$type][] = $_field;
		}

		ksort($sortedFields);

		foreach($sortedFields as $_type=>$_fields)
		{
			foreach($_fields as $_field)
			{
				$label = $this->_getFieldLabel($_field);
				$options[] = [$label, $_field->getUid()];
			}
		}

		$config["items"] = $options;
	}

	/**
	 * Populate fields
	 *
	 * @param array $config Configuration Array
	 * @param array $parentObject Parent Object
	 * @return array
	 */
	public function populateFieldsOnStoragePages(array &$config, &$parentObject)
	{
		$pages = $config["flexParentDatabaseRow"]["pages"];
		
		if(!is_array($pages))
			$pages = GeneralUtility::trimExplode(",", $pages);
			
		$pids = [];
		foreach($pages as $_page)
		{
			if(is_array($_page)) {
				$pids[] = $_page["uid"];
			}
			else {
				preg_match('/(?<table>.*)_(?<uid>[0-9]{0,11})|.*/', $_page, $match);
				
				if(is_array($match))
				{
					if(isset($match["uid"]))
						$pids[] = $match["uid"];
					else
						$pids[] = $match[0];
				}
			}
		
		}

		$options = [];
		$fields = $this->fieldRepository->findAllOnPids($pids);

		foreach($fields as $_field)
		{
			$label = $this->_getFieldLabel($_field);
			$options[] = [$label, $_field->getUid()];
		}

		$config["items"] = array_merge($config["items"], $options);
	}

	/**
	 * Populate fields field by record id
	 *
	 * @param array $config Configuration Array
	 * @param array $parentObject Parent Object
	 * @return array
	 */
	public function populateFieldsByRecord(array &$config, &$parentObject)
	{
		$options = [];

		// Plugin Settings Check
		if (is_array($config["row"]) && isset($config["row"]["settings.single_record_selection"])) {
			if (is_array($config["row"]["settings.single_record_selection"])) {
				$singleRecordId = (int)reset($config["row"]["settings.single_record_selection"]);
			} else {
				$singleRecordId = (int)$config["row"]["settings.single_record_selection"];
			}
		}
		else {
			// Variable Configuration
			$singleRecordId = (int)reset($config["row"]["record"]);
		}

		$record = $this->recordRepository->findByUid($singleRecordId, false);
		if ($record instanceof \MageDeveloper\Dataviewer\Domain\Model\Record)
		{
			$types = $record->getDatatype()->getSortedFields();

			foreach($types as $_type=>$_fields)
			{
				$options[] = [strtoupper($_type), "--div--"];

				if(count($_fields)>0)
				{
					foreach($_fields as $_field)
					{
						/* @var \MageDeveloper\Dataviewer\Domain\Model\Field $_field */
						$label	 	= $this->_getFieldLabel($_field);
						$options[] 	= [$label, $_field->getUid()];
					}


				}
			}

		}

		$config["items"] = array_merge($config["items"], $options);

	}

	/**
	 * Transforms a label for a field
	 *
	 * @param \MageDeveloper\Dataviewer\Domain\Model\Field $field
	 * @return string
	 */
	protected function _getFieldLabel(\MageDeveloper\Dataviewer\Domain\Model\Field $field)
	{
		return "[{$field->getPid()}] " . strtoupper($field->getType()) . ": " . $field->getFrontendLabel() . " {".$field->getCode()."}";
	}
}
