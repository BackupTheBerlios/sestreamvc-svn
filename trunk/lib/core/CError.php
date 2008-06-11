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

require_once("lib/core/Error.php");
require_once("lib/core/CSecure.php");

class CError extends Exception {
  public  $m_errCode;
  public  $m_arrArg;
  private $m_kErrorStr;
  private $m_kSearchReplace;

  public function __construct($errCode, $arrReplace = NULL)
  {
    $this->m_errCode    = $errCode;
    $this->m_arrArg     = $arrReplace;

    /* lade meldungen */
    $this->initErrorCode();

    /* erstelle und ersetze variablen mit wert aus array wenn nicht null */
    $message   = NULL;
    if (isset($arrReplace) == true) {
      $this->createRegexReplace($arrReplace);
      $message = preg_replace($this->m_kSearchReplace, 
                              $arrReplace, $this->m_kErrorStr[$errCode]);
    }
    else {
      $message = $this->m_kErrorStr[$errCode];
    }

    parent::__construct($message);
  }

  private function createRegexReplace(&$arrReplace)
  {
    for ($i = 0; $i < count($arrReplace); ++$i) {
      $arrReplace[$i] = CSecure::filterData($arrReplace[$i], 
                                            "encodehtmlentities"); 
    }
  }

  private function initErrorCode()
  {
    $this->m_kErrorStr = array(
  ERROR_XML_FILE              => "",
  ERROR_XML_PARSER            => "XML Parser Error on Line %1%",
  ERROR_INVALID_XML_MODEL     => "XML File '%1%' isn't a valid model!",
  ERROR_XML_FILE_NOT_FOUND    => "File not found: '%1%'",
  ERROR_SQL_CONNECT_FALSE     => "Can't connect to sql server",
  ERROR_SQL_DISCONNECT        => "",
  ERROR_SQL_QUERY             => "DB Querie: %1% \n Command: %2%",
  ERROR_NO_DATABASE_SET       => "",
  ERROR_UNKNOWN_USER          => "User have no valid user account on database!",
  ERROR_CONFIG_XML_NOT_FOUND  => "XML config file '%1%' was not found!",
  ERROR_CONFIG_XML_ERROR      => "Internal XML syntax error! Error: '%1%'",
  ERROR_OPEN_SESSFILE         => "Can't open Session File '%1%'",
  ERROR_WRITE_SESSFILE        => "Can't write Session File '%1%'",
  ERROR_DELETE_SESSFILE       => "Can't delete Session File '%1%'",
  ERROR_INVALID_SESSION       => 
      "Invalid Session from IP '%1%'. Possible a hack!",
  ERROR_SET_COOKIE            => "Can't set Cookie on client browser!",
  ERROR_OPEN_CACHE            => "Can't create or open cache file '%1%'",
  ERROR_INVALID_CACHE         => "Invalid cache file '%1%'",
  ERROR_WRITE_CACHE           => "Can't write cache file '%1%'",
  ERROR_PARAM_INVALID         => "Param '%1%' is a invalid part of '%2%",
  ERROR_MODELSET_INIT         => "Error on model '%1%'. please first init!",
  ERROR_GLOB_IS_SET           => "%1% with name '%2%' is all ready set!",
  ERROR_PARAM_SYNTAX          => "Param syntax error '%1%'",
  ERROR_PAGE_REQUIRELOGIN     => "You musst login for this page!",
  ERROR_PAGE_ACCESSDENY       => "You don't have access on this page!",
  ERROR_SECURE_VALID          => "'%1%' is false for value '%2%'!",
  ERROR_GET_VALID             => "GET '%1%' invalid with error: '%2%'",
  ERROR_MODEL_ACCESS          => 
      "Model '%1%': user have no access on Rule '%2%'!",
  ERROR_MODEL_EVENT           => "Event '%1%' unknown!",
  ERROR_MODEL_NAME            => "ModelSet with Name '%1%' is not set!",
  ERROR_MODEL_EXISTS          => "Model '%1%' all ready exists!",
  ERROR_FORM_NO_ERROR         => "Field '%1%' don't have a error!",
  ERROR_FORMSET_UNKOWN        => "FormSet '%1%' isn't defined!",
  ERROR_MODELSET_ITER         => 
       "Init first the iterator with nextIter! Model: '%1%'",
  ERROR_MODELSET_FIELD        => "Field '%2%' don't exists in Model '%1%'",
  ERROR_MODELSET_NOT_FOUND    => "Model '%1%' not found in '%2%'",
  ERROR_MODELSET_MODEL_FILE   => "Modelfile '%2%' not found! ModelSet '%1%'",
  ERROR_SESSION_KEY           => "The controll key for '%1%' is false!",
  ERROR_URL_NOT_FOUND         => "Page '%1%' not found!",
  ERROR_URL_NO_XML            => "Page '%1%' don't have a xml file!",
  ERROR_VIEW_NOT_DEFINED      => 
       "Controll file '%1%' didn't have a view file defined!",
  ERROR_VIEW_FILE_EXISTS      => "View File '%1%' not found!",
  ERROR_VIEW_TEMPLATE         => "Template system '%1%' unknown!"
);

   $this->m_kSearchReplace = array(
      "/%1%/",
      "/%2%/",
      "/%3%/",
      "/%4%/",
      "/%5%/",
      "/%6%/"
    );
  }

}

?>
