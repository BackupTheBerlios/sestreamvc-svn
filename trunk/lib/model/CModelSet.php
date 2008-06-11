<?
/*-
 * Copyright (c) 2008 Pascal Vizeli <pvizeli@yahoo.de>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR AND CONTRIBUTORS ``AS IS'' AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED.  IN NO EVENT SHALL THE AUTHOR OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS
 * OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
 * OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF
 * SUCH DAMAGE.
 */

require_once("lib/core/CParam.php");
require_once("lib/core/CCache.php");
require_once("lib/core/CConfig.php");
require_once("lib/core/CError.php");

require_once("lib/model/CXmlModel.php");
require_once("lib/model/CDatabase.php");

/**
 * Represent a Model
 *
 */
class CModelSet {
  private $m_modelConf;
  private $m_xmlSql;
  private $m_modelFile;
  private $m_modelName;
  private $m_cParam;
  private $m_cConfig;
  private $m_cDatabase;
  private $m_isInit;
  private $m_event;
  private $m_access;
  private $m_priority;
  private $m_outPut;
  private $m_statment;
  private $m_isTable;
  private $m_realName;
  private $m_dbData;
  private $m_iter;
  private $m_rowCount;

  public  function __construct(&$xmlModelConf = NULL)
  {
    if (isset($xmlModelConf) == true) {
      $this->setModelConf($xmlModelConf);
    }
  }

  public  function setModelConf(&$xmlModelConf)
  {
    $this->m_modelConf = &$xmlModelConf;

    /* init default var */
    $this->m_modelName  = &$this->m_modelConf["xmlAttribute"]["name"];
    $this->m_modelFile  = &$this->m_modelConf["xmlAttribute"]["xmlfile"];

    /* if access */
    if (array_key_exists("access", $this->m_modelConf["xmlAttribute"]) 
        == true) {
      $this->m_access = &$this->m_modelConf["xmlAttribute"]["access"];
    }

    /* use Event */
    $this->m_event   = NULL;
    if (array_key_exists("event", $this->m_modelConf["xmlAttribute"]) == true) {
      $this->m_event = &$this->m_modelConf["xmlAttribute"]["event"];
    }

    /* use priority */
    if (array_key_exists("priority", $this->m_modelConf["xmlAttribute"]) 
        == true) {
      $this->m_priority  = &$this->m_modelConf["xmlAttribute"]["priority"];
    }
    /* default priority is 0 */
    else {
      $this->m_priority  = 0;
    }

    /* use output filters */
    $this->m_outPut     = NULL;
    if (array_key_exists("output", $this->m_modelConf) == true) {
      $this->convertOutPut($this->m_modelConf["output"]);
    }

    $this->m_isInit     = false;
  }

  private function convertOutPut(&$arrOutPut)
  {
    for ($i = 0; $i <= $arrOutPut["xmlMulti"]; ++$i) {
      $name = &$arrOutPut[$i]["xmlValue"];
      $filt = &$arrOutPut[$i]["xmlAttribute"]["filter"];

      $this->m_outPut[$name] = &$filt;
    }
  }

  public  function initModelSet(CConfig &$config, CParam &$param, 
                                CDatabase &$db)
  {
    $this->m_cConfig    = &$config;
    $this->m_cParam     = &$param;
    $this->m_cDatabase  = &$db;

    /* load modelSql from xml file */
    $this->loadModelXml();

    /* set init */
    $this->m_isInit   = true;
  }

  public  function retrieveData()
  {
    /* if it isn't init jet, throw a exceptions */
    if ($this->m_isInit == false) {
      throw CError(ERROR_MODELSET_INI, array($this->m_modelName));
    }

    /* create sql code and retrieve Data */
    $statment       = $this->m_cDatabase->createStatment($this->m_xmlSql);

    /*-
     * replace placeholder in the sql statment with params */
     if (array_key_exists("param", $this->m_modelConf) == true) {
       $this->m_cParam->replaceWithParams($this->m_modelConf["param"], 
                                          $statment, 
                                          true);
     }

    /* retrieve daten von der datenbank */
    $this->m_dbData = $this->m_cDatabase->retrieveData($statment, 
                                                       $this->m_isTable);

    if ($this->m_xmlSql["xmlBasicSql"] == "SELECT") {
      
      if ($this->m_dbData != false) {
        $this->useFilter();

        /* setze iterator zurück und init rowCount mit anz zeilen */
        if ($this->m_isTable == true) {
          $this->m_rowCount  = count($this->m_dbData);
          $this->resetIter();
        }
        else {
          $this->m_rowCount  = 1; 
        }
      }
      else {
        $this->m_rowCount    = 0;
      }
    }
  }

