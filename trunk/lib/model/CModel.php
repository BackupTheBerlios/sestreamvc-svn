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

require_once("lib/core/CError.php");
require_once("lib/core/CParam.php");
require_once("lib/core/CConfig.php");
require_once("lib/core/CAccess.php");

require_once("lib/controller/CEvent.php");
require_once("lib/controller/CPage.php");

require_once("lib/model/CModelSet.php");
require_once("lib/model/CDatabase.php");

class CModel {
  private $m_cPage;
  private $m_cConfig;
  private $m_cParam;
  private $m_cDatabase;
  private $m_cAccess;
  private $m_cEvent;
  private $m_confXml;
  private $m_pageXml;
  private $m_arrModSet;
  private $m_priorityQue;
  private $m_eventPrioQue;
  private $m_eventSav;

  public  function __construct(CConfig &$config) 
  {
    /* init default */
    $this->m_cConfig      = &$config;

    $this->m_confXml      = &$config->getConfTree("modelconfig");
    $this->m_arrModSet    = array();
    $this->m_priorityQue  = array();
    $this->m_eventPrioQue = array();
    $this->m_eventSav     = array();
  }

  public  function initModel(CPage &$page, CDatabase &$db, 
                             CEvent &$event, CAccess &$access, CParam &$param) 
  {
    /* init */
    $this->m_cPage        = &$page;
    $this->m_cParam       = &$param;
    $this->m_cDatabase    = &$db;
    $this->m_cAccess      = &$access;
    $this->m_cEvent       = &$event;
  }

  public  function reLoadModel()
  {
    $this->m_arrModSet    = array();
    $this->m_priorityQue  = array();
    $this->m_eventPrioQue = array();
    $this->m_eventSav     = array();

    $this->loadModel();
  }

  public  function loadModel()
  {
    /* lade date von Page */
    $this->m_pageXml  = &$this->m_cPage->getModel();

    /* gibt es modele auf der page? */
    if (isset($this->m_pageXml) == false) {
      return;
    }

    /*-
     * create for all <model> a CModelSet. Style:
     * arr[ModelName] = CModelSet() */
    for ($i = 0; $i <= $this->m_pageXml["model"]["xmlMulti"]; ++$i) {
      $cModSet      = new CModelSet($this->m_pageXml["model"][$i]);
      $cModSet->initModelSet($this->m_cConfig, $this->m_cParam, 
                             $this->m_cDatabase);

      /* check access on model, add only if it have access */
      if ($cModSet->useAccess() == true) {
        $zone = $cModSet->getAccessZone();
        if ($this->m_cAccess->haveAccessOnRule($zone) == false) {
          continue;
        }
      }

      /*-ACHUTNG
       * Hier keine referenz uebergeben. der Adressraum wird ueberschrieben!  */
      $this->m_arrModSet[$cModSet->getModelName()]  = $cModSet; 
    }

    /* create PriorityQue and sort ModSets */
    $this->createPriorityQue();
  }

  private function createPriorityQue()
  {
    $maxPrio  = &$this->m_confXml["maximalpriority"]["xmlValue"];

    /* Erstelle default prioQue */
    for ($i = 0; $i <= $maxPrio; ++$i) {
      $this->m_priorityQue[$i] = array();
    }

    /* setze prio que als default, wird für den even PrioQue benutzt */
    $defaultEvn = $this->m_priorityQue;

    /*-
     * Add alle arrModSet in den Priority und event que in der richtigen 
     * reihenfolge! */
    foreach ($this->m_arrModSet as $name => &$cModSet) {
      $prio   = $cModSet->getPriority();

      /*-
       * Es ist Event gesteuert (Asynchron)
       *
       * setze es in den event prio que */
      if ($cModSet->useEvent() == true) {
        $eventName = $cModSet->getEvent();

        /* erstelle neue prio structur wenn event noch keine hat. */
        if (array_key_exists($eventName, $this->m_eventPrioQue) == false) {
          $this->m_eventPrioQue[$eventName]          = $defaultEnv;
        }

        $this->m_eventPrioQue[$eventName][$prio][] = &$cModSet;

        if ($this->m_eventSav[$eventName] != true) {
        /* add collback to CEvent */
        $this->m_cEvent->addCallbackToEvent($eventName, 
                                            array(&$this, "runEvent"),
                                            array($eventName), 
                                            EVENT_MODEL);

          /* setze event als benutzt */
          $this->m_eventSav[$eventName]         = true;
        }
      }
      /*-
       * Es wird Synchron ablaufen, add in priorityQue */
      else {
        $this->m_priorityQue[$prio][] = &$cModSet;
      }
    }
  }

  public  function retrieveDataFromModelSet(&$cModSet)
  {
    /* if it use access restriction on modelSet */
    if ($cModSet->useAccess() == true) {
      $zone   = $cModSet->getAccessZone();
      $name   = $cModSet->getModelName();

      /* check access */
      if ($this->m_cAccess->haveAccessOnRule($zone) == false) {
        throw new CError(ERROR_MODEL_ACCESS, array($name, $zone));
      }
    }

    $cModSet->initModelSet($this->m_cConfig, $this->m_cParam, 
                           $this->m_cDatabase);
    $cModSet->retrieveData();
  }

  public  function addModelSet(CModelSet &$modelSet, $modelName)
  {
    /* adde nur, wenn modelName noch frei ist. nicht überschreiben! */
    if (array_key_exists($modelName, $this->m_arrModSet) == false) {
      $this->m_arrModSet[$modelName]  = &$modelSet;
    }
    else {
      throw new CError(ERROR_MODEL_EXISTS, array($modelName));
    }
  }

  public  function retrieveStandardModelSet()
  {
    $this->retrieveQue($this->m_priorityQue);
  }

  public  function runEvent($eventName)
  {
    if (array_key_exists($eventName, $this->m_eventPrioQue) == true) {
      $this->retrieveQue($this->m_eventPrioQue[$eventName]);
    }
    else {
      throw new CError(ERROR_MODEL_EVENT, $eventName);
    }
  }

  private function retrieveQue(&$que)
  {
    $maxPrio  = &$this->m_confXml["maximalpriority"]["xmlValue"];

    /* durchlaufe den Que */
    for ($i = 1; $i <= $maxPrio; ++$i) {

      /* retrieve alle ModelSet */ 
      for ($j = 0; $j < count($que[$i]); ++$j) {
        $que[$i][$j]->retrieveData($this->m_cDatabase);
      }

      /* Wenn i = 0 dann ist fertig */
      if ($i == 0) {
        break;
      }

      /* setze am ende auf 0 um auch die ModSet ohne prioritäten zu 
        * bearbeiten */
      if ($i == $maxPrio) {
        $i = -1;
        continue;
      }
    }
  }

  private function existsModelSet(&$modelName)
  {
    if (array_key_exists($modelName, $this->m_arrModSet) == false) {
      throw new CError(ERROR_MODEL_NAME, array($modelName));
    }
  }

  public  function getValue($modelName, $field) {
    $this->existsModelSet($modelName);

    return $this->m_arrModSet[$modelName]->getValue($field);
  }

  public  function countRow($modelName) {
    $this->existsModelSet($modelName);

    return $this->m_arrModSet[$modelName]->countRow();
  }

  public  function nextIter($modelName) {
    $this->existsModelSet($modelName);

    return $this->m_arrModSet[$modelName]->nextIter();
  }

  public  function resetIter($modelName) {
    $this->existsModelSet($modelName);

    return $this->m_arrModSet[$modelName]->resetIter();
  }

}

?>
