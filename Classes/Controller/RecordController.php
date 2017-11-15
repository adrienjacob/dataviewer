<?php
namespace MageDeveloper\Dataviewer\Controller;

use MageDeveloper\Dataviewer\Domain\Model\Field;
use MageDeveloper\Dataviewer\Domain\Model\Record;
use MageDeveloper\Dataviewer\Domain\Model\Variable;
use MageDeveloper\Dataviewer\Service\Settings\Plugin\ListSettingsService;
use MageDeveloper\Dataviewer\Service\Settings\Plugin\SearchSettingsService;
use MageDeveloper\Dataviewer\Utility\DebugUtility;
use MageDeveloper\Dataviewer\Utility\LocalizationUtility as Locale;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentNameException;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

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

class RecordController extends AbstractController
{
	/***************************************************************************
	 * This controller manages the display of records or record parts.
	 * It is influenced by different session settings like sorting and filtering
	 * or the search.
	 *
	 * The list is possibly cached with a generated cache identfier resulting
	 * from a the parameters that cache during requesting.
	 ***************************************************************************/

	/**
	 * Storage Pids
	 *
	 * @var array
	 */
	protected $storagePids = [];

	/**
	 * Field Repository
	 *
	 * @var \MageDeveloper\Dataviewer\Domain\Repository\FieldRepository
	 * @inject
	 */
	protected $fieldRepository;

	/**
	 * List Settings Service
	 *
	 * @var \MageDeveloper\Dataviewer\Service\Settings\Plugin\ListSettingsService
	 * @inject
	 */
	protected $listSettingsService;

	/**
	 * The current cache identifier
	 *
	 * @var string
	 */
	protected $cacheIdentifier;

	/**
	 * Initializes the controller before invoking an action method.
	 *
	 * Override this method to solve tasks which all actions have in
	 * common.
	 *
	 * @return void
	 * @api
	 */
	protected function initializeAction()
	{
		// We obtain the cache lifetime from the configuration
		$lifetime = $this->listSettingsService->getCacheLifetime();
		$this->pluginCacheService->setLifetime($lifetime);
		
		// Setting the current selected language to the repository
		$languageUid = $GLOBALS["TSFE"]->sys_language_uid;
		$this->fieldRepository->setLanguageUid($languageUid);
		$this->recordRepository->setLanguageUid($languageUid);
		$this->variableRepository->setLanguageUid($languageUid);

		parent::initializeAction(); // TODO: Change the autogenerated stub
	}

	/**
	 * initializeView
	 * Initializes the view
	 *
	 * Adds some variables to view that could always
	 * be useful
	 *
	 * @param ViewInterface $view
	 * @return void
	 */
	protected function initializeView(ViewInterface $view)
	{
		// Inject current settings to the settings service
		$this->listSettingsService->setSettings($this->settings);
		
		parent::initializeView($view);
	}

