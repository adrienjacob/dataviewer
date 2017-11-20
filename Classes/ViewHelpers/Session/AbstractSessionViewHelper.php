<?php
namespace MageDeveloper\Dataviewer\ViewHelpers\Session;

use MageDeveloper\Dataviewer\ViewHelpers\AbstractViewHelper;

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
abstract class AbstractSessionViewHelper extends AbstractViewHelper
{
    /**
     * Session Service Container
     *
     * @var \MageDeveloper\Dataviewer\Service\Session\SessionServiceContainer
     * @inject
     */
    protected $sessionServiceContainer;

    /**
     * Initialize arguments.
     *
     * @return void
     * @api
     */
    public function initializeArguments()
    {
        // Target Uid has to be set for each session vie
        $this->registerArgument("targetUid", "int", "Target Plugin Uid", true);
        parent::initializeArguments();
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

        $uriBuilder = $this->controllerContext->getUriBuilder();
        $uriBuilder->reset()
            ->setTargetPageUid($redirectPid)
            ->setAddQueryString(true)
            ->setArguments($arguments);

        $this->redirectToURI($this->uriBuilder->build());

        exit();
    }
}