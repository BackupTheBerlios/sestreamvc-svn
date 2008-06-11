<?
/*-
 * Copyright (c) 2008 Pascal Vizeli <pvizeli@yahoo.de>
 * Copyright (c) 2008 Serge Ramseier
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

require_once("lib/core/CCache.php");
require_once("lib/core/CConfig.php");

require_once("lib/controller/CXmlController.php");

class CPlugInConfig extends CConfig {
  private $m_cConf;
  private $m_path;

  public  function __construct(CConfig &$config, $path)
  {
    $this->m_cConf = &$config;
    $this->m_path  = $path;
  }

  public function loadConfig($xmlFile)
  {
    $xmlFile  = $this->m_path + $xmlFile;

    /* init cache object for config file */
    $cCache = new CCache($this->m_cConf, $xmlFile); 

    /* if I can use the cache, I use it or I cache it! */
    if ($cCache->useCache == true) {
      $this->m_config = $cCache->getCache();
    }
    /**
     * Cache config */
    else {
      parent::loadConfig($xmlFile); 
      $cCache->setCache($this->m_config);
    }
  }
}

?>