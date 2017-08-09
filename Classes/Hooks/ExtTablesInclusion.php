<?php
namespace MageDeveloper\Dataviewer\Hooks;

use MageDeveloper\Dataviewer\Domain\Model\Field;
use MageDeveloper\Dataviewer\Domain\Repository\FieldRepository;
use MageDeveloper\Dataviewer\Factory\TcaFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

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
	 * Tca Factory
	 * 
	 * @var TcaFactory
	 * @inject
	 */
	protected $tcaFactory;
	
	/**
	 * Constructor
	 *
	 * @return ExtTablesInclusion
	 */
	public function __construct()
	{
		$this->objectManager    = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ObjectManager::class);
		$this->fieldRepository	= $this->objectManager->get(FieldRepository::class);
		$this->tcaFactory 		= $this->objectManager->get(TcaFactory::class);
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

		// We raw-create the tca information here and are ignoring all errors that could happen, just
		// to let TYPO3 live
		try {
			// We need to fetch all fields of the types, we have here, so we can pre-load the tca into the GLOBALS here
			$fields = $this->fieldRepository->findAll(false);

			foreach($fields as $_field)
			{
				if($_field instanceof Field)
				{
					$tca = $this->tcaFactory->generateByField($_field);
					$actualFieldName = $tca["fieldName"];

					// We only can store normal fields except the RTE field (that is named
					// '<id>_rte') because since TYPO3 8+
					// the RTE field is retrieved from the database when checkValue is called - but
					// we don't use that default database stuff in dataviewer since its all virtual
					if(is_numeric($actualFieldName)) {
						$config = $tca["processedTca"]["columns"][$actualFieldName]["config"];

						// Injecting the virtual tca into the globals for later usage
						$GLOBALS["TCA"]["tx_dataviewer_domain_model_record"]["columns"][$actualFieldName]["config"] = $config;
					}

				}
			}
			
		} catch (\Exception $e)	{
			return;
		}
	}

}

