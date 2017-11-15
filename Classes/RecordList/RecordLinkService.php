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
class RecordLinkService implements \TYPO3\CMS\Core\LinkHandling\LinkHandlingInterface
{
	/**
	 * The Base URN for this link handling to act on
	 * @var string
	 */
	protected $baseUrn = 't3://dataviewer';

	/**
	 * Returns all valid parameters for linking to a TYPO3 page as a string
	 *
	 * @param array $parameters
	 * @return string
	 */
	public function asString(array $parameters): string
	{
		$urn = $this->baseUrn;
		if (isset($parameters['pagealias']) && $parameters['pagealias'] !== 'current') {
			$urn .= '?alias=' . $parameters['pagealias'];
		} else {
			$urn .= '?page=' . $parameters['pageuid'];
		}
		$urn = rtrim($urn, ':');
		if (!empty($parameters['pagetype'])) {
			$urn .= '&type=' . $parameters['pagetype'];
		}
		if (!empty($parameters['parameters'])) {
			$urn .= '&' . ltrim($parameters['parameters'], '?&');
		}
		if (!empty($parameters['fragment'])) {
			$urn .= '#' . $parameters['fragment'];
		}

		return $urn;
	}

	/**
	 * Returns all relevant information built in the link to a page (see asString())
	 *
	 * @param array $data
	 * @return array
	 */
	public function resolveHandlerData(array $data): array
	{
		$result = [];

		if(isset($data["record"])) {
			$result["record"] = $data["record"];
		}
		if (isset($data['page'])) {
			$result['pageuid'] = $data['page'];
			unset($data['page']);
		}
		if (isset($data['alias'])) {
			$result['pagealias'] = $data['alias'];
			unset($data['alias']);
		}
		if (isset($data['type'])) {
			$result['pagetype'] = $data['type'];
			unset($data['type']);
		}
		if (!empty($data)) {
			$result['parameters'] = http_build_query($data);
		}
		if (empty($result)) {
			$result['pageuid'] = 'current';
		}

		return $result;
	}
}
