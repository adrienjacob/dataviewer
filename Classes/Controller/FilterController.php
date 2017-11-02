<?php
namespace MageDeveloper\Dataviewer\Controller;

use MageDeveloper\Dataviewer\Service\Session\FilterSessionService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

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
class FilterController extends AbstractController
{
	/***************************************************************************
	 * This controller is used for filtering records
	 ***************************************************************************/
	 
	/**
	 * Filter Settings Service
	 *
	 * @var \MageDeveloper\Dataviewer\Service\Settings\Plugin\FilterSettingsService
	 * @inject
	 */
	protected $filterSettingsService;

	/**
	 * Filter Session Service
	 *
	 * @var \MageDeveloper\Dataviewer\Service\Session\FilterSessionService
	 * @inject
	 */
	protected $filterSessionService;

	/**
	 * Field Repository
	 * 
	 * @var \MageDeveloper\Dataviewer\Domain\Repository\FieldRepository
	 * @inject
	 */
	protected $fieldRepository;

    /**
     * Prepared Filters
     *
     * @var array
     */
	protected $filters = [];

	/**
	 * Index Action
	 * Displays the search form and
	 * evaluates the search on post/get
	 *
	 * @return void
	 */
	public function indexAction()
	{
		$filters = $this->_getFilters();
		$activeFilters = $this->filterSessionService->getSelectedOptions();
		
		// Inject fields
		$this->_injectFields($filters);
		$this->_injectFields($activeFilters);
		
		$activeFiltersGrouped = [];
		foreach($activeFilters as $_activeFilter)
		{
			$activeFiltersGrouped[$_activeFilter["field_id"]]["field"] = $_activeFilter["field"];
			$activeFiltersGrouped[$_activeFilter["field_id"]]["filters"][] = $_activeFilter;
		}
		
		$this->view->assign("filters", $filters);
		$this->view->assign("activeFilters", $activeFiltersGrouped);
		$this->view->assign("targetUid", $this->filterSettingsService->getTargetContentUid());
	}
	
	/**
	 * Action for adding new filters to the session
	 *
	 * @return void
	 */
	public function addAction()
	{
		if(!$this->_checkTargetUid())
			$this->_redirectToPid();

		if ($this->request->getMethod() == "POST") {

            $selected = [];
		    if($this->request->hasArgument("filters")) {
                // Prepare Filters
                $filtersFromPost = $this->request->getArgument("filters");

                foreach($filtersFromPost as $_fieldId=>$_fArray)
                {
                    foreach($_fArray as $key=>$_id)
                    {
                        if ($selectedOption = $this->_getOptionById($_id))
                            $selected[] = $selectedOption;
                        else
                            if($selectedOption = $this->_getOptionById($key))
                            {
                                // We only add the filter, if we received a useful value
                                if($_id != "")
                                {
                                    $selectedOption["field_value"] = $_id;
                                    $selected[] = $selectedOption;
                                }
                            }
                            else
                            {
                                $merge = false; // TODO: configurable?
                                // Option was not found in current filter,
                                // so we determine the setting, if we can
                                // merge it anyway with the previous selected
                                if($merge === true)
                                {
                                    // We need to security check the selected option
                                    $selectedOption["field_value"] = $_id;
                                    $selected[] = $selectedOption;
                                }
                            }
                    }

                }
            }

			$previousSelected = $this->filterSessionService->getSelectedOptions();
			$selectedOptions = $this->_prepareSelectedOptionsArray([], $selected);

			/////////////////////////////////////////////////////////////////
			// Signal-Slot for the post-processing of the selected options //
			/////////////////////////////////////////////////////////////////
			$this->signalSlotDispatcher->dispatch(
				__CLASS__,
				"postProcessSelectedOptions",
				[
					&$selectedOptions,
					&$this,
				]
			);

			$this->filterSessionService->setSelectedOptions($selectedOptions);
		}

		$this->_redirectToPid();
	}

