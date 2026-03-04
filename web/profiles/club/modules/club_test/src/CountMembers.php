<?php

namespace Drupal\club_test;

class CountMembers {

  public static function countsByYear() {
    $db = \Drupal::database();

    // Get first membership year in database
    $start = $db->query("SELECT MIN(start_date) FROM {civicrm_membership}")->fetch();
    $start = get_mangled_object_vars($start);
    $start = reset($start);
    $year1 = date('Y', strtotime($start)); // first year in database
    $current_year = date("Y"); // current year

    // Process membership contributions.
    $pvars = "id, contact_id, receive_date, total_amount, contribution_status_id";

    // Test cases.
    //WHERE financial_type_id = :finId and contribution_status_id = :status and contact_id IN(6,27,63,84)", 

    $cids = $db->query("SELECT DISTINCT(contact_id) FROM {civicrm_contribution} 
      WHERE financial_type_id = :finId and contribution_status_id = :status
      ORDER BY contact_id", 
      [':finId' => 2, 
      ':status' => 1, 
      ])
      ->fetchALL();

    foreach ($cids as $contact) {
        // Initialize array of membership years.
        $year = $year1;
        $cid = $contact->contact_id;
        //$cid = 134;

        while ($year <= $current_year) {
          $members[$year][$cid] = 0; 
          $tMembers[$cid][$year] = 0;
          $year++;       
        }

        // Retrieve all completed membership payments.
        $payments = $db->query("SELECT $pvars FROM {civicrm_contribution} 
          WHERE financial_type_id = :finId and contribution_status_id = :status and contact_id = :cid", 
          [':finId' => 2, 
          ':status' => 1, 
          ':cid' => $cid, ])
          ->fetchALL();

        $i = 0; $fill_yr = 0;

        foreach ($payments as $payment) {
          $year = date('Y', strtotime($payment->receive_date));

          if($i == 0) {
            // Process first membership payment.
              $i++;
              $members[$year][$cid] = 1;
              $tMembers[$cid][$year] = 1;
              $fill_yr = $year;

              if( $payment->total_amount > 20 and $year < $current_year) {
                $fill_yr = $fill_yr + 1;
                $members[$fill_yr][$cid] = 1;
                $tMembers[$cid][$fill_yr] = 1;
              }
          } elseif ($i > 0) { 
              // Process membership payments after the first.
              if ($members[$year][$cid] == 0) {
                // Iterate year to one year after the last one filled.
                $fill_yr = $year;

                $members[$fill_yr][$cid] = 1;
                $tMembers[$cid][$fill_yr] = 1;

                if( $payment->total_amount > 20 and $year < 2023) {
                  $fill_yr = $fill_yr + 1;
                  $members[$fill_yr][$cid] = 1;
                  $tMembers[$cid][$fill_yr] = 1;
                }
              } elseif ($members[$year][$cid] > 0) {
                // Iterate year to one year after the last one filled.
                $fill_yr = $fill_yr + 1;

                $members[$fill_yr][$cid] = 1;
                $tMembers[$cid][$fill_yr] = 1;

                if( $payment->total_amount > 20 and $year < 2023) {
                  $fill_yr = $fill_yr + 1;
                  $members[$fill_yr][$cid] = 1;
                  $tMembers[$cid][$fill_yr] = 1;
                }
              } 
          }
        }
    }

    // Print array of years by member.
    foreach ($cids as $contact) {
      $cid = $contact->contact_id;

      echo "<br>" . $cid 
      . "|" . $tMembers[$cid][2015]
      . "|" . $tMembers[$cid][2016]
      . "|" . $tMembers[$cid][2017]
      . "|" . $tMembers[$cid][2018]
      . "|" . $tMembers[$cid][2019]
      . "|" . $tMembers[$cid][2020]
      . "|" . $tMembers[$cid][2021]
      . "|" . $tMembers[$cid][2022]
      . "|" . $tMembers[$cid][2023];
    }
    die;


 /*
    // Count memberships each year.
    $year = $year1;
    while ($year <= $current_year) {
      $counts[$year] = array_sum($members[$year]); 
      $year++;       
    }
    kint($counts);
*/
  }
}