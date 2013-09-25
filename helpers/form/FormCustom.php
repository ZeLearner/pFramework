<?php
namespace P\lib\framework\helpers\form;
use P\lib\framework\core\system as system;
use P\lib\framework\core\utils as utils;
use P\lib\framework\core\system\traits as traits;

class FormCustom extends \P\lib\framework\helpers\form\Form
{
    public $model;
    public $saved = true;
    
    public function __construct($poModel = '', $pbPopulate = true) {
        parent::__construct($poModel, $pbPopulate);
        
        $this->populateFromRawData();
        
        $this->getModel();

        if (utils\Http::isPosted())
        {
            if($this->checkForm())
            {
                $this->saved = $this->saveForm();
            }
            else
            {
                $this->saveRawData();
                utils\Http::redirect(\P\url());
            }
        }
        
        if (!$this->saved)
        {
            $this->saveRawData();
            utils\Http::redirect(\P\url());
        }
    }
    
    
    public function getModel()
    {
        
    }
    
    
    public function saveRawData()
    {
        
        $asData = array();
        foreach ($this->_fields as $oField)
        {
//            utils\Debug::e($oField->_field);
            
            $sFieldName = $oField->_field->getName();
            $asData[$sFieldName]        = $oField->_field->value;
        }
                
        $sData = serialize($asData);
        
        $sHash = md5(get_class());

        system\Session::set($sHash, $sData);
        
        $sSession = system\Session::get($sHash);
    }
    
    
    public function populateFromRawData()
    {
        if (!utils\Http::isPosted())
        {
            $sData = system\Session::get(md5(get_class()));
            $asData = unserialize($sData);

            foreach ($this->_fields as $oField)
            {
                if (isset($asData[$oField->_field->getName()]))
                    $oField->setValue($asData[$oField->_field->getName()]);
            }
        }
        
        system\Session::set(md5(get_class()), null);
    }
    
    
    public function checkForm()
    {
        throw new \ErrorException(__METHOD__.' must be implemented by '.__CLASS__);
    }
    
    
    public function saveForm()
    {
        throw new \ErrorException(__METHOD__.' must be implemented by '.__CLASS__);
    }
    
    
    public function __get($name) 
    {
        return $this->getField($name);
    }
}