	/**
	 * Remove a filter from the session
	 * 
	 * @param string $id
	 * @return void
	 */
	public function removeAction($id)
	{
		if(!$this->_checkTargetUid())
			$this->forward("index");
	
		$this->filterSessionService->removeOption($id);
		$this->forward("index");
	}

	/**
	 * Resets all filters
	 * 
	 * @return void
	 */
	public function resetAction()
	{
		if(!$this->_checkTargetUid())
			$this->forward("index");
	
		$this->filterSessionService->setSelectedOptions([]);
		$this->forward("index");
	}

	/**
	 * Injects fields to a filter array
	 * 
	 * @param array $filters
	 * @return void
	 */
	protected function _injectFields(array &$filters)
	{
		foreach($filters as $_fId=>$_filter)
		{
			$fieldId = $_filter["field_id"];
			
			if(is_numeric($fieldId))
			{
				$field   = $this->fieldRepository->findByUid($fieldId, true);
				if ($field instanceof \MageDeveloper\Dataviewer\Domain\Model\Field)
					$filters[$_fId]["field"] = $field;
				else
					unset($filters[$_fId]);	// We unset the filter, because we could'nt find the according field	
			}
			else
			{
				$filters[$_fId]["field"] = $fieldId;
			}
		}
	}

	/**
	 * Gets the filters from the plugin settings
	 *
	 * @return array
	 */
	protected function _getFilters()
	{
	    if(!$this->filters || empty($this->filters)) {

            $filters = $this->filterSettingsService->getFilters();

            // Fill in session data
            foreach($filters as $i=>$_filter)
            {
                $fieldId = $_filter["field_id"];
                $filters[$i]["is_active"] = false;
                $filterType = $_filter["filter_type"];

                foreach($_filter["options"] as $j=>$_option)
                {
                    $optionId = $_option["id"];
                    $optionSelected = $this->filterSessionService->checkIsSelected($fieldId, $optionId);
                    $filters[$i]["options"][$j]["selected"] = $optionSelected;
                    $filters[$i]["options"][$j]["filter_type"] = $filterType;

                    if ($optionSelected)
                        $filters[$i]["is_active"] = true;

                }
            }

            $this->filters = $filters;
        }

		return $this->filters;
	}

	/**
	 * Gets an according option setting by a given id hash
	 *
	 * @param string $id
	 * @return array|bool
	 */
	protected function _getOptionById($id)
	{
		$filters = $this->_getFilters();
		foreach($filters as $i=>$_filter)
		{
			$options = $_filter["options"];
			foreach($options as $_i=>$_option)
			{
				if ($_option["id"] == $id)
					return $_option;
			}

		}
		return false;
	}

	/**
	 * Prepares an final filter array from raw data from the form post
	 *
	 * @param array $previousSelectedOptions
	 * @param array $currentSelectedOptions
	 * @param bool $merge 
	 * @return array
	 */
	protected function _prepareSelectedOptionsArray(array $previousSelectedOptions = [], array $currentSelectedOptions = [], $merge = false)
	{
		$selectedOptions = [];

        // We remove filters, that have no filter_field
        foreach($previousSelectedOptions as $i=>$_prvOpt)
            if($_prvOpt["filter_field"] == "")
                unset($previousSelectedOptions[$i]);

        foreach($currentSelectedOptions as $i=>$_curOpt)
            if($_curOpt["filter_field"] == "")
                unset($currentSelectedOptions[$i]);


		foreach($previousSelectedOptions as $i=>$_prvOpt)
			foreach($currentSelectedOptions as $j=>$_curOpt)
			{
				if(($_prvOpt["field_id"] == $_curOpt["field_id"]) && $merge === false)
				{
					// Clean previous array
					unset($previousSelectedOptions[$i]);
				}

			}
		
		$selectedOptions = array_merge($previousSelectedOptions, $currentSelectedOptions);

		// We need to group the selected options by their field
        $selectedOptionsGrouped = $this->_groupSelectedOptions($selectedOptions);

        $options = [];
        foreach($selectedOptionsGrouped as $_groupId=>$_group) {
            $adjustedOptions = $this->_createAdjustedFilterCombinationForFilters($_group);
            $options = array_merge($options, $adjustedOptions);
        }

		return $options;
	}

