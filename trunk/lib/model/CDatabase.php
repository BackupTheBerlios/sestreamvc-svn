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

require_once("lib/core/CError.php");
require_once("lib/core/CConfig.php");

require_once("lib/model/CDatabasePostgresql.php");

class CDatabase {
  private $m_dbObj;
  private $m_cConfig;
  private $m_confXml;

  public function __construct(CConfig &$config)
  {
    /* make config setings */
    $this->m_cConfig    = &$config;
    $this->m_confXml    = &$this->m_cConfig->getConfTree("database", "core");
  }

  public function loadDatabase()
  {
    /* set database connection object */
    switch (strtolower($this->m_confXml["using"]["xmlValue"])) {
      case "postgresql" :
        $this->m_dbObj = new CDatabasePostgresql($this->m_cConfig);
        break;

      default:
         throw new CError(ERROR_NO_DATABASE_SET);
    }

    /* connect */
    $this->m_dbObj->connectToDb();
  }

  public  function endDatabase()
  {
    /* is connect */
    if ($this->m_dbObj->isConnected() == true) {
      try {
        /* disconnect */
        $this->m_dbObj->disconnectFromDb();
      }
      catch (CError $error) {

        /* throw the exceptions only if you use the database connection
         * in the strict mode!
         */
        if ($this->m_confXml["xmlValue"]["strict"] == true) {
          throw $error;
        }
      }
    }
  }

  public  function createStatment(&$sqlXml)
  {
    return $this->m_dbObj->convertXmlToSql($sqlXml);
  }

  public  function retrieveData(&$sqlStatment, $isTable)
  {
    /**
     * if it is a table, fetch table */
    if ($isTable == true) {
      return $this->m_dbObj->fetchDataTable($sqlStatment);
    }
    /**
     * fetch data, if it isn't a table */
    else {
      return $this->m_dbObj->fetchData($sqlStatment);
    }
  }
}

?>
