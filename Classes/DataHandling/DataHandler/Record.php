<?php
namespace MageDeveloper\Dataviewer\DataHandling\DataHandler;

use MageDeveloper\Dataviewer\Utility\LocalizationUtility as Locale;
use MageDeveloper\Dataviewer\Domain\Model\Datatype as DatatypeModel;
use MageDeveloper\Dataviewer\Domain\Model\Record as RecordModel;
use MageDeveloper\Dataviewer\Domain\Model\RecordValue as RecordValueModel;
use MageDeveloper\Dataviewer\Domain\Model\Field as FieldModel;
use MageDeveloper\Dataviewer\Domain\Model\FieldValue as FieldValueModel;

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
class Record extends AbstractDataHandler implements DataHandlerInterface
{
	/**
	 * RecordValue Session Service
	 *
	 * @var \MageDeveloper\Dataviewer\Service\Session\RecordValueSessionService
	 * @inject
	 */
	protected $recordValueSessionService;

	/**
	 * Field Validation
	 *
	 * @var \MageDeveloper\Dataviewer\Validation\FieldValidation
	 * @inject
	 */
	protected $fieldValidation;

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
	 * Record Value Repository
	 *
	 * @var \MageDeveloper\Dataviewer\Domain\Repository\RecordValueRepository
	 * @inject
	 */
	protected $recordValueRepository;

	/**
	 * Field Repository
	 *
	 * @var \MageDeveloper\Dataviewer\Domain\Repository\FieldRepository
	 * @inject
	 */
	protected $fieldRepository;

	/**
	 * @var array
	 */
	protected $tempColumns = [];

	/**
	 * Id of the main record
	 *
	 * @var int
	 */
	protected $mainRecordUid = 0;

	/**
	 * Constructor
	 *
	 * @return Record
	 */
	public function __construct()
	{
		parent::__construct();
		$this->fieldValueSessionService = $this->objectManager->get(\MageDeveloper\Dataviewer\Service\Session\FieldValueSessionService::class);
		$this->recordRepository			= $this->objectManager->get(\MageDeveloper\Dataviewer\Domain\Repository\RecordRepository::class);
		$this->recordValueRepository	= $this->objectManager->get(\MageDeveloper\Dataviewer\Domain\Repository\RecordValueRepository::class);
		$this->fieldRepository			= $this->objectManager->get(\MageDeveloper\Dataviewer\Domain\Repository\FieldRepository::class);
		$this->fieldValidation			= $this->objectManager->get(\MageDeveloper\Dataviewer\Validation\FieldValidation::class);

		/*
		$backend = $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface::class);
		//$dataMapRecord = $backend->getDataMapper()->getDataMap("MageDeveloper\\Dataviewer\\Domain\\Model\\Record");
		//$dataMapRecord->setTableName("tx_dataviewer_domain_model_record_external");
		$dataMapRecordValue = $backend->getDataMapper()->getDataMap("MageDeveloper\\Dataviewer\\Domain\\Model\\RecordValue");
		$dataMapRecordValue->setTableName("tx_dataviewer_domain_model_recordvalue_external");
		*/
	}

	/**
	 * Get an record by a given id
	 *
	 * @param int $id
	 * @return RecordModel|bool
	 */
	public function getRecordById($id)
	{
		if($id <= 0) return false;

		/* @var RecordModel $record */
		$record = $this->recordRepository->findByUid($id, false);

		if ($record instanceof RecordModel)
			return $record;

		return false;
	}

	/**
	 * Get an datatype by a given id
	 *
	 * @param int $id
	 * @return DatatypeModel|bool
	 */
	public function getDatatypeById($id)
	{
		if($id <= 0) return false;

		/* @var DatatypeModel $record */
		$datatype = $this->datatypeRepository->findByUid($id, false);

		if ($datatype instanceof DatatypeModel)
			return $datatype;

		return false;
	}

	/**
	 * Get an recordValue by a given id
	 *
	 * @param int $id
	 * @return RecordValueModel|bool
	 */
	public function getRecordValueById($id)
	{
		/* @var RecordValueModel $record */
		$recordValue = $this->recordValueRepository->findByUid($id, false);

		if ($recordValue instanceof RecordValueModel && $recordValue->getUid() == $id)
			return $recordValue;

		return false;
	}

