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

require_once("lib/core/CConfig.php");
require_once("lib/core/CGlob.php");

require_once("lib/model/CModel.php");
require_once("lib/model/CModelSet.php");

require_once("lib/controller/IAuthObject.php");

class CAuthDatabase implements IAuthObject {
  private $m_cConfig;
  private $m_cModel;
  private $m_cGlob;
  private $m_confXml;
  private $m_sysKey;

  public  function __construct(CConfig &$config, CModel &$model, CGlob &$glob)
  {
    $this->m_cConfig    = &$config;
    $this->m_cModel     = &$model;
    $this->m_cGlob      = &$glob;

    $this->m_confXml    = &$config->getConfTree("authentification", "database");
    $this->m_sysKey     = &$config->m_config["systemkey"]["xmlValue"];
  }

  public  function loginUser($user, $password)
  {
    $userId             = NULL;
    $cModSet            = new CModelSet($this->m_confXml["model"][0]);
    $fieldName          = &$this->m_confXml["useridfield"]["xmlValue"];

    /* setze value für Model */
    $this->m_cGlob->setTmp("auth_username", $user, $this->m_sysKey);
    $this->m_cGlob->setTmp("auth_password", $password, $this->m_sysKey);

    $this->m_cModel->RetrieveDataFromModelSet($cModSet);
    /* wurden daten geholt? Benutzer existiert und hat sich Auth */
    if ($cModSet->countRow() == 1) {
      $userId           = $cModSet->getValue($fieldName);
    }

    return $userId;
  }

}

?>
