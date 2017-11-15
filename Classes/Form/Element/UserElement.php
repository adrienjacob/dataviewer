<?php
namespace MageDeveloper\Dataviewer\Form\Element;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Form\Element\UserElement as BackendFormUserElement;

/**
 * Generation of elements of the type "user"
 */
class UserElement extends BackendFormUserElement
{
    /**
     * Additional ResultArray
     * for merging information
     * that needs to be processed
     *
     * @var array
     */
    public $additionalResultArray = [];

    /**
     * User defined field type
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $parameterArray = $this->data['parameterArray'];
        $parameterArray['table'] = $this->data['tableName'];
        $parameterArray['field'] = $this->data['fieldName'];
        $parameterArray['row'] = $this->data['databaseRow'];
        $parameterArray['parameters'] = isset($parameterArray['fieldConf']['config']['parameters'])
            ? $parameterArray['fieldConf']['config']['parameters']
            : [];
        $resultArray = $this->initializeResultArray();
        $resultArray['html'] = GeneralUtility::callUserFunction(
            $parameterArray['fieldConf']['config']['userFunc'],
            $parameterArray,
            $this
        );

        $resultArray = array_merge($resultArray, $this->additionalResultArray);
        return $resultArray;
    }
}