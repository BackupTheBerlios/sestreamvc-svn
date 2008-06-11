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

require_once("lib/core/CSecure.php");
require_once("lib/core/CError.php");
require_once("lib/core/CGlob.php");
require_once("lib/core/CConfig.php");
require_once("lib/core/CParam.php");

require_once("lib/controller/CPage.php");

class CGet {
  private $m_cGlob;
  private $m_cPage;
  private $m_cParam;
  private $m_cConfig;
  private $m_confXml;
  private $m_getXml;

  public  function __construct(CConfig &$conf)
  {
    $this->m_cConfig  = &$conf;
    $this->m_confXml  = &$conf->getConfTree("getconfig");
  }

  public  function initGet(CGlob &$glob, CPage &$page, CParam &$param)
  {
    $this->m_cGlob    = &$glob;
    $this->m_cPage    = &$page;
    $this->m_cParam   = &$param;
  }

  public  function loadGet()
  {
    /* get page GET data */
    $this->m_getXml = &$this->m_cPage->getGet();

    if (isset($this->m_getXml) == false) {
      return;
    }

    if (array_key_exists("get", $this->m_getXml) == false) {
      return;
    }

    for ($i = 0; $i <= $this->m_getXml["get"]["xmlMulti"]; ++$i) {
      /* insert element to glob */ 
      $this->addNodeToGlob($this->m_getXml["get"][$i]);
    }
  }

  public  function loadPageId()
  {
    $this->addNodeToGlob($this->m_cConfig->m_config["pageurl"]["pageidget"]);
  }

  private function addNodeToGlob(&$getArr)
  {
    $getName  = &$getArr["xmlValue"];
    $getValue = &$_GET[$getName];

    if (isset($getValue) == false) {
      return;
    }

    /*-
     * Valid / Filter / Guard -> check -> add */
    try {
      /*-
       * check Valid */
      if (array_key_exists("valid", $getArr["xmlAttribute"]) == true) {
        $setValid = &$getArr["xmlAttribute"]["valid"];
        CSecure::validData($getValue, $setValid);
      }

      /*-
       * filter value */
      if (array_key_exists("filter", $getArr["xmlAttribute"]) == true) {
        $filter    = &$getArr["xmlAttribute"]["filter"];

        CSecure::filterData($getValue, $filter);
      }
      
      /*-
       * Guard */
      if (strtolower($getArr["xmlAttribute"]["guard"]) != "off") {
        CSecure::guard($getValue);
      }

      /*-
       * If you use a key for protect the value */
      $key    = $this->getKey($getArr["xmlAttribute"]);

      /**
       * add to Glob */
      $this->m_cGlob->setGet($getName, $getValue, $key);

    }
    catch (CError $error) {
      if ($this->confXml["usestrict"]["xmlValue"] == true) {
        throw new CError(ERROR_GET_VALID, array($getName, 
                          $error->getMessage()));
      }
    }
  }

  private function getKey(&$xmlAttribute)
  {
    /*-
     * If you use a key for protect the value */
    $key    = "";
    if (@array_key_exists("key", $xmlAttribute) == true) {
      $key  = $xmlAttribute["key"];

      /* if key is set put without value, use syskey! */
      if (empty($key) == true) {
        $key  = $this->m_cConfig->m_config["systemkey"]["xmlValue"];
      }
    }

    return $key;
  }

  public  function setPageGet()
  {
    if (isset($this->m_getXml) == false) {
      return;
    }

    if (array_key_exists("setget", $this->m_getXml) == false) {
      return;
    }

    for ($i = 0; $i <= $this->m_getXml["setget"]["xmlMulti"]; ++$i) {
      $key      = $this->getKey($this->m_getXml["setget"][$i]["xmlAttribute"]);
      $getName  = &$this->m_getXml["setget"][$i]["xmlAttribute"]["name"];
      $value    = $this->m_cParam->extractValue(
        $this->m_getXml["setget"][$i]["xmlValue"]);

      /* if filter is set, use it! */
      if (array_key_exists("filter", 
        $this->m_getXml["setget"][$i]["xmlAttribute"]) == true) {

        $filter = &$this->m_getXml["setget"][$i]["xmlAttribute"]["filter"];
        CSecure::filterData($value, $filter);
      }

      /* set Get to glob */
      $this->m_cGlob->setGet($getName, $value, $key);
    }
  }
}

?>
