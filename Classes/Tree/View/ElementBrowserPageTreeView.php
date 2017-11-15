<?php
namespace MageDeveloper\Dataviewer\Tree\View;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Utility\MathUtility;

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
class ElementBrowserPageTreeView extends \TYPO3\CMS\Backend\Tree\View\ElementBrowserPageTreeView
{
	/**
	 * Wrapping the title in a link, if applicable.
	 *
	 * @param string $title Title, (must be ready for output, that means it must be htmlspecialchars()'ed).
	 * @param array $v The record
	 * @param bool $ext_pArrPages (ignored)
	 * @return string Wrapping title string.
	 */
	public function wrapTitle($title, $v, $ext_pArrPages = false)
	{
		if ($this->ext_isLinkable($v['doktype'], $v['uid'])) {
			$url = GeneralUtility::makeInstance(LinkService::class)->asString(['type' => "dataviewer", 'pageuid' => (int)$v['uid']] );
			return '<span class="list-tree-title active"><a href="' . htmlspecialchars($url) . '" class="t3js-pageLink">' . $title . '</a></span>';
		} else {
			return '<span class="list-tree-title text-muted">' . $title . '</span>';
		}
	}

	/**
	 * Wrap the plus/minus icon in a link
	 *
	 * @param string $icon HTML string to wrap, probably an image tag.
	 * @param string $cmd Command for 'PM' get var
	 * @param string $bMark If set, the link will have an anchor point (=$bMark) and a name attribute (=$bMark)
	 * @param bool $isOpen
	 * @return string Link-wrapped input string
	 */
	public function PM_ATagWrap($icon, $cmd, $bMark = '', $isOpen = false)
	{
		$recordId = GeneralUtility::_GET("record");
	
		if(!$recordId && is_array(GeneralUtility::_GET("curUrl"))) {
			$curUrl = GeneralUtility::_GET("curUrl");
			$query = parse_url($curUrl["url"], PHP_URL_QUERY);
			parse_str($query, $urlParts);
			
			if(isset($urlParts["record"]))
				$recordId = (int)$urlParts["record"];
		}
				
	
		$anchor = $bMark ? '#' . $bMark : '';
		$name = $bMark ? ' name=' . $bMark : '';
		$urlParameters = $this->linkParameterProvider->getUrlParameters([]);
		$urlParameters['PM'] = $cmd;
		
		if(MathUtility::canBeInterpretedAsInteger($recordId))
			$urlParameters["record"] = $recordId;
			
		$urlParameters["treeAction"] = 1;	
			
		$aOnClick = 'return jumpToUrl(' . GeneralUtility::quoteJSvalue($this->getThisScript() . ltrim(GeneralUtility::implodeArrayForUrl('', $urlParameters), '&')) . ',' . GeneralUtility::quoteJSvalue($anchor) . ');';
		return '<a class="list-tree-control ' . ($isOpen ? 'list-tree-control-open' : 'list-tree-control-closed')
			. '" href="#"' . htmlspecialchars($name) . ' onclick="' . htmlspecialchars($aOnClick) . '"><i class="fa"></i></a>';
	}
}
