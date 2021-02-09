<?php
  require_once __DIR__ . '/config/bot.php';
  require_once __DIR__ . '/config/slack.php';
  require __DIR__ . '/TempestAPIFunctions.php';
  require __DIR__ . '/TempestObservation.php';
  require __DIR__ . '/NWSAlert.php';

  // Static/Reused block content
  $botVersionBlock = array('type'=>'context','elements'=>[array('type'=>'mrkdwn','text'=>$bot_name . ' version: ' . $bot_version)]);
  $helpContextBlock = array('type'=>'context','elements'=>[array('type'=>'mrkdwn','text'=>'Get more bot help with `' . $bot_slashcommand . ' help` or at https://tempestweatherbot.mzonline.com/help.html')]);
  $dividerBlock = array('type'=>'divider');


  /**
   * getAppHomeBlocks($user_id) - generate the dynamic block content for $user_id's App Home tab
   * 
   * @return array of block content payload
   */
  function getAppHomeBlocks($user_id) {
    global $dividerBlock, $helpContextBlock, $botVersionBlock, $slackConditionIcons, $tempUnitLabel, $pressureUnitLabel,$windUnitLabel;
  
    // Header Block Content
    $blks = [array('type'=>'header','text'=>array('type'=>'plain_text','text'=>'Tempest Weather Bot'))];
    // Alert Data
    $alertData = include __DIR__ . '/config/nwsAlerts.generated.php';
    $activeAlerts = count($alertData['features']);

    if ($activeAlerts > 0) {
      $alert = new NWSAlert($alertData['features'][0]);
      $alertBlocks = $alert->getHomeBlocks();
      array_push($blks, $alertBlocks[0], $alertBlocks[1], $dividerBlock);
    }


    // Current Observation
    getLastStationObservation();
    $lastObservation = include __DIR__ . '/config/lastObservation.generated.php';
    $observation = new TempestObservation('current', $lastObservation['obs'][0]);

    array_push($blks, array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'*Currently (' . $observation->f_timestamp . '):*')));
    array_push($blks, array('type'=>'section','fields'=>array(['type'=>'mrkdwn','text'=>':thermometer: ' . $observation->f_temperature . ' (feels like ' . $observation->f_feelsLike . ')
        dew point ' . $observation->f_dew_point . ' (humidity ' . $observation->f_relative_humidity . ')'], ['type'=>'mrkdwn','text'=>':dash: Wind ' . $observation->f_windDir . ' ' . $observation->f_windAvg . ' (gusting ' . $observation->f_windGust . ')'])), $dividerBlock, array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'*Next four hours:*')));


    // Grab the forecast data
    getStationForecast();
    $stationForecast = include __DIR__ . '/config/stationForecast.generated.php';
    $dailyData = $stationForecast['forecast']['daily'];
    $hourlyData = $stationForecast['forecast']['hourly'];


    // Next Four Hours
    $hourCount = 4;
    $hour = 0;
    while  ($hour < $hourCount) {
      $observation = new TempestObservation('hour_forecast', $hourlyData[$hour]);

      array_push($blks, array('type'=>'section','fields'=>array(['type'=>'mrkdwn','text'=>'*' . $observation->f_timestamp . '*: ' . $slackConditionIcons[$observation->icon] . ' ' . $observation->f_temperature . ' (feels like ' . $observation->f_feelsLike . ')'],
      ($observation->precip_probability > 0) ? ['type'=>'plain_text','text'=>$observation->f_precip_probability . ' chance ' . $observation->f_precip_type . ' | ' . $observation->f_windDir . ' ' . $observation->f_windAvg . ' (gusting ' . $observation->f_windGust . ')','emoji'=>true] : ['type'=>'plain_text','text'=>$observation->f_windDir . ' ' . $observation->f_windAvg . ' (gusting ' . $observation->f_windGust . ')','emoji'=>true])));
      $hour++;
    }
    array_push($blks, $dividerBlock, array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'*Next five days:*')));


    // Next Five Days:
    $dayCount = 5;
    $day = 1;
    while  ($day <= $dayCount) {
      $observation = new TempestObservation('day_forecast', $dailyData[$day]);

      array_push($blks, array('type'=>'section','fields'=>array(['type'=>'mrkdwn','text'=>'*' . $observation->f_shortTimestamp . '*: ' . $slackConditionIcons[$observation->icon]],['type'=>'plain_text','text'=>' high: ' . $observation->f_high_temperature . ', low: ' . $observation->f_low_temperature],['type'=>'plain_text','text'=>' ','emoji'=>true],
      ($observation->precip_probability > 0) ? ['type'=>'plain_text','text'=>$observation->conditions . ' (' . $observation->f_precip_probability . ' chance ' . $observation->f_precip_type . ')','emoji'=>true] : ['type'=>'plain_text','text'=>$observation->conditions,'emoji'=>true])));

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