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
require_once("lib/core/CCache.php");
require_once("lib/core/CUser.php");
require_once("lib/core/CConfig.php");

require_once("lib/model/CModelSet.php");
require_once("lib/model/CModel.php");
require_once("lib/model/CXmlModel.php");

class CAccess {
  private $m_accessData;
  private $m_cConfig;
  private $m_confXml;
  private $m_cUser;
  private $m_cModel;

  public  function __construct(CConfig &$config)
  {
    $this->m_cConfig    = &$config;
    $this->m_confXml    = &$config->getConfTree("access");
    $this->m_accessData = array();
  }

  public  function initAccess(CUser &$user, CModel &$model)
  {
    $this->m_cUser      = &$user;
    $this->m_cModel     = &$model;
  }

  public  function reloadAccess()
  {
    $this->m_accessData = array();
    $this->loadAccess();
  }

  public  function loadAccess()
  {
    /* init a modset with in config defined values */
    $cModSet    = new CModelSet($this->m_confXml["model"][0]);

    /* retrieve data  and generate m_accessData */
    $this->m_cModel->retrieveDataFromModelSet($cModSet);
    $this->createAccessFromRetrieveData($cModSet->getData());
  }
  
  /**
   * Create access table with access data from database.
   *
   * Access Array Style (create with this function!):
   * $this->m_accessData[ZONE] = TRUE¦FALSE;
   *
   * Data from database need style:
   * [ROW] [zone]  = Zonen Name
   * [ROW] [user]  = UserID, if set access over userid
   * [ROW] [group] = GroupID, if set access over groupid
   * [ROW] [allow] = Enable or Disable, default set Disable!
   *
   * @param &$arrAccess     Data from database
   */
  private function createAccessFromRetrieveData(&$arrAccess)
  {
    /* "makros" */
    $fieldZone    = &$this->m_confXml["zonefield"]["xmlValue"];
    $fieldAllow   = &$this->m_confXml["allowfield"]["xmlValue"];
    $ifAllow      = &$this->m_confXml["allowvalue"]["xmlValue"];
    $fieldUser    = NULL;
    $fieldGroup   = NULL;

    /* replace NULL value */
    if (array_key_exists("userfield", $this->m_confXml) == true) {
      $fieldUser  = &$this->m_confXml["userfield"]["xmlValue"];
    }
    if (array_key_exists("groupfield", $this->m_confXml) == true) {
      $fieldGroup = &$this->m_confXml["groupfield"]["xmlValue"];
    }

    for ($i = 0; $i < count($arrAccess); ++$i) {
      /* init defaults */
      $zone       = &$arrAccess[$i][$fieldZone];
      $allow      = &$arrAccess[$i][$fieldAllow];

      /* existiert benutzer feld? */
      $user       = NULL;
      if (isset($fieldUser) == true) {
        $user     = &$arrAccess[$i][$fieldUser];
      }
      /* existiert gruppen feld? */
      $group      = NULL;
      if (isset($fieldGroup) == true) {
        $group    = &$arrAccess[$i][$fieldGroup];
      }

      /**
       * if zone is allready set, set it only if it is user defined */
      if (isset($this->m_accessData[$zone]) == true) {
        if (isset($user) == true) {
          /* allow zone for that user */
          if ($allow == $ifAllow) {
            $this->m_accessData[$zone] = true;
          }
          else {
            /* disable access on this zone */
            $this->m_accessData[$zone] = false;
          }
        }
      }
      /*-
       * Wenn benutzer berechtigung hat */
      elseif (isset($user) == true) {
        if ($allow == $ifAllow) {
          $this->m_accessData[$zone]  = true;
        }
        else {
          $this->m_accessData[$zone]  = false;
        }
      }
      /*-
       * allow zone for this user because user or group have access */
      elseif (isset($group) == true) {
        /* allow zone for that user */
        if ($allow == $ifAllow) {
          $this->m_accessData[$zone] = true;
        }
        else {
          /* disable access on this zone */
          $this->m_accessData[$zone] = false;
        }
      }
    } /* end for */
  }

  /**
   * Check if user have access on rule.
   *
   * @param $rule       Name of the access rule
   * @return            TRUE if user have access or FALSE
   */
  public  function haveAccessOnRule($rule)
  {
    $rule   = strtolower($rule);

    /* if tag is empty, it exists no restriction */
    if (empty($rule)) {
      return true;
    }

    /* if access set on rule? */
    if ($this->m_accessData[$rule] == true) {
      return true;
    }

    return false;
  }

}

?>
