<?php

namespace Drupal\club_report;

class GetPayPalData {

  /**
   * PayPal payment notifications are stored in civicrm_system-log context field as JSON data. 
   *  Read and extract JSON data and  save to the club_paypal table so that ...
   *  data may be joined with civicrm_contribution and Views can apply filters, sorts, etc.
   *
   */
  public function copySystemLog() {

    $db = \Drupal::database();

    // Copy new system_log data into club_paypal.
    // New items have id > maxid in club_PayPal.
    $maxid = $db->query(
      "SELECT max(id) AS maxId
        FROM {club_paypal}")
        ->fetch();
    $maxid = reset($maxid);

    $maxid = isset($maxid) ? $maxid : 0;

    $results = $db->query(
      "SELECT id, [timestamp], context
        FROM {civicrm_system_log}
        WHERE id > $maxid")
        ->fetchAll();

    $paypal = \Drupal::entityTypeManager()->getStorage('club_paypal');

    $count = 0;
    // Populate data array
    foreach ($results as $logged) {
      $count++;

      // Json data.
      $context = json_decode($logged->context);
      $custom  = json_decode($context->custom); 

      // Item_name is in form "1791-5677-Webform Payment: Membership" - we extract the part after the colon.
      $item = explode(": ", $context->item_name);

      $pdate = strtotime($context->payment_date);
      $ldate = strtotime($logged->timestamp);

      $pdatex = date("Y-m-d", $pdate) . "T" . date("H:i:s", $pdate);
      $ldatex = date("Y-m-d", $ldate) . "T" . date("H:i:s", $ldate);

      // Save civicrm_system_log data to club_paypal table.
      $newPaypal = $paypal->create([
        'paypal_id' => $logged->id,
        'contact_id' => $custom->contactID,
        'contribution_id' => $custom->contributionID,
        'name' => $context->first_name . ' ' . $context->last_name,
        'email' => $context->payer_email, 
        'item' => $item[1],
        'payment' => $context->payment_gross,
        'status' => $context->payment_status,
        'payment_date' => $pdatex,
        'log_date' => $ldatex,
      ]);
      $newPaypal->save();
    } 
    echo "<h1>Program Completed</h1>";
    echo "<h3>" . $count . ' records were copied from the civicrm_system_log table to the club_paypal table.</h3><br>';
    echo "<h2><a href='/civicrm-issues'>Return</a></h2>";
    die;	
  }
}