	/**
	 * List Action
	 * Displays a list of selected records
	 *
	 * @return void
	 */
	public function listAction()
	{
		$cacheIdentifier = $this->getCacheIdentifier();
		$cache = $this->cacheManager->getCache("cache_hash");

		$lifetime = $this->listSettingsService->getCacheLifetime();
		$this->pluginCacheService->setLifetime($lifetime);
		$cached = false;

		$cachedIds = null;
		if($lifetime > 0)
			$cachedIds = $cache->get($cacheIdentifier);
			
		if(is_array($cachedIds))
		{
			$cached = true;
			// We get the valid record ids from the cache
			$ids = $cachedIds;
			$selectedRecords = $this->recordRepository->findByUids($ids);
		}
		else
		{
			// We obtain the cache lifetime from the configuration
			$this->pluginCacheService->setLifetime($lifetime);
			$selectedRecords = $this->_getSelectedRecords(); // We get the valid records ids

			$ids = [];
			foreach($selectedRecords as $_s)
				$ids[] = $_s->getUid();

			$cache->set($cacheIdentifier, $ids, ["records"], $lifetime);
		}

		// We set these records as currently active and valid
		$this->pluginCacheService->setValidRecordIds($cacheIdentifier, $ids);
		$this->sessionServiceContainer->getInjectorSessionService()->setActiveRecordIds($ids);

		// Custom Headers
		$customHeaders = $this->getCustomHeaders();
		$this->performCustomHeaders($customHeaders);

		if($this->settings["template_selection"] == "FLUID")
		{
			$view = $this->view;
			$source = $this->settings["fluid_code"];
			$view->setTemplateSource($source);
		}
		else
		{
			$view = $this->getStandaloneView(true);
			$templateSwitch = $this->getTemplateSwitch();
			if($templateSwitch)
				$view->setTemplatePathAndFilename($templateSwitch);
		}

		$view->assign($this->listSettingsService->getRecordsVarName(), $selectedRecords);
		$view->assign("cached", $cached);
		$view->assign("cacheIdentifier", $cacheIdentifier);

		$rendered = $view->render();

		if($this->listSettingsService->renderOnlyTemplate() && !$this->listSettingsService->isDebug())
		{
			echo $rendered;
			exit();
		}

		return $rendered;
	}

	/**
	 * Adds custom headers to the response object
	 *
	 * @param array $customHeaders
	 * @return bool
	 */
	public function performCustomHeaders($customHeaders)
	{
		if(!empty($customHeaders))
		{
			// Setting custom headers
			foreach($customHeaders as $_headerName=>$_headerValue)
				$this->response->setHeader($_headerName, $_headerValue, true);

			$this->response->sendHeaders();
			return true;
		}

		return false;
	}

	/**
	 * Gets all custom headers that are valid for
	 * the current view
	 *
	 * @return array
	 */
	public function getCustomHeaders()
	{
		// Custom Headers Configuration
		$customHeadersConfiguration = $this->listSettingsService->getCustomHeaders();

		// Get a view with all injected variables
		$view = $this->getStandaloneView(true);

		$customHeaders = [];
		foreach($customHeadersConfiguration as $_header)
		{
			$conditionStr = $_header["headers"]["condition"];
			$headerName = $_header["headers"]["name"];
			$headerValue = $_header["headers"]["value"];
			$headerValue = $this->_replaceMarkersInString($headerValue);

			if($conditionStr == "")
			{
				// Header is always valid
				$isValid = true;
			}
			else
			{
				// Since we yet do not know how to render the nodes separately, we
				// just render a simple full fluid condition here
				$conditionText = "<f:if condition=\"{$conditionStr}\">1</f:if>";
				$isValid = (bool)$view->renderSource($conditionText);
			}

			if($isValid)
				$customHeaders[$headerName] = $headerValue;
		}

		return $customHeaders;
	}

	/**
	 * Detail Action
	 * Displays a selected record
	 *
	 * @return void
	 */
	public function detailAction()
	{
		$selectedRecordId 	= $this->listSettingsService->getSelectedRecordId();
		$record 			= $this->recordRepository->findByUid($selectedRecordId, false);
		
		if (!$record instanceof Record)
			$record = null;

		// We set this record as currently active
		$activeRecordIds = [];
		if(!is_null($record))
			$activeRecordIds = [$record->getUid()];

		$this->sessionServiceContainer->getInjectorSessionService()->setActiveRecordIds($activeRecordIds);

		// Custom Headers
		$customHeaders = $this->getCustomHeaders();
		$this->performCustomHeaders($customHeaders);

		if($this->settings["template_selection"] == "FLUID")
		{
			$view = $this->view;

			$source = $this->settings["fluid_code"];
			$view->setTemplateSource($source);
		}
		else
		{
			$view = $this->getStandaloneView(true);

			$templateSwitch = $this->getTemplateSwitch();
			if($templateSwitch)
				$view->setTemplatePathAndFilename($templateSwitch);
		}

		// Override by datatype template setting
		if ($record instanceof Record && $record->getDatatype()->getTemplatefile() && !$this->listSettingsService->isDebug())
			$view->setTemplatePathAndFilename($record->getDatatype()->getTemplatefile());

		// Assigning the record to the view	
		$view->assign($this->listSettingsService->getRecordVarName(), $record);

		if($this->listSettingsService->renderOnlyTemplate() && !$this->listSettingsService->isDebug())
		{
			echo $view->render();
			exit();
		}

		return $view->render();
	}

