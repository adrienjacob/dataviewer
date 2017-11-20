<?php
namespace MageDeveloper\Dataviewer\ViewHelpers\Session;

use MageDeveloper\Dataviewer\ViewHelpers\Session\AbstractSessionViewHelper;
use TYPO3\CMS\Core\Utility\HttpUtility;
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
class SortViewHelper extends AbstractSessionViewHelper
{
    /**
     * Initialize arguments.
     *
     * @return void
     * @api
     */
    public function initializeArguments()
    {
        $this->registerArgument("sortField", "string", "Sets the sorting field", false, "RECORD.uid");
        $this->registerArgument("sortOrder", "string", "Sets the sorting order", false, QueryInterface::ORDER_ASCENDING);
        $this->registerArgument("force", "bool", "Forces these settings", false, false);

        parent::initializeArguments();
    }

    /**
     * Sets the sorting into the session for a certain plugin that is
     * implemented after this call
     *
     * @return string
     */
    public function render()
    {
        // Setting the target uid
        $targetUid = (int)$this->arguments["targetUid"];
        $this->sessionServiceContainer->setTargetUid($targetUid);

        $sortField = null;
        if(isset($this->arguments["sortField"])) {
            $sortField = $this->arguments["sortField"];
            $this->sessionServiceContainer->getSortSessionService()->setSortField($sortField);
        }

        $sortOrder = null;
        if(isset($this->arguments["sortOrder"])) {
            switch(strtoupper($this->arguments["sortOrder"])) {
                case QueryInterface::ORDER_ASCENDING:
                case QueryInterface::ORDER_DESCENDING:
                    $sortOrder = $this->arguments["sortOrder"];
                    $this->sessionServiceContainer->getSortSessionService()->setSortOrder(strtoupper($sortOrder));
                    break;
                default:
                    break;
            }
        }

        if($this->arguments["force"] === true) {
            // Forcing the new settings, so we need to redirect to the current page if the session doesn't contain our val
            if( ((!is_null($sortField)) && $sortField != $this->sessionServiceContainer->getSortSessionService()->getSortField()) ||
                ((!is_null($sortOrder)) && $sortOrder != $this->sessionServiceContainer->getSortSessionService()->getSortOrder())
            ) {
                $uriBuilder = $this->controllerContext->getUriBuilder();
                $uri = $uriBuilder->reset()
                    ->setTargetPageUid( $GLOBALS["TSFE"]->id )
                    ->setAddQueryString(true)
                    ->build();

                HttpUtility::redirect($uri, 200);
                exit();
            }

        }

        return;
    }
}