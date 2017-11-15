<?php
namespace MageDeveloper\Dataviewer\RecordList;

use MageDeveloper\Dataviewer\Fluid\View\StandaloneView;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Recordlist\LinkHandler\PageLinkHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Page\PageRenderer;
use Psr\Http\Message\ServerRequestInterface;
use MageDeveloper\Dataviewer\Tree\View\ElementBrowserPageTreeView;

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
class RecordLinkHandler extends PageLinkHandler
{
	/**
	 * Object Manager
	 *
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 * @inject
	 */
	protected $objectManager;

	/**
	 * Datatype Repository
	 *
	 * @var \MageDeveloper\Dataviewer\Domain\Repository\DatatypeRepository
	 * @inject
	 */
	protected $datatypeRepository;

	/**
	 * Record Repository
	 *
	 * @var \MageDeveloper\Dataviewer\Domain\Repository\RecordRepository
	 * @inject
	 */
	protected $recordRepository;

	/**
	 * Plugin Settings Service
	 * 
	 * @var \MageDeveloper\Dataviewer\Service\Settings\Plugin\PluginSettingsService
	 * @inject
	 */
	protected $settingsService;

	/**
	 * Constructor
	 *
	 * @return RecordLinkHandler
	 */
	public function __construct()
	{
		$this->objectManager      = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
		$this->datatypeRepository = $this->objectManager->get(\MageDeveloper\Dataviewer\Domain\Repository\DatatypeRepository::class);
		$this->recordRepository   = $this->objectManager->get(\MageDeveloper\Dataviewer\Domain\Repository\RecordRepository::class);
		$this->settingsService    = $this->objectManager->get(\MageDeveloper\Dataviewer\Service\Settings\Plugin\PluginSettingsService::class);
	}

    /**
     * Checks if this is the handler for the given link
     *
     * The handler may store this information locally for later usage.
     *
     * @param array $linkParts Link parts as returned from TypoLinkCodecService
     *
     * @return bool
     */
    public function canHandleLink(array $linkParts)
    {
        if (!$linkParts['url']) {
            return false;
        }
        
        if(!isset($linkParts["type"]) || $linkParts["type"] !== "dataviewer") {
			return false;
        }

	    $url = $linkParts['url'];
	    
	    if(!isset($url["parameters"])) return false;
	    $parameters = $url["parameters"];
	    parse_str($parameters, $query);
	    
	    
	    if(isset($query["page"]))
		    $this->linkParts["page"] = (int)$query["page"];
		    
	    if(isset($query["record"]))
	        $this->linkParts["record"] = (int)$query["record"];

	    if(isset($linkParts["url"]["pageuid"]))
		    $this->linkParts["page"] = (int)$linkParts["url"]["pageuid"];

	    if(isset($linkParts["url"]["record"]))
		    $this->linkParts["record"] = (int)$linkParts["url"]["record"];
		    
		return true;
    }

    /**
     * Format the current link for HTML output
     *
     * @return string
     */
    public function formatCurrentUrl()
    {
        $recordIdInfo = $this->linkParts["record"];
        $recordId = str_replace("dataviewer:", "", $recordIdInfo);

        $record = $this->recordRepository->findByUid($recordId, false);

		if($record instanceof \MageDeveloper\Dataviewer\Domain\Model\Record) {
			return $record->getTitle() . " ({$record->getUid()})";
		}

		return "---";
    }