	/**
	 * Part Action
	 * Displays a part from the selected record
	 *
	 * @return void
	 */
	public function partAction()
	{
		$selectedRecordId 	= $this->listSettingsService->getSelectedRecordId();
		$selectedFieldId	= $this->listSettingsService->getSelectedFieldId();

		// We set this record as currently active
		$this->sessionServiceContainer->getInjectorSessionService()->setActiveRecordIds(array($selectedRecordId));

		$record = $this->recordRepository->findByUid($selectedRecordId, false);
		$field  = $this->fieldRepository->findByUid($selectedFieldId, false);

		// Template Override by plugin setting
		$templateSwitch = $this->getTemplateSwitch();
		if($templateSwitch)
			$this->view->setTemplatePathAndFilename($templateSwitch);

		if ($record instanceof Record && $field instanceof Field)
		{
			$value = $record->getValueByField($field);
			$this->view->assign($this->listSettingsService->getPartVarName(), $value);
		}

		// Custom Headers
		$customHeaders = $this->getCustomHeaders();
		$this->performCustomHeaders($customHeaders);

		if($this->listSettingsService->renderOnlyTemplate() && !$this->listSettingsService->isDebug())
		{
			echo $this->view->render();
			exit();
		}
	}

	/**
	 * Dynamic Detail Action
	 * Displays record details on dynamic parameters
	 *
	 * @param int $record
	 * @return void
	 */
	public function dynamicDetailAction($record = null)
	{
		if(is_null($record) && $this->request->hasArgument("record"))
			$record = $this->request->getArgument("record");

		$cacheIdentifier = $this->getCacheIdentifier([$record]);

		/* @var Record $recordObj */
		$recordObj = $this->recordRepository->findByUid($record, true);

		if (!$recordObj instanceof Record)
			$recordObj = null;

		// We set this record as currently active
		$activeRecordIds = [];
		if(!is_null($recordObj))
			$activeRecordIds = [$recordObj->getUid()];

		$this->sessionServiceContainer->getInjectorSessionService()->setActiveRecordIds($activeRecordIds);

		// Custom Headers
		$customHeaders = $this->getCustomHeaders();
		$this->performCustomHeaders($customHeaders);

		if($this->settings["template_selection"] == "FLUID")
		{
			$view = $this->view;

			$source = $this->settings["fluid_code"];
			$view->setTemplateSource($source);
		}
		else
		{
			$view = $this->getStandaloneView(true);

			$templateSwitch = $this->getTemplateSwitch();
			if($templateSwitch)
				$view->setTemplatePathAndFilename($templateSwitch);
		}

		// Override by datatype template setting
		if ($recordObj instanceof Record && $recordObj->getDatatype()->getTemplatefile() && !$this->listSettingsService->isDebug())
			$view->setTemplatePathAndFilename($recordObj->getDatatype()->getTemplatefile());

		////////////////////////////////////////////////////
		// We need to obtain the selected records for
		// getting information about which records are
		// allowed for this dynamic detail action
		////////////////////////////////////////////////////
		// We obtain the cache lifetime from the configuration
		$lifetime = $this->listSettingsService->getCacheLifetime();
		$cachedIds = $this->pluginCacheService->getValidRecordIds($cacheIdentifier);

		if(is_array($cachedIds))
		{
			$ids = $cachedIds;	// We get the valid record ids from the cache
		}
		else
		{
			$this->pluginCacheService->setLifetime($lifetime);

			$selectedRecords = $this->_getSelectedRecords(); // We get the valid records ids

			$ids = [];
			foreach($selectedRecords as $_s)
				$ids[] = $_s->getUid();

			$this->pluginCacheService->setValidRecordIds($cacheIdentifier, $ids);
		}

		if ($record > 0 && !in_array($record, $ids) && $this->listSettingsService->getRecordSelectionType())
			return;

		// Get selected records and check if the record is allowed
		$view->assign($this->listSettingsService->getRecordVarName(), $recordObj);

		if($this->listSettingsService->renderOnlyTemplate() && !$this->listSettingsService->isDebug())
		{
			echo $view->render();
			exit();
		}

		return $view->render();
	}

