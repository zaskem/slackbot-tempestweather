<?php
  require_once __DIR__ . '/config/bot.php';
  require_once __DIR__ . '/config/version.php';
  require_once __DIR__ . '/config/slack.php';
  require_once __DIR__ . '/config/nws.php';
  require __DIR__ . '/TempestAPIFunctions.php';
  require __DIR__ . '/TempestObservation.php';
  require __DIR__ . '/NWSAlert.php';

  // Static/Reused block content
  $refreshDataButton = array('type'=>'actions','elements'=>[array('type'=>'button','action_id'=>'refresh_data','text'=>array('type'=>'plain_text','text'=>':arrows_clockwise: Refresh Data','emoji'=>true),'value'=>'appHomeRefresh')]);
  $botVersionBlock = array('type'=>'context','elements'=>[array('type'=>'mrkdwn','text'=>$bot_name . ' core: ' . $bot_core_version . ' | config: ' . $bot_version)]);
  $helpContextBlock = array('type'=>'context','elements'=>[array('type'=>'mrkdwn','text'=>'Get more bot help with `' . $bot_slashcommand . ' help` or at https://tempestweatherbot.mzonline.com/help.html')]);
  $dividerBlock = array('type'=>'divider');


  /**
   * getAppHomeBlocks($user_id) - generate the dynamic block content for $user_id's App Home tab
   * 
   * @return array of block content payload
   */
  function getAppHomeBlocks($user_id) {
    global $useNWSAPIAlerts, $dividerBlock, $refreshDataButton, $helpContextBlock, $botVersionBlock, $slackConditionIcons, $tempUnitLabel, $pressureUnitLabel, $windUnitLabel, $severityLevels;
  
    // Header Block Content
    $blks = [array('type'=>'header','text'=>array('type'=>'plain_text','text'=>'Tempest Weather Bot'))];

    // Alert Data
    if ($useNWSAPIAlerts) {
      $alertData = include __DIR__ . '/config/nwsAlerts.generated.php';
      if (array_key_exists('status', $alertData)) {
        // We have an unexpected status (likely a service timeout)
        array_push($blks, array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'There was a problem obtaining alert data: ' . $alertData['status'])), $dividerBlock);
      } else {
        $activeAlerts = count($alertData['features']);

        if ($activeAlerts > 0) {
          $useFeatureIndex = 0;
          if ($activeAlerts > 1) {
            // Need to obtain just the "most severe" current alert
            $highestSeverity = 0;
            $i = 0;
            while ($i < $activeAlerts) {
              $alertSeverity = array_search(strtolower($alertData['features'][$i]['properties']['severity']), $severityLevels);
              if ($alertSeverity > $highestSeverity) {
                $highestSeverity = $alertSeverity;
                $useFeatureIndex = $i;
              }
              $i++;
            }
          }
          $alert = new NWSAlert($alertData['features'][$useFeatureIndex]);
          $alertBlocks = $alert->getHomeBlocks();
          foreach ($alertBlocks as $alertBlock) {
            array_push($blks, $alertBlock);
          }
          array_push($blks, $dividerBlock);
        }
      }
    }

    // Current Observation
    getLastStationObservation();
    $lastObservation = include __DIR__ . '/config/lastObservation.generated.php';
    if (!isset($lastObservation['obs'][0])) {
      // We are missing valid observation data
      array_push($blks, array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'There was a problem with current observation data.')));
    } else {
      $observation = new TempestObservation('current', $lastObservation['obs'][0]);
      $observationBlocks = $observation->getHomeObservationBlocks();
      foreach ($observationBlocks as $observationBlock) {
        array_push($blks, $observationBlock);
      }
    }
    array_push($blks, $refreshDataButton, $dividerBlock, array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'*Today\'s Data:*')));

    // Daily Summary
    $dailySummaryData = getStationObservationsByDay(date('Y-m-d', strtotime('today')), false);
    if (!isset($dailySummaryData['obs'][0])) {
      // We are missing valid observation data
      array_push($blks, array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'There was a problem with today\'s observation data.')));
    } else {
      $almanacObservation = new TempestObservation('history', $dailySummaryData['obs']);
      $observationBlocks = $almanacObservation->getHomeTodayBlocks();
      foreach ($observationBlocks as $observationBlock) {
        array_push($blks, $observationBlock);
      }
    }
    array_push($blks, $dividerBlock, array('type'=>'header','text'=>array('type'=>'plain_text','text'=>'WeatherBot Forecast')), array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'*Today:*')));

    // Grab the forecast data
    getStationForecast();
    $stationForecast = include __DIR__ . '/config/stationForecast.generated.php';
    $dailyData = $stationForecast['forecast']['daily'];
    $hourlyData = $stationForecast['forecast']['hourly'];

    // Today's Forecast
    $forecastObservation = new TempestObservation('day_forecast', $dailyData[0]);
    $observationBlocks = $forecastObservation->getHome0DayBlocks();
    foreach ($observationBlocks as $observationBlock) {
      array_push($blks, $observationBlock);
    }
    array_push($blks, $dividerBlock, array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'*Next four hours:*')));

    // Next Four Hours
    $hourCount = 4;
    $hour = 0;
    while  ($hour < $hourCount) {
      $observation = new TempestObservation('hour_forecast', $hourlyData[$hour]);
      $observationBlocks = $observation->getHome4HourBlocks();
      foreach ($observationBlocks as $observationBlock) {
        array_push($blks, $observationBlock);
      }
      $hour++;
    }
    array_push($blks, $dividerBlock, array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'*Next five days:*')));

    // Next Five Days:
    $dayCount = 5;
    $day = 1;
    while  ($day <= $dayCount) {
      $observation = new TempestObservation('day_forecast', $dailyData[$day]);
      $observationBlocks = $observation->getHome5DayBlocks();
      foreach ($observationBlocks as $observationBlock) {
        array_push($blks, $observationBlock);
      }
      $day++;
    }
    array_push($blks, $dividerBlock, $helpContextBlock, $botVersionBlock);

    $blocks = array('user_id'=>$user_id,'view'=>array('type'=>'home','blocks'=>$blks));

    return $blocks;
  }


  /**
   * UpdateAppHomeTab($payload, $debug) - function to push an App Home tab's JSON-encoded $payload to Slack.
   * 
   * @return string of Slack API response (including JSON if $debug).
   */
  function UpdateAppHomeTab($payload, $debug) {
    global $slackHomeViewPublish, $botOAuthToken;

    // Create/Submit cURL request
    $curl_request = curl_init();

    $slackHeader = array('Content-type: application/json;charset="utf-8"', 'Authorization: Bearer ' . $botOAuthToken);
    curl_setopt($curl_request, CURLOPT_URL, $slackHomeViewPublish);
    curl_setopt($curl_request, CURLOPT_HTTPHEADER, $slackHeader);
    curl_setopt($curl_request, CURLOPT_POST, true);
    curl_setopt($curl_request, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, false);

    $json = curl_exec($curl_request);
    $responseCode = curl_getinfo($curl_request, CURLINFO_RESPONSE_CODE);
    curl_close($curl_request);

    $result = json_decode($json, true);

    if (true == $result['ok']) {
      // Good response, but could have warning output
      if (array_key_exists("warning", $result)) {
        return ($debug) ? "Warning: $json" : "Warning Returned";
      } else {
        return ($debug) ? "Good Request: $json" : "Good Request";
      }
    } else {
    // Bad response
      if (array_key_exists("error", $result)) {
        return ($debug) ? "Error: $json" : "Bad Request";
      } else if (array_key_exists("warning", $result)) {
        return ($debug) ? "Warning: $json" : "Warning Returned";
      }
    }
  }
?>