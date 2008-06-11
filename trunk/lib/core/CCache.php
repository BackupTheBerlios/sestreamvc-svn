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

class CCache {
  private $m_file;
  private $m_cacheN;
  private $m_cConfig;
  private $m_confXml;
  private $m_seriaStr;
  private $m_strict;

  public  function __construct(CConfig &$config, $file)
  {
    /* set default value */
    $this->m_file     = $file;
    $this->m_cConfig  = &$config;
    $this->m_confXml  = &$config->getConfTree("cache");
    $this->m_strict   = $this->m_confXml["usestrict"]["xmlValue"];

    /* replace '/' with '_' */
    $fileCacheName  = preg_replace("/(\/|\.|\s)/", "_", $file);
    $cachePath      = $this->m_confXml["cachepath"]["xmlValue"];
    $this->m_cacheN = $cachePath . "/" . md5($fileCacheName);
  }

  public  function useCache()
  {
    /* if enable cache */
    if ($this->m_confXml["usecache"]["xmlValue"] == false) {
      return false;
    }

    if (file_exists($this->m_cacheN) == false or 
        file_exists($this->m_cacheN.".md5") == false) {
      return false;
    }

    if ($this->haveSameCheckSume() == false) {
      return false;
    }

    /* load cache */
    try {
      $this->loadCache();
    }
    catch (CError $error) {

      /* if you use cache in strict mode, trhow a exception */
      if ($this->m_strict == false) {
        return false;
      }
      else {
        throw $error;
      }
    }

    return true;
  }

  private function getMd5Cache()
  {
    $file = $this->m_cacheN.".md5";

    /* open file */
    if (($handl = fopen($file, "r")) == false and $this->m_strict == true) {
      throw new CError(ERROR_OPEN_CACHE, array($file));
    }

    $md5 = fread($handl, filesize($file));
    
    fclose($handl);
    return $md5;
  }

  private function setMd5Cache()
  {
    $file = $this->m_cacheN.".md5";

    /* open file */
    if (($handl = fopen($file, "w")) == false and $this->m_strict == true) {
      throw new CError(ERROR_OPEN_CACHE, array($file));
    }

    $md5 = md5_file($file);
    fwrite($handl, $md5);

    fclose($handl);
  }

  private function haveSameCheckSume()
  {
    $md5Org   = md5_file($this->m_file);
    $md5Cache = $this->getMd5Cache();

    if ($md5Org != $md5Cache) {
      return false;
    }

    return true;
  }

  private function loadCache()
  {
    $file = $this->m_cacheN;

    /* open file */
    if (($handl = fopen($file, "r")) == false and $this->m_strict == true) {
      throw new CError(ERROR_OPEN_CACHE, array($file));
    }

    /* load cache seria string */
    $this->m_seriaStr = fread($handl, filesize($file));
    if ($this->m_seriaStr == "" and $this->m_strict == true) {
      throw new CError(ERROR_INVALID_CACHE, array($file));
    }
    
    fclose($handl);
  }

  public function setCache($data)
  {
    $file = $this->m_cacheN;

    /* if enable cache */
    if ($this->m_confXml["usecache"]["xmlValue"] == false) {
      return;
    }

    /* open file */
    if (($handl = fopen($file, "w")) == false and $this->m_strict == true) {
      throw new CError(ERROR_OPEN_CACHE, array($file));
    }

    /* prepare and write data */
    $seria  = serialize($data);
    $anz    = fwrite($handl, $seria);

    /* if you use the strict mod, throw a exception if it can't wrote 
     * cache
     */
    if ($this->m_strict == true and $anz == false) {
      throw new CError(ERROR_WRITE_CACHE, array($file));
    }

    fclose($handl);
    $this->setMd5Cache();
  }

  public  function getCache()
  {
    return unserialize($this->m_seriaStr);
  }

}

?>
