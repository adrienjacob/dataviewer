<?php
if (!defined("TYPO3_MODE")) {
	die ("Access denied.");
}

/***********************************
 * New Content Element - Wizard
 ***********************************/
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig("<INCLUDE_TYPOSCRIPT: source=\"FILE:EXT:".$_EXTKEY."/Configuration/PageTS/modWizards.ts\">");

/***********************************
 * Change sorting for Records
 ***********************************/
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig("mod.web_list.tableDisplayOrder.tx_dataviewer_domain_model_record.before = pages, fe_groups, fe_users, tx_dataviewer_domain_model_datatype");

/***********************************
 * Register Cache for the plugins
 ***********************************/
if (!is_array($TYPO3_CONF_VARS["SYS"]["caching"]["cacheConfigurations"]["dataviewer_cache"]))
{
	$TYPO3_CONF_VARS["SYS"]["caching"]["cacheConfigurations"]["dataviewer_cache"] 							= array();
	$TYPO3_CONF_VARS["SYS"]["caching"]["cacheConfigurations"]["dataviewer_cache"]["frontend"] 				= \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class;
	$TYPO3_CONF_VARS["SYS"]["caching"]["cacheConfigurations"]["dataviewer_cache"]["backend"] 				= \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class;
	$TYPO3_CONF_VARS["SYS"]["caching"]["cacheConfigurations"]["dataviewer_cache"]["options"]["compression"] = 1;
}


/***********************************
 * Dataviewer Plugins
 * =================================
 * 
 * #1 - Display Record(s)
 * ---------------------------------
 * This plugin adds the possibility
 * to integrate a record, multiple
 * records or a record part to your
 * page.
 * 
 * This plugin is for
 * 
 * - Listing Records
 * - Viewing Record Details
 * - Viewing Record Details dynamically
 * - Showing a part from a record
 * - Listening for Ajax Requests
 * 
 * #2 - Search Records
 * ---------------------------------
 * This plugin can search through
 * selected records. It adds a 
 * searchbox to your site with
 * configurable search options.
 * 
 * #3 - Letter Selection
 * ---------------------------------
 * Adds a letter selection to your
 * site, to select records with
 * their starting letter.
 * 
 * #4 - Sorting
 * ---------------------------------
 * Adds a sort form to your site,
 * which enabled you to sort the
 * displayed records by given
 * sorting elements.
 * 
 * #5 - Filtering
 * ---------------------------------
 * Adds a filter form to your site
 * to filter availble records.
 * 
 * #6 - Selecting
 * ---------------------------------
 * Adds a selection form to your
 * site for selecting records.
 * 
 * #7 - Form
 * ---------------------------------
 * Adds a form to your site with a
 * customizable template. The
 * form, that will be included is
 * for creating new records in the 
 * backend.
 * 
 * #8 - Pager
 * ---------------------------------
 * Adds a page to your site for 
 * paging the records of the 
 * connected records plugin.
 * 
 * 
 ***********************************/
// #1 - Display Records
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	'MageDeveloper.'.$_EXTKEY,
	"Record",
	["Record" => "index, list, detail, dynamicDetail, part, ajaxRequest, ajaxResponse"], // Cached
	["Record" => "index, list"], // UnCached
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_PLUGIN
);

// #2 - Search Records
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	'MageDeveloper.'.$_EXTKEY,
	"Search",
	["Search" => "index, search, reset"], // Cached
	["Search" => "index, search, reset"], // UnCached
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_PLUGIN
);

// #3 - Letter Selection
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	'MageDeveloper.'.$_EXTKEY,
	"Letter",
	["Letter" => "index, letter"], // Cached
	["Letter" => "index, letter"], // UnCached
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_PLUGIN
);

// #4 - Sorting
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	'MageDeveloper.'.$_EXTKEY,
	"Sort",
	["Sort" => "index, sort"], // Cached
	["Sort" => "index, sort"], // UnCached
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_PLUGIN
);

// #5 - Filtering
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	'MageDeveloper.'.$_EXTKEY,
	"Filter",
	["Filter" => "index, add, remove, reset"], // Cached
	["Filter" => "index, add, remove, reset"], // UnCached
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_PLUGIN
);

// #6 - Selecting
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	'MageDeveloper.'.$_EXTKEY,
	"Select",
	["Select" => "index, select"], // Cached
	["Select" => "index, select"], // UnCached
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_PLUGIN
);

// #7 - Form
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	'MageDeveloper.'.$_EXTKEY,
	"Form",
	["Form" => "index, post, delete, error"], // Cached
	["Form" => "index, post, delete, error"], // UnCached
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_PLUGIN
);

// #8 - Pager
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	'MageDeveloper.'.$_EXTKEY,
	"Pager",
	["Pager" => "index, page"], // Cached
	["Pager" => "index, page"], // UnCached
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_PLUGIN
);

/** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
$signalSlotDispatcher->connect(
	\TYPO3\CMS\Extbase\Persistence\Generic\Backend::class,
	'beforeGettingObjectData',
	\MageDeveloper\Dataviewer\Persistence\Generic\Backend\ExtbaseEnforceLanguage::class,
	'forceLanguageForQueries'
);

// The code below is NO PUBLIC API!
/** @var $extbaseObjectContainer \TYPO3\CMS\Extbase\Object\Container\Container */
$extbaseObjectContainer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\Container\Container::class);
$extbaseObjectContainer->registerImplementation(\TYPO3\CMS\Extbase\Persistence\QueryResultInterface::class, \MageDeveloper\Dataviewer\Persistence\Storage\CustomQueryResult::class);
unset($extbaseObjectContainer);
