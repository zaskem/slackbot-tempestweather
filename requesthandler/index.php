<?php
  /**
   * THE FOLLOWING LINE IS THE _ONLY_ REQUIRED EDIT FOR THIS REQUEST URL HANDLER.
   * CHANGE THIS TO THE NON-WEB ACCESSIBLE PATH HOUSING THE REST OF THE BOT CODE.
   */
  $botCodePath = __DIR__.'/../';

  require $botCodePath . '/TempestAPIFunctions.php';
  require $botCodePath . '/ConversionUtils.php';
  require $botCodePath . '/SlackPost.php';
  require $botCodePath . '/config/bot.php';

  // Set the channel from which this request was called/invoked
  $slackbot_details['channel'] = $_POST['channel_id'];

  // Grab the generated list of valid users in the accompanying Slack workspace
  $slackUsers = include $botCodePath .'/config/slackUsers.generated.php';
  // Look for "authorization" to use this (is the requesting user at least in the known Slack users)
  if (!in_array($_POST['user_id'], $slackUsers)) {
    header("Content-Type: application/json");
    $response = array('response_type' => 'ephemeral', 'text' => 'Not authorized.');
    print json_encode($response);
    die();
  // Was the command invoked with the `help` keyword? Generate help response
  } else if (strpos($_POST['text'], 'help') !== false) {
    // Create basic text response (fallback text)
    $responseText = "The help command failed to generate output. Please visit https://tempestbot.mzonline.com for help information.";
    // Create blocks for formal `help` response
    // Use https://app.slack.com/block-kit-builder/ to test out block structure
    $helpHeaderBlock = array('type'=>'header','text'=>array('type'=>'plain_text','text'=>$bot_name . ' Help','emoji'=>true));
    $block1 = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'The ' . $bot_name . ' responds to a number of arguments and keywords explained below. When provided _no_ argument (e.g. `' . $bot_slashcommand . '`) the bot will respond with current conditions.'));
    $block2 = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'Example commands (e.g. `' . $bot_slashcommand . ' [argument]`)'));
    $argumentBlock = array('type'=>'section','fields'=>
      array(['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' <blank>`'],['type'=>'mrkdwn','text'=>'Display current conditions'],
        ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' now`'],['type'=>'mrkdwn','text'=>'Display current conditions'],
        ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' Tuesday`'],['type'=>'mrkdwn','text'=>'Display the forecast for Tuesday'],
        ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' 85 hours`'],['type'=>'mrkdwn','text'=>'Display the forecast 85 hours from now'],
        ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' 3 days`'],['type'=>'mrkdwn','text'=>'Display the forecast three days from now']
      )
    );
    $rangeHeaderBlock = array('type'=>'header','text'=>array('type'=>'plain_text','text'=>'Forecast Range','emoji'=>true));
    $block4 = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'The ' . $bot_name . ' can respond to forecast inquiries _up to 10 days_ from the current time. This means arguments (`hours`, `days`, and `week`) should fall within the specified ranges. Arguments outside this range will return a private error or display the current conditions.'));
    $rangeBlock = array('type'=>'section','fields'=>
      array(['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' X hours`'],['type'=>'mrkdwn','text'=>'X can range `1` to `120`'],
        ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' X days`'],['type'=>'mrkdwn','text'=>'X can range `1` to `10`'],
        ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' X week`'],['type'=>'mrkdwn','text'=>'X can only be `1`']
      )
    );
    $keywordHeaderBlock = array('type'=>'header','text'=>array('type'=>'plain_text','text'=>'Bot Keyword Actions','emoji'=>true));
    $block5 = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'The ' . $bot_name . ' has unique keywords unrelated to the weather conditions or forecast. Supported keyword actions:'));
    $keywordBlock = array('type'=>'section','fields'=>
      array(['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' help`'],['type'=>'mrkdwn','text'=>'Display this help information'],
        ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' 6 hours private`'],['type'=>'mrkdwn','text'=>'Display the forecast 6 hours from now with a private response']
      )
    );
    $block6 = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'The `private` keyword can be appended to the _end_ of any command to privately respond to the calling user. This keyword _*must*_ be the last argument in all commands.'));
    $botSourceHeaderBlock = array('type'=>'header','text'=>array('type'=>'plain_text','text'=>'Bot Project and Source Code','emoji'=>true));
    $block7 = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'The ' . $bot_name . ' project page and source code on GitHub can be found at https://tempestbot.mzonline.com.'));
    $dividerBlock = array('type'=>'divider');
    $contextBlock = array('type'=>'context','elements'=>[array('type'=>'mrkdwn','text'=>$bot_name . ' version: ' . $bot_version)]);

    // Build block response
    $blocks = [$helpHeaderBlock,$block1,$block2,$argumentBlock,$dividerBlock,$rangeHeaderBlock,$block4,$rangeBlock,$dividerBlock,$keywordHeaderBlock,$block5,$keywordBlock,$block6,$dividerBlock,$botSourceHeaderBlock,$block7,$dividerBlock,$contextBlock];

    header("Content-Type: application/json");
    $response = array('response_type' => 'ephemeral', 'text' => $responseText, 'blocks' => $blocks);
    print json_encode($response);
    die();
  // Looks like it'll be a valid request -- proceed with some weather magic
  } else {
    // Parse out the text of the command for keywords
    // Does the user want a private response?
    $private = (strpos($_POST['text'], 'private') !== false) ? true : false;
    $commandArgs = explode(' ', $_POST['text']);
    if ($private) {
      // Forecast string can't have the private keyword, so we remove it
      array_pop($commandArgs);
      $forecastArgs = implode(' ', $commandArgs);
    } else {
      $forecastArgs = $_POST['text'];
    }
    // Does the command match any of the 'current conditions' (incl. no text/default) patterns?
    $currentConditions = ((empty($commandArgs[0])) || ("now" == trim($commandArgs[0])) || ("private" == trim($commandArgs[0]))) ? true : false;
    if ($currentConditions) {
      $slackbot_details['icon_emoji'] = ':thermometer:';

      getLastStationObservation();
      $lastObservation = include $botCodePath . '/config/lastObservation.generated.php';
      $obsData = $lastObservation['obs'][0];

      $modifiedObs = array(
        'timestamp' => date('g:i a', $obsData['timestamp']),
        'temperature' => convertCToF($obsData['air_temperature']) . "ºF",
        'pressure' => convertMbToInHg($obsData['sea_level_pressure']),
        'feelsLike' => convertCToF($obsData['feels_like']) . "ºF",
        'windAvg' => convertMPSToMPH($obsData['wind_avg']),
        'windDir' => convertDegreesToWindDirection($obsData['wind_direction'])
      );

      // Create basic text response (fallback)
      $responseText = "At $modifiedObs[timestamp], the temperature was $modifiedObs[temperature] (feels like $modifiedObs[feelsLike]) with a $modifiedObs[windDir] wind at $modifiedObs[windAvg] MPH.";
      // Create blocks for prettier response
      // Use https://app.slack.com/block-kit-builder/ to test out block structure
      $headerBlock = array('type'=>'header','text'=>array('type'=>'plain_text','text'=>'Current conditions at ' . $modifiedObs['timestamp'],'emoji'=>true));
      $temperatureBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'Temperature: '. $modifiedObs['temperature'] . ' (feels like ' . $modifiedObs['feelsLike'] . ')'));
      $windBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'Wind at ' . $modifiedObs['windAvg'] . ' MPH from the ' . $modifiedObs['windDir'] . '.'));
      $dividerBlock = array('type'=>'divider');
      $contextBlock = array('type'=>'context','elements'=>[array('type'=>'mrkdwn','text'=>'Get help with `' . $bot_slashcommand . ' help`')]);

      // Build block response
      $blocks = [$headerBlock,$temperatureBlock,$windBlock,$dividerBlock,$contextBlock];
      $slackbot_details['blocks'] = $blocks;

      $result = SlackPost($responseText, $_POST['response_url'], $private, $slackbot_details, $debug_bot);
      if ($debug_bot) {
        header("Content-Type: application/json");
        print json_encode(array('response_type' => 'ephemeral', 'text' => $_POST['command'] . ' ' . $_POST['text'] . ' output response: ' . $result));
      }
    // Look for a forecast
    } else {
      $desiredTime = strtotime($forecastArgs);
      // We didn't match any valid time forecast string, so privately push back an 'error'
      if (false === $desiredTime) {
        header("Content-Type: application/json");
        $response = array('response_type' => 'ephemeral', 'text' => "`$_POST[text]` is not a valid time specification for this bot. Bot help is availble with the `$bot_slashcommand help` command.");
        print json_encode($response);
        die();
      // A valid timestamp format for forecast was provided, so go forward
      } else {
        $timeNow = strtotime("now");
        $hoursToForecast = ($desiredTime - $timeNow) / 3600;

        getStationForecast();
        $stationForecast = include $botCodePath . '/config/stationForecast.generated.php';
        $dailyData = $stationForecast['forecast']['daily'];
        $hourlyData = $stationForecast['forecast']['hourly'];

        // Look for "day" matches from our argument(s)
        $dayCount = 0;
        $dayMatches = array();
        foreach ($dailyData as $dayForecast) {
          if (in_array(strtotime($forecastArgs), $dayForecast)) {
            $dayMatches[] = $dayCount;
            }
          $dayCount++;
        }

        // Look for "hour" matches from our argument(s)
        $hourvalue = (round(strtotime($forecastArgs) / 3600) * 3600);
        $hourCount = 0;
        $hourMatches = array();
        foreach ($hourlyData as $hourlyForecast) {
          if (in_array($hourvalue, $hourlyForecast)) {
            $hourMatches[] = $hourCount;
          }
          $hourCount++;
        }

        // Do we match a "day" string? (preferred forecast)
        if (count($dayMatches) > 0) {
          $obsData = $dailyData[$dayMatches[0]];

          $modifiedObs = array(
            'timestamp' => date('l, F j', $obsData['day_start_local']),
            'high_temperature' => $obsData['air_temp_high'] . "ºF",
            'low_temperature' => $obsData['air_temp_low'] . "ºF",
            'precip_type' => $obsData['precip_type'],
            'precip_probability' => $obsData['precip_probability'] . "%",
            'conditions' => $obsData['conditions'],
            'sunrise' => date('g:i a', $obsData['sunrise']),
            'sunset' => date('g:i a', $obsData['sunset'])
          );

          $slackbot_details['icon_emoji'] = $slackConditionIcons[$obsData['icon']];
          // Create basic text response (fallback)
          $responseText = "The forecast for $modifiedObs[timestamp]: $modifiedObs[conditions] with a high of $modifiedObs[high_temperature] (low: $modifiedObs[low_temperature]).";
          if ($modifiedObs['precip_probability']> 0) { $responseText .= " There's a $modifiedObs[precip_probability] chance of $modifiedObs[precip_type]."; }
          $responseText .= " Sunrise: $modifiedObs[sunrise] | Sunset: $modifiedObs[sunset].";

          // Create blocks for prettier response
          // Use https://app.slack.com/block-kit-builder/ to test out block structure
          $headerBlock = array('type'=>'header','text'=>array('type'=>'plain_text','text'=>'Forecast for ' . $modifiedObs['timestamp'],'emoji'=>true));
          $conditionsBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>$modifiedObs['conditions'] . ' with a high of ' . $modifiedObs['high_temperature'] . ' (low: ' . $modifiedObs['low_temperature'] . ').'));
          $precipBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'There is a ' . $modifiedObs['precip_probability'] . ' chance of ' . $modifiedObs['precip_type'] . '.'));
          $sunBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'Sunrise: ' . $modifiedObs['sunrise'] . ' | Sunset: ' . $modifiedObs['sunset'] . '.'));
          $dividerBlock = array('type'=>'divider');
          $contextBlock = array('type'=>'context','elements'=>[array('type'=>'mrkdwn','text'=>'Get help with `' . $bot_slashcommand . ' help`')]);

          // Build block response
          if ($modifiedObs['precip_probability'] > 0) {
            $blocks = [$headerBlock,$conditionsBlock,$precipBlock,$sunBlock,$dividerBlock,$contextBlock];
          } else {
            $blocks = [$headerBlock,$conditionsBlock,$sunBlock,$dividerBlock,$contextBlock];
          }
          $slackbot_details['blocks'] = $blocks;
          
          $result = SlackPost($responseText, $_POST['response_url'], $private, $slackbot_details, $debug_bot);
          if ($debug_bot) {
            header("Content-Type: application/json");
            print json_encode(array('response_type' => 'ephemeral', 'text' => $_POST['command'] . ' ' . $_POST['text'] . ' output response: ' . $result));
          }
        // Do we match an "hourly" string?
        } else if (count($hourMatches) > 0) {
          $obsData = $hourlyData[$hourMatches[0]];

          $modifiedObs = array(
            'timestamp' => ($hoursToForecast > 8) ? date('l, F j, g:i a', $obsData['time']) : date('g:i a', $obsData['time']),
            'temperature' => $obsData['air_temperature'] . "ºF",
            'pressure' => $obsData['sea_level_pressure'],
            'precip_type' => $obsData['precip_type'],
            'precip_probability' => $obsData['precip_probability'] . "%",
            'conditions' => $obsData['conditions'],
            'feelsLike' => $obsData['feels_like'] . "ºF",
            'windAvg' => $obsData['wind_avg'],
            'windDir' => $obsData['wind_direction_cardinal']
          );

          $slackbot_details['icon_emoji'] = $slackConditionIcons[$obsData['icon']];
          // Create basic text response (fallback)
          $responseText = "The forecast for $modifiedObs[timestamp]: $modifiedObs[temperature] (feels like $modifiedObs[feelsLike])";
          if ($modifiedObs['precip_probability']> 0) { $responseText .= " with a $modifiedObs[precip_probability] chance of $modifiedObs[precip_type]"; }
          $responseText .= ". $modifiedObs[windDir] winds averaging $modifiedObs[windAvg] MPH.";

          // Create blocks for prettier response
          // Use https://app.slack.com/block-kit-builder/ to test out block structure
          $headerBlock = array('type'=>'header','text'=>array('type'=>'plain_text','text'=>'Forecast for ' . $modifiedObs['timestamp'],'emoji'=>true));
          $conditionsBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>$modifiedObs['conditions'] . ' (feels like ' . $modifiedObs['feelsLike'] . ')'));
          $precipBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'There is a ' . $modifiedObs['precip_probability'] . ' chance of ' . $modifiedObs['precip_type'] . '.'));
          $windBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>$modifiedObs['windDir'] . ' winds averaging ' . $modifiedObs['windAvg'] . ' MPH.'));
          $dividerBlock = array('type'=>'divider');
          $contextBlock = array('type'=>'context','elements'=>[array('type'=>'mrkdwn','text'=>'Get help with `' . $bot_slashcommand . ' help`')]);

          // Build block response
          if ($modifiedObs['precip_probability'] > 0) {
            $blocks = [$headerBlock,$conditionsBlock,$precipBlock,$windBlock,$dividerBlock,$contextBlock];
          } else {
            $blocks = [$headerBlock,$conditionsBlock,$windBlock,$dividerBlock,$contextBlock];
          }
          $slackbot_details['blocks'] = $blocks;

          $result = SlackPost($responseText, $_POST['response_url'], $private, $slackbot_details, $debug_bot);
          if ($debug_bot) {
            header("Content-Type: application/json");
            print json_encode(array('response_type' => 'ephemeral', 'text' => $_POST['command'] . ' ' . $_POST['text'] . ' output response: ' . $result));
          }
        // We didn't match any valid forecast string, so privately push back an 'error'
        } else {
          header("Content-Type: application/json");
          $response = array('response_type' => 'ephemeral', 'text' => "`$_POST[text]` is not a valid time specification for this bot. Bot help is availble with the `$bot_slashcommand help` command.");
          print json_encode($response);
          die();
        }
      }
    }
  }
?>