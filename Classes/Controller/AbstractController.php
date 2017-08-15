<?php
namespace MageDeveloper\Dataviewer\Controller;

use MageDeveloper\Dataviewer\Domain\Model\Variable;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentNameException;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

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
abstract class AbstractController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
	/**
	 * Current Content Uid
	 *
	 * @var int
	 */
	protected $uid = 0;
	
	/**
	 * Plugin Settings Service
	 *
	 * @var \MageDeveloper\Dataviewer\Service\Settings\Plugin\PluginSettingsService
	 * @inject
	 */
	protected $pluginSettingsService;

	/**
	 * persistenceManager
	 *
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
	 * @inject
	 */
	protected $persistenceManager;

	/**
	 * Signal/Slot Dispatcher
	 * 
	 * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
	 * @inject
	 */
	protected $signalSlotDispatcher;

	/**
	 * TypoScript Utility
	 *
	 * @var \MageDeveloper\Dataviewer\Utility\TypoScriptUtility
	 * @inject
	 */
	protected $typoScriptUtility;

	/**
	 * Record Repository
	 *
	 * @var \MageDeveloper\Dataviewer\Domain\Repository\RecordRepository
	 * @inject
	 */
	protected $recordRepository;

	/**
	 * Variable Repository
	 *
	 * @var \MageDeveloper\Dataviewer\Domain\Repository\VariableRepository
	 * @inject
	 */
	protected $variableRepository;

	/**
	 * Authentication Service
	 * 
	 * @var \MageDeveloper\Dataviewer\Service\Auth\AuthenticationService
	 * @inject
	 */
	protected $authenticationService;

	/**
	 * Session Service
	 * 
	 * @var \MageDeveloper\Dataviewer\Service\Session\SessionService
	 * @inject
	 */
	protected $sessionService;

	/**
	 * Plugin Cache Service
	 * 
	 * @var \MageDeveloper\Dataviewer\Service\Cache\PluginCacheService
	 * @inject
	 */
	protected $pluginCacheService;

	/**
	 * Cache Manager
	 * 
	 * @var \TYPO3\CMS\Core\Cache\CacheManager
	 * @inject
	 */
	protected $cacheManager;

	/**
	 * Session Service Container
	 *
	 * @var \MageDeveloper\Dataviewer\Service\Session\SessionServiceContainer
	 * @inject
	 */
	protected $sessionServiceContainer;
	
	/**
	 * Gets the session service
	 *
	 * @return \MageDeveloper\Dataviewer\Service\Session\SessionService
	 */
	public function getSessionService()
	{
		return $this->sessionService;
	}

	/**
	 * Gets the session service container
	 *
	 * @return \MageDeveloper\Dataviewer\Service\Session\SessionServiceContainer
	 */
	public function getSessionServiceContainer()
	{
		return $this->sessionServiceContainer;
	}
	
	/**
	 * Gets the extension name
	 * 
	 * @return string
	 */
	public function getExtensionName()
	{
		return $this->controllerContext->getRequest()->getControllerExtensionName();
	}
	
	/**
	 * Gets the extension key
	 * 
	 * @return string
	 */
	public function getExtensionKey()
	{
		return $this->controllerContext->getRequest()->getControllerExtensionKey();
	}
	
	/**
	 * Gets the plugin name
	 * 
	 * @return string
	 */
	public function getPluginName()
	{
		return $this->controllerContext->getRequest()->getPluginName();
	}

	/**
	 * Redirects to the current TYPO3 Page
	 *
	 * @param int|null $redirectPid Redirect Page Id
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
	 * @return void
	 */
	protected function _redirectToPid($redirectPid = null, array $arguments = array())
	{
		if (is_null($redirectPid))
		{
			$redirectPid = $this->configurationManager->getContentObject()->data["pid"];
			if (isset($GLOBALS["TSFE"]))
				$redirectPid = $GLOBALS["TSFE"]->id;
		}

		$this->uriBuilder->setTargetPageUid($redirectPid);
		$this->uriBuilder->setArguments($arguments);
		$this->redirectToURI($this->uriBuilder->build());

		exit();
	}

	/**
	 * Checks for targetUid
	 * 
	 * @return bool
	 */
	protected function _checkTargetUid()
	{
		$targetUid = $this->pluginSettingsService->getTargetContentUid();
		$pluginTargetUid = ($this->request->hasArgument("targetUid"))?$this->request->getArgument("targetUid"):null;

		// Prevent Plugin from storing search to other target uid's
		if(is_null($pluginTargetUid) || ($targetUid != $pluginTargetUid))
			return false;
			
		return true;
	}

	/**
	 * Injects and prepares variables
	 *
	 * @param array $ids Ids
	 * @return array
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentNameException
	 */
	public function prepareVariables(array $ids)
	{
		// Denied Variable Names
		$deniedVariableNames = [
			"record",
			"records",
			"part",
		];

		$variables = [];
		
		foreach($ids as $_id)
		{
			/* @var Variable $variable */
			$variable = $this->variableRepository->findByUid($_id, true);

			if($variable instanceof Variable)
			{
				$name = $variable->getVariableName();
			
				if(in_array($name, $deniedVariableNames))
					throw new InvalidArgumentNameException("Variable must not be named '".implode("' or '", $deniedVariableNames)."'!");

				$type = $variable->getType();

				switch($type)
				{
					case Variable::VARIBALE_TYPE_TYPOSCRIPT:
						$value = $variable->getVariableValue();
						$rendered = $this->typoScriptUtility->getTypoScriptValue($value);
						$variables[$name] = $rendered;
						break;
					case Variable::VARIABLE_TYPE_TYPOSCRIPT_VAR:
						$value = "10 < {$name}";
						$rendered = $this->typoScriptUtility->getTypoScriptValue($value);
						$variables[$name] = $rendered;
						break;
					case Variable::VARIABLE_TYPE_GET:
						$variables[$name] = null;
						if(isset($_GET[$name]))
						{
							$value = GeneralUtility::_GET($name);

                            if(is_array($value))
                            {
                                $value = array_map(function($v) {
                                    return \MageDeveloper\Dataviewer\Utility\GetPostUtility::secureVariableGet($v);
                                }, $value);
                            }
                            else
                            {
                                $value = \MageDeveloper\Dataviewer\Utility\GetPostUtility::secureVariableGet($value);
                            }

							$variables[$name] = $variable->castType($value);
						}
						break;
					case Variable::VARIABLE_TYPE_POST:
						$variables[$name] = null;
						if(isset($_GET[$name]))
						{
							$value = GeneralUtility::_POST($name);

                            if(is_array($value))
                            {
                                $value = array_map(function($v) {
                                    return \MageDeveloper\Dataviewer\Utility\GetPostUtility::secureVariablePost($v);
                                }, $value);
                            }
                            else
                            {
                                $value = \MageDeveloper\Dataviewer\Utility\GetPostUtility::secureVariablePost($value);
                            }
                            
							$variables[$name] = $variable->castType($value);
						}
						break;
					case Variable::VARIABLE_TYPE_RECORD:
						$variables[$name] = $variable->getRecord();
						break;
					case Variable::VARIABLE_TYPE_RECORD_FIELD:
						$field = $variable->getField();
						
						if(!is_null($field))
						{
							$record = $variable->getRecord();
							$value = $record->getValueByField($field);
							$variables[$name] = $value;
						}
						
						break;
					case Variable::VARIABLE_TYPE_DATABASE:
						$fields = GeneralUtility::trimExplode(",", $variable->getColumnName());
						$table = $variable->getTableContent();
						$where = $variable->getWhereClause();
						$result = $this->fieldRepository->rawQuery($fields, $table, $where);
						$variables[$name] = $result;
						break;
					case Variable::VARIABLE_TYPE_FRONTEND_USER:
						$feUser = null;
						if($this->authenticationService->isLoggedIn())
							$feUser = $this->authenticationService->getFrontendUser();
							
						$variables[$name] = $feUser;
						break;
					case Variable::VARIABLE_TYPE_SERVER:
						$env = $variable->getServer();
						$variables[$name] = $_SERVER[$env];
						break;
					case Variable::VARIABLE_TYPE_DYNAMIC_RECORD:
						$record = null;
						if ($this->request->hasArgument("record")) 
						{
							$recordUid = $this->request->getArgument("record");
							$record = $this->recordRepository->findByUid($recordUid, true);
							
							if(!$record instanceof \MageDeveloper\Dataviewer\Domain\Model\Record)
								$record = $recordUid;
						}
						
						$variables[$name] = $record;
						break;
					case Variable::VARIABLE_TYPE_USER_SESSION:
						$sessionKey = $variable->getSessionKey();
						$this->sessionService->setPrefixKey($sessionKey);
						$variables[$name] = $this->sessionService->getData($name);
						break;
					case Variable::VARIABLE_TYPE_PAGE:
						/* @var \TYPO3\CMS\Frontend\Page\PageRepository $pageRepository */
						$pageRepository = $this->objectManager->get(\TYPO3\CMS\Frontend\Page\PageRepository::class);
						$variables[$name] = $pageRepository->getPage($variable->getPage());
						break;
					case Variable::VARIABLE_TYPE_USERFUNC:
						$userFunc = $variable->getUserFunc();

						$params = [
							"parameters" => [
								"variable" => $variable,
							],
						];

						if ($this->request->hasArgument("record"))
						{
							$recordUid = $this->request->getArgument("record");
							$record = $this->recordRepository->findByUid($recordUid, true);

							if(!$record instanceof \MageDeveloper\Dataviewer\Domain\Model\Record)
								$record = $recordUid;
								
							$params["parameters"]["record"] = $record;	
						}
						//$variables[$name] =
						$userFuncResult = GeneralUtility::callUserFunction($userFunc, $params, $this);
						$variables[$name] = $userFuncResult;
						break;
					case Variable::VARIABLE_TYPE_FIXED:
					default:
						$variables[$name] = $variable->getVariableValue();
						break;
				}
			} // EO IF
		} // EO FOREACH

		///////////////////////////////////////////////////////////////////////////
		// Signal-Slot for manipulating the variables that are added to the view //
		///////////////////////////////////////////////////////////////////////////
		$this->signalSlotDispatcher->dispatch(
			__CLASS__,
			"prepareVariables",
			[
				&$variables,
				&$this,
			]
		);

		// Assign the rendered variables to the current view
		return $variables;
	}

	/**
	 * Checks if the request has dataviewer arguments
	 * at all
	 * 
	 * @return bool
	 */
	protected function _hasDataviewerArguments()
	{
		$get = $_GET;
		$keys = array_keys($get);
		
		foreach($keys as $_key)
			if(strpos($_key, "tx_dataviewer") !== false)
				return true;
		
		return false;
	}

	/**
	 * Gets a standalone view instance
	 *
	 * @return \MageDeveloper\Dataviewer\Fluid\View\StandaloneView
	 */
	protected function getStandaloneView($includeVariables = false)
	{
		$view = $this->objectManager->get(\MageDeveloper\Dataviewer\Fluid\View\StandaloneView::class);

		$view->assign("settings", $this->settings);

		if($includeVariables === true)
		{
			$pids = $this->storagePids;

			// Merging with template variables from the current page
			if(is_int($GLOBALS["TSFE"]->id))
				$pids[] = $GLOBALS["TSFE"]->id;

			$variables = $this->variableRepository->findByStoragePids($pids);
			$ids = [];

			foreach($variables as $_v)
				$ids[] = $_v->getUid();

			$variables = $this->prepareVariables($ids);
			$view->assignMultiple($variables);
		}

		// Assign settings to the view
		$view->assign("settings", $this->settings);

		return $view;
	}

	/**
	 * Replaces all markers in a given string
	 *
	 * @param string $string
	 * @return void
	 */
	protected function _replaceMarkersInString($string)
	{
		return $this->getStandaloneView(true)->renderSource($string);
	}

	/**
	 * Replaces all markers in filters
	 *
	 * @param array $filters
	 * @return void
	 */
	protected function _replaceMarkersInFilters(array &$filters)
	{
		foreach($filters as $i=>$_filter)
			$filters[$i]["field_value"] = $this->_replaceMarkersInString($_filter["field_value"]);
	}

	/**
	 * Gets the content uid
	 *
	 * @return int
	 */
	protected function _getContentUid()
	{
		$uid = 0;
		$contentObj = $this->configurationManager->getContentObject();

		if ($contentObj)
			$uid = $contentObj->data["uid"];

		return (int)$uid;
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
		if(is_array($this->settings))
			$this->pluginSettingsService->setSettings($this->settings);
	
		// Individual session key
		$uid = $this->_getContentUid();
		$this->sessionServiceContainer->setTargetUid($uid);

		$cObj = $this->configurationManager->getContentObject();
		if ($cObj instanceof \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer)
			$this->view->assign("cObj", $cObj->data);

		// Allowed Storage Pids
		$pageData = $cObj->data;
		$this->storagePids = GeneralUtility::trimExplode(",", $pageData["pages"], true);

		// Rendering custom fluid code, given in the records plugin
		// For this, we just replace the default view model
		// with our custom view and put some stuff in it
		if($this->pluginSettingsService->isCustomFluidCode())
		{
			// Assigning the custom view model as general
			$this->view = $this->getStandaloneView(false);

			$templateSource = $this->pluginSettingsService->getFluidCode();

			// Checking Debug
			if($this->pluginSettingsService->isDebug())
				$templateSource = "<f:debug inline=\"1\">{_all}</f:debug>".$templateSource;

			$templateSwitch = $this->getTemplateSwitch();
			if($templateSwitch)
				$this->view->setTemplatePathAndFilename($templateSwitch);
			else
				$this->view->setTemplateSource($templateSource);

		}

		// Adding variables to the view
		$pids = $this->storagePids;

		// Merging with template variables from the current page
		if(is_int($GLOBALS["TSFE"]->id))
			$pids[] = $GLOBALS["TSFE"]->id;

		$variables = $this->variableRepository->findByStoragePids($pids);
		$ids = $this->pluginSettingsService->getSelectedVariableIds();
		foreach($variables as $_v)
			$ids[] = $_v->getUid();

		$variables = $this->prepareVariables($ids);
		$this->view->assignMultiple($variables);
		
		// Add the baseURL to the view
		$this->view->assign("baseUrl", $GLOBALS["TSFE"]->baseURL);

		// Parent
		parent::initializeView($view);
	}

	/**
	 * Evaluations the conditions for a template switch
	 * and returns the evaluated template path that
	 * can be used
	 *
	 * @return string
	 */
	public function getTemplateSwitch()
	{
		// Evaluation the template switch conditions
		$conditions = $this->pluginSettingsService->getTemplateSwitchConditions();

		// Get a view with all injected variables
		$view = $this->getStandaloneView(true);

		foreach($conditions as $_condition)
		{
			$conditionStr = $_condition["switches"]["condition"];
			$templateId = $_condition["switches"]["template_selection"];

			// Since we yet do not know how to render the nodes separately, we
			// just render a simple full fluid condition here
			$conditionText = "<f:if condition=\"{$conditionStr}\">1</f:if>";
			$isValid = (bool)$view->renderSource($conditionText);

			if($isValid)
				return $this->pluginSettingsService->getPredefinedTemplateById($templateId);

		}

		if ($this->pluginSettingsService->hasTemplate() && !$this->pluginSettingsService->isDebug())
			return $this->pluginSettingsService->getTemplate();

		return;
	}
}
