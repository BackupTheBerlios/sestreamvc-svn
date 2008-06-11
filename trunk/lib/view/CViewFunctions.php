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

require_once("lib/model/CModel.php");

require_once("lib/controller/CLink.php");

/**
 * Funktions Wrapper für den View.
 * Die Funktionen die hier implementiert sind, können auf dem View 
 * angesprochen werden.
 *
 * Diese Klasse ist, wie auch PlugInFunctions, nur für das Wrappen er 
 * Funktionen zwischen dem Controller und dem View verantwortlich. Dies 
 * ist ein schutz vor unberechtigtem zugriff aus dem View heraus.
 */
class CViewFunctions {
  private $m_cParam;
  private $m_cModel;
  private $m_cLink;

  public  function __construct(CParam &$param, CModel &$model, CLink &$link)
  {
    $this->m_cParam   = &$param;
    $this->m_cModel   = &$model;
    $this->m_cLink    = &$link;
  }

  public  function ifIs($paramStr, $valid = NULL)
  {
    /* hole wert */
    $value    = $this->m_cParam->extractValue($paramStr);

    /* validiere den Wert */
    if (isset($valid) == true) {
      try {
        CSecure::validData($value, $valid);

        /* kein fehler, es ist okay */
        return true;
      }
      /* fehler beim validiren, false */
      catch (CError $error) {
        return false;
      }
    }

    /* wenn nicht validiert, gebe wert zurück */
    return $value;
  }

  public  function get($paramStr, $filter = NULL)
  {
    $value    = $this->m_cParam->extractValue($paramStr);

    /* Filtere den wert */
    if (isset($filter) == true) {
      CSecure::filterData($value, $filter);
    }

    /* gebe wert zurück */
    return $value;
  }

  public  function funct($paramStr)
  {
    /* generiere aus OBJ->funct, OBJ::funct */
    $paramStr   = preg_replace("/^(\w+)->(.+)$/", "$1::$2", $paramStr);
    $arrParts   = preg_split("/::/", $paramStr);

    switch (strtoupper($arrParts[0])) {
      case "MODEL"      :
        $this->execModel($arrParts);
    }
  }

  private function execModel($arrParts)
  {
    switch (strtolower($arrParts[1])) {
      case "nextiter"   :
        $this->m_cModel->nextIter($arrParts[2]);
        break;

      case "resetIter"  :
        $this->m_cModel->resetIter($arrParts[2]);
        break;
    }
  }

  public  function link($url, $extends = NULL)
  {
    return $this->m_cLink->getLink($url, $extends);
  }

}

?>
