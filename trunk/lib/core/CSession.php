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
require_once("lib/core/CSecure.php");
require_once("lib/core/CParam.php");

require_once("lib/controller/CPage.php");


class CSession {
  private $m_cConfig;
  private $m_confXml;
  private $m_sessData;
  private $m_sessId;
  private $m_sessFile;
  private $m_sysKey;
  private $m_cPage;
  private $m_cParam;

  public  function __construct(CConfig &$config)
  {
    /* init defaults */
    $this->m_cConfig  = &$config;
    $this->m_confXml  = &$config->getConfTree("session");
    $this->m_sessData = array();

    /* add first part (path) of m_sessFile */
    $this->m_sessFile = $this->m_confXml["sessionpath"]["xmlValue"] . "/";

    /* add SystemKey */
    $this->m_sysKey   = &$this->m_cConfig->m_config["systemkey"]["xmlValue"];
  }

  public  function initSession(CPage &$page, CParam &$param)
  {
    $this->m_cPage    = &$page;
    $this->m_cParam   = &$param;
  }

  public  function loadSession()
  {
    $cooName    = &$this->m_confXml["cookname"]["xmlValue"];

    /*-
     * exists a Coookie with session? None, create a new session */
    if (array_key_exists($cooName, $_COOKIE) == false) {

      /* get new session id */
      $this->createNewSessionId();

      /* set default user id with SysKey */
      $this->setSessionValue("userid", 
        $this->m_confXml["defaultuserid"]["xmlValue"],
        $this->m_sysKey);

      /* set cookie */
      if (setcookie($cooName,
          $this->m_sessId,
          $this->m_confXml["sessionvalid"]["xmlValue"] * 60 * 60 + time(),
          $this->m_confXml["path"]["xmlValue"],
          $this->m_confXml["domain"]["xmlValue"],
          $this->m_confXml["secureonly"]["xmlValue"],
          true) == false) {
        throw new CError(ERROR_SET_COOKIE);
      }
    }
    /*-
     * init current session */
    else {
      $exp  = $this->m_confXml["sessionvalid"]["xmlValue"] * 60 * 60;

      /* secure test of valid sessid */
      try {
        CSecure::validData($_COOKIE[$cooName], "word");
      }
      catch (CError $error) {
        throw new CError(ERROR_INVALID_SESSION, array($_SERVER["REMOTE_ADDR"]));
      }

      /* set default values */
      $this->m_sessId    = $_COOKIE[$cooName];
      $this->m_sessFile .= $this->m_sessId;

      /* if server session expire, invalid cookie! */
      if (filectime($this->m_sessFile) + $exp <= time()) {
        unlink($this->m_sessFile);
        throw new CError(ERROR_INVALID_SESSION, array($_SERVER["REMOTE_ADDR"]));
      }

      /* load data */
      $this->loadSessionData();
    }
  }

  private function createNewSessionId()
  {
    /* values for sessId */
    $rand = mt_srand();
    $req  = $_SERVER["REQUEST_TIME"];
    $ip   = $_SERVER["REMOTE_ADDR"];
    $arg  = $_SERVER["argc"];

    /* generate sessId and set default values */
    $this->m_sessId = md5($rand . $req . $ip . $arg);
    $sessF          = $this->m_confXml["sessionpath"]["xmlValue"] . "/" . 
                      $this->m_sessId;
    $this->m_sessFile = $sessF;

    /* if sess file exists jet, create a other sess id */
    if (file_exists($sessF) == true) {

      /* escape date */
      $fExp = ($this->m_confXml["sessionvalid"]["xmlValue"] * 60 * 60) 
        + filectime($sessF);
      /* if sessFile older than config[sessionvalid], remove it! */
      if ($fExp <= time()) {
        if (unlink($sessF) == false) {
          throw new CError(ERROR_DELETE_SESSFILE, array($sessF));
        }
      }

      /* generate new sessionId */
      $this->createNewSessionId();
    }
  }

  private  function loadSessionData()
  {
    /* open session file */
    if (($handl = fopen($this->m_sessFile, "r")) == false) {
      throw new CError(ERROR_OPEN_SESSFILE, array($this->m_sessFile));
    }

    /* read data  and reprapare data */
    $raw = fread($handl, filesize($this->m_sessFile));
    $this->m_sessData = unserialize($raw);

    fclose($handl);
  }

  private function saveSessionData()
  {
    /* open session file */
    if (($handl = fopen($this->m_sessFile, "w")) == false) {
      throw new CError(ERROR_OPEN_SESSFILE, array($this->m_sessFile));
    }

    /* prepare session data */
    $raw    = serialize($this->m_sessData);

    /* save to file */
    if (fwrite($handl, $raw) == false) {
      throw new CError(ERROR_WRITE_SESSFILE, array($this->m_sessFile));
    }

    fclose($handl);
  }

  public  function getSessionValue($nodeName)
  {
    return $this->m_sessData[$nodeName]["value"];
  }

  public  function setSessionValue($nodeName, $nodeValue, $key)
  {
    /* exists this session variable also, check access with key! */
    if (array_key_exists($nodeName, $this->m_sessData) == true) {
      $sKey = &$this->m_sessData[$nodeName]["key"];
      
      /* if the key is the same as the key witch set it */
      if ($sKey == $key or empty($sKey) == true) {
        $this->m_sessData[$nodeName]["value"] = utf8_encode($nodeValue);
        $this->saveSessionData();
      }
      else {
        throw new CError(ERROR_SESSION_KEY, array($nodeName));
      }
    }
    /*-
     * Add new Session Node */
    else {
      $this->m_sessData[$nodeName]["value"] = utf8_encode($nodeValue);
      $this->m_sessData[$nodeName]["key"]   = $key; 
    }

    /* save */
    $this->saveSessionData();
  }

  public  function setPageSession()
  {
    $arrSess    = &$this->m_cPage->getSession();

    if (isset($arrSess) == false) {
      return;
    }

    for ($i = 0; $i <= $arrSess["setsession"]["xmlMulti"]; ++$i) {
      $sess     = &$arrSess["setsession"][$i];
      $getName  = &$sess["xmlAttribute"]["sessname"];
      $value    = $this->m_cParam->extractValue($sess["xmlValue"]);

      /* if filter is set, use it! */
      if (array_key_exists("filter", $sess["xmlAttribute"]) == true) {

        $filter  = &$sess["xmlAttribute"]["filter"];
        CSecure::filterData($value, $filter);
      }

      /*-
       * If you use a key for protect the value */
      $key      = "";
      if (array_key_exists("key", $sess["xmlAttribute"]) == true) {
        $key    = $sess["xmlAttribute"]["key"];

        /* if key is set put without value, use syskey! */
        if (empty($key) == true) {
          $key  = $this->m_cConfig->m_config["systemkey"]["xmlValue"];
        }
      }

      /* set data to session */
      $this->setSessionValue($getName, $value, $key);
    }
  }
}

?>
