<?php
namespace MageDeveloper\Dataviewer\Factory;

use MageDeveloper\Dataviewer\Domain\Model\Datatype;
use MageDeveloper\Dataviewer\Domain\Model\Record;
use MageDeveloper\Dataviewer\Domain\Model\Field;
use MageDeveloper\Dataviewer\Domain\Model\FieldtypeConfiguration;
use MageDeveloper\Dataviewer\Exception\NoDatatypeException;
use MageDeveloper\Dataviewer\Form\Fieldtype\AbstractFieldtype;
use MageDeveloper\Dataviewer\Exception\UnknownFieldTypeException;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\SingletonInterface;

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

/**
 * Class TcaFactory
 *
 * This Factory generates the tca for fields by using the different fieldtypes.
 * 
 * @package MageDeveloper\Dataviewer\Factory
 */
class TcaFactory implements SingletonInterface
{
	/**
	 * Object Manager
	 *
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 * @inject
	 */
	protected $objectManager;

	/**
	 * Fieldtype Settings Service
	 *
	 * @var \MageDeveloper\Dataviewer\Service\Settings\FieldtypeSettingsService
	 * @inject
	 */
	protected $fieldtypeSettingsService;

	/**
	 * Get the final tca for a fieldtype
	 * 
	 * @param AbstractFieldtype $fieldtype
	 * @param \MageDeveloper\Dataviewer\Domain\Model\Field $field
	 * @param \MageDeveloper\Dataviewer\Domain\Model\Record $record
	 * @param bool $generateItems
	 * @return array
	 */
	public function getTcaByFieldtype(AbstractFieldtype $fieldtype, Field $field, Record $record, $generateItems = false)
	{
		$type = $field->getType();
		$fieldtype->formDataProviders = [];
		$fieldtype->setField($field);
		$fieldtype->setRecord($record);

		if($generateItems === true)
			$field->setType(""); // Removing type to prevent items generation

		$tca = $fieldtype->buildTca(); // Main generation of the TCA through our Fieldtype

		if($generateItems === true)
			$field->setType($type); // Adding the type back to the field

		return $tca;
	}
	
	/**
	 * Generates the tca by a given field and
	 * record
	 *
	 * @param Field  $field
	 * @param Record $record
	 * @param bool   $generateItems
	 * @return array
	 * @throws \MageDeveloper\Dataviewer\Exception\UnknownFieldTypeException
	 */
	public function generateByField(Field $field, Record $record = null, $generateItems = false)
	{
		if(is_null($record)) {
			$record = $this->objectManager->get(Record::class);
		}
		
		if($field->getType() == "")
			throw new UnknownFieldTypeException("Field has no type!");
		
		$type = $field->getType();

		/* @var FieldtypeConfiguration $fieldtypeConfig */
		$fieldtypeConfig = $this->fieldtypeSettingsService->getFieldtypeConfiguration($type);
		
		if(!$fieldtypeConfig)
			throw new UnknownFieldTypeException("FieldType Configuration for Type '{$type}' doesn't exist!");

		$class = $fieldtypeConfig->getFieldClass();
		if($this->objectManager->isRegistered($class))
		{
			/* @var AbstractFieldtype $fieldtype */
			$fieldtype = $this->objectManager->get($class);
			
			$tca = $this->getTcaByFieldtype($fieldtype, $field, $record, $generateItems);
			return $tca;
		}
		
		return [];
	}

	/**
	 * Generates the tca for a complete record with
	 * all the fields that the record has attached
	 *
	 * @param Record $record
	 * @param bool $generateItems
	 * @return array
	 * @throws \MageDeveloper\Dataviewer\Exception\NoDatatypeException
	 */
	public function generateByRecord(Record $record, $generateItems = false)
	{
		if(!$record->getDatatype() instanceof Datatype) {
			throw new NoDatatypeException("Record with Uid '{$record->getUid()}' has no datatype!");
		}
	
		$datatype = $record->getDatatype();
		return $this->generateByDatatype($datatype, $record);
	}

	/**
	 * Generate TCA by a datatype with an optional record
	 *
	 * @param \MageDeveloper\Dataviewer\Domain\Model\Datatype $datatype
	 * @param \MageDeveloper\Dataviewer\Domain\Model\Record|null $record
	 * @param bool $generateItems
	 * @return array
	 */
	public function generateByDatatype(Datatype $datatype, Record $record = null, $generateItems = false)
	{
		$fields = $datatype->getFields();
		$tca = [];
		
		if($fields->count() > 0) {
		
			
			foreach($fields as $_field) {
				
				$fieldTca = $this->generateByField($_field, $record, $generateItems);
				ArrayUtility::mergeRecursiveWithOverrule($tca, $fieldTca);
				
				
			}
		}
	
		return $tca;
	}
}
