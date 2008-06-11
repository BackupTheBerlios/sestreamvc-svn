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

require_once("lib/core/CGlob.php");
require_once("lib/core/CError.php");
require_once("lib/core/CParam.php");
require_once("lib/core/CSecure.php");
require_once("lib/core/CConfig.php");
require_once("lib/core/CAccess.php");

require_once("lib/controller/CPage.php");
require_once("lib/controller/CFormSet.php");

class CForm {
  private $m_cConfig;
  private $m_cGlob;
  private $m_cParam;
  private $m_cPage;
  private $m_cAccess;
  private $m_pageXml;
  private $m_arrForm;
  private $m_forward;

  /**
   * Setzte default Werte und setze Pointer zum Konfig.
   *
   * @param &$Config      Zeiger auf das Konfig Objekt.
   */
  public  function __construct(CConfig &$config) {

    /* init default */
    $this->m_cConfig      = &$config;

    $this->m_arrForm      = array();
    $this->m_forward      = NULL;
  }

  /**
   * Setze default Pointers.
   *
   * @param &$page        Zeiger auf die Page von wo die Formular Daten 
   *                      geladen werden.
   * @param &$param       Zeiger auf das Param Objekt.
   * @param &$glob        Zeiger auf den Internen Global Raum.
   * @param &$access      Zeiger auf das Access Objekt.
   */
  public  function  initForm(CPage &$page, CParam &$param,
                             CGlob &$glob, CAccess &$access)
  {
    $this->m_cPage        = &$page;
    $this->m_cParam       = &$param;
    $this->m_cAccess      = &$access;
    $this->m_cGlob        = &$glob;
  }

  /**
   * Lade Formular Daten der Page.
   * Im ersten schritt werden alle Formular Daten abgerufen und
   * danach wird jedes einzelne Formular in einem FormSet geladen.
   * Nach der Init der FormSet wird die Referenz in ein Array 
   * gespeichert, um sich spaeter darauf berufen zu koennen.
   */
  public  function loadForm()
  {
    $this->m_pageXml      = &$this->m_cPage->getForm();

    if (isset($this->m_pageXml) == false) {
      return;
    }

    /* durchlaufe alle formulare und init diese */
    for ($i = 0; $i <= $this->m_pageXml["form"]["xmlMulti"]; ++$i) {

      $cFormSet           = new CFormSet($this->m_cConfig,
                                         $this->m_cParam,
                                         $this->m_cGlob);

      $cFormSet->initFormSet($this->m_pageXml["form"][$i]);

      /* Benutzer hat Access auf das Formular? */
      /* versuche formular zu laden und zu validieren */
      $cFormSet->assessFormSet();

      /* Wenn Formular aktiviert und okay ist */
      if ($cFormSet->isOkay() == true) {
        
        /* wird nun weitergeleitet? */
        if ($cFormSet->useForward() == true)  {
          $this->m_forward = $cFormSet->getForwardPage();
        }
      }

      /* Keine Referenz uebertragen! Adressraum wird ueberschrieben */
      $this->m_arrForm[$cFormSet->getFormName()]  = $cFormSet;
    }
  }

  /**
   * Gibt die Fehlermeldung eines Feldes zurueck.
   *
   * @param $formSet      Name des Formulars
   * @param $postName     Feldname des Formulars
   * @return              Fehlermeldung wenn diese im Definiert wurde
   */
  public  function getErrorMsg($formSet, $postName)
  {
    if (array_key_exists($formSet, $this->m_arrForm) == true) {
      return $this->m_arrForm[$formSet]->getErrorMsg($postName);
    }

    throw new CError(ERROR_FORMSET_UNKOWN, $formSet);
  }

  /**
   * Prueft ob ein Valid Fehler auf diesem Feld vorliegt.
   *
   * @param $formSet      Name des Formulars
   * @param $postName     Feldname des Formulars
   * @return              TRUE oder FALSE
   */
  public  function haveErrorOnField($formSet, $postName)
  {
    if (array_key_exists($formSet, $this->m_arrForm) == true) {
      return $this->m_arrForm[$formSet]->haveErrorOnField($postName);
    }

    throw new CError(ERROR_FORMSET_UNKOWN, array($formSet));
  }

  public  function getDefaultValue($formSet, $postName)
  {
    if (array_key_exists($formSet, $this->m_arrForm) == true) {
      return $this->m_arrForm[$formSet]->getDefaultValue($postName);
    }

    throw new CError(ERROR_FORMSET_UNKNOWN, array($formSet));
  }

  public  function isOkay($formSet)
  {
    if (array_key_exists($formSet, $this->m_arrForm) == true) {
      return $this->m_arrForm[$formSet]->isOkay();
    }

    throw new CError(ERROR_FORMSET_UNKNOWN, array($formSet));
  }

  public  function forwardActive()
  {
    if (empty($this->m_forward) == true) {
      return false;
    }

    return true;
  }

  public  function getForwardPageName()
  {
    return $this->m_forward;
  }



}
?>
