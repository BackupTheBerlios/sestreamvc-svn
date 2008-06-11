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

require_once("lib/core/Path.php");
require_once("lib/core/CError.php");

require_once("lib/core/IPlugInFilter.php");

class CPlugInFilter implements IPlugInFilter {
  private $m_cClass;
  private $m_include;
  private $m_argv;

  public  function __construct($conObj)
  {
    $arrObj   = preg_split("/::/", $conObj);

    /* object */
    $this->m_cClass   = $arrObj[1];

    /* generate argv */
    $this->m_argv     = array();
    for ($i = 2; $i < count($arrObj); ++$i) {
      $this->m_argv[] = $arrObj[$i];
    }

    /* generate include Path/file */
    $this->m_include  = PLUGIN_FILTER_PATH . $arrObj[1] . 
      "/" . $arrObj[1] .".php";
  } 

  /**
   * load and execute Plguin::validData
   *
   * @param $value      Value for validation test
   * @param $argc       Not use in thise case
   * @return            TRUE or FALSE from PlugIn
   */
  public  static function validData($value, $argc = array()) {
    require_once($this->m_include);

     /* ab php version 5.3 */
    //return $this->m_cClass::validData($value, $this->m_argc);
  }

  /**
   * laod and execute Plugin::filterData
   *
   * @param &$value       A pointer to value use for filter
   * @param $argc       Not use in thise case
   */
  public  static function filterData(&$value, $argc = array()) {
    require_once($this->m_include);

   /* php version 5.3 */
    //return $this->m_cClass::filterData($value, $this->m_argc); 
  }
}

?>
