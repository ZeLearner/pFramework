<?php
namespace P\lib\framework\core\system\traits\crud;
use P\lib\framework\core\utils as utils;
use P\lib\framework\helpers as helpers;

trait readajax
{
    use read;
    
    protected $editable;
    protected $deletable;
    protected $columnType = array();
    protected $readajax_customConditions = array();
    
    
    public function read()
    {
        $this->theme->actions               = $this->_getActions();
        $this->theme->title                 = $this->_getTitle('index');
        $this->theme->searchable            = $this->_handleSerchableForDisplay();
        $this->theme->searchable_custom     = $this->_handleCustomSerchableForDisplay();
        
        $this->setTableOptions();
        
        $this->theme->tableHeader    = $this->readajax_getTableHeader(true, true);
        $this->theme->fields         = $this->readajax_getFieldNames();
        $this->theme->ajaxSource     = $this->readajax_getAjaxSource();
        
        // HTML HEADER
        $this->readajax_headerHtml();
        
        // code ajax supplémentaire
        $this->readajax_addJs();
        
        
        $this->theme->controller     = $this;
        
        return $this->theme->display($this->getReadAjaxTemplate());
    }
    
    protected function readajax_getSalt()
    {
        return md5(get_called_class());
    }
    
    
    public function readajax_getCookieName($psName)
    {
        return $this->readajax_getSalt().'_'.str_replace('.', '__', $psName);
    }
    
    protected function _readajax_customConfig()
    {
        return true;
    }
    
    public function setTableOptions()
    {
        $this->editable     = true;
        $this->deletable    = true;
    }
    
    
    public function readajax_getCustomFilters()
    {
        return array();
    }
    
    public function _handleCustomSerchableForDisplay()
    {
        $asFields = $this->readajax_getCustomFilters();
        
        $asFinal = array();
        foreach ($asFields as $sField => $sLabel)
        {
            $asFinal[$this->readajax_getCookieName($sField)] = $sLabel;
        }
        
        return $asFinal;
    }
    
    
    public function readajax_getTableHeader()
    {
        $asFields = $this->model->getFieldLabels('browsable');
        
        if ($this->editable)
            $asFields[] = '-';

        if ($this->deletable)
            $asFields[] = '-';
        
        return $asFields;
    }
    
    
    public function readajax_headerHtml()
    {
        $this->theme->readAjax_header = '';
    }
    
    
    protected function readajax_addJs()
    {
        
    }
    
    
    public function readajax_getFieldNames()
    {
        return $this->model->getFieldNames('browsable');
    }
    
    
    public function readajax_getColumnType()
    {
        $asTypes = array();
        $asFields = $this->readajax_getFieldNames();
        
        foreach ($asFields as $sName)
        {
            $asTypes[] = 'String';
        }
        
        return $asTypes;
    }
    
    
    public function readajax_getColumnNameByIndice($pnIndice)
    {
        return $this->readajax_getFieldNames()[($pnIndice)];
    }
    