	/**
	 * Get an field by a given id
	 *
	 * @param int $id
	 * @return Field|bool
	 */
	public function getFieldById($id)
	{
		/* @var FieldModel $field */
		$field = $this->fieldRepository->findByUid($id, false);

		if ($field instanceof FieldModel && $field->getUid() == $id)
			return $field;

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
		if ($table != "tx_dataviewer_domain_model_record") return;

		if ($command == "copy")
		{
			/* @var RecordModel $parentRecord */
			/* @var RecordModel $newRecord */
			$parentRecord = $this->getRecordById($id);
			$newRecordId = $parentObj->copyRecord($table, $id, $value, false, [], "record_values");
			$newRecord = $this->getRecordById($newRecordId);
			$pid = $newRecord->getPid();

			// Original Record Values that need to be copied
			$recordValues = $parentRecord->getRecordValues();

			foreach($recordValues as $_recordValue)
			{
				/* @var RecordValueModel $_recordValue */
				/* @var RecordValueModel $newRecordValue */
				$newRecordValueId = $parentObj->copyRecord("tx_dataviewer_domain_model_recordvalue", $_recordValue->getUid(), $pid, false, [], "field,field_value");
				$newRecordValue = $this->getRecordValueById($newRecordValueId, false);

				if ($newRecordValue && $_recordValue->getField())
				{
					$newRecordValue->setRecord($newRecord);
					$newRecordValue->setField($_recordValue->getField());
					$newRecordValue->setPid($pid);

					// We need to check the fieldtype to do certain copy behaviours here
					switch ($_recordValue->getField()->getType())
					{
						case "relation":
							/****************************************************************
							 * File Relation
							 * -------------------------------------------------------------
							 * We need to copy the file and create a new file relation here
							 * so everything can be kept for the new standalone record
							 ****************************************************************/

							/* @var \TYPO3\CMS\Core\Resource\FileRepository $fileRepository */
							$fileRepository = $this->objectManager->get(\TYPO3\CMS\Core\Resource\FileRepository::class);
							$relationFileId = $_recordValue->getValueContent();

							if($relationFileId > 0)
							{
								/* @var \TYPO3\CMS\Core\Resource\FileReference $fileReference */
								$fileReference = $fileRepository->findFileReferenceByUid($relationFileId);

								$folder = $fileReference->getParentFolder();
								$copiedFile = $fileReference->getOriginalFile()->copyTo($folder);

								$newId = "NEW1234";
								$data = [];
								$data["sys_file_reference"][$newId] = array(
									"table_local" 	=> "sys_file",
									"uid_local" 	=> $copiedFile->getUid(),
									"tablenames" 	=> "tx_dataviewer_domain_model_record",
									"uid_foreign" 	=> $newRecord->getUid(),
									"fieldname" 	=> $_recordValue->getField()->getUid(),
									"pid" 			=> $newRecord->getPid(),
								);
								$data["tx_dataviewer_domain_model_record"][$newRecord->getUid()] = [
									"tx_dataviewer_domain_model_record" => $newId,
								];

								$parentObj->start($data, []);
								$parentObj->process_datamap();
							}

							break;
						case "inline":
							/****************************************************************
							 * Regular Inline Elements
							 * -------------------------------------------------------------
							 * We need to copy all elements here and assign the copied to the
							 * new value
							 ****************************************************************/
							$ids = GeneralUtility::trimExplode(",", $newRecordValue->getValueContent());

							// Clearing the value content
							$newRecordValue->setValueContent("");

							$field = $newRecordValue->getField();
							$table = $field->getConfig("foreign_table");
							$destPid = ($field->getConfig("pid_config") > 0)?$field->getConfig("pid_config"):$pid;
							$exclude = "";

							$newIds = [];
							foreach($ids as $_id)
							{
								$_newId = $parentObj->copyRecord($table, $_id, $destPid, false, [], $exclude);
								if($_newId) $newIds[] = $_newId;
							}

							$newRecordValue->setValueContent(implode(",", $newIds));

							break;
						case "datatype":
							/****************************************************************
							 * Datatype Inline Elements
							 * -------------------------------------------------------------
							 * We need to copy all elements here and assign the copied to the
							 * new value
							 ****************************************************************/
							$ids = GeneralUtility::trimExplode(",", $newRecordValue->getValueContent());

							// Clearing the value content
							$newRecordValue->setValueContent("");

							$field = $newRecordValue->getField();
							$table = "tx_dataviewer_domain_model_record";
							$destPid = ($field->getConfig("pid_config") > 0)?$field->getConfig("pid_config"):$pid;
							$exclude = "record_values";

							$newIds = [];
							foreach($ids as $_id)
							{
								$_newId = $parentObj->copyRecord($table, $_id, $destPid, false, [], $exclude);
								if($_newId) $newIds[] = $_newId;
							}

							$newRecordValue->setValueContent(implode(",", $newIds));

							break;
						default:
							break;

					}

					$this->recordValueRepository->update($newRecordValue);
				}
			}

			// persisting the copy
			$this->persistenceManager->persistAll();

			$commandIsProcessed = false;
			// Do the normal action after this
		}

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
		if ($table != "tx_dataviewer_domain_model_record") return;

		$recordValues = $this->recordValueRepository->findByRecordId($id);
		if ($recordValues && $recordValues->count())
		{
			// Remove each record value
			/* @var RecordValueModel $_recordValue */
			foreach ( $recordValues as $_recordValue )
			{
				if($_recordValue->getField() instanceof FieldModel)
				{
					// We need to check the fieldtype to do certain delete behaviours here
					switch ($_recordValue->getField()->getType()) {
						case "datatype":
							$ids = GeneralUtility::trimExplode(",", $_recordValue->getValueContent());

							foreach ($ids as $_id) {
								$_record = $this->getRecordById($_id);
								if ($_record) {
									$_record->setDeleted(true);
									$this->recordRepository->update($_record);
								}
							}
							break;
						default:
							break;
					}

					$_recordValue->setDeleted(true);
					$this->recordValueRepository->update($_recordValue);
				}
			}

		}

		// Deleting the main record
		// The record is possibly not existing here
		$record = $this->getRecordById($id);
		if($record)
		{
			$record->setDeleted(true);
			$this->recordRepository->update($record);
		}

		$this->persistenceManager->persistAll();

		// Hook
		$recordWasDeleted = true;

		$message = Locale::translate("record_was_successfully_deleted", $id);
		$this->addBackendFlashMessage($message, '', FlashMessage::OK);
	}

