<?php

namespace Drupal\club_report\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block with total and net revenue for memberships and all events by year
 *
 * @Block(
 *   id = "revenue",
 *   admin_label = @Translation("Club revenue"),
 *   category = @Translation("Club"),
 * )
 */
class Revenue extends BlockBase {

  /**
   * {@inheritdoc}
   */

  public function build() {
    $db = \Drupal::database();

    // Aggregate revenue by year and financial type (event, membership)
    $all = $db->query("SELECT year(`receive_date`) as year, `financial_type_id` as type,
        SUM(`total_amount`) as total, SUM(`net_amount`) as net, SUM(`fee_amount`) as fees
      FROM `civicrm_contribution`
      WHERE `contribution_status_id` = 1
      GROUP BY year, type")
     ->fetchALL();

     // Get financial type labels.
     $type_names = $db->query("SELECT id as type, name
      FROM `civicrm_financial_type`
      ORDER BY type")
      ->fetchALL();

    if ($all) {
      // Convert array of objects to array of arrays
      $result = json_decode(json_encode($all), true);
      $typeNames = json_decode(json_encode($type_names),true);

      // Set type as the array key
      $typeLabel = array_column($typeNames, null, 'type');

     // Fill array of unique years and types.
      $years = [];
      $types = [];

      foreach($result as $key => $value){
        $years[] = $value['year'];
        $types[] = $value['type'];
      }

      // Use 'array_values' to reset keys after keeping unique values
      $years = array_values(array_unique($years));
      $types = array_values(array_unique($types));

      // Manually fill arrays to display years as rows
      //  with 3 columns for each TYPE showing subheader with "Total, Net, Fees"

      // Header has YEAR and TYPE
      $header[0] = 'Year';
      $i = 0; $j = 1;

        while($i < count($types)) {
          $type = $types[$i];
          $header[$j] = '';
          $header[$j+1] = $typeLabel[$type]['name'];
          $header[$j+2] = '';
          $i++;
          $j = $j + 3;
        }

      // Fill first row of data with  2nd level header.
      $data = [];
      $data[''][0] = '';
      $i = 0; $j = 1;

        while($i < count($types)) {
          $data[''][$j] = 'Total';
          $data[''][$j+1] = 'Net';
          $data[''][$j+2] = 'Fees';
          $i++;
          $j = $j + 3;
        }

      // Fill rows with data.
      $year = 0;
      foreach($result as $item) {

        if ($item['year'] > $year) {
          $year = $item['year'];
        }

        if ($item['year'] == $year and $item['type'] == $types[0]) {
          $j = 1;
          $type = $item['type'];
          $data[$year][0] = $year;
          $data[$year][$j] = $item['total'];
          $data[$year][$j+1] = $item['net'];
          $data[$year][$j+2] = $item['fees'];
        }

        elseif ($item['year'] == $year and $item['type'] != $type) {
          $j = $j + 3;
          $type = $item['type'];
          $data[$year][$j] = $item['total'];
          $data[$year][$j+1] = $item['net'];
          $data[$year][$j+2] = $item['fees'];
      }
    }

    // Build table
    $title = "<h3 class='w3-block-title'>Revenue by Year and Source</h3>";

    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $data,
      '#empty' => t('No content has been found.'),
      '#attributes' => array (
        'class' => ['number-table'],
      ),
      '#cache' => array (
        'max-age' => 0,
      ),
    ];

    $tableHTML = \Drupal::service('renderer')->renderPlain($build);
    return [
      '#type' => '#markup',
      '#markup' => $title . $tableHTML,
    ];
  }
}
}
