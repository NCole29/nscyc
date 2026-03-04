<?php

namespace Drupal\club_schedule;

use Drupal\Core\Datetime\DrupalDateTime;

class LoadSchedule {
  /**
   * Add records to the club_schedule table for each Sunday and Wednesday from April - Nov, for 5 years.
   * Fill array and sort by date, then load to database.
   */

  public static function loadSchedule() {

    // Fill $days array with timestamps for all days/years.
    // Counter/week_num code works if loading two days, else modify it.

    $entity = \Drupal::entityTypeManager()->getStorage('club_schedule');

    /* 
      // Original schedules for Sunday and Wed only from first week of April to last week of November.
      // Remainder of year flled for Sunday and Wednesday in adhoc program.
      $array_weekdays = ['Sunday','Wednesday'];
      $year = 2022;
      $lastyear = 2021;
    */
    // Fill schedule for all days.
    $array_weekdays = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    $year = 2022;
    $lastyear = 2021;

    $days = [];
    $now = time();

    // Loop over years and weekdays to fill array of dates.
    while ($year < 2029) {
      foreach($array_weekdays as $weekday){
        $start = date('Y-m-d', strtotime("First $weekday Of January $year"));
        $end = date('Y-m-d', strtotime("Last $weekday Of December $year"));
        $timestamp  = strtotime($start);
        $endTimestamp = strtotime($end);
        $day = date('d', $timestamp); // Initialize for the year & weekday

        $days[] = $timestamp; // Add timestamp to array

        while ($timestamp <= $endTimestamp) {
          $day = $day + 7; // keep adding 7 days
          // Month=1 because we start in January, $day keeps incrementing
          $timestamp = mktime(10, 0, 0, 1, $day, $year);
          $days[] = $timestamp;
        }
      } // End loop over weekdays
      $year++;
    } // End loop over years

    //Sort days in the year and load to database
    sort($days);


    foreach($days as $timestamp) {
      $drupal_date = DrupalDateTime::createFromTimestamp($timestamp, 'UTC');

      // Get date parts
      $date = $drupal_date->format("Y-m-d");
      $weekday = date('l', $timestamp);
      $year = date('Y', $timestamp);

      // Reset counter and week_num for new year
      if ($year > $lastyear) {
        $counter = 1;
        $week_num = 1;
      }
      //echo '<br>$counter, $date, $weekday, $week_num: ' . ' ' . $counter . ' ' . $date . ' ' .  $weekday . ' ' .  $week_num;

      $newDate = $entity->create([
        'week_num' => $week_num,
        'weekday' => $weekday,
        'field_schedule_date' => $date,
        'created' => $now,
        'changed' => $now,
        'langcode' => "en",
      ]);
      $newDate->save();

      // Increment week_num if counter is even (even because we load two weekday schedules)
      if ($counter % 2 == 0) {
        $week_num++;
      }
      $counter++;
      $lastyear = $year;
    } // End loop over days
  }
}