	/**
	 * Prevent saving of a news record if the editor doesn't have access to all categories of the news record
	 *
	 * @param array $incomingFieldArray
	 * @param string $table
	 * @param int $id
	 * @param \TYPO3\CMS\Core\DataHandling\DataHandler $parentObj
	 * @return bool
	 */
	public function processDatamap_preProcessFieldArray(&$incomingFieldArray, $table, $id, &$parentObj)
	{
		if ($table != "tx_dataviewer_domain_model_record") return;

		if(!$this->mainRecordUid)
			$this->mainRecordUid = $id;
		
		// Storing the fieldArray to the session to prefill form values for easier modifying
		$this->recordValueSessionService->store($id, $incomingFieldArray);

		$record = $this->getRecordById($id);

		$datatype = null;
		if ($record)
			$datatype = $record->getDatatype();

		if(!$datatype)
			$datatype = $this->getDatatypeById($incomingFieldArray["datatype"]);

		if(!$datatype)
			return;

		$datatypeId = $datatype->getUid();
		if(GeneralUtility::_POST()["datatype"]) {
			// This is for the single datatype selection on creating a new
			// record in the New-Record-Assistant of TYPO3
			$datatypeId = (int)GeneralUtility::_POST("datatype");
			$this->_redirectCurrentUrl(["datatype" => $datatypeId]);
		}

		$validationErrors = [];

		// Validate the POST data
		$validationErrors = $this->validateFieldArray($incomingFieldArray, $datatype);

		if (!empty($validationErrors))
		{
			// Record save data is invalid. We showed the messages before, now we need to reload the
			// form with the entered data
			foreach($validationErrors as $field=>$_errors)
				foreach($_errors as $_error)
					$this->addBackendFlashMessage($_error, $field);

			$incomingFieldArray["icon"] = $datatype->getIcon();
			$incomingFieldArray["hidden"] = true;

			// Storing the fieldArray to the session to prefill form values for easier modifying
			//$this->recordValueSessionService->store("NEW", $incomingFieldArray);
			$this->_redirectCurrentUrl(["datatype" => $datatypeId]);
			return;
		}

		// Records save data is stored for later usage to
		// correctly store NEW<hash>-Records
		$this->saveData[$id] = [
			$incomingFieldArray,
		];

		// We need to remove all elements from the array where the key is an integer,
		// so we can remove our custom fields in order to let the save procedure
		// in combination with the added GLOBALS (for the suggest wizard) removed
		foreach($incomingFieldArray as $_k=>$_v)
			if(is_numeric($_k))
				unset($incomingFieldArray[$_k]);

		// We clear the GLOBALS 
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
		if ($table != "tx_dataviewer_domain_model_record") return;
		
		// We need to clear the GLOBALS before database operations because we've injected
		// a lot of TCA for our needs into them
		// This is for saving compatibility and removes the 'mess' we've created!

		$columns = $GLOBALS["TCA"]["tx_dataviewer_domain_model_record"]["columns"];

		foreach($columns as $_id=>$_column)
			if(is_numeric($_id))
			{
				$this->tempColumns[$_id] = $_column;
				unset($GLOBALS["TCA"]["tx_dataviewer_domain_model_record"]["columns"][$_id]);
			}
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
		// Assign substNEWIds for later usage if the element is in our target
		$this->substNEWwithIDs = array_merge($this->substNEWwithIDs, $parentObj->substNEWwithIDs);
		$this->_substituteRecordValues();

		if ($table != "tx_dataviewer_domain_model_record") return;
		
		// Disable Versioning
		//$parentObj->bypassWorkspaceRestrictions = true;
		//$parentObj->updateModeL10NdiffDataClear = true;
		//$parentObj->updateModeL10NdiffData = false;

		// Substitute the NEW to Id on all record values and maybe in this array
		// We need to transform the saved values (NEW<hash>) to already saved ids)
		$this->_substituteRecordValues();
		
		// Retrieve clean id
		$recordId = $this->_getPossibleSubstitutedId($id);
		$record   = $this->getRecordById($recordId);

		$globals = $GLOBALS["TCA"]["tx_dataviewer_domain_model_record"]["columns"];
		$GLOBALS["TCA"]["tx_dataviewer_domain_model_record"]["columns"] = array_replace($globals, $this->tempColumns);

		if(!$record instanceof RecordModel)
		{
			$message  = Locale::translate("record_not_found", $id);
			$this->addBackendFlashMessage($message);
			return;
		}

		if (isset($this->saveData[$id]) && is_array($this->saveData[$id]))
		{
			$recordSaveData = reset($this->saveData[$id]);

			// Adding the parent id, if the record is not our main record
			if($id != $this->mainRecordUid)
				$recordSaveData["parent"] = $this->mainRecordUid;

			$result   = $this->processRecord($recordSaveData, $record);
			$message  = Locale::translate("record_not_saved");
			$severity = FlashMessage::ERROR;

			if ($result)
			{
				// Language Selector Box compatibility
				// TODO: this has to be reviewed directly
				if(array_key_exists("sys_language_uid", $fieldArray))
					$record->_setProperty("_languageUid", $fieldArray["sys_language_uid"]);

				// Save processed data
				$this->recordRepository->update($record);
				$this->persistenceManager->persistAll();

				$message  = Locale::translate("record_was_successfully_saved", [$record->getTitle(), $recordId]);
				$severity = FlashMessage::OK;
				
				// We clear the according session data
				$this->recordValueSessionService->resetForRecordId($id);
			}
			else
			{
				if($record)
				{
					$record->setHidden(true);
					$this->recordRepository->update($record);
					$this->persistenceManager->persistAll();
				}
			}

			// We only deliver a message, when 
			if(!$record->getDatatype()->getHideRecords() || $severity != FlashMessage::OK)
				$this->addBackendFlashMessage($message, '', $severity);

		}

		return;
	}

