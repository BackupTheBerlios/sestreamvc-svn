<?
/*-
 * Copyright (c) 2008 Pascal Vizeli <pvizeli@yahoo.de>
 * Copyright (c) 2008 Serge Ramseier
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

/**
 * Basic class for xml parser (expat)
 *
 * This class implement an xml parser and design.
 * The abstract design of this class make a instance handler impossible.
 * If you use a xml parser, you need inherit this class and implement
 * the abstract function for the parser.
 *
 * This Class don't make any process data! That made you inherit class.
 * All xml element and attribute names are UTF-8 and upper case. All
 * values of element and attribute are UTF-8.
 */
abstract class CXmlParser {

  /** the parser handler */
  private   $m_parser;
  /** a string with contain the URL of xml File */
  private   $m_xmlFile;
  /** is the class ready to use? */
  private   $m_isInit;
  /** use xml cache */
  private   $m_doCache;
  /** a array that contain all xml data. You musst use that in you 
   * inherit class! */
  protected $m_xmlData;
  /** if the class throw an error, it contain the line number where 
   * the parser found a trouble */
  public    $m_errorLine;

  /**
   * Set the xmlFile URL and start the initialise of class.
   *
   * @param $xmlFile  A string that contain the URL to xml File
   */
  public function __construct($xmlFile = NULL)
  {
    if (isset($xmlFile) == true) {
      $this->setXmlFile($xmlFile);
    }

    $this->init(); 
  }

  /**
   * The destructor.
   * It make the same clean things as clean()
   * @see clean()
   */
  public function __destruct()
  {
    if ($this->m_isInit == true) {
      $this->clean();
    }
  }

  /**
   * Initialise function of this class.
   * It start the xml parser and set the callback function for
   * evaluation. Second it set default values for data members.
   */
  private function init()
  {
    /* init xml parser and set callback handler */
    $this->m_parser = xml_parser_create();
    xml_set_element_handler($this->m_parser, 
        array(&$this, "startTag"), array(&$this, "endTag"));
    xml_set_character_data_handler($this->m_parser, array(&$this, "getCData"));

    /** init varable with default values */
    $this->m_xmlData = array();
    $this->m_isInit = true;
  }

  /**
   * Close the xml parser handler and clean all data members. 
   */
  private function clean()
  {
    xml_parser_free($this->m_parser);
    $this->m_xmlFile  = "";
    $this->m_xmlData  = array();
    $this->m_isInit   = false;
  }

  /**
   * It make the class ready for a new xml file.
   */
  public function refreshParser()
  {
    $this->clean();
    $this->init();
  }

  /**
   * Parse the xml file.
   * It open the xml file and read each line as element of an array.
   * On the second part, it give each line to xml parser. 
   */
  public function startParser()
  {
    /* read xml file into array. Each line is a element */
    if (($lines = file($this->m_xmlFile)) == false) {
      throw new CError(ERROR_XML_FILE);
    }

    /* parse each line. Make also error handling */
    foreach ($lines as $lineNum => $lineData) {
      if (xml_parse($this->m_parser, trim($lineData), $is_final) == 0) {
        $this->m_errorLine = $lineNum;
        throw new CError(ERROR_XML_PARSER, array($lineNum +1, 
                                                 $this->getError()));
      }

      /* is the parser finished? */
      if ($is_final == true) {
        break;
      }
    }
  }

  private function getError()
  {
    return xml_error_string(xml_get_error_code($this->m_parser));
  }

  /**
   * Set the URL to xml File.
   * If the file dosn't exists, it throw a exexptions.
   */
  public function setXmlFile($xmlFile)
  {
    if (file_exists($xmlFile) == true) {
      $this->m_xmlFile = $xmlFile;
    }
    else {
      throw new CError(ERROR_XML_FILE_NOT_FOUND, array($xmlFile));
    }
  }

  /**
   * Give the xml data array back.
   *
   * @return    the xml array.
   */
  public function getXmlData()
  {
    return $this->m_xmlData;
  }

  /**
   * Give the xml file back (path and name of file).
   *
   * @return    the xml file name.
   */
  public function getXmlFile()
  {
    return $this->m_xmlFile;
  }

  /**
   * Abstract callback function for open elements/tags.
   * You musst implement this function in your inherit class.
   * The parser call with this function when it start/open a xml element.
   *
   * @param $parser       parser handler
   * @param $elementName  the name of this element
   * @param $elementAttr  a list of attributes from this element
   */
  abstract protected function startTag($parser, $elementName, $elementAttr);

  /**
   * Abstract callback function for end elements/tags.
   * You musst implement this function in your inherit class.
   * The parser call with this function when the elements end.
   *
   * @param $parser       parser handler
   * @param $elementName  the name of the ending elements
   */
  abstract protected function endTag($parser, $elementName);

  /**
   * Abstract callback function for CData of xml elements.
   * You musst implement this function in your inherit class.
   * The parser call with this function when exists CDATA for the
   * element.
   *
   * @param $parser       parser handler
   * @param $elementValue CData of from the element
   */
  abstract protected function getCData($parser, $elementValue);

} /* end class CXmlParser */

?>