	/**
	 * Ajax Request Action
	 * This action is the main entry for the ajax request handling.
	 * It initially shows the configured template and is then
	 * ready for the ajax call.
	 *
	 * @return string
	 */
	public function ajaxRequestAction()
	{
		// This is a plain forwarder to the first introduction
		// of the ajax response action, so the initial records
		// will also be loaded through ajax
	}

	/**
	 * Ajax Response Action
	 * This action is for handling ajax requests that
	 * are done with the dataviewer extension.
	 *
	 * It can handle different type of requests, given as
	 * arguments in this action
	 *
	 * @return string
	 */
	public function ajaxResponseAction()
	{
		if($this->request->hasArgument("uid"))
		{
			/* @var \MageDeveloper\Dataviewer\Service\FlexFormService $flexFormService */
			$flexFormService = $this->objectManager->get(\MageDeveloper\Dataviewer\Service\FlexFormService::class);
			$uid = $this->request->getArgument("uid");
			$cObj = BackendUtility::getRecord("tt_content", $uid);

			// Storage Page Ids
			$this->storagePids = GeneralUtility::trimExplode(",", $cObj["pages"]);

			// Settings Array
			$flexArr = $flexFormService->convertFlexFormContentToArray($cObj["pi_flexform"]);
			$this->settings = $flexArr["settings"];
			$this->listSettingsService->setSettings($this->settings);

			// Session Container Connection
			$this->sessionServiceContainer->setTargetUid($uid);

			$parameters = [];
			if($this->request->hasArgument("parameters"))
				$parameters = $this->request->getArgument("parameters");

			$view = $this->getStandaloneView(true);
			$additionalVariables = [];

			/////////////////////////////////////////////
			// Signal-Slot for hooking the ajax return //
			/////////////////////////////////////////////
			$this->signalSlotDispatcher->dispatch(
				__CLASS__,
				"ajaxResponsePreRecords",
				[
					&$parameters,
					&$uid,
					&$additionalVariables,
					&$this,
				]
			);

			$records = $this->_getSelectedRecords();

			$templateSwitch = $this->getTemplateSwitch();
			if($templateSwitch)
				$view->setTemplatePathAndFilename($templateSwitch);

			/////////////////////////////////////////////
			// Signal-Slot for hooking the ajax return //
			/////////////////////////////////////////////
			$this->signalSlotDispatcher->dispatch(
				__CLASS__,
				"ajaxResponsePostRecords",
				[
					&$records,
					&$parameters,
					&$uid,
					&$additionalVariables,
					&$this,
				]
			);

			$view->assign($this->listSettingsService->getRecordsVarName(), $records);
			$view->assign("ajax", 1);
			$view->assign("parameters", $parameters);
			$view->assign("cObj", $cObj);

			if(!empty($additionalVariables))
				$view->assignMultiple($additionalVariables);

			return $view->render();
		}

		return "";
	}

