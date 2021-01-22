<?php
  /**
   * THE FOLLOWING LINE IS THE _ONLY_ REQUIRED EDIT FOR THIS REQUEST URL HANDLER.
   * CHANGE THIS TO THE NON-WEB ACCESSIBLE PATH HOUSING THE REST OF THE BOT CODE.
   */
  $botCodePath = __DIR__ . '/../';

  require $botCodePath . '/TempestAPIFunctions.php';
  require $botCodePath . '/UtilityFunctions.php';
  require $botCodePath . '/SlackPost.php';
  require $botCodePath . '/SlackResponseBlocks.php';
  require $botCodePath . '/config/bot.php';
  include $botCodePath . '/config/tempest.php';

  // Set the channel from which this request was called/invoked
  $slackbot_details['channel'] = $_POST['channel_id'];

  // Grab the generated list of valid users in the accompanying Slack workspace
  $slackUsers = include $botCodePath . '/config/slackUsers.generated.php';
  // Look for "authorization" to use this (is the requesting user at least in the known Slack users)
  if (!in_array($_POST['user_id'], $slackUsers)) {
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

    // Determine the type/nature of a seemingly valid request
    if (('' == $parsedArgs[0]) || ("now" == trim($parsedArgs[0])) || ("private" == trim($parsedArgs[0]))) {
      // Does the command match any of the 'current conditions' (incl. no text/default) patterns?
      $natureOfRequest = 'current';
    } else if (((("last" == trim($parsedArgs[0])) || ("this" == trim($parsedArgs[0]))) && (("week" == trim($parsedArgs[1])) || ("month" == trim($parsedArgs[1])) || ("year" == trim($parsedArgs[1])))) || (strpos($_POST['text'], ' to ') !== false)) {
      // Does the command match any of the 'history range' keyword patterns?
      $natureOfRequest = 'historyrange';
    } else if (("yesterday" == trim($parsedArgs[0])) || ($parsedArgs[0] < 0)) {
      // Is the argument negative (history)?
      $natureOfRequest = 'dayhistory';
    } else {
      // Assume a forecast otherwise
      $natureOfRequest = 'forecast';
    }

    // Command string can't have the private keyword, so we remove it
    if ($private) {
      array_pop($parsedArgs);
      $commandArgs = implode(' ', $parsedArgs);
    } else {
      $commandArgs = $_POST['text'];
    }

    // Complete request based on its nature/path
    switch ($natureOfRequest) {
      // Current Observation
      case 'current':
        $slackbot_details['icon_emoji'] = ':thermometer:';

        getLastStationObservation();
        $lastObservation = include $botCodePath . '/config/lastObservation.generated.php';
        $obsData = $lastObservation['obs'][0];

        $modifiedObs = array(
          'timestamp' => date('g:i a', $obsData['timestamp']),
          'temperature' => convertCToF($obsData['air_temperature']) . $tempUnitLabel,
          'pressure' => convertMbToInHg($obsData['sea_level_pressure']) . "$pressureUnitLabel",
          'feelsLike' => convertCToF($obsData['feels_like']) . $tempUnitLabel,
          'windAvg' => convertMPSToMPH($obsData['wind_avg']) . " $windUnitLabel",
          'windDir' => convertDegreesToWindDirection($obsData['wind_direction'])
        );

        // Create basic text response (fallback)
        $responseText = "At $modifiedObs[timestamp], the temperature was $modifiedObs[temperature] (feels like $modifiedObs[feelsLike]) with a $modifiedObs[windDir] wind at $modifiedObs[windAvg].";
        // Use blocks for prettier response
        $slackbot_details['blocks'] = getCurrentObservationBlocks($modifiedObs);

        $result = SlackPost($responseText, $_POST['response_url'], $private, $slackbot_details, $debug_bot);
        if ($debug_bot) {
          header("Content-Type: application/json");
          print json_encode(array('response_type' => 'ephemeral', 'text' => $_POST['command'] . ' ' . $_POST['text'] . ' output response: ' . $result));
        }
        break;
      // Multi-Day history range/period summary
      case 'historyrange':
        // Parse out and translate the supported statements
        $historyArgs = explode(' ', $commandArgs);
        if ("last" == trim($historyArgs[0])) {
          // "last" [week/month/year]
          if ("week" == trim($historyArgs[1])) {
            $startRange = strtotime('midnight last Monday');
            $endRange = strtotime('midnight last Sunday') + 86340;
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
            $startRange = strtotime('midnight Monday');
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
          $historyDates = explode(' to ', $_POST['text']);
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
          $observationData = json_decode(file_get_contents($tempestStationHistoryPath . 'stationHistory.generated.json'), true)['obs'];
          $totalObs = count($observationData);
          $lastObsID = count($observationData)-1;
          $timeDiff = $observationData[$lastObsID][0] - $observationData[0][0];
          $highWindID = getParentKey(max(array_column($observationData, 3)), $observationData, 3);
          $strikeCount = array_sum(array_column($observationData, 15));
          $closeStrike = (0 == $strikeCount) ? 0 : min(array_filter(array_column($observationData, 14)));
          $closeStrikeID = (0 == $strikeCount) ? '' : getParentKey($closeStrike, $observationData, 14);

          $summaryData = array(
            'historyDateStart' => ($timeDiff < 86400) ? date('l, F j, g:i a', $observationData[0][0]) : date('l, F j', $observationData[0][0]),
            'historyDateEnd' => ($timeDiff < 86400) ? date('l, F j, g:i a', $observationData[$lastObsID][0]) : date('l, F j', $observationData[$lastObsID][0]),
            'highTemp' => convertCToF(max(array_column($observationData, 7))) . $tempUnitLabel,
            'lowTemp' => convertCToF(min(array_column($observationData, 7))) . $tempUnitLabel,
            'avgTemp' => convertCToF(array_sum(array_column($observationData, 7))/$totalObs) . $tempUnitLabel,
            'highUV' => max(array_column($observationData, 10)),
            'highSolarRad' => number_format(max(array_column($observationData, 11)), 0, '.', ',') . ' ' . $solarRadLabel,
            'highLux' => number_format(max(array_column($observationData, 9)), 0, '.', ','). ' lux',
            'highWindGust' => convertMPSToMPH(max(array_column($observationData, 3))) . ' ' . $windUnitLabel,
            'highWindTimestamp' => date("F j", $observationData[$highWindID][0]) . ' at ' . date("g:i a", $observationData[$highWindID][0]),
            'highWindDir' => convertDegreesToWindDirection($observationData[$highWindID][4]),
            'windAvg' => convertMPSToMPH(array_sum(array_column($observationData, 2))/$totalObs) . ' ' . $windUnitLabel,
            'strikeCount' => number_format($strikeCount, 0, '.', ',')
          );
          if ($strikeCount > 0) {
            $summaryData['closeStrike'] = $closeStrike;
            $summaryData['closeStrikeTimestamp'] = date("F j", $observationData[$closeStrikeID][0]) . ' at ' . date("g:i a", $observationData[$closeStrikeID][0]);
            $summaryData['closestStrike'] = convertKmToMiles($closeStrike) . ' ' . $distanceUnitLabel;
          } else {
            $summaryData['closeStrike'] = '';
            $summaryData['closeStrikeTimestamp'] = '';
            $summaryData['closestStrike'] = '';
          }
          
          // Create basic text response (fallback)
          $responseText = "During the period of $summaryData[historyDateStart] to $summaryData[historyDateEnd], the high temperature was $summaryData[highTemp] with a low of $summaryData[lowTemp]. The average temperature for the period was $summaryData[avgTemp]. A high wind gust of $summaryData[highWindGust] was observed on $summaryData[highWindTimestamp].";
          // Use blocks for prettier response
          $slackbot_details['blocks'] = getMultiDayHistoryBlocks($summaryData);

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
          $observationData = json_decode(file_get_contents($fileToGrab), true)['obs'];
          $totalObs = count($observationData);
          $lastObsID = count($observationData)-1;
          $pressDiff = $observationData[0][6] - $observationData[$lastObsID][6];
          $highWindID = getParentKey(max(array_column($observationData, 3)), $observationData, 3);
          $strikeCount = array_sum(array_column($observationData, 15));
          $closeStrike = (0 == $strikeCount) ? 0 : min(array_filter(array_column($observationData, 14)));
          $closeStrikeID = (0 == $strikeCount) ? '' : getParentKey($closeStrike, $observationData, 14);
          
          $summaryData = array(
            'historyDate' => date('l, F j', $observationData[0][0]),
            'highTemp' => convertCToF(max(array_column($observationData, 7))) . $tempUnitLabel,
            'lowTemp' => convertCToF(min(array_column($observationData, 7))) . $tempUnitLabel,
            'avgTemp' => convertCToF(array_sum(array_column($observationData, 7))/$totalObs) . $tempUnitLabel,
            'highPress' => convertMbToInHg(max(array_column($observationData, 6))) . $pressureUnitLabel,
            'lowPress' => convertMbToInHg(min_mod(array_column($observationData, 6))) . $pressureUnitLabel,
            'pressTrend' => ($pressDiff >= 1) ? "Falling" : "Rising",
            'highUV' => max(array_column($observationData, 10)),
            'highSolarRad' => number_format(max(array_column($observationData, 11)), 0, '.', ',') . ' ' . $solarRadLabel,
            'highLux' => number_format(max(array_column($observationData, 9)), 0, '.', ','). ' lux',
            'highWindGust' => convertMPSToMPH(max(array_column($observationData, 3))) . ' ' . $windUnitLabel,
            'highWindTimestamp' => date("g:i a", $observationData[$highWindID][0]),
            'highWindDir' => convertDegreesToWindDirection($observationData[$highWindID][4]),
            'windAvg' => convertMPSToMPH(array_sum(array_column($observationData, 2))/$totalObs) . ' ' . $windUnitLabel,
            'dailyPrecip' => convertMmToInch($observationData[$lastObsID][20]) . $precipUnitLabel,
            'strikeCount' => number_format($strikeCount, 0, '.', ',')
          );
          if ($strikeCount > 0) {
            $summaryData['closeStrike'] = $closeStrike;
            $summaryData['closeStrikeTimestamp'] = date("g:i a", $observationData[$closeStrikeID][0]);
            $summaryData['closestStrike'] = convertKmToMiles($closeStrike) . ' ' . $distanceUnitLabel;
          } else {
            $summaryData['closeStrike'] = '';
            $summaryData['closeStrikeTimestamp'] = '';
            $summaryData['closestStrike'] = '';
          }
          
          // Create basic text response (fallback)
          $responseText = "On $summaryData[historyDate], the high temperature was $summaryData[highTemp] with a low of $summaryData[lowTemp]. The average temperature for the day was $summaryData[avgTemp]. A high wind gust of $summaryData[highWindGust] was observed at $summaryData[highWindTimestamp].";
          // Use blocks for prettier response
          $slackbot_details['blocks'] = getDayHistoryBlocks($summaryData);


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
            if (in_array(strtotime($commandArgs), $dayForecast)) {
              $dayMatches[] = $dayCount;
              }
            $dayCount++;
          }

          // Look for "hour" matches from our argument(s)
          $hourvalue = (round(strtotime($commandArgs) / 3600) * 3600);
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
              'high_temperature' => $obsData['air_temp_high'] . $tempUnitLabel,
              'low_temperature' => $obsData['air_temp_low'] . $tempUnitLabel,
              'precip_type' => (isset($obsData['precip_type'])) ? $obsData['precip_type'] : '',
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

            // Use blocks for prettier response
            $slackbot_details['blocks'] = getDayForecastBlocks($modifiedObs);
            
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
              'temperature' => $obsData['air_temperature'] . $tempUnitLabel,
              'pressure' => $obsData['sea_level_pressure'] . $pressureUnitLabel,
              'precip_type' => (isset($obsData['precip_type'])) ? $obsData['precip_type'] : '',
              'precip_probability' => $obsData['precip_probability'] . "%",
              'conditions' => $obsData['conditions'],
              'feelsLike' => $obsData['feels_like'] . $tempUnitLabel,
              'windAvg' => $obsData['wind_avg'] . $windUnitLabel,
              'windDir' => $obsData['wind_direction_cardinal']
            );

            $slackbot_details['icon_emoji'] = $slackConditionIcons[$obsData['icon']];
            // Create basic text response (fallback)
            $responseText = "The forecast for $modifiedObs[timestamp]: $modifiedObs[temperature] (feels like $modifiedObs[feelsLike])";
            if ($modifiedObs['precip_probability']> 0) { $responseText .= " with a $modifiedObs[precip_probability] chance of $modifiedObs[precip_type]"; }
            $responseText .= ". $modifiedObs[windDir] winds averaging $modifiedObs[windAvg].";

            // Use blocks for prettier response
            $slackbot_details['blocks'] = getHourForecastBlocks($modifiedObs);

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