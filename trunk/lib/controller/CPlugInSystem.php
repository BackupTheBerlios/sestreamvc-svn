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

require_once("lib/core/Path.php");

require_once("lib/core/CError.php");
require_once("lib/core/CParam.php");
require_once("lib/core/CConfig.php");
require_once("lib/core/CAccess.php");

require_once("lib/controller/CPlugInConfig.php");
require_once("lib/controller/CPlugInFunctions.php");
require_once("lib/controller/CEvent.php");
require_once("lib/controller/CPage.php");

class CPlugInSystem {
  private $m_cConfig;
  private $m_cCParam;
  private $m_cPage;
  private $m_cAccess;
  private $m_cPlugFunct;
  private $m_pageXml;
  private $m_confXml;
  private $m_eventSav;
  private $m_plugInQue;
  private $m_priorityQue;
  private $m_eventPrioQue;

  public  function __construct(CConfig &$config) {

    $this->m_cConfig      = &$config;
    $this->m_confXml      = &$config->getConfTree("pluginconfig");
    $this->m_eventSav     = array();
  }

  public  function initPlugin(CParam &$param, CPage &$page, CAccess &$access,
                              CPlugInFunctions &$plugFunct) {

    $this->m_cParam       = &$param;
    $this->m_cPage        = &$page;
    $this->m_cAccess      = &$access;
    $this->m_cPluginFunct = &$plugFunct;
  }

  public  function loadPlugIn()
  {
    /* lade plugin liste von page */
    $this->m_pageXml      = &$this->m_cPage->getPlugin();

    /* keine Plugins gesetzt */
    if (isset($this->m_pageXml) == false) {
      return;
    }

    /* durchelaufe alle plugins und erstelle diese */
    for ($i = 0; $i <= $this->m_pageXml["plugin"]["xmlMulti"]; ++$i) {
      $this->addPlugIn($this->m_pageXml["plugin"][$i]);
    }

    /* erstelle Priority Ques und Event Ques */
    $this->createPriorityQue();
  }

  private function addPlugIn(&$plugXml)
  {
    $name       = &$plugXml["xmlAttribute"]["name"];
    $obj        = preg_split("/::/", $plugXml["xmlAttribute"]["object"]);

    /*-
     * braucht man berechtigungen? */
    if (array_key_exists("access", $plugXml["xmlAttribute"]) == true) {
      $rule     = &$plugXml["xmlAttribute"]["access"];

      /* user hat berechtigungen */
      if ($this->m_cAccess->haveAccessOnRule($rule) == false) {
        return;
      }
    }

    /* erstelle priorität */
    $prio     = 0;
    if (array_key_exists("priority", $plugXml["xmlAttribute"]) == true) {
      $prio   = &$plugXml["xmlAttribute"]["priority"];
    }

    /* hole event */
    $event    = NULL;
    if (array_key_exists("event", $plugXml["xmlAttribute"]) == true) {
      $event  = &$plugXml["xmlAttribute"]["event"];
    }

    /* erstelle pfad und include */
    $pfad                                = PLUGIN_PATH . $obj[0] . "/";
    $this->m_plugInQue[$name]["include"] = $pfad . $obj[1] . ".php";
    $this->m_plugInQue[$name]["pfad"]    = $pfad;
    $this->m_plugInQue[$name]["object"]  = $obj[1];
    $this->m_plugInQue[$name]["event"]   = $event;
    $this->m_plugInQue[$name]["prio"]    = $prio;
    $this->m_plugInQUe[$name]["param"]   = $plugXml["param"];
  }

  private function createPriorityQue()
  {
    $maxPrio  = &$this->confXml["maximalpriority"]["xmlValue"];

    /* Erstelle default prioQue */
    for ($i = 0; $i <= $maxPrio; ++$i) {
      $this->m_priorityQue[$i] = array();
    }

    /* setze prio que als default, wird für den even PrioQue benutzt */
    $defaultEvn = $this->m_priorityQue;

    /*-
     * Add alle pluginQue in den Priority und event que in der richtigen 
     * reihenfolge! */
    foreach ($this->m_plugInQue as $name => &$plugArr) {
      $prio   = $plugArr["prio"];

      /*-
       * Es ist Event gesteuert (Asynchron)
       *
       * setze es in den event prio que */
      if (isset($plugArr["event"]) == true) {
        $eventName = $plugArr["event"];

        /* erstelle neue prio structur wenn event noch keine hat. */
        if (array_key_exists($eventName, $this->m_eventPrioQue) == false) {
          $this->m_eventPrioQue[$eventName]        = $defaultEnv;
        }

        $this->m_eventPrioQue[$eventName][$prio][] = &$plugArr;

        if ($this->m_eventSav[$eventName] != true) {
          /* add collback to CEvent */
          $this->m_cEvent->addCallbackToEvent($eventName,
                                              array($this, "runEvent"),
                                              array($eventName), 
                                              EVENT_PLUGIN);

          /* setze event als benutzt */
          $this->m_eventSav[$eventName]         = true;
        }
      }
      /*-
       * Es wird Synchron ablaufen, add in priorityQue */
      else {
        $this->m_priorityQue[$prio][]           = &$plugArr;
      }
    }
  }


  public  function runEvent($name)
  {
    $this->runPluginQue($this->m_eventPrioQue[$name]);
  }

  public  function runStandardPlugin()
  {
    $this->runPluginQue($this->m_priorityQue);
  }

  private function runPluginQue(&$que)
  {
    $maxPrio      = &$this->confXml["maximalpriority"]["xmlValue"];

    /* durchlaufe den Que */
    for ($i = 1; $i <= $maxPrio; ++$i) {

      /* retrieve alle ModelSet */ 
      foreach ($que[$i] as &$plugArr) {
        $this->runPlugIn($plugArr);
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

  private function runPlugIn(&$plugArr)
  {
    require_once($plugArr["inlcude"]);

    $plugInConf = new CPlugInConfig($this->m_cConfig, $plugArr["path"]);
    $plugIn     = new $plugArr["object"]($plugInConf, $this->m_cPlugFunct);

    $plugIn->run($plugArr["param"]);
  }

}
?>
