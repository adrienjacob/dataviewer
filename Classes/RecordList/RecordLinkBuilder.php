<?php
declare(strict_types=1);
namespace MageDeveloper\Dataviewer\RecordList;

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
class RecordLinkBuilder extends \TYPO3\CMS\Frontend\Typolink\PageLinkBuilder
{
	/**
	 * @inheritdoc
	 */
	public function build(array &$linkDetails, string $linkText, string $target, array $conf): array
	{
		if(isset($linkDetails["record"])) {
			$recordId = $linkDetails["record"];
			$customLinkDetails = $linkDetails;
			unset($customLinkDetails["record"]);
			$customLinkDetails["type"] = "page";
			
			$parameters = [];
			if(isset($customLinkDetails["parameters"])) {
				parse_str($customLinkDetails["parameters"], $parameters);
				unset($parameters["record"]);
			}

			$parameters["tx_dataviewer_record"] = [
				"record" => $recordId,
				"action" => "dynamicDetail",
				"controller" => "Record",
			];
			$customLinkDetails["parameters"] = http_build_query($parameters);

			return parent::build($customLinkDetails, $linkText, $target, $conf);
		}
	
	
		return parent::build($linkDetails, $linkText, $target, $conf);
	}

}
