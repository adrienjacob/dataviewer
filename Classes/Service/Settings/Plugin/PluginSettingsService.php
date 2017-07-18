<?php
namespace MageDeveloper\Dataviewer\Service\Settings\Plugin;

use MageDeveloper\Dataviewer\Configuration\ExtensionConfiguration as Configuration;
use MageDeveloper\Dataviewer\Service\Settings\AbstractSettingsService;
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
class PluginSettingsService extends AbstractSettingsService
{
	/**
	 * Template Selection
	 * @var string
	 */
	const TEMPLATE_SELECTION_CUSTOM			= "CUSTOM";
	const TEMPLATE_SELECTION_FLUID			= "FLUID";

	/**
	 * Plugin Name
	 * @var string
	 */
	protected $extensionName;

	/**
	 * Constructor
	 * 
	 * @return PluginSettingsService
	 */
	public function __construct()
	{
		$this->setExtensionName( "tx_" . Configuration::EXTENSION_KEY );
	}

	/**
	 * Sets the extension name
	 * 
	 * @param string $extensionName
	 * @return void
	 */
	public function setExtensionName($extensionName)
	{
		$this->extensionName = $extensionName;
	}

	/**
	 * Gets the extension name
	 * 
	 * @return string
	 */
	public function getExtensionName()
	{
		return $this->extensionName;
	}

	/**
	 * Gets the extension configuration
	 * by a given configuration pathj
	 * 
	 * @param string $path Path to the configuration
	 * @return string|null
	 */
	public function getConfiguration($path)
	{
		$extensionName = $this->getExtensionName();
		return $this->getExtensionConfiguration($extensionName, $path);
	}

	/**
	 * Gets the target content uid
	 *
	 * @return int
	 */
	public function getTargetContentUid()
	{
		return (int)$this->getSettingByCode("target_plugin");
	}

	/**
	 * Gets the configured variable name
	 * for records
	 * 
	 * @return string
	 */
	public function getRecordsVarName()
	{
		$name = $this->getConfiguration("settings.recordsVariableName");
		return \MageDeveloper\Dataviewer\Utility\StringUtility::createCodeFromString($name);
	}

	/**
	 * Gets the configured variable name for
	 * a single record
	 * 
	 * @return string
	 */
	public function getRecordVarName()
	{
		$name = $this->getConfiguration("settings.singleRecordVariableName");
		return \MageDeveloper\Dataviewer\Utility\StringUtility::createCodeFromString($name);
	}

	/**
	 * Gets the configured variable name for
	 * a part of a record
	 * 
	 * @return string
	 */
	public function getPartVarName()
	{
		$name = $this->getConfiguration("settings.partVariableName");
		return \MageDeveloper\Dataviewer\Utility\StringUtility::createCodeFromString($name);
	}

	/**
	 * Gets the configuration for the predefines
	 * templates
	 * 
	 * @return array
	 */
	public function getPredefinedTemplates()
	{
		$path = "templates";
		$configuration = $this->getConfiguration($path);
		
		return $configuration;		
	}
	
	/**
	 * Gets the predefined template by a given
	 * id
	 * 
	 * @param string $templateId
	 * @return string|null
	 */
	public function getPredefinedTemplateById($templateId)
	{
		$templates = $this->getPredefinedTemplates();
		return (isset($templates[$templateId]))?$templates[$templateId]:null;
	}

	/**
	 * Gets the template override setting
	 *
	 * @return null|string
	 */
	public function getTemplateOverride()
	{
		return $this->getSettingByCode("template_override");
	}

	/**
	 * Gets the value from the template
	 * selection
	 *
	 * @return null|string
	 */
	public function getTemplateSelection()
	{
		return $this->getSettingByCode("template_selection");
	}

	/**
	 * Retrieves the template path from the template selection
	 * either from the override or the selector box
	 *
	 * @return string
	 */
	public function getTemplate()
	{
		$templateSelection = $this->getTemplateSelection();
		$templateOverride  = $this->getTemplateOverride();

		if($templateSelection == self::TEMPLATE_SELECTION_CUSTOM && $templateOverride)
			return $templateOverride;

		return $this->getPredefinedTemplateById($templateSelection);
	}

	/**
	 * Checks if the plugin setting has a template
	 * override
	 *
	 * @return bool
	 */
	public function hasTemplate()
	{
		$templateSelection = $this->getTemplateSelection();
		$templateOverride  = $this->getTemplateOverride();

		if($templateSelection == self::TEMPLATE_SELECTION_CUSTOM)
			if($templateOverride)
				return true;
			else
				return false;

		if($templateSelection)
			return true;

		return false;
	}

	/**
	 * Gets the template switch conditions
	 * from the plugin configuration
	 *
	 * @return array
	 */
	public function getTemplateSwitchConditions()
	{
		$conditions = $this->getSettingByCode("template_switch");

		if(!is_array($conditions))
			$conditions = [];

		return $conditions;
	}

	/**
	 * Gets the entered fluid code
	 *
	 * @return null|string
	 */
	public function getFluidCode()
	{
		return $this->getSettingByCode("fluid_code");
	}

	/**
	 * Checks if the plugin wants to render custom
	 * fluid code
	 *
	 * @return bool
	 */
	public function isCustomFluidCode()
	{
		return ($this->getTemplateSelection() == self::TEMPLATE_SELECTION_FLUID);
	}

	/**
	 * Gets selected variable ids
	 *
	 * @return array
	 */
	public function getSelectedVariableIds()
	{
		$variableInjectionConfig = $this->getSettingByCode("variable_injection");
		$variablesFromInjection = GeneralUtility::trimExplode(",", $variableInjectionConfig, true);

		$variableInlineConfig = $this->getSettingByCode("inline_variable_injection");
		$variablesFromInline = GeneralUtility::trimExplode(",", $variableInlineConfig, true);

		return array_merge($variablesFromInjection, $variablesFromInline);
	}

	/**
	 * Debug Mode Enabled
	 *
	 * @return bool
	 */
	public function isDebug()
	{
		return (bool)$this->getSettingByCode("debug");
	}
}
