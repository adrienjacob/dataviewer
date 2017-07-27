<?php
namespace MageDeveloper\Dataviewer\Hooks;

use MageDeveloper\Dataviewer\Domain\Model\Field;
use MageDeveloper\Dataviewer\Utility\LocalizationUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class generates the whole tca information for all fields and adds the
 * generated tca information to the globals of the table 'tx_dataviewer_domain_model_record'
 *
 * This helps e.g. to run the default SuggestWizard without any additional configuration
 * or to prevent strange errors later.
 *
 * The TCA that is generated here and stored into the GLOBALS are compliant to the
 * TCA structure of TYPO3 fields.
 */

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
class ExtTablesInclusion implements \TYPO3\CMS\Core\Database\TableConfigurationPostProcessingHookInterface
{
	/**
	 * Object Manager
	 *
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 * @inject
	 */
	protected $objectManager;

	/**
	 * Field Repository
	 *
	 * @var \MageDeveloper\Dataviewer\Domain\Repository\FieldRepository
	 * @inject
	 */
	protected $fieldRepository;

	/**
	 * Field Repository
	 *
	 * @var \MageDeveloper\Dataviewer\Domain\Repository\RecordRepository
	 * @inject
	 */
	protected $recordRepository;

	/**
	 * Fieldtype Settings Service
	 *
	 * @var \MageDeveloper\Dataviewer\Service\Settings\FieldtypeSettingsService
	 * @inject
	 */
	protected $fieldtypeSettingsService;

	/**
	 * Field Configuration
	 *
	 * @var array
	 */
	protected $fieldConfig = [];

	/**
	 * Constructor
	 *
	 * @return ExtTablesInclusion
	 */
	public function __construct()
	{
		$this->objectManager    = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
		$this->fieldRepository	= $this->objectManager->get(\MageDeveloper\Dataviewer\Domain\Repository\FieldRepository::class);
		$this->recordRepository = $this->objectManager->get(\MageDeveloper\Dataviewer\Domain\Repository\RecordRepository::class);
		$this->fieldtypeSettingsService = $this->objectManager->get(\MageDeveloper\Dataviewer\Service\Settings\FieldtypeSettingsService::class);
	}

	/**
	 * Function which may process data created / registered by extTables
	 * scripts (f.e. modifying TCA data of all extensions)
	 *
	 * This method generates the whole tca information of dataviewer fields
	 * and stores the generated information to the GLOBALS
	 *
	 * @return void
	 */
	public function processData()
	{
		// We only need to modify the GLOBALS in backend environment
		if (TYPO3_MODE !== "BE") {
			return;
		}

		if(!ExtensionManagementUtility::isLoaded("dataviewer"))
			return;

		// We need to create a dirty try-catch here, since we have nothing better to check for existence of many different needs
		try {
			// We need to fetch all fields of the types, we have here, so we can pre-load the tca into the GLOBALS here
			$fields = $this->fieldRepository->findAll(false);

			/* @var \MageDeveloper\Dataviewer\Domain\Model\Record $record */
			$record = $this->objectManager->get(\MageDeveloper\Dataviewer\Domain\Model\Record::class);

			foreach($fields as $_field)
			{
				if($_field instanceof Field)
				{
					$fieldtypeConfiguration = $this->fieldtypeSettingsService->getFieldtypeConfiguration($_field->getType());

					if($fieldtypeConfiguration)
					{
						/* @var \MageDeveloper\Dataviewer\Domain\Model\Field $_field */
						$fieldId = $_field->getUid();
						$type = $_field->getType();
						$class = $fieldtypeConfiguration->getFieldClass();

						if($this->objectManager->isRegistered($class))
						{
							/* @var \MageDeveloper\Dataviewer\Form\Fieldtype\AbstractFieldtype $fieldtype */
							$fieldtype = $this->objectManager->get($class);
							$fieldtype->formDataProviders = [];
							$fieldtype->setField($_field);
							$fieldtype->setRecord($record);

							// Removing type to prevent items generation
							$_field->setType("");

							$tca = $fieldtype->buildTca();
							$actualFieldName = $tca["fieldName"];

							$_field->setType($type);

							// We only can store normal fields except the RTE field (that is named
							// '<id>_rte') because since TYPO3 8+
							// the RTE field is retrieved from the database when checkValue is called
							if(is_numeric($actualFieldName)) {
								$config = $tca["processedTca"]["columns"][$actualFieldName]["config"];

								// Injecting the virtual tca into the globals for later usage
								$GLOBALS["TCA"]["tx_dataviewer_domain_model_record"]["columns"][$actualFieldName]["config"] = $config;
							}

						}
					}
					
				}
			}

		} catch (\Exception $e)	{
			return;
		}
	}

}

