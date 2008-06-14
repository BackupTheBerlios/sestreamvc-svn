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
require_once("lib/core/CSession.php");
require_once("lib/core/CCache.php");

require_once("lib/model/CModel.php");
require_once("lib/model/CModelSet.php");
require_once("lib/model/CXmlModel.php");

class CUser {
  private $m_cConfig;
  private $m_cSession;
  private $m_cModel;
  private $m_confXml;
  private $m_isLogin;
  private $m_dataSet;
  private $m_userId;
  private $m_userName;
  private $m_email;

  public function __construct(CConfig &$config)
  {
    /* init defaults */
    $this->m_cConfig  = &$config;
    $this->m_confXml  = &$config->getConfTree("user");
    $this->m_isLogin  = false;

    /* init CModelSet */
    $this->m_dataSet  = new CModelSet();
  }

  public  function initUser(CSession &$session, CModel &$model)
  {
    /* init default */
    $this->m_cModel   = &$model;
    $this->m_cSession = &$session;
  }

  public  function reloadUserData()
  {
    $this->m_userId = utf8_decode($this->m_cSession->getSessionValue("userid"));
    $this->m_cModel->retrieveDataFromModelSet($this->m_dataSet);

    $this->setLoginFlag();
  }

  public function loadUser()
  {
    $this->m_dataSet->setModelConf($this->m_confXml["model"][0]);

    /* get user id form session */
    $this->m_userId = utf8_decode($this->m_cSession->getSessionValue("userid"));

    /* load user data form database */ 
    $this->m_cModel->retrieveDataFromModelSet($this->m_dataSet);

    /* exeptions */
    if ($this->m_dataSet->countRow() == 0) {
      throw new CError(ERROR_UNKNOWN_USER);
    }

    $this->setLoginFlag();
  }

  private function setLoginFlag()
  {
    /* if the user are login, set login variable */
    if ($this->m_userId != 
        $this->m_cConfig->m_config["session"]["defaultuserid"]["xmlValue"]) {
      $this->m_isLogin = true;
    }
    else {
      $this->m_isLogin = false;
    }
  }

  public  function getUserName()
  {
    $field = strtolower($this->m_confXml["name"]["xmlValue"]);
    return $this->m_dataSet->getValue($field);
  }

  public  function getEmail()
  {
    $field = strtolower($this->m_confXml["email"]["xmlValue"]);
    return $this->m_dataSet->getValue($field);
  }

  public  function getUserId()
  {
    return $this->m_userId;
  }

  public  function getUserData($field)
  {
    $field   = strtolower($field);
    return $this->m_dataSet->getValue($field);
  }

  public  function isLogin()
  {
    return $this->m_isLogin;
  }

};


?>