	/**
	 * Gets merged filters
	 *
	 * @return array
	 */
	protected function _getFilters()
	{
		/////////////////////////////////////////////////////////////////////////////////////////
		// Record Selection Type Filters
		/////////////////////////////////////////////////////////////////////////////////////////
		$selectionType 	= $this->listSettingsService->getRecordSelectionType();
		$selectFilters	= $this->_getAdditionalFiltersBySelectionType($selectionType);

		/////////////////////////////////////////////////////////////////////////////////////////
		// Field/Value Filter Settings
		/////////////////////////////////////////////////////////////////////////////////////////
		$fieldFilter	= $this->listSettingsService->getFieldValueFilters();

		/////////////////////////////////////////////////////////////////////////////////////////
		// Search Filters
		/////////////////////////////////////////////////////////////////////////////////////////
		$searchFields	= $this->sessionServiceContainer->getSearchSessionService()->getSearchFields();
		$searchString	= $this->sessionServiceContainer->getSearchSessionService()->getSearchString();
		$searchType		= $this->sessionServiceContainer->getSearchSessionService()->getSearchType();
		$searchFilters	= $this->_getAdditionalFiltersBySearch($searchType, $searchString, $searchFields);

		/////////////////////////////////////////////////////////////////////////////////////////
		// Filter Plugin Filters
		/////////////////////////////////////////////////////////////////////////////////////////
		$filter			= $this->sessionServiceContainer->getFilterSessionService()->getCleanSelectedOptions();

		/////////////////////////////////////////////////////////////////////////////////////////
		// Selection Plugin Filters
		/////////////////////////////////////////////////////////////////////////////////////////
		$selection		= $this->sessionServiceContainer->getSelectSessionService()->getSelectedRecords();
		$selectionFilters = $this->_getAdditionalFiltersByRecordSelection($selection);

		/////////////////////////////////////////////////////////////////////////////////////////
		// Letter Selection Plugin Filters
		/////////////////////////////////////////////////////////////////////////////////////////
		$letter			= $this->sessionServiceContainer->getLetterSessionService()->getSelectedLetter();
		$letterField	= $this->sessionServiceContainer->getLetterSessionService()->getLetterSelectionField();
		$letterFilters	= $this->_getAdditionalFiltersByLetterSelection($letter, $letterField);

		// Merging all Filters
		$filters	= array_merge($fieldFilter, $filter, $selectionFilters, $selectFilters, $searchFilters, $letterFilters);

		return $filters;
	}

	/**
	 * Generates a cache identifier by a few environment settings
	 * such as Filters, Sorting, Pagination
	 *
	 * @param array $additionalCacheParameters
	 * @return string
	 */
	public function getCacheIdentifier(array $additionalCacheParameters = [])
	{
		if(!$this->cacheIdentfier)
		{
			$filters 		= $this->_getFilters();
			$this->_replaceMarkersInFilters($filters);

			$limit			= $this->listSettingsService->getLimitation();
			$perPage		= $this->sessionServiceContainer->getPagerSessionService()->getPerPage();
			$selectedPage	= $this->sessionServiceContainer->getPagerSessionService()->getSelectedPage();

			// Sorting
			$sortField		= $this->sessionServiceContainer->getSortSessionService()->getSortField();
			$sortOrder		= $this->sessionServiceContainer->getSortSessionService()->getSortOrder();

			if(is_null($perPage)) $perPage = $this->listSettingsService->getPerPage();

			if(!$this->sessionServiceContainer->getSortSessionService()->hasOrderings() || !$this->_hasTargetPlugin("dataviewer_sort"))
			{
				// We initally set orderings from our plugin settings and will use
				// information from the sort plugin later, once it was used
				$sortField	= $this->listSettingsService->getSortField();
				$sortOrder	= $this->listSettingsService->getSortOrder();
			}

			$contentObj = $this->configurationManager->getContentObject();
			if($contentObj instanceof \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer &&
				isset($contentObj->data)
			) {
				$additionalCacheParameters["uid"]=$contentObj->data["uid"];
				$additionalCacheParameters["pid"]=$contentObj->data["pid"];
			}

			$variableIds = implode("-", $this->listSettingsService->getSelectedVariableIds());
			$get = md5(serialize(GeneralUtility::_GET()));

			$key = json_encode($filters) .
				json_encode($additionalCacheParameters) .
				$variableIds .
				$get .
				$limit .
				$perPage .
				$selectedPage .
				$sortField .
				$sortOrder;

			$this->cacheIdentifier = md5($key);
		}

		return $this->cacheIdentifier;
	}

