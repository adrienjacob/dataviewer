<?php
namespace MageDeveloper\Dataviewer\Persistence\Generic\Backend;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Session;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

class ExtbaseEnforceLanguage implements SingletonInterface
{
	protected $overrideLanguage = FALSE;
	protected $languageUid = 0;
	protected $languageMode = NULL;
	protected $languageOverlayMode = FALSE;
	
	/**
	 * This method is registered on the Signal beforeGettingObjectData of the Extbase Generic Backend
	 * to override the returned model language
	 *
	 * @param QueryInterface $query
	 * @return QueryInterface
	 */
	public function forceLanguageForQueries(QueryInterface $query) 
	{
		if (!$this->overrideLanguage) 
			return [$query];
			
		$querySettings = $query->getQuerySettings();
		$querySettings->setLanguageUid($this->languageUid);
		$querySettings->setLanguageMode($this->languageMode);
		$querySettings->setLanguageOverlayMode($this->languageOverlayMode);
		$query->setQuerySettings($querySettings);
		
		return [$query];
	}
	
	/**
	 * @return boolean
	 */
	public function isOverrideLanguage()
	{
		return $this->overrideLanguage;
	}
	
	/**
	 * When changing the override language, we clear the persistence session of extbase,
	 * otherwise the non translated objects would be returned (cache).
	 *
	 * @param boolean $overrideLanguage
	 * @return void
	 */
	public function setOverrideLanguage($overrideLanguage)
	{
		$this->overrideLanguage = $overrideLanguage;
		/** @var ObjectManager $objectManager */
		$objectManager = GeneralUtility::makeInstance(ObjectManager::class);
		/** @var Session $persistenceSession */
		$persistenceSession = $objectManager->get(Session::class);
		$persistenceSession->destroy();
	}
	
	/**
	 * @return int
	 */
	public function getLanguageUid()
	{
		return $this->languageUid;
	}
	
	/**
	 * @param int $languageUid
	 * @return void
	 */
	public function setLanguageUid($languageUid)
	{
		$this->languageUid = $languageUid;
	}
	
	/**
	 * @return mixed
	 */
	public function getLanguageMode()
	{
		return $this->languageMode;
	}
	
	/**
	 * @param mixed $languageMode
	 * @return void
	 */
	public function setLanguageMode($languageMode)
	{
		$this->languageMode = $languageMode;
	}
	
	/**
	 * @return mixed
	 */
	public function getLanguageOverlayMode()
	{
		return $this->languageOverlayMode;
	}
	
	/**
	 * @param mixed $languageOverlayMode
	 * @return void
	 */
	public function setLanguageOverlayMode($languageOverlayMode)
	{
		$this->languageOverlayMode = $languageOverlayMode;
	}
}
