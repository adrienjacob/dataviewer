<?php
namespace MageDeveloper\Dataviewer\Domain\Model;

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
abstract class AbstractModel extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
	/**
	 * FlexForm Service
	 *
	 * @var \MageDeveloper\Dataviewer\Service\FlexFormService
	 * @inject
	 */
	protected $flexFormService;

	/**
	 * Record Creation Date
	 *
	 * @var int
	 */
	protected $crdate;

	/**
	 * Record Timestamp
	 *
	 * @var int
	 */
	protected $tstamp;

	/**
	 * Field is deleted
	 *
	 * @var boolean
	 */
	protected $deleted = FALSE;

	/**
	 * Field is hidden
	 *
	 * @var boolean
	 */
	protected $hidden = FALSE;

	/**
	 * Gets the crdate
	 *
	 * @return int
	 */
	public function getCrdate()
	{
		return $this->crdate;
	}

	/**
	 * Sets the crdate
	 *
	 * @param int $crdate
	 * @return void
	 */
	public function setCrdate($crdate)
	{
		$this->crdate = $crdate;
	}

	/**
	 * Gets the timestamp
	 *
	 * @return int
	 */
	public function getTstamp()
	{
		return $this->tstamp;
	}

	/**
	 * Sets the timestamp
	 *
	 * @param int $tstamp
	 * @return void
	 */
	public function setTstamp($tstamp)
	{
		$this->tstamp = $tstamp;
	}

	/**
	 * Gets the deleted status
	 *
	 * @return boolean
	 */
	public function isDeleted()
	{
		return $this->deleted;
	}

	/**
	 * Gets the deleted status
	 *
	 * @return boolean
	 */
	public function getDeleted()
	{
		return $this->deleted;
	}

	/**
	 * Sets the record value deleted
	 *
	 * @param boolean $deleted
	 * @return void
	 */
	public function setDeleted($deleted)
	{
		$this->deleted = $deleted;
	}

	/**
	 * Checks if the fieldvalue is hidden
	 *
	 * @return bool
	 */
	public function isHidden()
	{
		return $this->hidden;
	}

	/**
	 * Checks if the fieldvalue is hidden
	 *
	 * @return bool
	 */
	public function getHidden()
	{
		return $this->hidden;
	}

	/**
	 * Sets the hidden status
	 *
	 * @param bool $hidden
	 */
	public function setHidden($hidden)
	{
		$this->hidden = $hidden;
	}
}
