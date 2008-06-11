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
require_once("lib/core/CConfig.php");

require_once("lib/controller/CXmlController.php");

class CMessage {
  private $m_fileName;
  private $m_xmlFile;
  private $m_messXml;
  private $m_cConfig;

  public  function __construct(CConfig &$config, $file) {
    /* set default */
    $this->m_cConfig  = &$config;
    $this->m_fileName = $file;
    $this->m_xmlFile  = MESSAGE_PATH . $this->m_fileName;
  }

  public  function loadMessage()
  {
    /* init cache object */
    $cCache = new CCache($this->m_cConfig, $this->m_xmlFile); 

    /* if it can use the cache, I use it or I cache it! */
    if ($cCache->useCache == true) {
      $this->m_messXml = $cCache->getCache();
    }
    /**
     * Cache config */
    else {
      $xmlData = new CXmlController($this->m_xmlFile);
      $xmlData->setDefaultType("string-utf8");

      $xmlData->startParser();
      $this->m_messXml = $xmlData->getXmlData();

      /* cache it for next one */
      $cCache->setCache($this->m_messXml);
    }
  }

  public  function getMessage($status)
  {
    $status   = strolower($status);

    if (array_key_exists($status, $this->m_messXml) == true) {
      return $this->m_messXml[$status]["de"]["xmlValue"];
    }
  }

  public  function &getMessageData()
  {
     return $this->m_messXml;
  }

}

?>
