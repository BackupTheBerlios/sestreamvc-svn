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

class CXmlController extends CXmlParser {
  private $m_depthTree;
  private $m_tmpArrTreeName;
  private $m_lastElement;
  private $m_lastAttribute;
  private $m_lastType;
  private $m_defaultType;
  private $m_lastElementNode;

  public  function __construct($xmlFile = NULL)
  {
    if (isset($xmlFile) == true) {
      $this->setXmlFile($xmlFile);
    }

    $this->init();
    parent::__construct(NULL);
  }

  private function init()
  {
    $this->m_tmpTreeName  = array();
    $this->m_depthTree    = 0;
    $this->m_lastElement  = "";
    $this->m_lastType     = "";
  }

  private function convertStringAndLower($utf8UpperString)
  {
    return strtolower(utf8_decode($utf8UpperString));
  }

  private function attachAttributeToNode(&$arrayXml)
  {
    $arrayXml["xmlAttribute"] = array();

    /* Ist die attributes liste nicht leer, hänge an */
    if (count($this->m_lastAttribute) > 0) {
      $arrayXml["xmlAttribute"] = array();

      /* durchlaufe attribute */
      foreach ($this->m_lastAttribute as $key => $value) {
        $key = $this->convertStringAndLower($key);
        
        /* aktivierte typen erkennung, wenn gesetzt */
        if ($key == "type") {
          $this->m_lastType = $this->convertStringAndLower($value);
        }
        /* setze Attribute */
        else {
          $arrayXml["xmlAttribute"][$key] = $value;
        }
      }
    }
  }

  private function attachToTree($depth, &$arrayXml)
  {
    /* Loesche die type angabe, wenn wieder ein element kommt und kein 
      * CDATA */
    $this->m_lastType = "";

    /** node 
     *
     * Add a new Node to tree
     */
    if ($depth == $this->m_depthTree) {
      $this->m_tmpArrTreeName[$depth] = $this->m_lastElement;
      ++$this->m_depthTree;

      /* Testet ob es ein Multibles Element ist */
      $multiNodes = "/^(get|model|event|param|output|form|setget|setsession|field|url)$/";
      if (preg_match($multiNodes, $this->m_lastElement) == 1) {
        $lastNum  = 0;

        /* Wurde schon ein element an den Multiblen zweig gehaengt? */
        if (@array_key_exists("xmlMulti", $arrayXml[$this->m_lastElement]) 
          == true) {

          /* Ja, nehme die index zahl (Multi) und setze +1 */
          $lastNum = $arrayXml[$this->m_lastElement]["xmlMulti"];
          $arrayXml[$this->m_lastElement]["xmlMulti"] = ++$lastNum;
        }
        /*-
         * Nein, erstelle neues index */
        else {
          $arrayXml[$this->m_lastElement] = array("xmlMulti" => 0);
          $lastNum = 0;
        }

        /* haenge nun die attribute an */
        $this->attachAttributeToNode
          ($arrayXml[$this->m_lastElement][$lastNum]);

        $this->m_lastElementNode  = 
                &$arrayXml[$this->m_lastElement][$lastNum];
      }
      /*-
       * Es ist ein ganz normales Element, erstelle es neu und haenge 
       * die attribute an */
      else {
        $arrayXml[$this->m_lastElement] = array();
        $this->attachAttributeToNode($arrayXml[$this->m_lastElement]);
        $this->m_lastElementNode  = &$arrayXml[$this->m_lastElement]; 
      }
    }
    /*- Rekursive teil
     * Wenn noch nicht am ende des baumes angekommen, gehe weiter den
     * baum hinunter (Rekursive) */
    else {
      /* hole multible */
      $tmpMulti = NULL;
      if (array_key_exists("xmlMulti", 
          $arrayXml[$this->m_tmpArrTreeName[$depth]]) == true) {
        $tmpMulti = $arrayXml[$this->m_tmpArrTreeName[$depth]]["xmlMulti"];
      }
      
      /*-
       * Wenn es ein multible Ast ist, haenge es dann den letzen Zweit 
       */
      if (isset($tmpMulti) == true) {
        $this->attachToTree($depth +1, 
          $arrayXml[$this->m_tmpArrTreeName[$depth]][$tmpMulti]);
      }
      /* sonst ganz normal eine Stufe tiefer (rekurse) */
      else {
        $this->attachToTree($depth +1, 
          $arrayXml[$this->m_tmpArrTreeName[$depth]]);
      }
    }
  }

  public function setDefaultType($defType) {
    $this->m_defaultType = $defType;
  }

  protected function startTag($parser, $elementName, $elementAttr)
  {
    $element = $this->convertStringAndLower($elementName);

    /* uberspringe XML Root Namespace, die brauchen wir nicht imm Array! */
    if ($element == "nsconfig" or $element == "nscontroller" or 
        $element == "nsmessage" or $element == "nsurl") {
      return;
    }

    /* setze erkennungsmerkmale und haenge es rekursive ins array */
    $this->m_lastAttribute = $elementAttr;
    $this->m_lastElement   = $element;
    $this->attachToTree(0, $this->m_xmlData);
  }

  protected function endTag($parser, $elementName)
  {
    $element = $this->convertStringAndLower($elementName);

    /* skip xml root namespace */
    if ($element == "nsconfig" or $element == "nscontroller" or
        $element == "nsmessage" or $element == "nsurl") {
      return;
    }

    --$this->m_depthTree;
    $this->m_lastElement  = $element;
  }

  protected function getCData($parser, $elementValue)
  {
    /** type hinting / wird für boolean gebraucht
     */
    $value      = $this->convertStringAndLower($elementValue);

    /* wird für das array */
    $addValue   = NULL;

    /* add default type */
    if (empty($this->m_lastType) == true or isset($this->m_lastType) == false) {
      $this->m_lastType = $this->m_defaultType;
    }

    switch ($this->m_lastType) {
      case "boolean":
        if ($value == "true") {
          $addValue = true;
        }
        else {
          $addValue = false;
        }
        break;
        
      case "string-iso":
        $addValue = utf8_decode($elementValue);
        break;

      case "string-utf8":
        $addValue = $elementValue;
        break;
    }

    /** find his position and attach to array 
     */
    $this->m_lastElementNode["xmlValue"] = $addValue;
  } 
}

?>