    /**
     * Creates correct filter rules for grouped selected options
     *
     * @param array $selectedOptionsGrouped
     * @return array
     */
	protected function _createAdjustedFilterCombinationForFilters(array $selectedOptionsGrouped)
    {
        $filters = $this->_getFilters();
        $groupFilterCombinations = [];
        foreach($filters as $_filter) {
            $groupFilterCombinations[$_filter["field_id"]] = $_filter["group_filter_combination"];
        }

        // We need to create a correct working filter_condition for all the filters in our array
        if(count($selectedOptionsGrouped) == 1) {

            // Filter combination of the initial element
            $fieldId = $selectedOptionsGrouped[0]["field_id"];
            $filterCombination = $groupFilterCombinations[$fieldId];
            $selectedOptionsGrouped[0]["filter_combination"] = "{$filterCombination} (...)";

        } elseif (count($selectedOptionsGrouped) == 2) {

            // Filter Combination of the Starting Element
            $fieldIdStart = $selectedOptionsGrouped[0]["field_id"];
            $filterCombinationStart = $groupFilterCombinations[$fieldIdStart];
            $selectedOptionsGrouped[0]["filter_combination"] = "{$filterCombinationStart} (...";

            // Filter Combination of the Last Element
            $filterCombinationEnd = $selectedOptionsGrouped[1]["filter_combination"];
            $selectedOptionsGrouped[1]["filter_combination"] = "{$filterCombinationEnd} ...)";

        } elseif (count($selectedOptionsGrouped) > 2) {
            for($i = 0; $i<count($selectedOptionsGrouped);$i++) {
                if($i == 0) {

                    // FIRST ONE HAS TO START WITH 'AND (...' TO OPEN
                    $fieldIdStart = $selectedOptionsGrouped[$i]["field_id"];
                    $filterCombinationStart = $groupFilterCombinations[$fieldIdStart];
                    $selectedOptionsGrouped[$i]["filter_combination"] = "{$filterCombinationStart} (...";

                } elseif($i == count($selectedOptionsGrouped) - 1) {

                    // LAST ONE HAS TO END WITH A ) TO CLOSE ALL
                    $filterCombinationOfLast = $selectedOptionsGrouped[count($selectedOptionsGrouped)-1]["filter_combination"];
                    $selectedOptionsGrouped[$i]["filter_combination"] = "{$filterCombinationOfLast} ...)";

                } else {
                    $currentFilterCombination = $selectedOptionsGrouped[$i]["filter_combination"];
                    $selectedOptionsGrouped[$i]["filter_combination"] = "{$currentFilterCombination} ...";
                }
            }
        }

        return $selectedOptionsGrouped;
    }



    /**
     * Groups selected options by field
     *
     * @param array $selectedOptions
     * @param string $groupField
     * @return array
     */
	protected function _groupSelectedOptions($selectedOptions, $groupField = "field_id")
    {
        $grouped = [];
        foreach($selectedOptions as $i=>$_sel) {
            $fieldId = trim($_sel[$groupField]);
            $grouped[$fieldId][] = $_sel;
        }

        return $grouped;
    }

	/**
	 * initializeView
	 * Initializes the view
	 *
	 * Adds some variables to view that could always
	 * be useful
	 *
	 * @param ViewInterface $view
	 * @return void
	 */
	protected function initializeView(ViewInterface $view)
	{
		// Individual session key
		$uid = $this->filterSettingsService->getTargetContentUid();
		$sessionKey = FilterSessionService::SESSION_PREFIX_KEY;
		$this->filterSessionService->setPrefixKey("{$sessionKey}-{$uid}");

		// Parent
		parent::initializeView($view);
	}
}
