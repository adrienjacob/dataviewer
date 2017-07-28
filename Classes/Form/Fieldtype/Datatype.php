<?php
namespace MageDeveloper\Dataviewer\Form\Fieldtype;

use MageDeveloper\Dataviewer\Domain\Model\Field;
use MageDeveloper\Dataviewer\Domain\Model\RecordValue;

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
class Datatype extends Inline
{
	/**
	 * Gets built tca array
	 *
	 * @return array
	 */
	public function buildTca()
	{
		$fieldName 					= $this->getField()->getUid();
		$tableName 					= "tx_dataviewer_domain_model_record";
		$value 						= $this->getValue();
		$databaseRow 				= $this->getDatabaseRow();
		$databaseRow[$fieldName] 	= $value;

		$tca = [
			"command" => "edit",
			"tableName" => $tableName,
			"databaseRow" => $databaseRow,
			"fieldName" => $fieldName,
			"processedTca" => [
				"ctrl" => [
					"label" => $this->getField()->getFrontendLabel(),
				],
				"columns" => [
					$fieldName => [
						"exclude" => (int)$this->getField()->isExclude(),
						"label" => $this->getField()->getFrontendLabel(),
						"config" => [
							"type" => "inline",
							"foreign_table" => "tx_dataviewer_domain_model_record",
							"foreign_record_defaults" => [
								"datatype" => $this->getField()->getConfig("datatype"),
							],	
							"maxitems"      => 9999,
							"appearance" => [
								"collapseAll" => 1,
								"levelLinksPosition" => "top",
								"showSynchronizationLink" => 1,
								"showPossibleLocalizationRecords" => 1,
								"useSortable" => 1,
								"showAllLocalizationLink" => 1,
							],
						],
					],
				],
			],
			"inlineStructure" => [],
			"inlineFirstPid" => $this->getInlineFirstPid(),
			"inlineResolveExistingChildren" => true,
			"inlineCompileExistingChildren"=> true,
		];

		$this->prepareTca($tca);
		$this->tca = $tca;
		return $this->tca;
	}

}
