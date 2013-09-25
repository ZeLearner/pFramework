<?php
namespace P\lib\framework\core\system\abstractClasses;
use P\lib\framework\core\system as system;
use P\lib\framework\core\utils as utils;
use P\lib\framework\helpers as helpers;
use P\lib\framework\core\system\interfaces as interfaces;
use P\lib\framework\core\system\traits as traits;

abstract class Controller extends Object implements interfaces\isCallable
{
    public $model;

    /**
        * @var type \P\lib\framework\themes\ThemeManager
        */
    public      $theme;
    public      $context;
    protected   $_oRights;
    static      $oaClasses = array();


    public function __construct()
    {
        $this->theme    = \P\lib\framework\themes\ThemeManager::load();

        return parent::__construct();
    }
    
    
    public function info()
    {
//        throw new \ErrorException('ghjkl');
        phpinfo();
        die();
    }


    /**
        * Returns the name of the class (even if it is called by a child class)
        */
    public function getName()
    {
        return get_called_class();
    }


    public function getViewDir()
    {
            return system\PathFinder::getViewDir($this->getName());
    }

    
    public function __call($name, $arguments)
    {
        foreach(self::$oaClasses as $oObject)
        {
            if (method_exists($oObject, $name))
            {
                return $oObject->$name($arguments);
            }
        }
        
        throw new \ErrorException('Method '.get_called_class().'::'.$name.' is undefined');
        
        return utils\Http::error404();
    }


    public function getTitle($psName, $psAction)
    {
        return ucfirst($psName).' : '.$psAction;
    }


    public function getActionButton()
    {
        return array();
    }


    public function getTableName()
    {
        if ($this->model instanceof Model)
        {
            return $this->model->getTable();
        }
    }


    public function populateForeignField($poDalField, $poFormField)
    {
        system\ForeignFieldData::populate($this, $poDalField, $poFormField);
    }
        
        
    public function getLabel($pnPK)
    {
        if ($pnPK == 0) return 'aucun';

        $sFieldLabel = $this->model->getLabelFieldName();

        $oRecord = $this->model->selectByPK($pnPK);

        //utils\Debug::dump($oRecord);

        if (isset($oRecord->$sFieldLabel)) return $oRecord->$sFieldLabel;
    }


    protected function getKey()
    {
        return (int) utils\Http::getParam('key');
    }
        
        
        
    protected function _getTitle($psAction)
    {
        $sVar = strtoupper($this->model->getTable());
        
        switch($psAction)
        {
            case 'read':
            case 'index':
                return constant('INDEX_'.$sVar);
                break;
            case 'create':
                return constant('ADD_'.$sVar);
                break;
            case 'delete':
                return constant('DELETE_'.$sVar);
                break;
            case 'update':
                return constant('EDIT_'.$sVar);
                break;
        }
        
        return ;
   }
   
   
   protected function _disableControllerCall()
   {
       
   }
   
   
   public function ajax()
   {
       \P\lib\framework\themes\ThemeManager::setAjax();
       
       $sAjax = strtolower(utils\Http::getParam('ajax'));
       
       switch ($sAjax)
       {
           case 'editinplace':
               return $this->_ajaxEditInPlace();
               break;
           
           case 'upload':
               return $this->_ajaxUpload();
               break;
           
           default:
               return $this->_ajaxCustomCall($sAjax);
       }
   }
   
   
    protected function _ajaxCustomCall($psAjax)
    {
        return true;
    }
    
   
    protected function _ajaxEditInPlace()
    {
        $nKey       = utils\Http::getParam('pk');
        $sName      = utils\Http::getParam('name');
        $sValue     = utils\Http::getParam('value');

        if ($nKey > 0 && in_array($sName, $this->model->getFieldNames()))
        {
            $this->model->save(array($sName => $sValue), $nKey);
        }
    }


    protected function _ajaxUpload()
    {
        return '';
    }
    
    
    
    public static function registerClass($poObject)
    {
        self::$oaClasses[] = $poObject;
    }
    
    
    
    
    
}