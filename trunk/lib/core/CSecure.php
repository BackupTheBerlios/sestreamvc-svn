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
require_once("lib/core/CPlugInFilter.php");

class CSecure {

  public  static function validData($value, $valid)
  {
    $arrVal   = preg_split("/;/", $valid);

    /*-
     * test all valid param into loop */
    for ($i = 0; $i < count($arrVal); ++$i) {

      /*-
       * use regex */
      if (preg_match("/^regex=(true|false)::.*$/", $arrVal[$i]) == 1) {
        $arrReg   = preg_split("/::/", $arrVal[$i]);
        $resl     = $arrReg[1] == "true" ? 0 : 1;
        if (preg_match($arrReg[2], $value) == $resl) {
          throw new CError(ERROR_SECURE_VALID, array($arrVal[$i], $value));
        }
        continue;
      }

      /*-
       * use minimum and maximum len for check */
      if (preg_match("/(minlen|maxlen)=\d/", $arrVal[$i]) == 1) {
        $data   = preg_split("/=/", $arrVal[$i]);

        /* test of minimum len */
        if ($data[0] == "minlen") {
          if (strlen($value) < $data[1]) {
            throw new CError(ERROR_SECURE_VALID, array("minlen", $value, 
                                                       $arrVal[$i]));
          }
        }
        /* test of maximum len */
        else {
          if (strlen($value) > $data[1]) {
            throw new CError(ERROR_SECURE_VALID, array("maxlen", $value,
                                                       $arrVal[$i]));
          }
        }
      }

      /*-
       * Ist gleich als */
      if (preg_match("/eq=\w+/", $arrVal[$i]) == 1) {
        $word   = preg_replace("/eq=(\w+)/", "$1", $arrVal[$i]);
        if ($value != $word) {
          throw new CError(ERROR_SECURE_VALID, array("eq", $value, 
                                                     $arrVal[$i]));
        }
      }

      /*-
       * use FilterPlugin Object */
      if (preg_match("/plugin::\w*/", $arrVal[$i]) == 1) {
        $cPluginFilt  = new CPlugInFilter($arrVal[$i]);
        $cPluginFilt->validData($value);
        continue;
      }

      /*-
       * Core regex tests */
      switch (strtolower($arrVal[$i])) {
        /* Integer */
        case "integer" :
          if (preg_match("/^\d+$/", $value) == 0) {
            throw new CError(ERROR_SECURE_VALID, array("integer", $value));
          }
          break;

        case "string" :
          if (preg_match("/^[^\t\n\f\r]{1,}$/", $value) == 0) {
            throw new CError(ERROR_SECURE_VALID, array("string", $value));
          }
          break;

        case "word" :
          if (preg_match("/^[^\s]+$/", $value) == 0) {
            throw new CError(ERROR_SECURE_VALID, array("word", $value));
          }
          break;

        case "path" :
          if (preg_match("/^[A-Za-z_0-9 .\/\\]{1}$/", $value) == 0) {
            throw new CError(ERROR_SECURE_VALID, array("path", $value));
          }
          break;

        case "!path" :
          if (preg_match("/[.\/\\]/", $value) == 1) {
            throw new CError(ERROR_SECURE_VALID, array("_path", $value));
          }
          break;

        case "file"  :
          if (preg_match("/^[^\/\\]*\.\w{3,4}$/", $value) == 0) {
            throw new CError(ERROR_SECURE_VALID, array("file", $value));
          }
          break;

        case "email" :
          if (preg_match("/^[-_\.A-Za-z0-9_]+@(?:[-A-Za-z0-9]{2,}\.)(?:[a-zA-z]{2,3}|info|museum)$/", $value) == 0) {
            throw new CError(ERROR_SECURE_VALID, array("email", $value));
          }
          break;

        case "password" :
          if (preg_match(PASSWORD_REGEX, $value) == 0) {
            throw new CError(ERROR_SECURE_VALID, array("password", $value));
          }
          break;

        case "true"   :
          if ($value != true) {
            throw new CError(ERROR_SECURE_VALID, array("true", $value));
          }
          break;

        case "false"   :
          if ($value != false) {
            throw new CError(ERROR_SECURE_VALID, array("false", $value));
          }
          break;
      }
    }
    
    return true;
  }

