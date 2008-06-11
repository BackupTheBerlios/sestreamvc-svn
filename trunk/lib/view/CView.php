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

require_once("lib/core/CConfig.php");
require_once("lib/core/CError.php");

require_once("lib/view/CTemplateSmarty.php");
require_once("lib/view/CViewFunctions.php");

class CView {
  private $m_cConfig;
  private $m_cPage;
  private $m_cTemplate;
  private $m_cViewFunct;
  private $m_confXml;
  private $m_pageXml;
  private $m_viewFile;
  private $m_cache;
  private $m_templateDir;

  public  function __construct(CConfig $config)
  {
    $this->m_cConfig      = &$config;
    $this->m_confXml      = &$config->getConfTree("viewconfig");

    $this->m_templateDir  = $this->m_confXml["templatedir"]["xmlValue"] . "/";
  }

  public  function initView(CPage &$page, CViewFunctions &$viewFunct)
  {
    $this->m_cPage        = &$page;
    $this->m_cViewfunct   = &$viewFunct;

    /* erstelle Template Objekt */
    $templateSys = $this->m_confXml["templatesystem"]["xmlValue"];
    switch (strtoupper($templateSys)) {
      case "SMARTY"   :
        $this->m_cTemplate  = new CTemplateSmarty($this->m_cConfig, $viewFunct);
        break;
     
      default         :
        throw new CError(ERROR_VIEW_TEMPLATE, array($templateSys));
    }
  }

  public  function loadView()
  {
    $this->m_pageXml        = &$this->m_cPage->getView();

    /* view file / existiert dies? */
    if (@array_key_exists("file", $this->m_pageXml) == false) {
      throw new CError(ERROR_VIEW_NOT_DEFINED, 
                       array($this->m_cPage->getFileName()));
    }

    /* gebe file */
    $this->m_viewFile     = $this->m_templateDir . 
                            $this->m_pageXml["file"]["xmlValue"];

    if (file_exists($this->m_viewFile) == false) {
      throw new CError(ERROR_VIEW_FILE_EXISTS, array($this->m_viewFile));
    }

    /*-
     *setze cache, config cache off wird als höchstes bewertet */
    if ($this->confXml["cache"]["xmlValue"] == true) {
      if (array_key_exists("cache", $this->m_pageXml) == true) {
        $this->m_cache    = &$this->m_pageXml["cache"]["xmlValue"];
      }
    }
    else {
      $this->m_cache      = false;
    }
    $this->m_cTemplate->loadTemplate($this->m_viewFile, $this->m_cache);
  }

  public  function show()
  {
    $this->m_cTemplate->show();
  }


}

?>
