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

require_once("lib/core/CUser.php");
require_once("lib/core/CSession.php");
require_once("lib/core/CGlob.php");
require_once("lib/core/CError.php");
require_once("lib/core/CSecure.php");
require_once("lib/core/CAccess.php");

require_once("lib/model/CModel.php");

require_once("lib/controller/CForm.php");
require_once("lib/controller/CEvent.php");
require_once("lib/controller/CPage.php");
require_once("lib/controller/CAuthentification.php");

class CParam {
  private $m_cUser;
  private $m_cSession;
  private $m_cGlob;
  private $m_cConfig;
  private $m_cModel;
  private $m_cForm;
  private $m_cEvent;
  private $m_cAuth;
  private $m_cAccess;
  private $m_cPage;

  public  function __construct(CConfig &$config, CUser &$user, 
                               CSession &$session, CGlob &$glob, 
                               CModel &$model, CForm &$form, 
                               CEvent &$event, CAccess &$access,
                               CAuthentification &$auth, CPage &$page)
  {
    $this->m_cUser    = &$user;
    $this->m_cSession = &$session;
    $this->m_cGlob    = &$glob;
    $this->m_cConfig  = &$config;
    $this->m_cModel   = &$model;
    $this->m_cForm    = &$form;
    $this->m_cEvent   = &$event;
    $this->m_cAuth    = &$auth;
    $this->m_cAccess  = &$access;
    $this->m_cPage    = &$page;
  }

  public  function replaceWithParams(&$arrParam, &$reStr, $isSql = false)
  {
    /* init */
    $search = array();
    $replac = array();

    /* iter all param and create a regex syntax for replace */
    for ($i = 0; $i <= $arrParam["xmlMulti"]; ++$i) {

      /* use it a filter? */
      $filter = NULL;
      if (array_key_exists("filter", $arrParam[$i]["xmlAttribute"]) == true) {
        $filter  = &$arrParam[$i]["xmlAttribute"]["filter"];
      }

      /* create search regex */
      $search[]	 = "/". preg_quote("$".$arrParam[$i]["xmlAttribute"]["name"], 
                   "/") . "/";
      /* create replace with value */
      $replace[] = $this->extractValue($arrParam[$i]["xmlValue"], $filter, 
                                      $isSql);
    }

    /* replace value */
    $reStr = preg_replace($search, $replace, $reStr);
  }

  public  function createAssocFromParam(&$arrParam)
  {
    $assoc      = array();

    /* durchlaufe param liste */
    for ($i = 0; $i <= $arrParam["xmlMulti"]; ++$i) {
      $name     = &$arrParam[$i]["xmlAttribute"]["name"];

      /* wird ein filter benutzt? */
      if (array_key_exists("filter", $arrParam[$i]["xmlAttribute"]) == true) {
        $filter = &$arrParam[$i]["xmlAttribute"]["filter"];
      }

      $val      = $this->extractValue($arrParam[$i]["xmlValue"], $filter);

      /* erstelle assoc array */
      $assoc[$name] = $val;
    }

    return $assoc; 
  }

  public  function extractValue($value, $filter = NULL, $isSql = false)
  {
    $return   = "";

    /*-
     * ist es eine funktion? Erkennt man am OBJ->FUNCT::ARG */
    if (preg_match("/^\w+->.+$/", $value) == 1) {
      /* verwandle es zurück in den Style OBJ::FUNCT::ARG */
      $tmpVal    = preg_replace("/^(\w+)->(.+)$/", "$1::$2", $value);
      $arrParts  = preg_split("/::/", $tmpVal);

      switch(strtoupper($arrParts[0])) {
        case "FORM"   :
          $return = $this->functFromCForm($arrParts);
          break;

        case "MODEL"  :
          $return = $this->functFromCModel($arrParts);
          break;

        case "USER"  :
          $return = $this->functFromCUser($arrParts);
          break;

        case "AUTH"  :
          $return = $this->functFromCAuthentification($arrParts);
          break;

        case "PAGE"               :
          $return = $this->functFromCPage($arrParts);
          break;

        default       :
          throw new CError(ERROR_PARAM_INVALID, array($arrParts[0], $value));
      }
    }
    /*-
     * es ist ein wert. Erkennung: OBJ::ARG1::ARGX */
    else {
      $arrParts = preg_split("/::/", $value);

      switch ($arrParts[0]) {
        case "GET"      : 
          $return = $this->extrFromCGlob($arrParts);
          break;

        case "POST"     :
          $return = $this->extrFromCGlob($arrParts);
          break;

        case "TMP"      :
          $return = $this->extrFromCGlob($arrParts);
          break;

        case "SESSION"  :
          $return = $this->extrFromCSession($arrParts);
          break;

        case "USER"     :
          $return = $this->extrFromCUser($arrParts);
          break;

        case "MODEL"    :
          $return = $this->extrFromCModel($arrParts);
          break;

        case "FORM"     :
          $return = $this->extrFromCForm($arrParts);
          break;

        case "STATIC"   :
          $return = $arrParts[1];
          break;

        case "EVENT"    :
          $return = $this->extrFromCEvent($arrParts);
          break;

        case "ACCESS"   :
          $return = $this->extrFromCAccess($arrParts);
          break;

        default         :
          throw new CError(ERROR_PARAM_INVALID, array($arrParts[0], $value));
      }
    }

    /*- 
     * is the value are a SQL statment, make the string sql incet secure 
     */
    if (isSql == true) {
      CSecure::encodeSqlInject($return);
    }

    /* if a filter is defined, use it! */
    if (isset($filter) == true) {
      CSecure::filterData($return, $filter);
    }

    return $return;
  }
  
