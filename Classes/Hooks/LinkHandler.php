<?php
namespace MageDeveloper\Dataviewer\Hooks;

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
class LinkHandler
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
	 * */
	protected $settingsService;

	/**
	 * Constructor
	 *
	 * @return LinkHandler
	 */
	public function __construct()
	{
		$this->objectManager      = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
		$this->datatypeRepository = $this->objectManager->get(\MageDeveloper\Dataviewer\Domain\Repository\DatatypeRepository::class);
		$this->recordRepository   = $this->objectManager->get(\MageDeveloper\Dataviewer\Domain\Repository\RecordRepository::class);
		$this->settingsService    = $this->objectManager->get(\MageDeveloper\Dataviewer\Service\Settings\Plugin\PluginSettingsService::class);
	}

	/**
	 * Process the typolink
	 *
	 * todo: think of rewriting the link
	 *
	 * @param string $linktxt The linktext
	 * @param array $conf Configuraion
	 * @param string $linkHandlerKeyword should be regional_object
	 * @param string $linkHandlerValue The uid of the record
	 * @param string $linkParams Full link params
	 * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObjectRenderer
	 *
	 * @return string the rendered link
	 */
	public function main($linktxt, $conf, $linkHandlerKeyword, $linkHandlerValue, $linkParams, &$contentObjectRenderer)
	{
		if($linkHandlerKeyword === "record") {
		
			$parts = GeneralUtility::trimExplode("|", $linkHandlerValue);
			list($recordId, $page) = $parts;
			
			$pageParts = GeneralUtility::trimExplode("#", $page);
			list($pageId, $anchor) = $pageParts;
		
			// We need to check if the record exist, because if not,
			// we just do not link it
			$record = $this->recordRepository->findByUid($recordId, false);
			
			if(!$record instanceof \MageDeveloper\Dataviewer\Domain\Model\Record) {
				return $linktxt;
			}
			
			return $contentObjectRenderer->typoLink($linktxt, [
				"parameter" => $pageId,
				"title" => $record->getTitle(),
				"additionalParams" => "&tx_dataviewer_record[record]={$recordId}&tx_dataviewer_record[action]=dynamicDetail&tx_dataviewer_record[controller]=Record",
				"returnLast" => $conf["returnLast"],
				"useCacheHash" => 1,
			]);
		
		}
	}

}
