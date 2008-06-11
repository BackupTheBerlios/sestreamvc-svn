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

require_once("lib/core/CXmlParser.php");
require_once("lib/core/CError.php");

class CXmlModel extends CXmlParser {
  private $m_lastElement;
  private $m_lastObjectName;

  protected function startTag($parser, $elementName, $elementAttr)
  {
    $basicName = strtoupper(utf8_decode($elementName));

    /* skip xml root namespace */
    if ($basicName == "NSMODEL") {
      return;
    }

    /* if it's a basic node, set default values and skip */
    if ($basicName == "SELECT" or $basicName == "INSERT" 
        or $basicName == "UPDATE" or $basicName == "DELETE" or 
        $baiscName == "RAW") {

      $this->setNewBasic($basicName, $elementAttr);
      return;
    }

    /* test of valid tags and set lastElement used by getCData() */ 
    if ($basicName == "VIEW" or $basicName == "TABLE" or $basicName == "WHERE"
        or $basicName == "LAST" or $basicName == "SET" or 
        $basicName == "VALUES" or $basicName == "ORDER" or 
        $basicName == "SQL") {

      $this->m_lastElement = strtolower($basicName); 
      return;
    }
  }

  private function setNewBasic($basic, &$attr)
  {
    $objName      = $attr["MODELNAME"];
    $objIsTable   = FALSE;
    $objRealName  = NULL;

    if (array_key_exists("MODELTABLE", $attr) == true) {
      if (strtoupper(utf8_decode($attr["MODELTABLE"])) == "TRUE") {
        $objIsTable = TRUE;
      }
    }

    if (array_key_exists("NAME", $attr) == true) {
      $objRealName = &$attr["NAME"];
    }

    $this->m_xmlData[$objName] = array(
      "xmlModelName"  => $objRealName,
      "xmlModelObj"   => $objName,
      "xmlModelTable" => $objIsTable,
      "xmlBasicSql"   => $basic);

    $this->m_lastObjectName = $objName;
  }

  protected function endTag($parser, $elementName)
  {
    $this->m_lastElement    = NULL;
  }

  protected function getCData($parser, $elementValue)
  {
    if (isset($this->m_lastElement) == false) {
      return;
    }

    $this->m_xmlData[$this->m_lastObjectName]
                    [$this->m_lastElement] .= $elementValue;
  }
}

?>