	/**
	 * Validates an field array that came with the
	 * form post on the record editing
	 *
	 * @param array $fieldArray
	 * @param \MageDeveloper\Dataviewer\Domain\Model\Datatype $datatype
	 * @return array
	 */
	public function validateFieldArray(array $fieldArray, \MageDeveloper\Dataviewer\Domain\Model\Datatype $datatype)
	{
		// We need to check if all contents of the fieldArray are fields from the TCA
		// If not, we need to validate the other fields
		// We check the tca configuration, and retrieve all fields for the table that are general
		if(isset($GLOBALS["TCA"]["tx_dataviewer_domain_model_record"]["columns"]))
		{
			$validColumns = $GLOBALS["TCA"]["tx_dataviewer_domain_model_record"]["columns"];
			$diff = array_diff_key($fieldArray, $validColumns);

			if(empty($diff))
				return [];
		}

		$fieldValidationErrors = [];

		foreach($datatype->getFields() as $_field)
		{
			/* @var \MageDeveloper\Dataviewer\Domain\Model\Field $_field */
			$this->fieldValidation->setField($_field);

			$value = null;
			if(isset($fieldArray[$_field->getUid()]))
				$value = $fieldArray[$_field->getUid()];

			$fieldValueValidationErrors = $this->fieldValidation->validate($value);

			if(!empty($fieldValueValidationErrors))
			{
				foreach($fieldValueValidationErrors as $_error)
					$fieldValidationErrors[$_field->getFrontendLabel()][] = $_error;
			}
		}

		return $fieldValidationErrors;
	}



