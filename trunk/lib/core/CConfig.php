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

require_once("lib/controller/CXmlController.php");

class CConfig {
  public $m_config;

  public function loadConfig($xmlFile)
  {
    try {
      $xmlData = new CXmlController($xmlFile);
      $xmlData->setDefaultType("string-iso");

      $xmlData->startParser();
      $this->m_config = $xmlData->getXmlData();
    }
    /**
     * Exceptions handling between parser error and config error */
    catch (CError $error) {

      /* switch erroCode (lib/core/Error.php) */
      switch ($error->m_errCode) {
        case ERROR_XML_FILE_NOT_FOUND :
        case ERROR_XML_FILE           :
          throw new CError(ERROR_CONFIG_XML_NOT_FOUND, 
                           array($xmlFile, $error->m_errCode, 
                                 $error->m_arrArg));

        default                       :
          throw new CError(ERROR_CONFIG_XML_ERROR, 
                           array($xmlFile, $error->m_errCode, 
                                 $error->m_arrArg));
      }
    }
  }

  public function &getConfTree($treeName1, $treeName2 = NULL)
  {
    if (isset($treeName2) == false) {
      return $this->m_config[$treeName1];
    }

    return $this->m_config[$treeName1][$treeName2];
  }

}

?>
