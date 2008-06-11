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

require_once("lib/core/CGlob.php");
require_once("lib/core/CError.php");
require_once("lib/core/CConfig.php");
require_once("lib/core/CAccess.php");
require_once("lib/core/CUser.php");
require_once("lib/core/CSecure.php");

require_once("lib/controller/CPage.php");
require_once("lib/controller/CPageUrl.php");

class CLink {
  private $m_cConfig;
  private $m_cGlob;
  private $m_cUser;
  private $m_cAccess;
  private $m_cPageUrl;
  private $m_confXml;

  public  function __construct(CConfig &$config) {

    $this->m_cConfig      = &$config;
    $this->m_confXml      = &$config->getConfTree("pageurl");
  }

  public  function initLink(CGlob &$glob, CUser &$user,
                            CAccess &$access, CPageUrl &$url) {
    $this->m_cUser        = &$user;
    $this->m_cAccess      = &$access;
    $this->m_cGlob        = &$glob;
    $this->m_cPageUrl     = &$url;
  }

  public  function getLink($url, $extends = NULL)
  {
    $link     = $this->m_confXml["pageindex"]["xmlValue"];

    /* hänge page erkennungs get an */
    $link    .= "?" . $this->m_confXml["pageidget"]["xmlValue"] . "=";

    /* hänge page name an, zuerst aber noch encoden */
    $urlEn    = $url;
    CSecure::filterData($urlEn, "encoderawurl");
    $link    .= $urlEn;

    /* lade die verlinkte Page */
    $page     = new CPage($this->m_cConfig);
    $page->initPage($this->m_cUser, $this->m_cAccess);
    $pageXml  = $this->m_cPageUrl->getPageXmlFromUrl($url);
    $page->loadPage($pageXml);

    /* gebe liste von allen get werten welche auf dieser seite benötigt 
     * werden */
    $getContr = &$page->getGet();

    /* durchlaufe die get liste der nächsten seite und hänge die 
     * pare/werte an */
    for ($i = 0; $i <= $getContr["get"]["xmlMulti"]; ++$i) {
      
      $getName  = &$getContr["get"][$i]["xmlValue"];
      $val      = $this->m_cGlob->getGet($getName);

      /* wenn wert nicht leer, anhängen */
      if (isset($val) == true) {
        $link  .= "&" . $getName . "=" . $val;
      }
    }

    /* zusätze anfügen */
    if (empty($extends) == false) {
      $link    .= "&" . $extends;
    }

    return $link;
  }

}

?>
