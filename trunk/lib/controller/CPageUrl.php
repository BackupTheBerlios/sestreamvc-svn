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
require_once("lib/core/CCache.php");
require_once("lib/core/CGlob.php");

require_once("lib/controller/CXmlController.php");

class CPageUrl {
  private $m_cConfig;
  private $m_cGlob;
  private $m_confXml;
  private $m_urlXml;
  private $m_xmlFile;

  public  function __construct(CConfig &$config)
  {
    $this->m_cConfig    = &$config;
    $this->m_urlXml     = array();

    $this->m_confXml    = &$config->getConfTree("pageurl");
    $this->m_xmlFile    = URL_XML_FILE;
  }

  public  function initUrl(CGlob &$glob)
  {
    $this->m_cGlob      = &$glob;
  }

  public  function loadUrl()
  {
    /* init cache object */
    $cCache = new CCache($this->m_cConfig, $this->m_xmlFile); 

    /* if it can use the cache, I use it or I cache it! */
    if ($cCache->useCache == true) {
      $urlX   = $cCache->getCache();
    }
    /**
     * Cache config */
    else {
      $xmlData = new CXmlController($this->m_xmlFile);
      $xmlData->setDefaultType("string-utf8");

      $xmlData->startParser();
      $urlX    = $xmlData->getXmlData();

      /* cache it for next one */
      $cCache->setCache($urlX);
    }

    /* erstelle m_urlXml */
    $this->assocUrl($urlX);
  }

  private function assocUrl(&$arr)
  {
    for ($i = 0; $i <= $arr["url"]["xmlMulti"]; ++$i) {

      $name                   = &$arr["url"][$i]["xmlValue"];
      $xml                    = &$arr["url"][$i]["xmlAttribute"]["xmlfile"];
      $this->m_urlXml[$name]  = $xml;
    } 
  }

  public  function getPageXmlFromUrl($url)
  {
    if (array_key_exists($url, $this->m_urlXml) == false) {
      throw new CError(ERROR_URL_NO_XML, array($url));
    }

    return $this->m_urlXml[$url];
  }

  public  function getPageXml()
  {
    $getName      = &$this->m_confXml["pageidget"]["xmlValue"];
    $getIdValue   = $this->m_cGlob->getGet($getName);

    /* wenn getValue nicht gesetzt setze startseite aus config */
    if (isset($getIdValue) == false) {
      $getIdValue   = &$this->m_confXml["pagestart"]["xmlValue"];
    }

    /* wenn es seite nicht gibt */
    if (array_key_exists($getIdValue, $this->m_urlXml) == false) {
      throw new CError(ERROR_URL_NOT_FOUND, array($getIdValue));
    }

    /* gebe xml back */
    return $this->getPageXmlFromUrl($getIdValue);
  }

}

?>
