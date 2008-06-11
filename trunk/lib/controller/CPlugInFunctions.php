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

require_once("lib/core/CParam.php");
require_once("lib/core/CSession.php");
require_once("lib/core/CGlob.php");

require_once("lib/model/CModel.php");

define(PLUGINFUNCT_GLOB_POST,         1);
define(PLUGINFUNCT_GLOB_GET,          2);
define(PLUGINFUNCT_GLOB_TMP,          3);
define(PLUGINFUNCT_SESSION,           4);

class CPlugInFunctions {
  private $m_cParam;
  private $m_cSession;
  private $m_cGlob;
  private $m_cModel;

  public  function __construct(CModel &$model, CParam &$param, CGlob &$param,
    CSession &$session)
  {
    $this->m_cSession = &$session;
    $this->m_cGlob    = &$glob;
    $this->m_cParam   = &$param;
    $this->m_cModel   = &$model;
  }

  public  function setScopeValue($flag, &$name, &$value, &$key)
  {
    switch (strtolower($flag)) {
      case PLUGINFUNCT_GLOB_POST    :
        return $this->m_cGlob->setPost($name, $value, $key);

      case PLUGINFUNCT_GLOB_GET     :
        return $this->m_cGlob->setGet($name, $value, $key);

      case PLUGINFUNCT_GLOB_TMP     :
        return $this->m_cGlob->setTmp($name, $value, $key);

      case PLUGINFUNCT_SESSION      :
        return $this->m_cSession->setSessionValue($name, $value, $key);
    }
  }
  
  public  function getScopeValue($flag, &$name)
  {
    switch (strtolower($flag)) {
      case PLUGINFUNCT_GLOB_POST    :
        return $this->m_cGlob->getPost($name);

      case PLUGINFUNCT_GLOB_GET     :
        return $this->m_cGlob->getGet($name);

      case PLUGINFUNCT_GLOB_TMP     :
        return $this->m_cGlob->getTmp($name);

      case PLUGINFUNCT_SESSION      :
        return $this->m_cSession->getSessionValue($name);
    }
  }

  public  function extractValue(&$value, $filter = "", $isSql = false)
  {
    return $this->m_cParam->extractValue($value, $filter, $isSql);
  }

  public  function createAssocFromParam(&$arrParam)
  {
    return $this->m_cModel->createAssocFromParam($arrParam);
  }

  public  function retrieveDataFromSet(CModelSet &$modelSet)
  {
    return $this->m_cModel->retrieveDataFromSet($modelSet);
  }

  public  function addModelSet(CModelSet &$modelSet, &$modelName)
  {
    return $this->m_cModel->addModelSet($modelSet, $modelName);
  }

}

?>