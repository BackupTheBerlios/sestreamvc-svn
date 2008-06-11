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

class CGlob {
  private $m_get;
  private $m_post;
  private $m_tmp;

  public  function __construct()
  {
    /* init */
    $m_get  = array();
    $m_post = array();
    $m_tmp  = array();
  }

  public  function getTmp($name)
  {
    $name  = strtolower($name);
    return $this->m_tmp[$name]["value"];
  }

  public  function setTmp($name, $value, $key)
  {
    /** @see setVar() */
    $this->setVar($this->m_tmp, "TMP", $name, $value, $key);
  }

  public  function getGet($name)
  {
    $name  = strtolower($name);
    return $this->m_get[$name]["value"];
  }

  public  function setGet($name, $value, $key)
  {
    /** @see setVar() */
    $this->setVar($this->m_get, "GET", $name, $value, $key);
  }

  public  function getPost($name)
  {
    $name  = strtolower($name);
    return $this->m_post[$name]["value"];
  }

  /**
   * Setzt wert als POST in Glob.
   *
   * @param $name                 Name der POST Variable
   * @param $value                Wert der POST Variable
   * @param $key                  Schlüssel um den Wert vor umbefugtem 
   *                              überschreiben zu schützen.
   */
  public  function setPost($name, $value, $key)
  {
    /** @see setVar() */
    $this->setVar($this->m_post, "POST", $name, $value, $key);
  }

  private function setVar(&$arr, $flag, &$name, &$value, &$key)
  {
    $name = strtolower($name);

    /* fühge einen neuen wert ins array, wenn noch nicht existiert */
    if (@array_key_exists($name, $arr) == false) {
      $arr[$name]           = array("value" => utf8_encode($value),
                                    "key"   => $key);
    }
    /*-
     * Falls es existiert teste berechtigung zum überschreiben */
    else {
      $gKey = $arr[$name]["key"];

      if ($gKey == $key or empty($gKey)) {
        $arr[$name]["value"] = utf8_encode($value);
      }
      /*-
       * keine berechtigung, gebe fehler aus */
      else {
        throw new CError(ERROR_GLOB_IS_SET, array($flag, $name));
      }
    }
  }

}

?>