	/**
	 * Gets the selected records with the use of all
	 * session services to apply the following cicumstances
	 *
	 * - Filtering
	 * - Sorting
	 * - Searching
	 * - Letter Selection
	 *
	 * @return array|QueryInterface
	 */
	protected function _getSelectedRecords()
	{
		// Limit
		$limit			= $this->listSettingsService->getLimitation();
		
		// Pager
		$perPage		= $this->sessionServiceContainer->getPagerSessionService()->getPerPage();
		
		// Session has no pager settings, so we take the default setting from the plugin settings
		if(!$perPage)
		{
			$perPage	= $this->listSettingsService->getPerPage();
			$this->sessionServiceContainer->getSortSessionService()->setPerPage($perPage);
		}
		
		if($limit > 0)
			$perPage = $limit;
		
		$selectedPage	= $this->sessionServiceContainer->getPagerSessionService()->getSelectedPage();
		if(is_null($selectedPage) || !$selectedPage) $selectedPage = 1;

		$page			= ($selectedPage*$perPage) - $perPage;

		// If nothing was set before, we use the per page setting from our records plugin
		if(is_null($perPage)) $perPage = $this->listSettingsService->getPerPage();

		if($limit === "0") {
		    $limit = null; // Removing the limit
        } else if($perPage && $selectedPage > 0) {
            $limit = "$page,{$perPage}";
        }

		if(!$this->_hasTargetPlugin("dataviewer_sort") || !$this->sessionServiceContainer->getSortSessionService()->hasOrderings())
		{
			// If this plugin has no sorting plugin that is targeting to this plugin,
			// we can set the default sorting settings to the plugin settings
			$sortField	= $this->listSettingsService->getSortField();
			$sortOrder	= $this->listSettingsService->getSortOrder();

			if(!$this->sessionServiceContainer->getSortSessionService()->getSortField())
                $this->sessionServiceContainer->getSortSessionService()->setSortField($sortField);

			if(!$this->sessionServiceContainer->getSortSessionService()->getSortOrder())
    			$this->sessionServiceContainer->getSortSessionService()->setSortOrder($sortOrder);
		}

		$sortField		= $this->sessionServiceContainer->getSortSessionService()->getSortField();
		$sortOrder		= $this->sessionServiceContainer->getSortSessionService()->getSortOrder();

		// Assign the current stats to the view
		$this->view->assign("selectedPage", $selectedPage);
		$this->view->assign("perPage", $perPage);
		$this->view->assign("sortField", $sortField);
		$this->view->assign("sortOrder", $sortOrder);

		// Retrieving all filters from different sources
		$filters = $this->_getFilters();

		////////////////////////////////////////////////////////////////////////////////
		// Signal-Slot for manipulating the complete filters for the record selection //
		////////////////////////////////////////////////////////////////////////////////
		$this->signalSlotDispatcher->dispatch(
			__CLASS__,
			"prepareFilters",
			[
				&$filters,
				&$this,
			]
		);

		// Replace markers in the filters
		$this->_replaceMarkersInFilters($filters);

		/***************************************************************************************************
		Adding a filter:
		--------------------------------------
		[field_id] => 5|title
		[filter_condition] => like
		[field_value] => filterval
		[filter_combination] => AND|OR
		[filter_field] => search|value_content

		---------------------------------------------------------------------------------------------------------
		eq			=			'{$var}'					->equals
		neq			!=			'{$var}'					->logicalNot->equals
		like		LIKE		'%{$var}%'					->like
		nlike		NOT LIKE	'%{$var}%'					->logicalNot->like
		in			IN			([trimExplode]{$var})		->in
		nin			NOT IN		([trimExplode]{$var})		->logicalNot->in
		gt			>			{(int)$var}					->greaterThan
		lt			<			{(int)$var}					->lessThan
		gte			>=			{(int)$var}					->greaterThanOrEqual
		lte			<=			{(int)$var}					->lessThanOrEqual
		 ****************************************************************************************************/
		if($this->settings["debug"] == 1)
		{
			$statement = $this->recordRepository->getStatementByAdvancedConditions($filters, $sortField, $sortOrder, $limit, $this->storagePids);
			$this->view->assign("statement", $statement);
		}
		
		$validRecords = $this->recordRepository->findByAdvancedConditions($filters, $sortField, $sortOrder, $limit, $this->storagePids);

		//////////////////////////////////////////////////////
		// Signal-Slot for post-processing selected records //
		//////////////////////////////////////////////////////
		$this->signalSlotDispatcher->dispatch(
			__CLASS__,
			"postProcessSelectedRecords",
			[
				&$validRecords,
				&$this,
			]
		);

		return $validRecords;
	}