  private function useFilter()
  {
    if ($this->m_isTable == true) {
      for ($i = 0; $i < count($this->m_dbData); ++$i) {
        $this->useFilterOnRow($this->m_dbData[$i]);
      }
    }
    else {
      $this->useFilterOnRow($this->m_dbData);
    }
  }

  private function useFilterOnRow(&$arrRow)
  {
    /* use filter over all fields */
    foreach ($arrRow as $key => &$value) {
      /* basics: decode sql inject code */
      CSecure::decodeSqlInject($value);

      /* if it set a filter for this field */
      if (isset($this->m_outPut) == true and 
          array_key_exists($key, $this->m_outPut)) {
        CSecure::filterData($value, $this->m_outPut[$key]);
      }
    }
  }

  private function loadModelXml()
  {
    $xmlFile  = MODEL_PATH . $this->m_modelFile;
    $cCache   = new CCache($this->m_cConfig, $xmlFile);

    if (file_exists($xmlFile) == false) {
      throw new CError(ERROR_MODELSET_MODEL_FILE, array($this->m_modelName, 
                                                       $xmlFile));
    }

    /*-
     * Can it use cache? */
    if ($cCache->useCache() == true) {
      $xmlModels      = $cCache->getCache();

      /* push the modelName from the Model list */
      $this->m_xmlSql = $xmlModels[$this->m_modelName];
    }
    /*-
     * Read the xml file and cache it */
    else {
      $xmlData        = new CXmlModel($xmlFile);

      $xmlData->startParser();
      $xmlModels      = $xmlData->getXmlData();
      
      if (array_key_exists($this->m_modelName, $xmlModels) == false) {
        throw new CError(ERROR_MODELSET_NOT_FOUND, array($this->m_modelName,
                                                         $xmlFile));
      }
      /* set xmlSql from model with modelName */
      $this->m_xmlSql = $xmlModels[$this->m_modelName];

      /* cache */
      $cCache->setCache($xmlModels);
    }

    /* init default data */
    $this->m_isTable    = &$this->m_xmlSql["xmlModelTable"];
    $this->m_realName   = &$this->m_xmlSql["xmlModelName"];
  }

  public  function useAccess()
  {
    if (empty($this->m_access)) {
      return false;
    }

    return true;
  }

  public  function getAccesZone()
  {
    return $this->m_access;
  }

  public  function useEvent()
  {
    if(isset($this->m_event) == false) {
      return false;
    } 

    return true;
  }

  public  function getEvent()
  {
    return $this->m_event;
  }

  public  function getPriority()
  {
    return $this->m_priority;
  }

  public  function getData()
  {
    return  $this->m_dbData;
  }

  public  function getModelName()
  {
    return $this->m_modelName;
  }

  public  function nextIter()
  {
    /* ist iter NULL, gebe error */
    if (isset($this->m_iter) == false) {
      throw new CError(ERROR_MODELSET_ITER, array($this->modelName));
    }

    /* setze iter auf nächstee zeile */
    ++$this->m_iter;

    /* Ist am ende der Tabelle */
    if ($this->m_rowCount >= $this->m_iter) {
      return false;
    }

    return true;
  }

  public  function resetIter()
  {
    if ($this->m_rowCount > 0) {
      $this->m_iter = 0;
    }
    /* es hat keine Zeilen, setzte iter auf NULL */
    else {
      $this->m_iter   = NULL;
    }
  }

  public  function isTables()
  {
    return $this->m_isTable;
  }

  private function existsField(&$arrData, $field)
  {
    if (array_key_exists($field, $arrData) == false) {
      throw new CError(ERROR_MODELSET_FIELD, array($this->m_modelName, $field));
    }
  }

  /**
   * Gibt die Anzhal der Zeilen zurück.
   *
   * @return                      Die anzhal Zeilen oder NULL wenn es 
   *                              keine Tabele ist.
   */
  public  function  countRow()
  {
    return $this->m_rowCount;
  }

  /**
   * Gibt den Wert eines Feldes.
   * Falls es sich um eine Tabelle handelt, gabe den  Wert des Feldes 
   * von der Aktuellen Position dess Iters wieder.
   *
   * @param $fieldName            Name des Feldes
   * @return                      Wert des Feldes
   */
  public  function getValue($fieldName)
  {
    $fieldName = strtolower($fieldName);

    /* wenn table, gebe vert von position des iterators */
    if ($this->m_isTable == true) {
      /* test iter, ob init */
      if (isset($this->m_iter) == false) {
        throw new CError(ERROR_MODELSET_ITER, array($this->m_modelName));
      }
      $this->existsField($this->m_dbData[$this->m_iter], $fieldName);

      return $this->m_dbData[$this->m_iter][$fieldName];
    }
    /*-
     * Sonst gebe feldwert */
    else {
      $this->existsField($this->m_dbData, $fieldName);
      return $this->m_dbData[$fieldName];
    }
  }
}

?>