	/**
	 * Transforms the NEW-ID into the
	 * correct ID if found in Substitute Id Array
	 *
	 * @param string|int $id
	 * @return string|int
	 */
	protected function _getPossibleSubstitutedId($id)
	{
		if(isset($this->substNEWwithIDs[$id]))
			return $this->substNEWwithIDs[$id];

		return $id;
	}

	/**
	 * Substitutes all record values with the now known ids
	 *
	 * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
	 * @return void
	 */
	protected function _substituteRecordValues()
	{
		if(!empty($this->substNEWwithIDs))
		{
			// This is dumb but we just replace the NEWhash.hash which is stored already in
			// the database into the uid that shows up here
			foreach($this->substNEWwithIDs as $_subNew=>$_subId)
			{
				$recordValues = $this->recordValueRepository->findByValueContent($_subNew);

				foreach($recordValues as $_recordValue)
				{
					$valueContent = $_recordValue->getValueContent();
					$newValueContent = str_replace($_subNew, $_subId, $valueContent);

					$_recordValue->setValueContent($newValueContent);
					$this->recordValueRepository->update($_recordValue);
				}

			}

			$this->persistenceManager->persistAll();
		}
	}

	/**
	 * Processes record information
	 *
	 * @param array $recordSaveData
	 * @param RecordModel $record
	 * @return bool
	 */
	public function processRecord(array $recordSaveData, RecordModel $record)
	{
		// Get datatype
		$datatype = $record->getDatatype();

		if(!$datatype)
		{
			// We try loading the datatype by the recordSaveData Information
			if(isset($recordSaveData["datatype"]))
				$datatype = $this->datatypeRepository->findByUid((int)$recordSaveData["datatype"], false);

		}

		if(!$datatype)
			return false;

		// Refresh record timestamp
		$record->setTstamp(time());

		// Add icon
		$record->setIcon($datatype->getIcon());

		//////////////////////
		// RECORD SAVE DATA //
		//////////////////////
		if(!is_array($recordSaveData))
			return false;
			
		$this->_processRecordSaveData($record, $recordSaveData);

		return true;
	}

