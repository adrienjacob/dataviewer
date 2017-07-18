<?php
namespace MageDeveloper\Dataviewer\Domain\Repository;

/**
 * MageDeveloper Dataviewer Extension
 * -----------------------------------
 *
 * @category    TYPO3 Extension
 * @package     MageDeveloper\Dataviewer
 * @author		Bastian Zagar

 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
abstract class AbstractRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
	/**
	 * Record Limitation
	 *
	 * @var int
	 */
	protected $limit = 0;

	/**
	 * Language Uid
	 * 
	 * @var int|null
	 */
	protected $languageUid = null;

	/**
	 * AbstractRepository constructor.
	 *
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
	 */
	public function __construct(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager)
	{
		if( (
		     !$GLOBALS["TSFE"]->gr_list || 
		     empty($GLOBALS["TSFE"]->gr_list) || 
		     is_null($GLOBALS["TSFE"]->gr_list)
		    ) && TYPO3_MODE == "BE"
		)
		{
			$GLOBALS["TSFE"] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::class, $TYPO3_CONF_VARS, 0, 0);
			$GLOBALS["TSFE"]->initFEuser();
			$GLOBALS["TSFE"]->initUserGroups();
			//$GLOBALS['TSFE']->gr_list = "";
		}	
		parent::__construct($objectManager);
		
	}

	/**
	 * Sets the record limitation
	 *
	 * @param int $limit Limit Value
	 * @return void
	 */
	public function setLimit($limit)
	{
		$this->limit = $limit;
	}

	/**
	 * Gets the limitation of records to fetch
	 *
	 * @return int
	 */
	public function getLimit()
	{
		return (int)$this->limit;
	}

	/**
	 * @param int $languageUid
	 */
	public function setLanguageUid($languageUid)
	{
		$this->languageUid = $languageUid;
	}
	
	/**
	 * Returns a query for objects of this repository
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryInterface
	 * @api
	 */
	public function createQuery()
	{
		$query = parent::createQuery();

		if($this->getLimit() > 0)
			$query->setLimit( $this->getLimit() );

		return $query;
	}

	/**
	 * Creates a query with predefined settings
	 *
	 * @param bool $respectSysLanguage
	 * @param bool $ignoreEnableFields
	 * @param bool $respectStoragePage
	 * @param array $storagePids
	 * @param int $languageUid
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryInterface
	 */
	public function createQueryWithSettings($respectSysLanguage = true, $ignoreEnableFields = true, $respectStoragePage = false, array $storagePids = [], $languageUid = null)
	{
		$query = $this->createQuery();
		
		$query->getQuerySettings()->setRespectSysLanguage($respectSysLanguage);
		$query->getQuerySettings()->setIgnoreEnableFields($ignoreEnableFields);
		$query->getQuerySettings()->setRespectStoragePage($respectStoragePage);
		
		if(!is_null($languageUid))
			$query->getQuerySettings()->setLanguageUid($languageUid);
		
		if (!empty($storagePids))
			$query->getQuerySettings()->setStoragePageIds($storagePids);

		return $query;
	}


	/**
	 * Find a category from the repository with a
	 * specified uid
	 *
	 * @param int $uid Uid
	 * @param bool $onlyEnabled  Only Enabled category
	 * @param int $languageUid
	 * @return \MageDeveloper\Dataviewer\Domain\Model\AbstractModel
	 */
	public function findByUid($uid, $onlyEnabled = true)
	{
		$query = $this->createQueryWithSettings(false, !$onlyEnabled, false, [], $this->languageUid);

		$record = $query->matching(
			$query->equals("uid", $uid)
		)->execute()->getFirst();

		return $record;
	}

}
