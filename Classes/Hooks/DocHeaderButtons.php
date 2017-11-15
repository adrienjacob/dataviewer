<?php
namespace MageDeveloper\Dataviewer\Hooks;

use MageDeveloper\Dataviewer\Utility\LocalizationUtility;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
class DocHeaderButtons 
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
	 * Icon Factory
	 * 
	 * @var \TYPO3\CMS\Core\Imaging\IconFactory
	 * @inject
	 */
	protected $iconFactory;

    /**
     * Backend Access Service
     *
     * @var \MageDeveloper\Dataviewer\Service\Backend\BackendAccessService
     * @inject
     */
    protected $backendAccessService;

    /**
     * Standalone View
     *
     * @var \MageDeveloper\Dataviewer\Fluid\View\StandaloneView
     * @inject
     */
    protected $standaloneView;

	/**
	 * Constructor
	 *
	 * @return DocHeaderButtons
	 */
	public function __construct()
	{
		$this->objectManager        = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
		$this->datatypeRepository   = $this->objectManager->get(\MageDeveloper\Dataviewer\Domain\Repository\DatatypeRepository::class);
		$this->recordRepository     = $this->objectManager->get(\MageDeveloper\Dataviewer\Domain\Repository\RecordRepository::class);
		$this->iconFactory		    = $this->objectManager->get(\TYPO3\CMS\Core\Imaging\IconFactory::class);
		$this->backendAccessService = $this->objectManager->get(\MageDeveloper\Dataviewer\Service\Backend\BackendAccessService::class);
	    $this->standaloneView       = $this->objectManager->get(\MageDeveloper\Dataviewer\Fluid\View\StandaloneView::class);
	}

	/**
	 * Get buttons
	 *
	 * @param array $params
	 * @param ButtonBar $buttonBar
	 * @return array
	 */
	public function getButtons(array $params, ButtonBar $buttonBar)
	{
		$buttons 				= $params['buttons'];
		$currentPageId 			= $this->_resolveCurrentPageId();

		$allowedModules = [
			"web_layout",
			"web_list",
			"web_info",
			"web_func",
			//"web_ViewpageView",
		];

		if (!in_array(GeneralUtility::_GET("M"), $allowedModules) || !$currentPageId || $currentPageId == 0)
			return $buttons;

        // Assigning the current page id to the standalone view
        $this->standaloneView->assign("currentPageId", $currentPageId);

        // Fetching all datatypes on the current pid
        $datatypesOnThisPid = $this->datatypeRepository->findAllOnPid($currentPageId, ["sorting" => QueryInterface::ORDER_ASCENDING]);

        // Datatypes from the current pid
        if($datatypesOnThisPid) {

		    foreach($datatypesOnThisPid as $_datatype) {
				/* @var \MageDeveloper\Dataviewer\Domain\Model\Datatype $_datatype */

				$buttonContent = $this->_getDatatypeButtonContents($_datatype);

                $button = $buttonBar->makeFullyRenderedButton();

                if($button) {
                    $button->setHtmlSource($buttonContent);
                    $buttons[ButtonBar::BUTTON_POSITION_LEFT][2][] = $button;
                }
 			}
			
		}

        // Datatypes from the page TSconfig
        $datatypeIdsFromTSConfig = $this->backendAccessService->getDocHeaderDatatypes($currentPageId);
        foreach($datatypeIdsFromTSConfig as $_id) {

            /* @var \MageDeveloper\Dataviewer\Domain\Model\Datatype $_datatype */
            $_datatype = $this->datatypeRepository->findByUid($_id, true);

            $buttonContent = $this->_getDatatypeButtonContents($_datatype);

            $button = $buttonBar->makeFullyRenderedButton();

            if($button) {
                $button->setHtmlSource($buttonContent);
                $buttons[ButtonBar::BUTTON_POSITION_LEFT][4][] = $button;
            }
        }

		return $buttons;
	}

    /**
     * Gets an button according to a datatype model
     *
     * @param \MageDeveloper\Dataviewer\Domain\Model\Datatype $datatype
     * @return string
     */
	protected function _getDatatypeButtonContents($datatype)
    {
        $html 		= "{namespace core=TYPO3\\CMS\\Core\\ViewHelpers}
					   {namespace dv=MageDeveloper\\Dataviewer\\ViewHelpers}";

        $htmlButton = "<a href=\"{dv:backend.newLink(pid:'{currentPageId}',table:'tx_dataviewer_domain_model_record',datatype:datatype.uid)}\" title=\"{title}\"><core:icon identifier=\"extensions-dataviewer-{icon}\" size=\"small\" /><core:icon identifier=\"actions-add\" size=\"small\" /></a>";
        /* @var \MageDeveloper\Dataviewer\Fluid\View\StandaloneView $view */

        if($datatype->getHideAdd())
            return;

        $iconId = "default";
        if($datatype->getIcon())
            $iconId = $datatype->getIcon();

        $rendered = "";
        $buttonHtmlRender = $html . $htmlButton;
        $title = LocalizationUtility::translate("module.create_record_by_datatype", [$datatype->getName()]);

        $this->standaloneView->assign("datatype", $datatype);
        $this->standaloneView->assign("icon", $iconId);
        $this->standaloneView->assign("title", $title);

        $rendered = $this->standaloneView->renderSource($buttonHtmlRender);

        return $rendered;
    }


	/**
	 * Resolves the current page id
	 *
	 * @return int
	 */
	protected function _resolveCurrentPageId()
	{
		$currentPageId = (int)GeneralUtility::_GP("id");

		if (!$currentPageId || $currentPageId <= 0)
		{
			$returnUrl = GeneralUtility::_GP("returnUrl");
			$currentPageId = \MageDeveloper\Dataviewer\Utility\UrlUtility::extractPidFromUrl($returnUrl);
		}

		return (int)$currentPageId;
	}
}
