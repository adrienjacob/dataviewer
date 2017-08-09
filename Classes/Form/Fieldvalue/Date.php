<?php
namespace MageDeveloper\Dataviewer\Form\Fieldvalue;

use MageDeveloper\Dataviewer\Domain\Model\Field;
use MageDeveloper\Dataviewer\Utility\CheckboxUtility;
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
class Date extends AbstractFieldvalue implements FieldvalueInterface
{
	/**
	 * Gets the optimized value for the field
	 *
	 * @return string
	 */
	public function getValueContent()
	{
		$value = $this->getValue();
		
		// Value convert to timestamp
		if(!is_numeric($value))
			$value = strtotime($value);
		
		return $value;
	}

	/**
	 * Validates a date for the required format
	 * 
	 * @param string $date
	 * @return bool
	 */
	protected function _validateDate($date)
	{
		$format = $this->getFormat();
		$d = \DateTime::createFromFormat($format, $date);

		return $d && $d->format($format) == $date;
	}

	/**
	 * Gets the date format
	 * 
	 * @return string
	 */
	public function getFormat()
	{
		return $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] ?: 'Y-m-d';
	}

	/**
	 * Gets a datetime model by a
	 * given timestamp
	 *
	 * @param int $timestamp
	 * @return \DateTime
	 */
	protected function _getDateTimeByTimestamp($timestamp)
	{
		$defaultTimezone = $this->getDefaultTimezone();
		date_default_timezone_set($defaultTimezone);
		$timezone = new \DateTimeZone($defaultTimezone);
		$date = new \DateTime('@' . $timestamp, $timezone);

		return $date;
	}

	/**
	 * Get default timezone
	 *
	 * @return string
	 */
	protected function getDefaultTimezone()
	{
		$timeZone = $GLOBALS['TYPO3_CONF_VARS']['SYS']['phpTimeZone'];
		if (empty($timeZone))
		{
			// Time zone from the server environment (TZ env or OS query)
			$defaultTimeZone = @date_default_timezone_get();
			if ($defaultTimeZone !== '')
				$timeZone = $defaultTimeZone;
			else
				$timeZone = 'UTC';
		}

		return $timeZone;
	}

	/**
	 * Gets the optimized search string for the field
	 *
	 * @return string
	 */
	public function getSearch()
	{
		return $this->getValue();
	}

	/**
	 * Gets the final frontend value, that is
	 * pushed in {record.field.value}
	 *
	 * This or these values are the most different
	 * part of the whole output, so if you handle
	 * this, you need to have some knowledge,
	 * what value is returned.
	 *
	 * @return \DateTime|void
	 */
	public function getFrontendValue()
	{
		$value = $this->getValue();
		if(!$value || !is_numeric($value))
			return;
		
		return $this->_getDateTimeByTimestamp($value);
	}

    /**
     * Gets the value or values as a plain string-array for
     * usage in different possitions to show
     * and use it when needed as a string
     *
     * @return array
     */
    public function getValueArray()
    {
        return [$this->getValue()];
    }
}
