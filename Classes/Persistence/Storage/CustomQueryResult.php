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

class CustomQueryResult extends \TYPO3\CMS\Extbase\Persistence\Generic\QueryResult 
{
	/**
	 * @var \MageDeveloper\Dataviewer\Persistence\Storage\CustomDataMapper
	 * @inject
	 */
	protected $dataMapper;
}