	/**
	 * Gets additional filters computed
	 * by a letter and the according field
	 *
	 * @param array $recordSelection
	 * @return array
	 */
	protected function _getAdditionalFiltersByRecordSelection(array $recordSelection = [])
	{
		$additionalFilters = [];

		if(!empty($recordSelection))
		{
			$selectedRecordIds = implode(",", $recordSelection);
			$additionalFilters[] = [
				"field_id" => "RECORD.uid",
				"filter_condition" => "in",
				"field_value" => $selectedRecordIds,
				"filter_combination" => "AND",
				"filter_field" => "search",
			];
		}

		return $additionalFilters;
	}

	/**
	 * Gets additional filters computed
	 * by a letter and the according field
	 *
	 * @param string $letter
	 * @param int|string $letterField
	 * @return array
	 */
	protected function _getAdditionalFiltersByLetterSelection($letter, $letterField)
	{
		$additionalFilters = [];

		if(strlen($letter) === 1)
		{
			if(!is_numeric($letterField))
				$letterField = "RECORD.{$letterField}";

			$additionalFilters[] = [
				"field_id" => $letterField,
				"filter_condition" => "like",
				"field_value" => "{$letter}%",
				"filter_combination" => "AND",
				"filter_field" => "search",
			];
		}

		return $additionalFilters;
	}

	/**
	 * Gets additional filters computed
	 * by search
	 *
	 * @param string $searchType
	 * @param string $searchString
	 * @param array $searchFields
	 * @return array
	 */
	protected function _getAdditionalFiltersBySearch($searchType, $searchString, array $searchFields)
	{
		$additionalFilters = [];

		foreach($searchFields as $i=>$_sF)
			$searchFields[$i]["field_value"] = $searchString;

		switch($searchType)
		{
			case SearchSettingsService::SEARCH_RECORD_TITLE:
				$additionalFilters[] = [
					"field_id" => "RECORD.title",
					"filter_condition" => "like",
					"field_value" => "%{$searchString}%",
					"filter_combination" => "AND",
					"filter_field" => "search",
				];
				break;
			case SearchSettingsService::SEARCH_RECORD_TITLE_FIELDS:
				$additionalFilters[] = [
					"field_id" => "RECORD.title",
					"filter_condition" => "like",
					"field_value" => "%{$searchString}%",
					"filter_combination" => "AND",
					"filter_field" => "search",
				];
				$additionalFilters = array_merge($additionalFilters, $searchFields);
				break;
			case SearchSettingsService::SEARCH_FIELDS:
				$additionalFilters = array_merge($additionalFilters, $searchFields);
				break;
		}

		return $additionalFilters;
	}