	/**
	 * Processes record elements
	 *
	 * @param RecordModel $record
	 * @param array $recordSaveData
	 * @return void
	 */
	protected function _processRecordSaveData(RecordModel $record, array $recordSaveData = [])
	{
		$datatype = $record->getDatatype();

		// Default reset the record title and store the previous title
		$previousTitle = $record->getTitle(true);
		$record->setTitle("");

		if(isset($recordSaveData["hidden"]))
			$record->setHidden((bool)$recordSaveData["hidden"]);

		///////////////////////////////////////////
		// process all uploads
		///////////////////////////////////////////
		$this->_processUploads($_FILES);
		
		///////////////////////////////////////////
		// We go through all fields of the datatype
		///////////////////////////////////////////
		$overallResult = null;

		foreach($recordSaveData as $_fieldId=>$_value)
		{
			$originalValue = $_value;
			//if($record->_hasProperty($_fieldId))
			//	$record->_setProperty($_fieldId, $_value);

			/* @var FieldModel $field */
			$field = $datatype->getFieldById($_fieldId);
			
			if (!$field instanceof FieldModel)
				continue;

			// Process Array (formerly Flexform Element) but don't process checkbox values
			if(is_array($_value))
			{
				// Panic substitute :)
				$_value = $this->_substituteArrayNEWwithIds($_value);
				$_value = $this->dataHandler->getFlexformValue($_value, $record, $field);
				$_value = $this->flexTools->flexArray2Xml($_value);
			}

			// We need to check the field
			// We get the tca from the fieldtype class
			// We check agains checkValue_SW in the dataHandler
			/* @var \MageDeveloper\Dataviewer\Domain\Model\Fieldtype $fieldtype */
			$fieldtypeConfiguration = $this->fieldtypeSettingsService->getFieldtypeConfiguration($field->getType());
			$class = $fieldtypeConfiguration->getFieldClass();

			if($this->objectManager->isRegistered($class))
			{
				$this->dataHandler->BE_USER		= $GLOBALS["BE_USER"];

				/* @var \MageDeveloper\Dataviewer\Form\Fieldtype\FieldtypeInterface $fieldtypeModel */
				$fieldtypeModel = $this->objectManager->get($class);

				$fieldtypeModel->setField($field);
				$fieldtypeModel->setRecord($record);

				$tca = $fieldtypeModel->getFieldTca();

				$res = [];
				$uploadedFiles = [];

				if(isset($this->dataHandler->uploadedFileArray["tx_dataviewer_domain_model_record"][$record->getUid()][$field->getUid()]))
					$uploadedFiles = $this->dataHandler->uploadedFileArray["tx_dataviewer_domain_model_record"][$record->getUid()][$field->getUid()];

				$val = $this->dataHandler->checkValue_SW(
					$res,
					$_value,
					$tca,
					"tx_dataviewer_domain_model_record",
					$record->getUid(),
					$_value,
					"new",
					$record->getPid(),
					"[tx_dataviewer_domain_model_record:{$record->getUid()}:{$field->getUid()}]",
					$field->getUid(),
					$uploadedFiles,
					$record->getPid(),
					[]
				);

				// Select and Inline Value
				if(array_key_exists("value", $val) && $tca["type"] !== "select" && $tca["type"] !== "inline")
					$_value = $val["value"];

				if ($tca["type"] == "inline")
				{
					// We divorce the values and set them back together
					$values = GeneralUtility::trimExplode(",", $_value, true);
					$_value = [];
					foreach($values as $v)
						if ($v) $_value[] = $v;

					$_value = implode(",", $_value);
				}

				if($field->getType() == "rte")
				{
				
					// Initialize transformation:
					/* @var RteHtmlParser $parseHTML */
					$parseHTML = GeneralUtility::makeInstance(RteHtmlParser::class);
					$parseHTML->init("tt_content" . ':' . "bodytext", $record->getPid()); // We imitate a tt_content bodytext field
					// Perform transformation for value -> db:
					$_value = $parseHTML->TS_transform_db($_value);
				}
			}
			
			$result = $this->_saveRecordValue($record, $field, $_value);

			if (!$result)
				$overallResult = false;

		}

		if($record->getTitle(true) == "")
		{
			// No title was set before, so we check, if a new title can be set from
			// the recordSaveData
			if(isset($recordSaveData["title"]))
				$record->setTitle($recordSaveData["title"]);
			else
				$record->setTitle($previousTitle);

		}

		if ($overallResult === false)
		{
			// We hide the record until it is ok
			$record->setHidden(true);

			// Persist valid fields
			//$this->persistenceManager->persistAll();

			// Redirect back to form
			$this->_redirectRecord($record->getUid());
		}
	}

