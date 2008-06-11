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
require_once("lib/core/CConfig.php");
require_once("lib/core/CUser.php");
require_once("lib/core/CAccess.php");
require_once("lib/core/CCache.php");

require_once("lib/controller/CXmlController.php");

class CPage {
  private $m_cConfig;
  private $m_cUser;
  private $m_cAccess;
  private $m_pageXml;
  private $m_confXml;
  private $m_cPageUrl;
  private $m_fileName;

  public  function __construct(CConfig &$config)
  {
    $this->m_cConfig  = &$config;

  }

  public  function initPage(CUser &$user, CAccess &$access, 
                            CPageUrl &$url = NULL)
  {
    $this->m_cUser    = &$user;
    $this->m_cAccess  = &$access;
    $this->m_cPageUrl = &$url;

    /* init pageXml */
    $this->m_pageXml  = array();
  }

  public  function loadAutoPage()
  {
    $this->loadPage($this->m_cPageUrl->getPageXml());
  }

  private function loadPageXml($xmlFile)
  {
    /* init */
    $file   = CONTROLLER_PATH . $xmlFile;
    $this->m_fileName = $xmlFile;
    $cCache = new CCache($this->m_cConfig, $file);
    $retXml = array();

    /**
     * Can it use cache? */
    if ($cCache->useCache() == true) {
      $retXml   = $cCache->getCache();
    }
    /**
     * Read the xml file and cache it */
    else {
      $xmlData  = new CXmlController($file);
      $xmlData->setDefaultType("string-iso");

      $xmlData->startParser();
      $retXml   = $xmlData->getXmlData();

      /* cache */
      $cCache->setCache($retXml);
    }

    return $retXml;
  }

  public  function reLoadPage($xmlFile)
  {
    $this->loadPage($xmlFile);
  }

  public  function loadPage($xmlFile)
  {
    $pageXml        = $this->loadPageXml($xmlFile);

    /* page use a controller xml file for layout? */
    if (array_key_exists("layoutcontroller", $pageXml["header"]) == true) {
      $file         = &$pageXml["header"]["layoutcontroller"]["xmlValue"];
      $layoutXml    = $this->loadPage($file);

      $pageXml      = array_merge_recursive($pageXml, $layoutXml);
    }

    /* page xml are ready for use */
    $this->m_pageXml = $pageXml;
  }

  public  function checkAccess()
  {
    /* require a login for this page? */
    if (array_key_exists("requirelogin", $this->m_pageXml["header"]) == true) {
      /* check */
      if ($this->m_pageXml["header"]["requirelogin"]["xmlValue"] == true) {
        /* is user logon? */
        if ($this->cUser->isLogin() == false) {
          throw new CError(ERROR_PAGE_REQUIRELOGIN);
        }
      }
    }
    
    /* if page have a access restriction */
    if (array_key_exists("accesspage", $this->m_pageXml["header"]) == true) {
      $rule = &$this->m_pageXml["header"]["accesspage"]["xmlValue"];

      /* user don't have access on this page */
      if ($this->m_cAccess->haveAccessOnRule($rule) == false) {
        throw new CError(ERROR_PAGE_ACCESSDENY);
      }
    }
  }

  public  function getFileName()
  {
    return $this->m_fileName;
  }

  public  function &getTitle()
  {
    return $this->m_pageXml["header"]["title"]["xmlValue"];
  }

  public  function &getEvent()
  {
    return $this->getPageTree("eventcontroll");
  }

  public  function &getGet()
  {
    return $this->getPageTree("getcontroll");
  }

  public  function &getModel()
  {
    return $this->getPageTree("modelcontroll");
  }

  public  function &getForm()
  {
    return $this->getPageTree("formcontroll");
  }

  public  function &getSession()
  {
    return $this->getPageTree("sessioncontroll");
  }

  public  function &getAuthentification()
  {
    return $this->getPageTree("authentification");
  }

  public  function &getPlugin()
  {
    return $this->getPageTree("plugincontroll");
  }

  private function &getPageTree($name)
  {
    $name   =  strtolower($name);

    if (array_key_exists($name, $this->m_pageXml) == true) {
      return $this->m_pageXml[$name];
    }

    return NULL;
  }

  public  function &getView()
  {
    return $this->m_pageXml["header"]["view"];
  }

}

?>
