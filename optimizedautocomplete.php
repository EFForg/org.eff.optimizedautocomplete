<?php

require_once 'optimizedautocomplete.civix.php';

function optimizedautocomplete_civicrm_buildForm($formName, $form) {
  if($formName == 'CRM_Admin_Form_Setting_Search') {
    $resources = CRM_Core_Resources::singleton();
    $resources->addScriptFile('org.eff.optimizedautocomplete', 'resources/search.js');
  }
}

function optimizedautocomplete_civicrm_contactListQuery(&$query, $name, $context, $id) {
  // if other params are passed, the return without any changes
  if($_GET['context'] != 'navigation' 
    && $_GET['context'] != 'softcredit'
    && $_GET['context'] != 'relationship') {
    return;
  }

  // create temp table for storing result set
  $random_num = md5(uniqid());
  $quick_search_temp_table = "civicrm_temp_quick_search_{$random_num}";
  $sql = "
    CREATE TEMPORARY TABLE {$quick_search_temp_table} ( 
      contact_id int unsigned,
      data varchar(255),
      PRIMARY KEY ( contact_id )
    ) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci";
  CRM_Core_DAO::executeQuery($sql);

  // find the match from contact table
  $sql = "
    SELECT id, sort_name
    FROM civicrm_contact 
    WHERE sort_name LIKE '$name%'
    AND is_deleted = 0
    ORDER BY sort_name LIMIT 0, 25";
  $dao = CRM_Core_DAO::executeQuery($sql);
  $result = array();
  while($dao->fetch()) {
    $result[$dao->id] = $dao->sort_name;
  }
  
  // if matches found less than 10, try to match from email table
  if($dao->N < 10) {
    // find the match from email table
    $sql = "
      SELECT contact_id, email 
      FROM civicrm_email 
      WHERE email LIKE '$name%' 
      ORDER BY email 
      LIMIT 0, 100";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $email_result = array();
    while($dao->fetch()) {
      if(!array_key_exists( $dao->contact_id, $result)) {
        $email_result[$dao->contact_id] = $dao->email;
      }
    }

    if(!empty($email_result)) {
      // remove deleted contacts from $email_result
      $ids = array();
      foreach($email_result as $id => $value) { $ids[] = $id; }
      $ids = implode(", ", $ids);
      $sql = "
        SELECT id 
        FROM civicrm_contact 
        WHERE is_deleted = 1
        AND id IN ( $ids )";
      $dao = CRM_Core_DAO::executeQuery($sql);
      while($dao->fetch()) {
        if(isset($email_result[$dao->id])) {
          unset($email_result[$dao->id]);
        }
      }
      
      // merge $email_result into $result
      foreach($email_result as $id => $value) {
        $result[$id] = $value;
      }
    }
  }

  // find email addresses, cities for these contacts
  $contacts = array();
  $ids = array();
  foreach($result as $id => $value) { $ids[] = $id; }
  $ids = implode(", ", $ids);
  
  // find names
  $names = array();
  $sql = "SELECT id, sort_name FROM civicrm_contact WHERE id IN ( $ids ) ORDER BY sort_name";
  $dao = CRM_Core_DAO::executeQuery($sql);
  while($dao->fetch()) {
    $names[$dao->id] = $dao->sort_name;
  }

  // find email addresses
  $emails = array();
  $sql = "
    SELECT civicrm_contact.id, civicrm_email.email
    FROM civicrm_contact, civicrm_email
    WHERE civicrm_contact.id = civicrm_email.contact_id 
    AND civicrm_email.is_primary = 1 
    AND civicrm_contact.id IN ( $ids )"; 
  $dao = CRM_Core_DAO::executeQuery($sql);
  while($dao->fetch()) {
    $emails[$dao->id] = $dao->email;
  }

  // find cities
  $cities = array();
  $sql = "
    SELECT civicrm_contact.id, civicrm_address.city 
    FROM civicrm_contact, civicrm_address 
    WHERE civicrm_contact.id = civicrm_address.contact_id 
    AND civicrm_address.is_primary = 1 
    AND civicrm_contact.id IN ( $ids )"; 
  $dao = CRM_Core_DAO::executeQuery($sql);
  while($dao->fetch()) {
    $cities[$dao->id] = $dao->city;
  }
  foreach($result as $id => $value) {
    $display = "";
    if(array_key_exists($id, $names)) {
      $display .= $names[$id]." :: ";
    }
    if(array_key_exists($id, $emails)) {
      $display .= $emails[$id]." :: ";
    }
    if(array_key_exists($id, $cities)) {
      $display .= $cities[$id]." :: ";
    }
    $contacts[$id] = ltrim(rtrim($display, " :: "), " :: ");
  }

  // insert into temp table
  if(!empty($contacts)) {
    foreach($contacts as $cid => $value) {
      $value = addslashes($value);
      $insert_sql[] = "( {$cid}, '{$value}' )";
    }
    $insert_values = implode( ',', $insert_sql );
    $sql = "INSERT INTO {$quick_search_temp_table} (contact_id, data) VALUES {$insert_values}";
    CRM_Core_DAO::executeQuery($sql);
  }

  // return final query
  $query = "SELECT data, contact_id as id FROM {$quick_search_temp_table}";
}

/**
 * Implementation of hook_civicrm_config
 */
function optimizedautocomplete_civicrm_config(&$config) {
  _optimizedautocomplete_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function optimizedautocomplete_civicrm_xmlMenu(&$files) {
  _optimizedautocomplete_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function optimizedautocomplete_civicrm_install() {
  return _optimizedautocomplete_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function optimizedautocomplete_civicrm_uninstall() {
  return _optimizedautocomplete_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function optimizedautocomplete_civicrm_enable() {
  return _optimizedautocomplete_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function optimizedautocomplete_civicrm_disable() {
  return _optimizedautocomplete_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function optimizedautocomplete_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _optimizedautocomplete_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function optimizedautocomplete_civicrm_managed(&$entities) {
  return _optimizedautocomplete_civix_civicrm_managed($entities);
}
