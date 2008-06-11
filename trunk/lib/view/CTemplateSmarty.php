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

define("SMARTY_DIR",                     "app/smarty/");

require_once(SMARTY_DIR . "Smarty.class.php");

require_once("lib/view/CViewFunctions.php");
require_once("lib/view/ITemplate.php");

class CTemplateSmarty implements ITemplate {
  private $m_cConfig;
  private $m_cViewFunct;
  private $m_viewFile;
  private $m_cache;
  private $m_xmlConf;
  private $m_smarty;

  public  function __construct(CConfig &$config, CViewFunctions &$viewFunct)
  {
    $this->m_cConfig       = &$config;
    $this->m_confXml       = &$config->getConfTree("viewconfig");
    
    $this->m_cViewFunct    = &$viewFunct;

    /* init smarty */
    $this->m_smarty     = new Smarty();
    $this->m_smarty->template_dir = 
        &$this->m_confXml["templatedir"]["xmlValue"] . "/";
    $this->m_smarty->compile_dir  = 
        &$this->m_confXml["cachedir"]["xmlValue"] . "/";
    $this->m_smarty->cache_dir    = 
        &$this->m_confXml["cachedir"]["xmlValue"] . "/";

  }

  public  function loadTemplate(&$viewFile, $cache)
  {
    $this->m_smarty->caching  = &$cache;
    $this->m_viewFile         = &$viewFile;
  }

  public  function show()
  {
    /* extrahiere "datei.end" */
    $file  = preg_replace("/(?:.*\/)?(\w+\.\w{3})$/", "$1", $this->m_viewFile);

    /* hänge funktions wrapper an */
    $this->m_smarty->assign_by_ref("MVC", $this->m_cViewFunct);

    /* zeige seite */
    $this->m_smarty->display($file);
  }

}

?>
