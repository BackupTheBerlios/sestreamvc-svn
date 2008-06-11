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

require_once("lib/core/CMessage.php");

class CMessageForm extends CMessage {
  private $m_errFormMessXml;

  public  function __construct(CConfig &$config, $fileName)
  {
    parent::__construct($config, $fileName);
    $this->errFormMessXml   = array();
  }

  public  function loadMessage()
  {
    parent::loadMessage();

    /* erstelle aus der message eine brauchbare meldung */
    $this->createAssoc($this->getMessageData());
  }

  private function createAssoc(&$messXml)
  {
    foreach ($messXml as $flag => &$errmsg) {
      for ($i = 0; $i <= $errmsg["field"]["xmlMulti"]; ++$i) {
        $name  = &$errmsg["field"][$i]["xmlAttribute"]["name"];
        $this->m_errFormMessXml[$flag][$name] = &$errmsg["field"][$i];
      }
    }
  }

  public  function getFormMessage($flag, $fieldName)
  {
    return $this->m_errFormMessXml[$flag][$fieldName]["de"]["xmlValue"];
  }

}

?>