	/**
	 * Render the link handler
	 *
	 * @param ServerRequestInterface $request
	 *
	 * @return string
	 */
	public function render(ServerRequestInterface $request)
	{
		$queryParams = $request->getQueryParams();

		if($request->getMethod() == "POST" && $request->hasHeader("x-requested-with") && reset($request->getHeader("x-requested-with")) == "XMLHttpRequest" && !isset($queryParams["treeAction"]))
			return $this->_handleRequest($request);

		$standaloneView = $this->objectManager->get(StandaloneView::class);
		$templatePaths = $this->settingsService->getTemplatePaths();

		$recordId = null;
		$pageId = null;
		
		
		if(is_array($this->linkParts)) {
			if(isset($this->linkParts["record"]))
				$recordId = $this->linkParts["record"];

			if(isset($this->linkParts["page"]))
				$pageId = $this->linkParts["page"];
		}

		if(isset($queryParams["record"])) {
			$recordId = (int)$queryParams["record"];
		}

		if(isset($queryParams["page"])) {
			$pageId = (int)$queryParams["page"];
		}
		
		if($recordId) {
			$record = $this->recordRepository->findByUid($recordId);
			$standaloneView->assign("selectedRecord", $record);
		}
		
		$standaloneView->assign("selectedPage", $pageId);
		
		GeneralUtility::makeInstance(PageRenderer::class)->loadRequireJsModule('TYPO3/CMS/Dataviewer/RecordLinkHandler');

		$standaloneView->setTemplateRootPaths($templatePaths);
		$standaloneView->setTemplate("RecordList/Index.html");

		$latestRecords = $this->recordRepository->findLatest(10);
		$standaloneView->assign("latest", $latestRecords);

		$url = $_SERVER["REQUEST_URI"];
		$standaloneView->assign("url", $url);

        $this->expandPage = isset($request->getQueryParams()['expandPage']) ? (int)$request->getQueryParams()['expandPage'] : 0;
        $this->setTemporaryDbMounts();

        $backendUser = $this->getBackendUser();

        /** @var ElementBrowserPageTreeView $pageTree */
        $pageTree = GeneralUtility::makeInstance(ElementBrowserPageTreeView::class);
        $pageTree->setLinkParameterProvider($this);
        $pageTree->ext_showNavTitle = (bool)$backendUser->getTSConfigVal('options.pageTree.showNavTitle');
        $pageTree->ext_showPageId = (bool)$backendUser->getTSConfigVal('options.pageTree.showPageIdWithTitle');
        $pageTree->ext_showPathAboveMounts = (bool)$backendUser->getTSConfigVal('options.pageTree.showPathAboveMounts');
        $pageTree->addField('nav_title');
        $tree = $pageTree->getBrowsableTree();
        
        $standaloneView->assign("tree", $tree);

		return $standaloneView->render();
	}

	/**
	 * @param array $values Array of values to include into the parameters or which might influence the parameters
	 *
	 * @return string[] Array of parameters which have to be added to URLs
	 */
	public function getUrlParameters(array $values)
	{
		$parameters = parent::getUrlParameters($values);
		
		if(is_array($this->linkParts)) {
			if(isset($this->linkParts["record"])) {
				$recordParts = GeneralUtility::trimExplode(":", $this->linkParts["record"]);
				$recordId = $recordParts[1];
				$parameters["selectedRecord"] = $recordId;
			}
		} else if ((int)GeneralUtility::_GET("selectedRecord") > 0) {
			$parameters["selectedRecord"] = (int)GeneralUtility::_GET("selectedRecord");
		}

		return $parameters;
	}

	/**
	 * @param array $values Values to be checked
	 *
	 * @return bool Returns TRUE if the given values match the currently selected item
	 */
	public function isCurrentlySelectedItem(array $values)
	{
		return !empty($this->linkParts) && (int)$this->linkParts['page'] === (int)$values['pid'];
	}

	/**
	 * @param ServerRequestInterface $request
	 * @return void
	 */
	protected function _handleRequest(ServerRequestInterface $request)
	{
	    $params = $request->getParsedBody();

	    if(is_array($params) && isset($params["value"])) {

	        $search = $params["value"];

	        $filters = [
	            [
	                "field_id" => "RECORD.title",
                    "filter_condition" => "like",
                    "field_value" => "%{$search}%",
                    "filter_combination" => "AND",
                    "filter_field" => "value_content",
                ],
            ];

            $records = $this->recordRepository->findByAdvancedConditions($filters, "title", QueryInterface::ORDER_ASCENDING, 5);

            $standaloneView = $this->objectManager->get(StandaloneView::class);
            $templatePaths = $this->settingsService->getTemplatePaths();
            $standaloneView->setTemplateRootPaths($templatePaths);
            $standaloneView->setTemplate("RecordList/SearchResults.html");

            $standaloneView->assign("records", $records);

		    $url = $_SERVER["REQUEST_URI"];
		    $standaloneView->assign("url", $url);

            echo $standaloneView->render();
        }

		exit();
	}

}
