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
require_once("lib/core/CConfig.php");
require_once("lib/core/CParam.php");
require_once("lib/core/CAccess.php");
require_once("lib/core/CSecure.php");

require_once("lib/controller/CPage.php");

/** Event ist ein MODEL */
define("EVENT_MODEL",                       "1");
/** Event ist eine Authentifikation */
define("EVENT_AUTH",                        "2");
/** Event ist ein Plugin */
define("EVENT_PLUGIN",                      "3");

class CEvent {
  private $m_cConfig;
  private $m_cCParam;
  private $m_cPage;
  private $m_cAccess;
  private $m_pageXml;
  private $m_confXml;
  private $m_eventCall;
  private $m_aktiveEvent;
  private $m_eventParam;

  public  function __construct(CConfig &$conf)
  {

    $this->m_cConfig      = &$conf;

    $this->m_activeEvent  = array();
    $this->m_eventParam   = array();
    $this->m_eventCall    = array(EVENT_AUTH   => array(), 
                                  EVENT_MODEL  => array(),
                                  EVENT_PLUGIN => array());
  }

  public  function initEvent(CParam &$param, CPage &$page, CAccess &$access)
  {
    $this->m_cParam       = &$param;
    $this->m_cPage        = &$page;
    $this->m_access       = &$access;
  }

  public  function loadEvent()
  {
    $this->m_pageXml  = &$this->m_cPage->getEvent();

    /* sind events definiert? */
    if (isset($this->m_pageXml) == false) {
      return;
    }

    /* erstelle die events */
    for ($i = 0; $i <= $this->m_pageXml["event"]["xmlMulti"]; ++$i) {
      $this->createEventData($this->m_pageXml["event"][$i]);
    }
  }

  private function createEventData(&$xmlEvent)
  {
    $eventName      = &$xmlEvent["xmlAttribute"]["name"];

    /* soll es validiert? */
    $paramValid     = NULL;
    if (array_key_exists("valid", $xmlEvent["xmlAttribute"]) == true) {
      $paramValid   = &$xmlEvent["xmlAttribute"]["valid"];
    }

    /* Hat der Benutzer die Berechtigungen? sonst abbruch */
    if (array_key_exists("access", $xmlEvent["xmlAttribute"]) == true) {
      $access      = &$xmlEvent["xmlAttribute"]["access"];
      
      if ($this->m_cAccess->haveAccessOnRule($access) == false) {
        return;
      }
    }

    /* speichere die werte um die aktivität festellen zu können. */
    $this->m_eventParam[$eventName]["valid"]   = &$paramValid;
    $this->m_eventParam[$eventName]["value"]   = &$xmlEvent["xmlValue"];

    /* Erstelle Register für die speicherung der callback funktionen,
     * für jedes flag */
    foreach ($this->m_eventCall as $flag => &$regi) {
      $this->m_eventCall[$flag][$eventName] = array();
    }
  }

  public  function addCallbackToEvent($event, $arrCallbackFunct, $arrParam,
      $flag)
  {
    /* if event is defined */
    if (array_key_exists($event, $this->m_eventCall[$flag])) {

      /* füge callback funktion zu hivent hinzu */
      $this->m_eventCall[$flag][$event][] = 
          array("callback" => $arrCallbackFunct,
                "param"    => $arrParam);
    }
    else {
      throw new CError(ERROR_EVENT_UNKNOW, array($event));
    }
  }

  public  function runEvent($flag)
  {
    /* existiren events? */
    if (count($this->m_eventCall) == 0) {
      return;
    }

    /* update m_activeEvent */
    $this->updateActiveEvent();

    /* durchlaufe alle events vom type flag */
    foreach ($this->m_eventCall[$flag] as $event => &$call) {
      /* ist event aktive? */  
      if (array_key_exists($event, $this->m_activeEvent) == true) {
        for($i = 0; $i < count($call); ++$i) {

          /* event wird aufgerufen */
          call_user_func_array($call[$i]["callback"], $call[$i]["param"]);
        }
      }
    }
  }

  private function updateActiveEvent()
  {
    foreach ($this->m_eventParam as $event => &$param) {
      try {
        $value    = $this->m_cParam->extractValue($param["value"]);

        /* soll es mit valid geprüft werden? */
        if (array_key_exists("valid", $param) == true) {
          CSecure::validData($value, $param["valid"]);
          $this->m_activeEvent[$event]  = true;
        }
        /*-
         * sonst, event ist aktive wenn wert nicht empty() */
        else {
          if (empty($value) == false) {
            $this->m_activeEvent[$event]  = true;
          } 
        }
      }
      catch(CError $error) {
        /* mache nichts, warscheindlich sind daten noch nicht abrufbar 
         * Zeige nur den ERROR_PARAM_INVALID, da dort wohl ein fehler ist,
         * und nie erfolgreich sein wird */
        if ($error->m_errCode == ERROR_PARAM_INVALID) {
          throw $error;
        }
      }
    }
  }

  public  function isActive($event)
  {
    if (array_key_exists($event, $this->m_eventParam) == false) {
      throw new CError(ERROR_EVENT_UNKNOW, array($event));
    }

    /* event nicht aktive */
    if ($this->m_activeEvent[$event] != true) {
      return false;
    }

    return true;
  }

}

?>
