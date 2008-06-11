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

set_include_path(get_include_path() . PATH_SEPARATOR . "./");

require_once("lib/core/CUser.php");
require_once("lib/core/CAccess.php");
require_once("lib/core/CGlob.php");
require_once("lib/core/CParam.php");
require_once("lib/core/CSession.php");
require_once("lib/core/CError.php");

require_once("lib/controller/CSysConfig.php");
require_once("lib/controller/CPage.php");
require_once("lib/controller/CPageUrl.php");
require_once("lib/controller/CLink.php");
require_once("lib/controller/CPlugInSystem.php");
require_once("lib/controller/CPlugInFunctions.php");
require_once("lib/controller/CGet.php");
require_once("lib/controller/CForm.php");

require_once("lib/model/CModel.php");
require_once("lib/model/CDatabase.php");

require_once("lib/view/CView.php");
require_once("lib/view/CViewFunctions.php");

try {
/* Laden der Objekte */
$cSysConfig    = new CSysConfig         ();
$cGlob         = new CGlob              ();
$cPageUrl      = new CPageUrl           ($cSysConfig);
$cSession      = new CSession           ($cSysConfig);
$cDatabase     = new CDatabase          ($cSysConfig);
$cModel        = new CModel             ($cSysConfig);
$cUser         = new CUser              ($cSysConfig);
$cAccess       = new CAccess            ($cSysConfig);
$cPage         = new CPage              ($cSysConfig);
$cGet          = new CGet               ($cSysConfig);
$cForm         = new CForm              ($cSysConfig);
$cEvent        = new CEvent             ($cSysConfig);
$cAuth         = new CAuthentification  ($cSysConfig);
$cLink         = new CLink              ($cSysConfig);
$cPlugSys      = new CPlugInSystem      ($cSysConfig);
$cView         = new CView              ($cSysConfig);

/* Zauber Objekt, kann strings in werte verwandeln */
$cParam        = new CParam ($cSysConfig, $cUser, $cSession, $cGlob, 
                             $cModel, $cForm, $cEvent, $cAccess, $cAuth,
                             $cPage);

/* Für die Plugins, entählt erlaubte funktionen, Funktons Wrapper */
$cPlugFunct    = new CPlugInFunctions ($cModel, $cParam, $cGlob, $cSession);

/* Für die Views befehle, entählt erlaubte funktionen, Funktions Wrapper */
$cViewFunct    = new CViewFunctions   ($cParam, $cModel, $cLink);

/* Lade Module der Standard Objekte */
$cModel->initModel    ($cPage,    $cDatabase, $cEvent,    $cAccess,    $cParam);
$cUser->initUser      ($cSession, $cModel);
$cAccess->initAccess  ($cUser,    $cModel);
$cPage->initPage      ($cUser,    $cAccess,   $cPageUrl);
$cGet->initGet        ($cGlob,    $cPage,     $cParam);
$cForm->initForm      ($cPage,    $cParam,    $cGlob,     $cAccess);
$cEvent->initEvent    ($cParam,   $cPage,     $cAccess);
$cLink->initLink      ($cGlob,    $cUser,     $cAccess,   $cPageUrl);
$cAuth->initAuth      ($cPage,    $cGlob,     $cSession,  $cParam,     $cEvent,
                       $cModel);
$cPlugSys->initPlugIn ($cParam,   $cPage,     $cAccess,   $cPlugFunct);
$cSession->initSession($cPage,    $cParam);
$cView->initView      ($cPage,    $cViewFunct);
$cPageUrl->initUrl    ($cGlob);

  $cDatabase->loadDatabase();

  $cSession->loadSession();
  $cUser->loadUser();
  $cAccess->loadAccess();
  $cGet->loadPageId();
  $cPageUrl->loadUrl();
  $cPage->loadAutoPage();
  $cPage->checkAccess();
  $cEvent->loadEvent();
  $cForm->loadForm();
  $cAuth->loadAuth();
  $cEvent->runEvent(EVENT_AUTH);
  if ($cAuth->loginSuccess() == true) {
    $cUser->reloadUserData();
    $cAccess->reloadAccess();
  }
  if ($cForm->forwardActive() == true) {
    $pageName   = $cForm->getForwardPageName();
    $pageXml    = $cPageUrl->getPageXmlFromUrl($pageName);
    $cPage->reLoadPage($pageXml);
    $cModel->reLoadModel();

  }
  $cGet->loadGet();
  $cModel->loadModel();
  $cModel->retrieveStandardModelSet();
  $cEvent->runEvent(EVENT_MODEL, $cModel);

  $cPlugSys->loadPlugIn();
  $cEvent->runEvent(EVENT_PLUGIN);

  $cGet->setPageGet();
  $cSession->setPageSession();

  $cView->loadView();
  $cView->show();

  $cDatabase->endDatabase();

}
catch (CError $error) {
  print_r($error->m_arrArg);
  echo $error->getMessage();
#  die("Fehler");
throw($error);
}

?>
