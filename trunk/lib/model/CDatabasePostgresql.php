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

require_once("lib/core/CConfig.php");
require_once("lib/core/CError.php");

require_once("lib/model/IDatabaseConnection.php");

class CDatabasePostgresql implements IDatabaseConnection {
  private $m_pgConStr;
  private $m_cConfig;
  private $m_confXml;
  private $m_pgCon;
  private $m_isConnect;
  private $m_isPersist;

  public function __construct(CConfig &$config)
  {
    /* make config settings */
    $this->m_cConfig      = &$config;
    $this->m_confXml      = &$this->m_cConfig->
                              getConfTree("database", "postgresql");

    /* create Pg connection string */
    $this->m_pgConStr  = "host='"     . 
      $this->m_confXml["host"]["xmlValue"]      . "' ";
    $this->m_pgConStr .= "port='"     . 
      $this->m_confXml["port"]["xmlValue"]      . "' ";
    $this->m_pgConStr .= "dbname='"   . 
      $this->m_confXml["database"]["xmlValue"]  . "' ";
    $this->m_pgConStr .= "user='"     . 
      $this->m_confXml["user"]["xmlValue"]      . "' ";
    $this->m_pgConStr .= "password='" . 
      $this->m_confXml["password"]["xmlValue"]  . "'";

    $this->m_isConnect = false;
    $this->m_isPersist = $this->m_confXml["persistent"]["xmlValue"];
  }

  public function __destruct()
  {
    if ($this->isConnected() == true) {
      $this->disconnectFromDb();
    }
  }

  public function connectToDb()
  {
    if ($this->m_isPersist == true) {
      /* connect with persisten db connection */
      if (($this->m_pgCon = @pg_pconnect($this->m_pgConStr)) == false) {
        throw new CError(ERROR_SQL_CONNECT_FALSE, array($this->getErrorStr()));
      }
    }
    else {
      /* connect with static db connecion */
      if (($this->m_pgCon = @pg_connect($this->m_pgConStr)) == false) {
        throw new CError(ERROR_SQL_CONNECT_FALSE, array($this->getErrorStr()));
      }
    }
    $this->m_isConnect = true;
  }

  public function disconnectFromDb()
  {
    if ($this->m_isConnect == true and $this->m_isPersist == false) {
      if (pg_disconnect($this->m_pgCon) == false) {
        throw new CError(ERROR_SQL_DISCONNECT, array($this->getErrorStr()));
      }
    }
  }

  public function isConnected()
  {
    return $this->m_isConnect;
  }

  public function convertXmlToSql(&$xmlArray)
  {
    $sqlResl = "";

    switch ($xmlArray["xmlBasicSql"]) {
      case "SELECT" :
        $sqlResl .= "SELECT "  . $xmlArray["view"];
        $sqlResl .= " FROM "   . $xmlArray["table"];
        if (array_key_exists("where", $xmlArray) == true) {
          $sqlResl .= " WHERE "  . $xmlArray["where"];
        }
        if (array_key_exists("last", $xmlArray) == true) {
          $sqlResl .= " "        . $xmlArray["last"];
        }
        $sqlResl .= ";";
        break;

      case "INSERT" :
        $sqlResl .= "INSERT INTO " . $xmlArray["table"];
        if (array_key_exists("order", $xmlArray) == true) {
          $sqlResl .= " ( "          . $xmlArray["order"] . " )";
        }
        $sqlResl .= " VALUES ( "   . $xmlArray["values"] . " );";
        break;

      case "UPDATE" :
        $sqlResl .= "UPDATE "     . $xmlArray["table"];
        $sqlResl .= " SET "       . $xmlArray["set"];
        if (array_key_exists("where", $xmlArray) == true) {
          $sqlResl .= " WHERE "     . $xmlArray["where"] . ";";
        }
        $sqlResl .= ";";
        break;

      case "RAW" :
        $sqlResl .= $xmlArray["sql"] . ";"; 
        break;
    }

    return $sqlResl;
  }

  private function sendCmd(&$sqlCmd)
  {
    if (($result = @pg_query($this->m_pgCon, $sqlCmd)) == false) {
      throw new CError(ERROR_SQL_QUERY, array($this->getErrorStr(), $sqlCmd));
    }

    return $result;
  }

  public function fetchData(&$sqlCmd)
  {
    $dbResult  = $this->sendCmd($sqlCmd);

    $resultArr = pg_fetch_assoc($dbResult);

    pg_free_result($dbResult);
    return $resultArr;
  }

  public function fetchDataTable(&$sqlCmd)
  {
    $reslTable  = array();
    $dbResult   = $this->sendCmd($sqlCmd);

    /* create table list from result */
    while ($assoc  = pg_fetch_assoc($dbResult)) {
      $reslTable[] = $assoc;
    }
    
    pg_free_result($dbResult);
    return $reslTable;
  }

  public function sendCommand(&$sqlCmd)
  {
    $this->sendCmd($sqlCmd);
  }

  private function getErrorStr()
  {
    return pg_last_error($this->m_pgCon);
  }

}

?>