	/**
	 * Gets additional filters computed
	 * by a selection type
	 *
	 * @param string $selectionType
	 * @return array
	 */
	protected function _getAdditionalFiltersBySelectionType($selectionType)
	{
		$additionalFilters = [];

		switch($selectionType)
		{
			case ListSettingsService::SELECTION_TYPE_DATATYPES:
				$selectedDatatypes = $this->listSettingsService->getSelectedDatatypeIds();

				$additionalFilters[] = [
					"field_id" => "RECORD.datatype",
					"filter_condition" => "in",
					"field_value" => $selectedDatatypes,
					"filter_combination" => "AND",
					"filter_field" => "search",
				];
				break;
			case ListSettingsService::SELECTION_TYPE_RECORDS:
				$selectedRecordIds = $this->listSettingsService->getSelectedRecordIds();
				$additionalFilters[] = [
					"field_id" => "RECORD.uid",
					"filter_condition" => "in",
					"field_value" => $selectedRecordIds,
					"filter_combination" => "AND",
					"filter_field" => "search",
				];
				break;
			case ListSettingsService::SELECTION_TYPE_CREATION_DATE:
				// Date From
				$dateFrom = $this->listSettingsService->getDateFrom();
				// Date To
				$dateTo = $this->listSettingsService->getDateTo();

				$additionalFilters[] = [
					"field_id" => "RECORD.crdate",
					"filter_condition" => "gte",
					"field_value" => $dateFrom->getTimestamp(),
					"filter_combination" => "AND",
					"filter_field" => "search",
				];

				$additionalFilters[] = [
					"field_id" => "RECORD.crdate",
					"filter_condition" => "lte",
					"field_value" => $dateTo->getTimestamp(),
					"filter_combination" => "AND",
					"filter_field" => "search",
				];
				break;
			case ListSettingsService::SELECTION_TYPE_CHANGE_DATE:
				// Date From
				$dateFrom = $this->listSettingsService->getDateFrom();
				// Date To
				$dateTo = $this->listSettingsService->getDateTo();

				$additionalFilters[] = [
					"field_id" => "RECORD.tstamp",
					"filter_condition" => "gte",
					"field_value" => $dateFrom->getTimestamp(),
					"filter_combination" => "AND",
					"filter_field" => "search",
				];

				$additionalFilters[] = [
					"field_id" => "RECORD.tstamp",
					"filter_condition" => "lte",
					"field_value" => $dateTo->getTimestamp(),
					"filter_combination" => "AND",
					"filter_field" => "search",
				];
				break;
			case ListSettingsService::SELECTION_TYPE_FIELD_VALUE_FILTER:
				break;
			case ListSettingsService::SELECTION_TYPE_ALL_RECORDS:
				break;
		}

		return $additionalFilters;
	}

	/**
	 * Checks if there is a sort plugin, that is
	 * targeting to this element and is active
	 *
	 * @param string $listType
	 * @return bool
	 */
	protected function _hasTargetPlugin($listType)
	{
		if($this->listSettingsService->isForcedSorting())
			return false;

		$uid = (int)$this->uid;

		if($uid <= 0)
		{
			return false;
		}

		/* @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
		$query = GeneralUtility::makeInstance(ConnectionPool::class)
			->getQueryBuilderForTable("tt_content");

		$query->select("uid")
			->from("tt_content")
			->where("list_type = '{$listType}'")
			->andWhere($query->expr()->eq("hidden", "0"))
			->andWhere($query->expr()->eq("deleted", "0"))
			->andWhere("pi_flexform RLIKE '<field index=\"settings.target_plugin\">.*<value index=\"vDEF\">{$uid}</value>.*</field>'")
		;

		$rows = $query->execute()->fetchAll();

		return (count($rows)>0);
	}

}