  private function extrFromCGlob(&$arrParts)
  {
    if (count($arrParts) > 2) {
      throw new CError(ERROR_PARAM_SYNTAX, array(implode($arrParts)));
    }

    /*-
     *  GET */
    if ($arrParts[0] == "GET") {
      return $this->m_cGlob->getGet($arrParts[1]);
    }
    /*-
     * POST */
    elseif ($arrParts[0] == "POST") {
      return $this->m_cGlob->getPost($arrParts[1]);
    }
    /*-
     * TMP */
    else {
      return $this->m_cGlob->getTmp($arrParts[1]);
    } 
  }

  private function extrFromCSession(&$arrParts)
  {
    if (count($arrParts) > 2) {
      throw new CError(ERROR_PARAM_SYNTAX, array(implode($arrParts)));
    }

    return $this->m_cSession->getSessionValue($arrParts[1]);
  }

  private function extrFromCEvent(&$arrParts)
  {
    if (count($arrParts) > 2) {
      throw new CError(ERROR_PARAM_SYNTAX, array(implode($arrParts)));
    }

    return $this->m_cEvent->isActive($arrParts[1]);
  }

  private function extrFromCUser(&$arrParts)
  {
    if (count($arrParts) > 2) {
      throw new CError(ERROR_PARAM_SYNTAX, array(implode($arrParts)));
    }

    return $this->m_cUser->getUserData($arrParts[1]);
  }

  private function extrFromCAccess(&$arrParts)
  {
    if (count($arrParts) > 2) {
      throw new CError(ERROR_PARAM_SYNTAX, array(implode($arrParts)));
    }

    return $this->m_cAccess->haveAccessOnRule($arrParts[1]);
  }


  private function extrFromCModel(&$arrParts)
  {
    if (count($arrParts) > 3) {
      throw new CError(ERROR_PARAM_SYNTAX, array(implode($arrParts)));
    }

    return $this->m_cModel->getValue($arrParts[1], $arrParts[2]);
  }

  private function functFromCModel(&$arrParts)
  {
    switch (strtolower($arrParts[1])) {
      case "countrow"   :
        return $this->m_cModel->countRow($arrParts[2]);

      default           :
        throw new CError(ERROR_PARAM_SYNTAX, array(implode($arrParts)));
    }
  }

  private function functFromCForm(&$arrParts)
  {
    switch (strtolower($arrParts[1])) {
      case "isokay"   :
        return $this->m_cForm->isOkay($arrParts[2]);

      case "haveerroronfield"  :
        return $this->m_cForm->haveErrorOnField($arrParts[2], $arrParts[3]);

      case "geterrormsg"      :
        return $this->m_cForm->getErrorMsg($arrParts[2], $arrParts[3]);

      case "getdefaultvalue"    :
        return $this->m_cForm->getDefaultValue($arrParts[2], $arrParts[3]);

    default           :
      throw new CError(ERROR_PARAM_SYNTAX, array(implode($arrParts)));
    }
  }

  private function functFromCAuthentification(&$arrParts)
  {
    switch (strtolower($arrParts[1])) {
      case  "loginsuccess"  :
        return $this->m_cAuth->loginSuccess();

    default           :
      throw new CError(ERROR_PARAM_SYNTAX, array(implode($arrParts)));
    }
  }

  private function functFromCUser(&$arrParts)
  {
    switch (strtolower($arrParts[1])) {
      case "islogin"   :
        return $this->m_cUser->isLogin();

      case "getemail"   :
        return $this->m_cUser->getEmail();

      case "getuserid"   :
        return $this->m_cUser->getUserId();

      case "getusername" :
        return $this->m_cUser->getUserName();

    default           :
      throw new CError(ERROR_PARAM_SYNTAX, array(implode($arrParts)));
    }
  }

  private function functFromCPage(&$arrParts)
  {
    switch (strtolower($arrParts[1])) {
      case "gettitle"   :
        return $this->m_cPage->getTitle();

    default           :
      throw new CError(ERROR_PARAM_SYNTAX, array(implode($arrParts)));
    }
  }

}

?>