  /**
   * Input/Output filter
   *
   * filter the Output and input data stream with filter values.
   *
   * @param $filter         delimiter string with ';' inlcudes filter 
   *                        name   rules. For regex use syntax: 
   *                        regex::regexExpresSearch::regexExpresReplace.
   *                        For a plugin use filter syntax:
   *                        PlugInName[::ARGC0][::ARGC1]
   * @param &$value         A pointer to value. Use filters on this. 
   */
  public  static function filterData(&$value, $filter)
  {
    $arrVal   = preg_split("/;/", $filter);

    /*-
     * filter all value into loop */
    for ($i = 0; $i < count($arrVal); ++$i) {

      /*-
       * use regex */
      if (preg_match("/^regex=.*::.*/", $arrVal[$i]) == 1) {
        $arrReg   = preg_split("/::/", 
                              preg_replace("/^regex=(.*)$/", "$1",$arrVal[$i]));
        $value    = preg_replace($arrReg[0], $arrReg[1], $value);
        continue;
      }

      /*-
       * use FilterPlugin Object */
      if (preg_match("/plugin::\w*/", $arrVal[$i]) == 1) {
        $cPluginFilt  = new CPlugInFilter($arrVal[$i]);

        /* test PluginFIler::filterDate(value) */
        if ($cPluginFilt->filterData($value) == false) {
          throw new CError(ERROR_SECURE_FILTER, array($arrVal[$i], $value));
        }
        continue;
      }

      /*-
       * Core filter set */
      switch (strtolower($arrVal[$i])) {
        /* strip tags */
        case "strip"              :
          $value = @strip_tags($value);
          break;

        /* convert to htmlEntities */
        case "encodehtmlentities" :
          $value = @htmlentities($value, ENT_QUOTES, "UTF-8");
          break;

        /* decode htmlEntities */
        case "decodehtmlentities" :
          $value = @html_entity_decode($value, ENT_QUOTES, "UTF-8");
          break;
        
        /* decode rawUrl */
        case "decoderawurl"       :
          $value = @rawurldecode($value);
          break;

        /* encode rawUrl */
        case "encoderawurl"       :
          $value = @rawurlencode($value);
          break;

        /* decode Base64 */
        case "decodebase64"       :
          $value = @base64_decode($value);
          break;

        /* encode Base64 */
        case "encodebase64"           :
          $value = @base64_encode($value);
          break;

        case "encodehtmlspecialchars" :
          $value = @htmlspecialchars($value, ENT_COMPAT, "", false);
          break;

        case "decodehtmlspecialchars" :
          $value = @htmlspecialchars($value, ENT_COMPAT);
          break;

        case "md5"                    :
          $value = @md5($value);
          break;

        default :
          throw new CError(ERROR_SECURE_UNKNOWN, array("encoderawurl", $value));
      }
    }

    return $value;
  }

  /**
   * Make sql string secure for injects.
   *
   * @param &$valSql    string for sql database
   */
  public  static function encodeSqlInject(&$valSql)
  {
    $valSql = addslashes($valSql);
  }

  /**
   * Reconstruct the original string.
   *
   * @param &$valSql      String to decode
   */
  public  static function decodeSqlInject(&$valSql)
  {
    $valSql = stripslashes($valSql);
  }

  /**
   * Guard function of input streams.
   * This filter is using for all input streams. You musst
   * explicite deactivate this guard for enable CSS.
   * @param &$value      Use Guard on this value.
   */
  public  static function guard(&$value)
  {
    $value  = strip_tags($value);
  }

}

?>
