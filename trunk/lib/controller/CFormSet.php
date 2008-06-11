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

require_once("lib/controller/CMessageForm.php");

require_once("lib/core/CGlob.php");
require_once("lib/core/CError.php");
require_once("lib/core/CParam.php");
require_once("lib/core/CSecure.php");
require_once("lib/core/CConfig.php");

class CFormSet {
  private $m_cConfig;
  private $m_cParam;
  private $m_cMessageF;
  private $m_confXml;
  private $m_xmlForm;
  private $m_formName;
  private $m_access;
  private $m_sendName;
  private $m_active;
  private $m_ok;
  private $m_errFields;
  private $m_sysKey;
  private $m_forward;

  public  function __construct(CConfig &$config, CParam &$param, 
      CGlob &$glob)
  {
    /* init default */
    $this->m_cConfig      = &$config;
    $this->m_cParam       = &$param;
    $this->m_cGlob        = &$glob;
    $this->m_confXml      = &$config->getConfTree("formconfig");
    $this->m_errFields    = array();
    $this->m_forward      = NULL;
    $this->m_access       = NULL;

    /* setzte System Schluessel */
    $this->m_sysKey = $this->m_cConfig->m_config["systemkey"]["xmlValue"];
  }

  public  function initFormSet(&$xmlForm)
  {
    /* init defaults */
    $this->m_xmlForm     = &$xmlForm;
    $this->m_formName    = &$xmlForm["xmlAttribute"]["name"];
    $this->m_sendName    = &$xmlForm["xmlAttribute"]["active"];
    $this->m_active      = true;
    $this->m_ok          = false;

    /* use access */
    if (array_key_exists("access", $xmlForm["xmlAttribute"])) {
      $this->m_access    = &$xmlForm["xmlAttribute"]["access"];
    }

    /* use errorMessage */
    if (array_key_exists("errormessage", $xmlForm) == true) {
      $file              = &$xmlForm["errormessage"]["xmlValue"];

      $this->m_cMessageF   = new CMessageForm($this->m_cConfig, $file);
      $this->m_cMessageF->loadMessage();
    }
    else {
      $this->m_cMessageF  = NULL;
    }

    /* wird weitergeleitet? */
    if (array_key_exists("forward", $xmlForm) == true) {
      $this->m_forward  = &$xmlForm["forward"]["xmlValue"];
    }
    else {
      $this->m_forward = NULL;
    }
  }

  public  function assessFormSet()
  {
    /* if form is active, check inputs */
    if ($this->setActive() == true) {

      /* load $_POST[] values into CGlob and test valid */
      $this->checkInputs();

    }
  }

  private function setActive()
  {
    /* if this formular is active */
    if (isset($_POST[$this->m_sendName])) {

      /* set _post[] glob value */
      $this->m_cGlob->setPost($this->m_sendName, true, $this->m_sysKey);

      /* set formSet active */
      return true;
    }
    return false;
  }

  private function checkInputs()
  {
    $inputs   = &$this->m_xmlForm["field"];

    for ($i = 0; $i <= $inputs["xmlMulti"]; ++$i) {
      $postName   = &$inputs[$i]["xmlValue"];
      $postValue  = &$_POST[$postName];

      /*-
       * Valide / Filter / eq / guard -> checks -> and add */
      try {
        /*-
         * check Valid */
        if (array_key_exists("valid", $inputs[$i]["xmlAttribute"]) == true) {
          $postValid  = &$inputs[$i]["xmlAttribute"]["valid"];

          CSecure::validData($postValue, $postValid);
        }

        /*-
         * filter value */
        if (array_key_exists("filter", $inputs[$i]["xmlAttribute"]) == true) {
          $filter    = &$inputs[$i]["xmlAttribute"]["filter"];

          CSecure::filterData($postValue, $filter);
        }

        /*-
         * are to post values eq */
        if (array_key_exists("select", $inputs[$i]["xmlAttribute"]) == true) {
          $selectName = $inputs[$i]["xmlAttribute"]["select"];

          /* test it */
          if ($_POST[$postName] != $_POST[$selectName]) {
            $this->setError($postName, "select");
            continue;
          }
        }

        /*-
         * Guard */
        if (strtolower($inputs[$i]["xmlAttribute"]["guard"]) != "off") {
          CSecure::guard($postValue);
        }

        /*-
         * If you use a key for protect the value */
        $key    = "";
        if (array_key_exists("key", $inputs[$i]["xmlAttribute"]) == true) {
          $key  = &$inputs[$i]["xmlAttribute"]["key"];

          /* if key is set put without value, use syskey! */
          if (empty($key) == true) {
            $key = $this->m_sysKey; 
          }
        }

        /*-
         *  set value to CGlob::Post */
        $this->m_cGlob->setPost($postName, $postValue, $key);

      }
      catch (CError $error) {
        $this->m_active = false;

        /* if it isn't a ERROR_SECURE_VALID exceptions, throw it! */
        if ($error->m_errCode != ERROR_SECURE_VALID) {
          if ($this->m_confXml["usestrict"]["xmlValue"] == true) {
            throw $error;
          }
        }
        else {
            $this->setError($postName, $error->m_arrArg[0]);
        }
        continue;
      }
    } /* end for */
    if ($this->m_active == true) {
      $this->m_ok = true;
    }
  }

  private function setError(&$postName, $flag)
  {
    $this->m_active = false;

    /* nur nachricht hinterlegen wenn Message instanziert ist */
    if (is_object($this->m_cMessageF) == true) {
      $this->m_errFields[$postName] = 
        $this->m_cMessageF->getFormMessage($flag, $postName); 
    }
  }

  public  function getdefaultValue($postName)
  {
    if ($this->isOkay() == false) {
      return CSecure::filterData($_POST[$postName], "encodehtmlspecialchars");
    }
  }

  public  function haveErrorOnField($postName)
  {
    if (empty($this->m_errFields[$postName]) == true) {
      return false;
    }
    else {
      return true;
    }
  }

  public  function getErrorMsg($postName)
  {
    if (empty($this->m_errFields[$postName]) == false) {
      return $this->m_errFields[$postName];
    }
    else {
      if ($this->m_confXml["usestrict"]["xmlValue"] == true) {
        throw new CError(ERROR_FORM_NO_ERROR, array($postName));
      }
    }
  }

  /**
   * Formular wurde erfolgreich verarbeitet und es traten keine Fehler 
   * auf.
   *
   * @return              TRUE oder FALSE
   */
  public  function isOkay()
  {
    return $this->m_ok;
  }

  /**
   * Gibt den Namen des Formulares zurueck. Ueber diesen Namen 
   * referenziert man sich auf dieses Formular.
   *
   * @return            String mit Namen des Formulares.
   */
  public  function getFormName()
  {
    return $this->m_formName;
  }

  /**
   * Wird auf eine andere page verwiesen, wenn
   * das Formular erfolgreich war?
   *
   * @return          TRUE oder FALSE
   */
  public  function useForward()
  {
    if (isset($this->m_forward) == false) {
      return false;
    }

    return true;
  }

  public  function getForwardPage()
  {
    return $this->m_forward;
  }

  /**
   * Muss zugriff kontrolliert werden?
   *
   * @return          TRUE oder FALSE
   */
  public  function useAccess()
  {
    if (empty($this->m_access) == true) {
      return false;
    }

    return true;
  }

}
?>
