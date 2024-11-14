<?php

require_once 'unlinkuser.civix.php';

use CRM_Unlinkuser_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function unlinkuser_civicrm_config(&$config): void {
  _unlinkuser_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function unlinkuser_civicrm_install(): void {
  _unlinkuser_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function unlinkuser_civicrm_enable(): void {
  _unlinkuser_civix_civicrm_enable();
}

function unlinkuser_civicrm_summaryActions( &$actions, $contactID ) {
  if (CRM_Core_Permission::check('add contacts')) {
    $uid = CRM_Core_BAO_UFMatch::getUFId($contactID);
    if ($uid) {
      // Add Unlink user Url in Action Dropdown list on contact summary page.
      $actions['otherActions']['unlink-user-record'] = [
        'title' => ts('Unlink User record from Contact'),
        'description' => ts('Unlink the user record for this contact.'),
        'weight' => 21,
        'ref' => 'crm-contact-unlink-user-record',
        'key' => 'unlink-user-record',
        'tab' => 'unlink-user-record',
        'class' => 'unlink-user-record',
        'href' => CRM_Utils_System::url('civicrm/contact/view', 'reset=1&action=detach'),
        'icon' => 'crm-i fa-unlink',
        'permissions' => ['add contacts']
      ];
    }
  }
}

function unlinkuser_civicrm_pageRun(&$page) {
  if (get_class($page) == 'CRM_Contact_Page_View_Summary') {
    if (CRM_Core_Permission::check('add contacts')) {
      // Get action and contact id from url parameter.
      $action = CRM_Utils_Request::retrieve('action', 'String');
      $contactId = CRM_Utils_Request::retrieve('cid', 'Positive');
      // if the action is detach then get UID from contact id.
      if ($action & CRM_Core_Action::DETACH) {
        $uid = CRM_Core_BAO_UFMatch::getUFId($contactId);
        if ($contactId && $uid) {
          // Find the UF match entry using contact id and user id for current
          // domain.
          $uFMatches = \Civi\Api4\UFMatch::get(TRUE)
            ->addWhere('uf_id', '=', $uid)
            ->addWhere('contact_id', '=', $contactId)
            ->addWhere('domain_id', '=', 'current_domain')
            ->setLimit(1)
            ->execute()->first();
          if (!empty ($uFMatches)) {
            // Delete the entry from UF match table.
            \Civi\Api4\UFMatch::delete(TRUE)
              ->addWhere('id', '=', $uFMatches['id'])
              ->execute();
            $viewContact = CRM_Utils_System::url('civicrm/contact/view',
              "action=view&reset=1&cid={$contactId}"
            );
            CRM_Core_Error::statusBounce(ts('Unlinked user successfully.'), $viewContact,
              'Unlinked user successfully');
          }
        }
      }
    }
  }
}
