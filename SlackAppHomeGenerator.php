<?php
  require __DIR__ . '/TempestAPIFunctions.php';
  require __DIR__ . '/UtilityFunctions.php';
  require_once __DIR__ . '/config/slack.php';
  require_once __DIR__ . '/config/bot.php';

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


    // Current Observation
    getLastStationObservation();
    $lastObservation = include __DIR__ . '/config/lastObservation.generated.php';
    $obsData = $lastObservation['obs'][0];
  
    $currentObservation = array(
      'timestamp' => date('g:i a', $obsData['timestamp']),
      'temperature' => convertCToF($obsData['air_temperature']) . $tempUnitLabel,
      'pressure' => convertMbToInHg($obsData['sea_level_pressure']) . "$pressureUnitLabel",
      'feelsLike' => convertCToF($obsData['feels_like']) . $tempUnitLabel,
      'windAvg' => convertMPSToMPH($obsData['wind_avg']) . " $windUnitLabel",
      'windDir' => convertDegreesToWindDirection($obsData['wind_direction'])
    );
    array_push($blks, array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'*Currently (' . $currentObservation['timestamp'] . '):*')));
    array_push($blks, array('type'=>'section','fields'=>array(['type'=>'mrkdwn','text'=>':thermometer: ' . $currentObservation['temperature'] . ' (feels like ' . $currentObservation['feelsLike'] . ')'], ['type'=>'mrkdwn','text'=>':dash: Wind ' . $currentObservation['windDir'] . ' ' . $currentObservation['windAvg']])), $dividerBlock, array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'*Next four hours:*')));


    // Grab the forecast data
    getStationForecast();
    $stationForecast = include __DIR__ . '/config/stationForecast.generated.php';
    $dailyData = $stationForecast['forecast']['daily'];
    $hourlyData = $stationForecast['forecast']['hourly'];


    // Next Four Hours
    $hourCount = 4;
    $hour = 0;
    while  ($hour < $hourCount) {
      $obsData = $hourlyData[$hour];
      $observation = array(
        'timestamp' => date('g:i a', $obsData['time']),
        'icon' => $obsData['icon'],
        'temperature' => $obsData['air_temperature'] . $tempUnitLabel,
        'pressure' => $obsData['sea_level_pressure'] . $pressureUnitLabel,
        'precip_type' => (isset($obsData['precip_type'])) ? $obsData['precip_type'] : '',
        'precip_probability' => $obsData['precip_probability'] . "%",
        'conditions' => $obsData['conditions'],
        'feelsLike' => $obsData['feels_like'] . $tempUnitLabel,
        'windAvg' => $obsData['wind_avg'] . " $windUnitLabel",
        'windDir' => $obsData['wind_direction_cardinal']
      );
      array_push($blks, array('type'=>'section','fields'=>array(['type'=>'mrkdwn','text'=>'*' . $observation['timestamp'] . '*: ' . $slackConditionIcons[$observation['icon']] . ' ' . $observation['temperature'] . ' (feels like ' . $observation['feelsLike'] . ')'],
      ($observation['precip_probability'] > 0) ? ['type'=>'plain_text','text'=>$observation['precip_probability'] . ' chance ' . $observation['precip_type'] . ' | ' . $observation['windDir'] . ' ' . $observation['windAvg'],'emoji'=>true] : ['type'=>'plain_text','text'=>$observation['windDir'] . ' ' . $observation['windAvg'],'emoji'=>true])));
      $hour++;
    }
    array_push($blks, $dividerBlock, array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'*Next five days:*')));


    // Next Five Days:
    $dayCount = 5;
    $day = 1;
    while  ($day <= $dayCount) {
      $obsData = $dailyData[$day];
      $observation = array(
        'timestamp' => date('l', $obsData['day_start_local']),
        'icon' => $obsData['icon'],
        'high_temperature' => $obsData['air_temp_high'] . $tempUnitLabel,
        'low_temperature' => $obsData['air_temp_low'] . $tempUnitLabel,
        'precip_type' => (isset($obsData['precip_type'])) ? $obsData['precip_type'] : '',
        'precip_probability' => $obsData['precip_probability'] . "%",
        'conditions' => $obsData['conditions'],
        'sunrise' => date('g:i a', $obsData['sunrise']),
        'sunset' => date('g:i a', $obsData['sunset'])
      );
      array_push($blks, array('type'=>'section','fields'=>array(['type'=>'mrkdwn','text'=>'*' . $observation['timestamp'] . '*: ' . $slackConditionIcons[$observation['icon']]],['type'=>'plain_text','text'=>' high: ' . $observation['high_temperature'] . ', low: ' . $observation['low_temperature']],['type'=>'plain_text','text'=>' ','emoji'=>true],
      ($observation['precip_probability'] > 0) ? ['type'=>'plain_text','text'=>$observation['conditions'] . ' (' . $observation['precip_probability'] . ' chance ' . $observation['precip_type'] . ')','emoji'=>true] : ['type'=>'plain_text','text'=>$observation['conditions'],'emoji'=>true])));

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