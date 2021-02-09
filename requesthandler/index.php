<?php
  /**
   * THE FOLLOWING LINE IS THE _ONLY_ REQUIRED EDIT FOR THIS REQUEST URL HANDLER.
   * CHANGE THIS TO THE NON-WEB ACCESSIBLE PATH HOUSING THE REST OF THE BOT CODE.
   */
  $botCodePath = __DIR__ . '/../';

  require $botCodePath . '/config/bot.php';
  include $botCodePath . '/config/tempest.php';
  require $botCodePath . '/SlackPost.php';
  require $botCodePath . '/SlackResponseBlocks.php';
  require $botCodePath . '/TempestAPIFunctions.php';
  require $botCodePath . '/TempestObservation.php';

  // The `array_key_exists` function only exists in PHP 7.3.0 or above; create a fallback for < 7.3.0
  if (! function_exists("array_key_last")) {
    function array_key_last($array) {
      if (!is_array($array) || empty($array)) {
        return NULL;
      }
      return array_keys($array)[count($array)-1];
    }
  }

  // Set the channel from which this request was called/invoked
  $slackbot_details['channel'] = $_POST['channel_id'];

  // Grab the generated list of valid users in the accompanying Slack workspace
  $slackUsers = include $botCodePath . '/config/slackUsers.generated.php';
  // Look for "authorization" to use this (is the requesting user at least in the known Slack users)
  if (!array_search($_POST['user_id'], array_column($slackUsers['members'], 'id'))) {
    header("Content-Type: application/json");
    $response = array('response_type' => 'ephemeral', 'text' => 'Not authorized.');
    print json_encode($response);
    die();
  // Was the command invoked with the `help` keyword? Generate help response
  } else if (strpos($_POST['text'], 'help') !== false) {
    // Grab just the remaining text from the `help` argument (regardless of `help`'s position in the command string)
    $arguments = array_filter(array_map('trim', explode('help', $_POST['text'])));
    switch (reset($arguments)) {
      case 'conditions':
        $blockResponse = getHelpContentBlocks('conditions');
        break;
      case 'forecast':
        $blockResponse = getHelpContentBlocks('forecast');
        break;
      case 'history':
        $blockResponse = getHelpContentBlocks('history');
        break;
      default:
        $blockResponse = getHelpContentBlocks();
        break;
    }
    // Create basic text response (fallback text pointing to project site)
    $responseText = "The help command failed to generate output. Please visit https://tempestweatherbot.mzonline.com/ for help information.";

    header("Content-Type: application/json");
    $response = array('response_type' => 'ephemeral', 'text' => $responseText, 'blocks' => $blockResponse);
    print json_encode($response);
    die();
  // Looks like it'll be a valid request -- proceed with some weather magic
  } else {
    // Parse out the text of the command for keywords
    $parsedArgs = explode(' ', $_POST['text']);
    // Does the user want a private response?
    $private = (strpos($_POST['text'], 'private') !== false) ? true : false;

    // Command string can't have the private keyword, so we remove it
    if ($private) {
      array_pop($parsedArgs);
      $commandArgs = implode(' ', $parsedArgs);
    } else {
      $commandArgs = $_POST['text'];
    }

    // Determine the type/nature of a seemingly valid request
    if ((!isset($parsedArgs[0])) || ('' == $parsedArgs[0]) || ("now" == trim($parsedArgs[0])) || ("private" == trim($parsedArgs[0]))) {
      // Does the command match any of the 'current conditions' (incl. no text/default) patterns?
      $natureOfRequest = 'current';
    } else if (("alerts" == trim($parsedArgs[0]))) {
      // Is the argument 'yesterday' or negative (history)?
      $natureOfRequest = 'alerts';
    } else if ("next" == trim($parsedArgs[0])) {
      // Does the command match any of the 'forecast range' keyword patterns?
      $natureOfRequest = 'forecastrange';
    } else if (((("last" == trim($parsedArgs[0])) || ("this" == trim($parsedArgs[0]))) && (("week" == trim($parsedArgs[1])) || ("month" == trim($parsedArgs[1])) || ("year" == trim($parsedArgs[1])))) || (strpos($commandArgs, ' to ') !== false)) {
      // Does the command match any of the 'history range' keyword patterns?
      $natureOfRequest = 'historyrange';
    } else if (("yesterday" == trim($parsedArgs[0])) || (strtotime($commandArgs) < time())) {
      // Is the argument 'yesterday' or negative (history)?
      $natureOfRequest = 'dayhistory';
   } else {
      // Assume a forecast otherwise
      $natureOfRequest = 'forecast';
    }

    // Complete request based on its nature/path
    switch ($natureOfRequest) {
      // Current Observation
      case 'current':
        require $botCodePath . '/NWSAlert.php';
        require $botCodePath . '/NWSAlertFunctions.php';
        $slackbot_details['icon_emoji'] = ':thermometer:';

        $alertDataFile = $botCodePath . '/config/nwsAlerts.generated.php';
        // Refresh the alert data if it's older than 10 minutes
        if (filemtime($alertDataFile) < (time() - 600)) {
          getAlertsByPoint(true);
        }

        $alertData = include $alertDataFile;
        $activeAlerts = count($alertData['features']);

        if ($activeAlerts > 0) {
          $alert = new NWSAlert($alertData['features'][0]);
        } else {
          $alert = null;
        }

        getLastStationObservation();
        $lastObservation = include $botCodePath . '/config/lastObservation.generated.php';
        $observation = new TempestObservation('current', $lastObservation['obs'][0]);

        // Create basic text response (fallback)
        $responseText = "At $observation->f_timestamp, the temperature was $observation->f_temperature (feels like $observation->f_feelsLike) with a $observation->f_windDir wind at $observation->f_windAvg.";
        // Use blocks for prettier response
        $slackbot_details['blocks'] = getCurrentObservationBlocks($observation, $alert);

        $result = SlackPost($responseText, $_POST['response_url'], $private, $slackbot_details, $debug_bot);
        if ($debug_bot) {
          header("Content-Type: application/json");
          print json_encode(array('response_type' => 'ephemeral', 'text' => $_POST['command'] . ' ' . $_POST['text'] . ' output response: ' . $result));
        }
        break;
      // Alerts
      case 'alerts':
        require $botCodePath . '/NWSAlert.php';
        require $botCodePath . '/NWSAlertFunctions.php';
        $slackbot_details['icon_emoji'] = ':warning:';

        $alertDataFile = $botCodePath . '/config/nwsAlerts.generated.php';
        // Refresh the alert data if it's older than 10 minutes
        if (filemtime($alertDataFile) < (time() - 600)) {
          getAlertsByPoint(true);
        }

        $alertData = include $alertDataFile;
        $activeAlerts = count($alertData['features']);

        if ($activeAlerts > 1) {
          // TODO: Handle situation in which more than one alert is active at a given time
          //  Possibly rank by the alertSeverityIndex?    
        } else if ($activeAlerts > 0) {
          $alert = new NWSAlert($alertData['features'][0]);

          // Create basic text response (fallback)
          $responseText = $alert->headline;
          // Use blocks for prettier response
          $slackbot_details['blocks'] = $alert->getFullAlertBlocks();

          $result = SlackPost($responseText, $_POST['response_url'], $private, $slackbot_details, $debug_bot);
          if ($debug_bot) {
            header("Content-Type: application/json");
            print json_encode(array('response_type' => 'ephemeral', 'text' => $_POST['command'] . ' ' . $_POST['text'] . ' output response: ' . $result));
          }
        } else {
          // Create basic text response (fallback)
          $responseText = "No active alerts at this time.";
          // Use blocks for prettier response
          $slackbot_details['blocks'] = [array('type'=>'header','text'=>array('type'=>'plain_text','text'=>'Active NWS alerts','emoji'=>true)), array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'No active alerts at this time.'))];

          $result = SlackPost($responseText, $_POST['response_url'], $private, $slackbot_details, $debug_bot);
          if ($debug_bot) {
            header("Content-Type: application/json");
            print json_encode(array('response_type' => 'ephemeral', 'text' => $_POST['command'] . ' ' . $_POST['text'] . ' output response: ' . $result));
          }
        }
        break;
      // Forecast Range
      case 'forecastrange':
        // Identify the desired timestamp
        $desiredTime = strtotime($commandArgs);
        // We didn't match any valid time forecast string, so privately push back an 'error'
        if ((false === $desiredTime) && (strpos($commandArgs, 'next') === false)) {
          header("Content-Type: application/json");
          $response = array('response_type' => 'ephemeral', 'text' => "`$_POST[text]` is not a valid time specification for this bot. Bot help is availble with the `$bot_slashcommand help` command.");
          print json_encode($response);
          die();
        // A valid timestamp format for forecast was provided, so go forward
        } else {
          $slackbot_details['icon_emoji'] = ':crystal_ball:';

          // Parse out and translate the supported statements
          $forecastArgs = explode(' ', $commandArgs);
          $dayKeyword = strpos($commandArgs, 'day');
          $weekKeyword = strpos($commandArgs, 'week');
          // Show a forecast "range" up to 10 days
          $forecastRange = ("next" == trim($forecastArgs[0]));
          if ($forecastRange) {
            unset($forecastArgs[0]);
            $commandArgs = implode(' ', $forecastArgs);
          }

          // Modify the $commandArgs for day/week keywords
          if ($dayKeyword || $weekKeyword) {
              $matchStamp = strtotime("midnight " . $commandArgs);
          } else {
            $matchStamp = strtotime($commandArgs);
          }

          $hoursToForecast = ($matchStamp - time()) / 3600;

          getStationForecast();
          $stationForecast = include $botCodePath . '/config/stationForecast.generated.php';
          $dailyData = $stationForecast['forecast']['daily'];
          $hourlyData = $stationForecast['forecast']['hourly'];

          // Look for "day" matches from our argument(s), specifically the last one
          $dayCount = 0;
          $dayMatches = array();
          foreach ($dailyData as $dayForecast) {
            if (in_array($matchStamp, $dayForecast)) {
              $dayMatches[] = $dayCount;
              }
            $dayCount++;
          }

          // Look for "hour" matches from our argument(s), specifically the last one
          $hourvalue = (round($matchStamp / 3600) * 3600);
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
            $dayForecasts = array();
            $day = 1;
            while  ($day <= $dayMatches[0]) {
              $dayForecasts[] = new TempestObservation('day_forecast', $dailyData[$day]);
              $day++;
            }

            // Create basic text response (fallback) -- just the _last_ forecast match
            // Get the last ID since we dynamically adjust to keep ourselves under the block limit
            $lastForecast = array_key_last($dayForecasts);
            $responseText = "The forecast for " . $dayForecasts[$lastForecast]->f_timestamp . ": " . $dayForecasts[$lastForecast]->conditions . " with a high of " . $dayForecasts[$lastForecast]->f_high_temperature . " (low: " . $dayForecasts[$lastForecast]->f_low_temperature . ").";
            if ($dayForecasts[$lastForecast]->precip_probability > 0) { $responseText .= " There's a " . $dayForecasts[$lastForecast]->f_precip_probability . " chance of " . $dayForecasts[$lastForecast]->f_precip_type . "."; }
            $responseText .= " Sunrise: " . $dayForecasts[$lastForecast]->f_sunrise . " | Sunset: " . $dayForecasts[$lastForecast]->f_sunset;

            // Use blocks for prettier and complete response
            $slackbot_details['blocks'] = getForecastDayRangeBlocks($dayForecasts);

            $result = SlackPost($responseText, $_POST['response_url'], $private, $slackbot_details, $debug_bot);
            if ($debug_bot) {
              header("Content-Type: application/json");
              print json_encode(array('response_type' => 'ephemeral', 'text' => $_POST['command'] . ' ' . $_POST['text'] . ' output response: ' . $result));
            }
          // Do we match an "hourly" string?
          } else if (count($hourMatches) > 0) {
            // Adjust our forecast cadence based on an 8-hour interval (this keeps the block output under Slack's maximum of 50)
            $useEveryXHour = ceil((($matchStamp - time()) / 3600) / 8);

            $hourForecasts = array();
            $hour = 0;
            while  ($hour <= $hourMatches[0]) {
              $hourForecasts[] = new TempestObservation('hour_forecast', $hourlyData[$hour]);
              $hour += $useEveryXHour;
            }

            // Create basic text response (fallback) -- just the _last_ forecast match
            // Get the last ID since we dynamically adjust to keep ourselves under the block limit
            $lastForecast = array_key_last($hourForecasts);
            $responseText = "The forecast for " . $hourForecasts[$lastForecast]->f_timestamp . ": " . $hourForecasts[$lastForecast]->f_temperature . " (feels like " . $hourForecasts[$lastForecast]->f_feelsLike . ")";
            if ($hourForecasts[$lastForecast]->precip_probability > 0) { $responseText .= " with a " . $hourForecasts[$lastForecast]->f_precip_probability . " chance of " . $hourForecasts[$lastForecast]->f_precip_type . ". "; }
            $responseText .= $hourForecasts[$lastForecast]->f_windDir . " winds averaging " . $hourForecasts[$lastForecast]->f_windAvg . ".";

            // Use blocks for prettier response
            $slackbot_details['blocks'] = getForecastHourRangeBlocks($hourForecasts);

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
        break;
      // Multi-Day history range/period summary
      case 'historyrange':
        // Parse out and translate the supported statements
        $historyArgs = explode(' ', $commandArgs);
        if ("last" == trim($historyArgs[0])) {
          // "last" [week/month/year]
          if ("week" == trim($historyArgs[1])) {
            // Relative to now
            //$startRange = strtotime('yesterday') - 518400;
            //$endRange = strtotime('yesterday') + 86340;
            // Last week (assuming Monday == beginning of week)
            $startRange = strtotime('midnight Monday this week') - 604800;
            $endRange = strtotime('midnight Monday this week') - 60;
          }
          if ("month" == trim($historyArgs[1])) {
            $startRange = strtotime('midnight first day of last month');
            $endRange = strtotime('midnight last day of last month') + 86340;
          }
          if ("year" == trim($historyArgs[1])) {
            $startRange = strtotime('midnight first day of January last year');
            $endRange = strtotime('midnight last day of December last year') + 86340;
          }
          // Correct start time if it precedes the bot's history
          if ($startRange < strtotime($bot_historyStarts)) {
            $startRange = strtotime($bot_historyStarts);
          }
        } else if ("this" == trim($historyArgs[0])) {
          // "this" [week/month/year]
          if ("week" == trim($historyArgs[1])) {
            $startRange = strtotime('midnight Monday this week');
          }
          if ("month" == trim($historyArgs[1])) {
            $startRange = strtotime('midnight first day of this month');
          }
          if ("year" == trim($historyArgs[1])) {
            $startRange = strtotime('midnight first day of January this year');
          }
          $endRange = time();
          // Correct start time if it precedes the bot's history
          if ($startRange < strtotime($bot_historyStarts)) {
            $startRange = strtotime($bot_historyStarts);
          }
        } else {
          // Other date range provided; split by the "to" keyword
          $historyDates = explode(' to ', $commandArgs);
          // Correct dates if submitted in the wrong order and/or if start time precedes the bot's history
          if (strtotime($historyDates[0]) > strtotime($historyDates[1])) {
            if (strtotime($historyDates[1]) < strtotime($bot_historyStarts)) {
              $startRange = strtotime($bot_historyStarts);
            } else {
              $startRange = strtotime($historyDates[1]);
            }
            $endRange = strtotime($historyDates[0]) + 86340;
          } else {
            if (strtotime($historyDates[0]) < strtotime($bot_historyStarts)) {
              $startRange = strtotime($bot_historyStarts);
            } else {
              $startRange = strtotime($historyDates[0]);
            }
            $endRange = strtotime($historyDates[1]) + 86340;
          }
        }

        // Time to fail or fetch an observation range
        if ($endRange < strtotime($bot_historyStarts)) {
          // A pre-historic request; fail kindly.
          header("Content-Type: application/json");
          $response = array('response_type' => 'ephemeral', 'text' => "The end of your range (`". date('Y-m-d', $endRange) . "`) is before this bot's history start date of $bot_historyStarts. Try again with a different date range. Bot help is availble with the `$bot_slashcommand help` command.");
          print json_encode($response);
          die();
        } else {
          // Process the request
          $slackbot_details['icon_emoji'] = ':book:';

          getStationObservationsByRange($startRange, $endRange);
          $observations = new TempestObservation('history', json_decode(file_get_contents($tempestStationHistoryPath . 'stationHistory.generated.json'), true)['obs']);
          
          // Create basic text response (fallback)
          $responseText = "During the period of $observations->f_historyDateStart to $observations->f_historyDateEnd, the high temperature was $observations->f_highTemp with a low of $observations->f_lowTemp. The average temperature for the period was $observations->f_avgTemp. A high wind gust of $observations->f_highWindGust was observed $observations->f_highWindTimestamp.";
          // Use blocks for prettier response
          $slackbot_details['blocks'] = getMultiDayHistoryBlocks($observations);

          $result = SlackPost($responseText, $_POST['response_url'], $private, $slackbot_details, $debug_bot);
          if ($debug_bot) {
            header("Content-Type: application/json");
            print json_encode(array('response_type' => 'ephemeral', 'text' => $_POST['command'] . ' ' . $_POST['text'] . ' output response: ' . $result));
          }
        }
        break;
      // Standard "single-day" history summary
      case 'dayhistory':
        if (strtotime($commandArgs) < strtotime($bot_historyStarts)) {
          // A pre-historic request; fail kindly.
          header("Content-Type: application/json");
          $response = array('response_type' => 'ephemeral', 'text' => "`$_POST[text]` is before this bot's history start date of $bot_historyStarts. Try again with a more current date. Bot help is availble with the `$bot_slashcommand help` command.");
          print json_encode($response);
          die();
        } else {
          $slackbot_details['icon_emoji'] = ':book:';

          $fileToGrab = $tempestStationHistoryPath . date('Y-m-d', strtotime($commandArgs)) . '.json';
          if (strtotime($commandArgs) >= strtotime('yesterday')) {
            // Always grab a refreshed copy of data for the most recent ~0-48 hours
            getStationObservationsByDay(date('Y-m-d', strtotime($commandArgs)));
          }
          if (!file_exists($fileToGrab)) {
            // Pull the requested day's data if it doesn't exist.
            getStationObservationsByDay(date('Y-m-d', strtotime($commandArgs)));
          }
          $observation = new TempestObservation('history', json_decode(file_get_contents($fileToGrab), true)['obs']);
          
          // Create basic text response (fallback)
          $responseText = "On $observation->f_historyDateStart, the high temperature was $observation->f_highTemp with a low of $observation->f_lowTemp. The average temperature for the day was $observation->f_avgTemp. A high wind gust of $observation->f_highWindGust was observed $observation->f_highWindTimestamp.";
          // Use blocks for prettier response
          $slackbot_details['blocks'] = getDayHistoryBlocks($observation);

          $result = SlackPost($responseText, $_POST['response_url'], $private, $slackbot_details, $debug_bot);
          if ($debug_bot) {
            header("Content-Type: application/json");
            print json_encode(array('response_type' => 'ephemeral', 'text' => $_POST['command'] . ' ' . $_POST['text'] . ' output response: ' . $result));
          }
        }
        break;
      // Forecast
      case 'forecast':
        // Identify the desired timestamp
        $desiredTime = strtotime($commandArgs);
        // We didn't match any valid time forecast string, so privately push back an 'error'
        if (false === $desiredTime) {
          header("Content-Type: application/json");
          $response = array('response_type' => 'ephemeral', 'text' => "`$_POST[text]` is not a valid time specification for this bot. Bot help is availble with the `$bot_slashcommand help` command.");
          print json_encode($response);
          die();
        // A valid timestamp format for forecast was provided, so go forward
        } else {
          // Parse out and translate the supported statements
          $dayKeyword = strpos($commandArgs, 'day');
          $weekKeyword = strpos($commandArgs, 'week');

          // Modify the $commandArgs for day/week keywords
          if ($dayKeyword || $weekKeyword) {
              $matchStamp = strtotime("midnight " . $commandArgs);
          } else {
            $matchStamp = strtotime($commandArgs);
          }

          $hoursToForecast = ($desiredTime - time()) / 3600;

          getStationForecast();
          $stationForecast = include $botCodePath . '/config/stationForecast.generated.php';
          $dailyData = $stationForecast['forecast']['daily'];
          $hourlyData = $stationForecast['forecast']['hourly'];

          // Look for "day" matches from our argument(s)
          $dayCount = 0;
          $dayMatches = array();
          foreach ($dailyData as $dayForecast) {
            if (in_array($matchStamp, $dayForecast)) {
              $dayMatches[] = $dayCount;
              }
            $dayCount++;
          }

          // Look for "hour" matches from our argument(s)
          $hourvalue = (round($matchStamp / 3600) * 3600);
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
            $observation = new TempestObservation('day_forecast', $dailyData[$dayMatches[0]]);

            $slackbot_details['icon_emoji'] = $slackConditionIcons[$observation->icon];
            // Create basic text response (fallback)
            $responseText = "The forecast for $observation->f_timestamp: $observation->conditions with a high of $observation->f_high_temperature (low: $observation->f_low_temperature).";
            if ($observation->precip_probability> 0) { $responseText .= " There's a $observation->f_precip_probability chance of $observation->f_precip_type."; }
            $responseText .= " Sunrise: $observation->f_sunrise | Sunset: $observation->f_sunset.";

            // Use blocks for prettier response
            $slackbot_details['blocks'] = getDayForecastBlocks($observation);
            
            $result = SlackPost($responseText, $_POST['response_url'], $private, $slackbot_details, $debug_bot);
            if ($debug_bot) {
              header("Content-Type: application/json");
              print json_encode(array('response_type' => 'ephemeral', 'text' => $_POST['command'] . ' ' . $_POST['text'] . ' output response: ' . $result));
            }
          // Do we match an "hourly" string?
          } else if (count($hourMatches) > 0) {
            $observation = new TempestObservation('hour_forecast', $hourlyData[$hourMatches[0]]);

            $slackbot_details['icon_emoji'] = $slackConditionIcons[$observation->icon];
            // Create basic text response (fallback)
            $responseText = "The forecast for $observation->f_timestamp: $observation->f_temperature (feels like $observation->f_feelsLike)";
            if ($observation->precip_probability > 0) { $responseText .= " with a $observation->f_precip_probability chance of $observation->f_precip_type"; }
            $responseText .= ". $observation->f_windDir winds averaging $observation->f_windAvg.";

            // Use blocks for prettier response
            $slackbot_details['blocks'] = getHourForecastBlocks($observation);

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
        break;
      // Handle anything else with an error
      default:
        header("Content-Type: application/json");
        $response = array('response_type' => 'ephemeral', 'text' => "`$_POST[text]` is not a valid command for this bot. Bot help is availble with the `$bot_slashcommand help` command.");
        print json_encode($response);
        die();
        break;
    }
  }
?>