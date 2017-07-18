<?php
namespace MageDeveloper\Dataviewer\Persistence\Storage;

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

class CustomDataMapper extends \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper 
{
	/**
	 * Maps a single row on an object of the given class
	 *
	 * @param string $className The name of the target class
	 * @param array $row A single array with field_name => value pairs
	 * @return object An object of the given class
	 */
	protected function mapSingleRow($className, array $row) 
	{
		if(TYPO3_MODE == "BE")	
			$row["uid"] = isset($row['_LOCALIZED_UID']) ? $row['_LOCALIZED_UID'] : $row['uid'];
	
		$uid = $row["uid"];
		
		if ($this->persistenceSession->hasIdentifier($uid, $className)) {
			$object = $this->persistenceSession->getObjectByIdentifier($uid, $className);
		} else {
			$object = $this->createEmptyObject($className);
			$this->persistenceSession->registerObject($object, $uid);
			$this->thawProperties($object, $row);
			$this->emitAfterMappingSingleRow($object);
			$object->_memorizeCleanState();
			$this->persistenceSession->registerReconstitutedEntity($object);
		}
		return $object;
	}
}