	/**
	 * Saves record field value content
	 *
	 * @param RecordModel $record
	 * @param FieldModel $field
	 * @param mixed $value
	 * @return int
	 */
	protected function _saveRecordValue(RecordModel $record, FieldModel $field, $value)
	{
		$pid   = $record->getPid();

		/* @var RecordValueModel $recordValue */
		$recordValue = $this->recordValueRepository->findOneByRecordAndField($record, $field);

		if(!$recordValue instanceof RecordValueModel)
			$recordValue = $this->objectManager->get(RecordValueModel::class);

		$recordValue->setRecord($record);
		$recordValue->setField($field);
		$recordValue->setPid($pid);

		// Defaults
		$valueContent = $value;
		$search = $value;

		//////////////////////////////////////////////////////////////////////////////////
		// Specific Save Part
		// -------------------------------------------------------------------------------
		// The data is finalized by the according fieldValue 
		// It is splitted up in two parts:
		//
		// Value Content for the main database entry that is performed 
		// withing the SingleFieldContainer
		//
		// Search for the database search entry that will be used
		// in all search, sorting and filtering plugins
		//////////////////////////////////////////////////////////////////////////////////
		$fieldtypeConfiguration = $this->fieldtypeSettingsService->getFieldtypeConfiguration( $field->getType() );
		$valueClass = $fieldtypeConfiguration->getValueClass();

		if (!$this->objectManager->isRegistered($valueClass))
			$valueClass = \MageDeveloper\Dataviewer\Form\Fieldvalue\General::class;

		/* @var \MageDeveloper\Dataviewer\Form\Fieldvalue\FieldvalueInterface $fieldValue */
		$fieldvalue = $this->objectManager->get($valueClass);

		if($fieldvalue instanceof \MageDeveloper\Dataviewer\Form\Fieldvalue\FieldvalueInterface)
		{
			// We need to initialize the fieldvalue with the plain value
			$fieldvalue->init($field, $value);
			$fieldvalue->setRecordValue($recordValue);

			////////////////////////////////////////////////////////////////////////////////////////////////
			// This is the place where we will later pre-render the value through each fieldValue
			// so we can retrieve a TYPO3-valid save value for the database
			// TODO: render value through each formvalue
			////////////////////////////////////////////////////////////////////////////////////////////////

			// We retrieve our needed data from the fieldvalue
			$valueContent 	= $fieldvalue->getValueContent();
			$search 		= $fieldvalue->getSearch();
		}

		// Assign value to the recordValue
		$recordValue->setValueContent($valueContent);
		// Assign clean search string to the recordValue
		$recordValue->setSearch($search);

		// Add or update
		if($recordValue->getUid() > 0)
		{
			// Update
			$this->recordValueRepository->update($recordValue);
		}
		else
		{
			// Add
			$this->recordValueRepository->add($recordValue);
			$record->addRecordValue($recordValue);
		}

		// FieldType Text can overwrite the record title, so it can be inactive
		if ($field->getIsRecordTitle() && (strlen($value) < 250))
			$record->appendTitle($valueContent);

		return true;
	}

	/**
	 * Builds default record contents from inline contents
	 * Our inline contents contain a 'value'-Key, that is unneeded but had
	 * to be in out Inline-Form-Field-Configuration. To bad :(
	 *
	 * @param array $inlineContents
	 * @return array
	 */
	protected function _buildRecordContentsFromInlineContents(array $inlineContents = [])
	{
		$recordContents = [];

		$parent = null;
		foreach($inlineContents as $_key=>$_value)
		{
			if ($_key == "value")
			{
				$recordContents = $inlineContents["value"];
			}
			else
			{
				if (is_array($_value))
					$recordContents[$_key] = $this->_buildRecordContentsFromInlineContents($_value);
				else
					$recordContents[$_key] = $_value;
			}

		}

		return $recordContents;
	}
}
