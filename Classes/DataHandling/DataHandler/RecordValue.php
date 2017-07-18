<?php
namespace MageDeveloper\Dataviewer\DataHandling\DataHandler;

use MageDeveloper\Dataviewer\Utility\LocalizationUtility as Locale;
use MageDeveloper\Dataviewer\Domain\Model\Datatype as DatatypeModel;
use MageDeveloper\Dataviewer\Domain\Model\Record as RecordModel;
use MageDeveloper\Dataviewer\Domain\Model\RecordValue as RecordValueModel;
use MageDeveloper\Dataviewer\Domain\Model\Field as FieldModel;
use MageDeveloper\Dataviewer\Domain\Model\FieldValue as FieldValueModel;
use MageDeveloper\Dataviewer\Configuration\ExtensionConfiguration as Config;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Html\RteHtmlParser;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * MageDeveloper Dataviewer Extension
 * -----------------------------------
 *
 * @category    TYPO3 Extension
 * @package     MageDeveloper\Dataviewer
 * @author		Bastian Zagar
 * @copyright   Magento Developers / magedeveloper.de <kontakt@magedeveloper.de>
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL .0)
 */
class RecordValue extends AbstractDataHandler implements DataHandlerInterface
{
	/**
	 * RecordValue Repository
	 *
	 * @var \MageDeveloper\Dataviewer\Domain\Repository\RecordValueRepository
	 * @inject
	 */
	protected $recordValueRepository;

	/**
	 * Constructor
	 *
	 * @return RecordValue
	 */
	public function __construct()
	{
		parent::__construct();
		$this->recordValueRepository = $this->objectManager->get(\MageDeveloper\Dataviewer\Domain\Repository\RecordValueRepository::class);
	}

	/**
	 * Get an recordvalue by a given id
	 *
	 * @param int $id
	 * @return RecordValueModel|bool
	 */
	public function getRecordValueById($id)
	{
		/* @var RecordValueModel $field */
		$recordValue = $this->recordValueRepository->findByUid($id, false);

		if ($recordValue instanceof RecordValueModel && $recordValue->getUid() == $id)
			return $recordValue;

		return false;
	}

	/**
	 * processCmdmap
	 *
	 * @param string $command
	 * @param string $table
	 * @param mixed $value
	 * @param bool $commandIsProcessed
	 * @param \TYPO3\CMS\Core\DataHandling\DataHandler $parentObj
	 * @param bool $pasteUpdate
	 * @return void
	 */
	public function processCmdmap($command, $table, $id, $value, &$commandIsProcessed, $parentObj, $pasteUpdate)
	{
		if ($table != "tx_dataviewer_domain_model_recordvalue") return;
	}

	/**
	 * @param string $table
	 * @param int $id
	 * @param array $recordToDelete
	 * @param bool $recordWasDeleted
	 * @param \TYPO3\CMS\Core\DataHandling\DataHandler $parentObj
	 */
	public function processCmdmap_deleteAction($table, $id, $recordToDelete, &$recordWasDeleted, &$parentObj)
	{
		if ($table != "tx_dataviewer_domain_model_recordvalue") return;
	}

	/**
	 * Prevent saving of a news record if the editor doesn't have access to all categories of the news record
	 *
	 * @param array $incomingFieldArray
	 * @param string $table
	 * @param int $id
	 * @param \TYPO3\CMS\Core\DataHandling\DataHandler $parentObj
	 */
	public function processDatamap_preProcessFieldArray(&$incomingFieldArray, $table, $id, &$parentObj)
	{
		if ($table != "tx_dataviewer_domain_model_recordvalue") return;
	}

	/**
	 * @param string $status
	 * @param string $table
	 * @param int $id
	 * @param array $fieldArray
	 * @param \TYPO3\CMS\Core\DataHandling\DataHandler $parentObj
	 */
	public function processDatamap_postProcessFieldArray($status, $table, $id, $fieldArray, &$parentObj)
	{
		if ($table != "tx_dataviewer_domain_model_recordvalue") return;
	}

	/**
	 * @param string $status
	 * @param string $table
	 * @param int $id
	 * @param array $fieldArray
	 * @param \TYPO3\CMS\Core\DataHandling\DataHandler $parentObj
	 */
	public function processDatamap_afterDatabaseOperations($status, $table, $id, $fieldArray, &$parentObj)
	{
		if ($table != "tx_dataviewer_domain_model_recordvalue") return;
	}
}
