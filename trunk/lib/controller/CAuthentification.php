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
require_once("lib/core/CConfig.php");
require_once("lib/core/CParam.php");
require_once("lib/core/CGlob.php");
require_once("lib/core/CSession.php");

require_once("lib/model/CModel.php");

require_once("lib/controller/CEvent.php");
require_once("lib/controller/CPage.php");

/* Interface Objekte: IAuthObject */
require_once("lib/controller/CAuthDatabase.php");

class CAuthentification {
  private $m_cConfig;
  private $m_cParam;
  private $m_cGlob;
  private $m_cPage;
  private $m_cModel;
  private $m_cEvent;
  private $m_cSession;
  private $m_xmlPage;
  private $m_confXml;
  private $m_sysKey;
  private $m_authObj;
  private $m_enable;
  private $m_success;

  public  function __construct(CConfig &$config) {

    /* init default */
    $this->m_cConfig    = &$config;

    $this->m_confXml    = &$config->getConfTree("authentification", "core");
    $this->m_sysKey     = &$config->m_config["systemkey"]["xmlValue"];
    $this->m_success    = false;

    /* aktiviert/deaktiviert objekt */
    $this->m_enable     = &$this->m_confXml["enableauth"]["xmlValue"];
    /* beende dich, wenn nicht aktive */
    if ($this->m_enable == false) {
      return;
    }
  }

  public  function initAuth(CPage &$page, CGlob &$glob, CSession &$session,
                            CParam &$param, CEvent &$event, CModel &$model)
  {
    $this->m_cPage      = &$page;
    $this->m_cGlob      = &$glob;
    $this->m_cSession   = &$session;
    $this->m_cParam     = &$param;
    $this->m_cEvent     = &$event;
    $this->m_cModel     = &$model;
  }

  public  function loadAuth()
  {
    /* beende dich, wenn nicht aktive */
    if ($this->m_enable == false) {
      return;
    }

    /*-
     * lade Auth aus page und erstelle das Event */
    $this->m_pageXml  = &$this->m_cPage->getAuthentification();

    /* gibt es ein Authentifications eintrag auf der Page? */
    if (isset($this->m_pageXml) == true) {

      /* erstelle Auth Objekt */
      switch (strtolower($this->m_confXml["useforauth"]["xmlValue"])) {
        case "database"   :
          $this->m_authObj  = 
            new CAuthDatabase($this->m_cConfig, $this->m_cModel, 
                              $this->m_cGlob);
           break;

        default           :
          throw new CError(ERROR_AUTH_METHOD);
      }

      $event    = &$this->m_pageXml["xmlAttribute"]["event"];

      /* erstelle username */
      $name     = &$this->m_pageXml["name"]["xmlValue"];

      /* erstelle passwort */
      $password = &$this->m_pageXml["password"]["xmlValue"];

      /* adde event */
      $this->m_cEvent->addCallbackToEvent($event,
                                          array(&$this, "startLogin"),
                                          array($name, $password),
                                          EVENT_AUTH
                                          );
    }
  }

  public  function startLogin($user, $pw)
  {
    $userId     = NULL;
    $userName   = $this->m_cParam->extractValue($user);
    $password   = $this->m_cParam->extractValue($pw);

    /* login */
    $userId     = $this->m_authObj->loginUser($userName, $password);

    /* ist user nicht gleich NULL, erfolgreich eingelogt, setze neue id */
    if (isset($userId) == true) {

      $this->m_cSession->setSessionValue("userid", $userId, $this->m_sysKey);
      $this->m_success = true;
    }
  }

  public  function loginSuccess()
  {
    return $this->m_success;
  }

}

?>