    public function readajax_getSearcheableFields()
    {
        throw new \ErrorException('Vous devez implémenter readajax_getSearcheableFields() et retourner un array() of fields');
    }
    
    
    public function getReadAjaxTemplate()
    {
        return 'trait_readajax_read.tpl.php';
    }
    
    
    public function readajax_getAjaxSource()
    {
        return \P\url('', 'datatable');
    }
    
    
    public function datatable()
    {
        $this->_readajax_customConfig();
        $this->setTableOptions();
        $this->theme->setAjax();
        $this->_disableControllerCall(__METHOD__);
        $this->columnType = $this->readajax_getColumnType();
        
        
        // paging
        $nStart     = utils\Http::getParam('iDisplayStart', 0);
        $nLimit     = utils\Http::getParam('iDisplayLength', 100);
        
        // token
        $sEcho      = utils\Http::getParam('sEcho');
        
        // search
        $sSearch    = utils\Http::getParam('sSearch');
        
        // sort
        $sOrder     = utils\Http::getParam('sSortDir_0');
        
        $sSort = $this->readajax_getOrder();
        
        if (!empty($sOrder))
            $sSort = $this->readajax_getColumnNameByIndice(utils\Http::getParam ('iSortCol_0')).' '.$sOrder;
        
        $asFieldsName       = $this->readajax_getFieldNames();
        $asFieldsSearch     = $this->_handleSearchableFields();
        
        // Total records = 
        $nCountTotal    = $this->readajax_getQueryCount();
        $oResults       = $this->readajax_getQuery($asFieldsName, $asFieldsSearch, $nStart, $nLimit, $sSort, $sSearch);
        
        $sPrimary   = $this->model->getPrimary();
        $aasData    = array();
        $i          = 0;
        
        while ($oRecord = $oResults->readNext(RESPONSE_RAW))
        {
            $i++;
            $asLine = array();
            
            $nColumnIndex = 0;
            foreach ($asFieldsName as $sField)
            {
                $asLine[] = $this->readajax_renderColumn($oRecord, $sField, $nColumnIndex);
                $nColumnIndex++;
            }
            
            if ($this->editable)
                $asLine[] = \P\tag('a', '<i class="icon icon-edit"></i>', array('href' => \P\url('', 'update')->setParam('key', $oRecord->$sPrimary)))->__toString();
            
            if ($this->deletable)
                $asLine[] = \P\tag('a', '<i class="icon icon-remove"></i>', array('href' => \P\url('', 'delete')->setParam('key', $oRecord->$sPrimary)))->__toString();
            
            $this->readAjax_addCustomColumns($asLine, $oRecord);
            
            $aasData[] = $asLine;
        }
        
        $asReturn = array('sEcho' => $sEcho, 'iTotalRecords' => $nCountTotal, 'iTotalDisplayRecords' => $oResults->count, 'aaData' => $aasData);
        
        return json_encode($asReturn);
    }
    
    
    private function _handleSearchableFields()
    {
        $asFields   = $this->readajax_getSearcheableFields();
        
//        utils\Debug::e($_COOKIE);
        
        $asFinal = array();
        foreach ($asFields as $sField => $sLabel)
        {
            if (is_integer($sField))
            {
                $asFinal[] = $sLabel;
            }
            else
            {
//                utils\Debug::e($sField.' => '.$sLabel);
//                utils\Debug::e('ignore'.md5($sField).' = '.\P\lib\framework\core\system\Session::get('ignore'.md5($sField), 0));
                
                if (!(bool) \P\lib\framework\core\system\Session::get($this->readajax_getCookieName($sField), 0))
                {
                    $asFinal[] = $sField;
                }
            }
        }
        
//        utils\Debug::e($asFinal);
        
        return $asFinal;
    }
    
    
    public function readajax_handleCustomSearchFields()
    {
        return array();
    }
    
    
    private function _handleSerchableForDisplay()
    {
        $asFields = $this->readajax_getSearcheableFields();
        
        $asFinal = array();
        foreach ($asFields as $sField => $sLabel)
        {
            if (is_integer($sField))
                return false;
            else
            {
                $asFinal[$this->readajax_getCookieName($sField)] = $sLabel;
            }
        }
        
        return $asFinal;
    }
    
    
    
    protected function readAjax_addCustomColumns(&$pasLine, $poRecord)
    {
        return true;
    }
    
    
    
    
    protected function readajax_renderColumn($poRecord, $psField, $pnColumnIndex)
    {
        return $this->readajax_renderColumnDefault($poRecord, $psField, $pnColumnIndex);
    }
    
    
    protected function readajax_renderColumnDefault($poRecord, $psField, $pnColumnIndex)
    {
        $asItem = explode('.', $psField);
        
        if (count($asItem) > 1)
            $psField = $asItem[1];
        
        $sType = 'string';
        if (isset($this->columnType[$pnColumnIndex]))
            $sType = $this->columnType[$pnColumnIndex];
        
        $sValue = $poRecord->$psField;
        
        switch($sType)
        {
            case 'integer':
                return (int) $sValue;
                break;
            
            case 'date':
                return utils\Date::toDisplay($sValue, true);
                break;
            
            case 'datetime':
                return utils\Date::toDisplay($sValue);
                break;
            
            case 'money':
                return utils\Number::money($sValue);
                break;
            
            case 'foreign':
                return $this->readajax_RenderForeignField($psField, $sValue);
            
            case 'string':
            default :
                return $sValue;
                break;
        }
    }
    
    
    protected function readajax_RenderForeignField($psField, $psValue)
    {
        $oField = $this->model->getField($psField);
        
        if (is_object($oField))
        {
            if ($oField->foreign)
            {
                $sTable = $oField->getForeignTable();
                $sField = $oField->getForeignField();
                $sLabel = $oField->getForeignLabelField();
                
//                utils\Debug::e($sLabel);
//                utils\Debug::e($sField);
//                utils\Debug::e($sTable);
//                
//                utils\Debug::e($oField);
                
                if (!empty($sTable))
                {
                    $oObject = \P\lib\framework\core\system\ClassManager::getInstance($sTable);
                    $oRecord = $oObject->model->selectByPK((int) $psValue);

                    if (isset($oRecord->$sLabel))
                        return \P\tag ('a', $oRecord->$sLabel, array('href' => \P\url($sTable, 'update')->setParam('key', $psValue)))->__toString();
                }
            }
        }
        
        return $psValue;
    }
    
    
    protected function readajax_getQueryCount()
    {
        return $this->model->count();
    }
    
    
    protected function readajax_getQuery($pasFieldsName, $pasFieldsSearch, $pnStart, $pnLimit, $psSort, $psSearch)
    {
        $this->readajax_handleCustomSearchFields();
        return $this->model->selectForDatatable($pasFieldsName, $pasFieldsSearch, $pnStart, $pnLimit, $psSort, $psSearch, '', $this->readajax_customConditions);
    }
    
    
    public function readajax_getOrder()
    {
        return $this->model->getPrimary().' DESC';
    }
}