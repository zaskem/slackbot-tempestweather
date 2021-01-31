<?php
  require_once __DIR__. '/config/bot.php';

  // The guide/mechanism at https://app.slack.com/block-kit-builder/ is awesome for testing block structure
  $helpContextBlock = array('type'=>'context','elements'=>[array('type'=>'mrkdwn','text'=>'Get help with `' . $bot_slashcommand . ' help`')]);
  $botVersionBlock = array('type'=>'context','elements'=>[array('type'=>'mrkdwn','text'=>$bot_name . ' version: ' . $bot_version)]);
  $botSourceHeaderBlock = array('type'=>'header','text'=>array('type'=>'plain_text','text'=>'Bot Project and Source Code','emoji'=>true));
  $botSourceDetailBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'The ' . $bot_name . ' project page and source code on GitHub can be found at https://tempestweatherbot.mzonline.com/.'));
  $dividerBlock = array('type'=>'divider');


  function getHelpContentBlocks($args = null) {
    global $bot_name, $bot_slashcommand, $bot_historyStarts, $dividerBlock, $botVersionBlock, $botSourceHeaderBlock, $botSourceDetailBlock;

    $helpHeaderBlock = array('type'=>'header','text'=>array('type'=>'plain_text','text'=>$bot_name . ' Help','emoji'=>true));
    $keywordPrivateBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'The `private` keyword can be appended to the _end_ of any command to privately respond to the calling user. This keyword _*must*_ be the last argument in all commands.'));

    // Generate help content based on topic
    switch ($args) {
      case 'conditions':
        // Current Conditions Detail
        $currentConditionsHeaderBlock = array('type'=>'header','text'=>array('type'=>'plain_text','text'=>$bot_name . ' Help: Current Conditions','emoji'=>true));
        $exampleCommandSection = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'Example commands (e.g. `' . $bot_slashcommand . ' [argument]`)'));
        $exampleCommandBlock = array('type'=>'section','fields'=>
          array(['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . '`'],['type'=>'mrkdwn','text'=>'Display current conditions'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' now`'],['type'=>'mrkdwn','text'=>'Display current conditions'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' now private`'],['type'=>'mrkdwn','text'=>'Display current conditions with a private response']
          )
        );
        // Build block response
        $blocks = [$currentConditionsHeaderBlock, $exampleCommandSection, $exampleCommandBlock, $keywordPrivateBlock, $dividerBlock, $botVersionBlock];
        break;
        
      case 'forecast':
        // Forecast Range Detail
        $forecastHeaderBlock = array('type'=>'header','text'=>array('type'=>'plain_text','text'=>$bot_name . ' Help: Forecast Commands and Range','emoji'=>true));
        $forecastDetailBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'The ' . $bot_name . ' can respond to forecast inquiries _up to 10 days_ from the current time. Arguments (`hours`, `days`, and `week`) should fall within the specified ranges:'));
        $forecastRangeBlock = array('type'=>'section','fields'=>
          array(['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' X hour[s]`'],['type'=>'mrkdwn','text'=>'X can range `0` to `240`. Display the forecast for the specified hour'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' X day[s]`'],['type'=>'mrkdwn','text'=>'X can range `0` to `10`. Display the hour-specific forecast +X day[s]'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' X week`'],['type'=>'mrkdwn','text'=>'X can only be `1`']
          )
        );
        $forecastNuanceBlock = array('type'=>'context','elements'=>[array('type'=>'mrkdwn','text'=>'When providing a numeric request (e.g. `X hours`) the _hour-specific_ forecast will be returned based on the request time. However, an `X days` request made at 5 p.m. will return the _daily_ forecast for `X` days from now.')]);
        $forecastRelativeBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'Relative keywords (`tomorrow`, `next`, and weekday names) can also be used:'));
        $forecastRelativeRangeBlock = array('type'=>'section','fields'=>
          array(['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' tomorrow`'],['type'=>'mrkdwn','text'=>'Display forecast for tomorrow'],
          ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' Tuesday`'],['type'=>'mrkdwn','text'=>'Display forecast for Tuesday'],
          ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' next 24 hours`'],['type'=>'mrkdwn','text'=>'Display forecasts for the next 24 hours'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' next 10 days`'],['type'=>'mrkdwn','text'=>'Display forecasts for the next 10 days']
          )
        );
        $forecastRelativeRangeDetailBlock = array('type'=>'context','elements'=>[array('type'=>'mrkdwn','text'=>'When providing a numeric relative request (e.g. `next X [hours/days/week]`) `X` must fall in the ranges identified above. Relative `day` and `week` requests will return daily forecasts for the period. Relative `hour` forecasts will generate dynamically-appropriate intervals for the period, generally not to exceed 10 individual forecasts per request.')]);
        // Build block response
        $blocks = [$forecastHeaderBlock, $forecastDetailBlock, $forecastRangeBlock, $forecastNuanceBlock, $dividerBlock, $forecastRelativeBlock, $forecastRelativeRangeBlock, $forecastRelativeRangeDetailBlock, $keywordPrivateBlock, $dividerBlock, $botVersionBlock];
        break;

      case 'history':
        // History Range Detail
        $historyHeaderBlock = array('type'=>'header','text'=>array('type'=>'plain_text','text'=>$bot_name . ' Help: History Commands and Range','emoji'=>true));
        $historyDetailBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'The ' . $bot_name . ' can respond with daily station history summaries _on or *after*_ ' . date('F j, Y', strtotime($bot_historyStarts)) . '.'));
        $historyExactBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'Specify a specific date or date range. `dateString` should be in `YYYY-MM-DD` or `DD-MM-YYYY` format:'));
        $historyExactRangeBlock = array('type'=>'section','fields'=>
          array(['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' X hour/day/week/month[s]`'],['type'=>'mrkdwn','text'=>'X is a *negative* number within range. Display summary for the matching date'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' dateString`'],['type'=>'mrkdwn','text'=>'Display summary for the matching date'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' dateString to dateString`'],['type'=>'mrkdwn','text'=>'Display summary for the submitted period']
          )
        );
        $historyRelativeBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'Relative keywords (`today`, `yesterday`, `last`, and `this`) can also be used:'));
        $historyRelativeRangeBlock = array('type'=>'section','fields'=>
          array(['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' today`'],['type'=>'mrkdwn','text'=>'Display daily summary for yesterday'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' yesterday`'],['type'=>'mrkdwn','text'=>'Display daily summary for yesterday'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' last week/month/year`'],['type'=>'mrkdwn','text'=>'Display summary for the requested period'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' this week/month/year`'],['type'=>'mrkdwn','text'=>'Display summary for the requested period (through the current day).']
          )
        );
        $historyRelativeDetailBlock = array('type'=>'context','elements'=>[array('type'=>'mrkdwn','text'=>'Note: `week`s are relative to Mondays.')]);
        // Build block response
        $blocks = [$historyHeaderBlock, $historyDetailBlock, $dividerBlock, $historyExactBlock, $historyExactRangeBlock, $dividerBlock, $historyRelativeBlock, $historyRelativeRangeBlock, $historyRelativeDetailBlock, $dividerBlock, $keywordPrivateBlock, $dividerBlock, $botVersionBlock];
        break;

      default:
        // Generate generic help content
        // Opening Content
        $openingBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'The ' . $bot_name . ' responds to a number of arguments.'));
        // Command Examples
        $exampleCommandSection = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'Example commands:'));
        $exampleConditionsBlock = array('type'=>'section','fields'=>
          array(['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . '`'],['type'=>'mrkdwn','text'=>'Display current conditions'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' now`'],['type'=>'mrkdwn','text'=>'Display current conditions'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' private`'],['type'=>'mrkdwn','text'=>'Display current conditions privately']
          )
        );
        $exampleForecastBlock = array('type'=>'section','fields'=>
          array(['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' Tuesday`'],['type'=>'mrkdwn','text'=>'Display the forecast for Tuesday'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' 8 hour`'],['type'=>'mrkdwn','text'=>'Display the forecast +8 hours from now'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' 2 days`'],['type'=>'mrkdwn','text'=>'Display the forecast two days from now'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' next 5 days`'],['type'=>'mrkdwn','text'=>'Display the five-day forecast']
          )
        );
        $exampleHistoryBlock = array('type'=>'section','fields'=>
          array(['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' today`'],['type'=>'mrkdwn','text'=>'Display summary for today'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' yesterday`'],['type'=>'mrkdwn','text'=>'Display summary for yesterday'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' last month`'],['type'=>'mrkdwn','text'=>'Display summary for last month']
          )
        );
        // Keyword Details
        $keywordHeaderBlock = array('type'=>'header','text'=>array('type'=>'plain_text','text'=>'Bot Keywords','emoji'=>true));
        $keywordDetailBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'The ' . $bot_name . ' also responds to unique keywords:'));
        $keywordHelpCommandBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'Detailed `help` topics are also supported:'));
        $keywordHelpBlock = array('type'=>'section','fields'=>
          array(['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' help`'],['type'=>'mrkdwn','text'=>'Display this generic help information'],
          ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' help conditions`'],['type'=>'mrkdwn','text'=>'Help regarding current conditions'],
          ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' help forecast`'],['type'=>'mrkdwn','text'=>'Help regarding station forecasts'],
          ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' help history`'],['type'=>'mrkdwn','text'=>'Help regarding history summaries']
          )
        );
        // Build block response
        $blocks = [$helpHeaderBlock, $openingBlock, $exampleCommandSection, $exampleConditionsBlock, $exampleForecastBlock, $exampleHistoryBlock, $dividerBlock, $keywordHeaderBlock, $keywordDetailBlock, $keywordPrivateBlock, $keywordHelpCommandBlock, $keywordHelpBlock, $dividerBlock, $botSourceHeaderBlock, $botSourceDetailBlock, $dividerBlock, $botVersionBlock];
        break;
    }
    return $blocks;
  }


  function getCurrentObservationBlocks($observation, $args = null) {
    global $helpContextBlock, $dividerBlock;

    // Current Observations Block Content
    $headerBlock = array('type'=>'header','text'=>array('type'=>'plain_text','text'=>'Current conditions at ' . $observation->f_timestamp,'emoji'=>true));
    $temperatureBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'Temperature: '. $observation->f_temperature . ' (feels like ' . $observation->f_feelsLike . ')'));
    $windBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'Wind at ' . $observation->f_windAvg . ' from the ' . $observation->f_windDir . '.'));

    // Build block response
    $blocks = [$headerBlock, $temperatureBlock, $windBlock, $dividerBlock, $helpContextBlock];

    return $blocks;
  }


  function getDayForecastBlocks($observation, $args = null) {
    global $helpContextBlock, $dividerBlock;

    // "Day" Forecast Block Content
    $headerBlock = array('type'=>'header','text'=>array('type'=>'plain_text','text'=>'Forecast for ' . $observation->f_timestamp,'emoji'=>true));
    $conditionsBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>$observation->conditions . ' with a high of ' . $observation->f_high_temperature . ' (low: ' . $observation->f_low_temperature . ').'));
    $precipBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'There is a ' . $observation->f_precip_probability . ' chance of ' . $observation->f_precip_type . '.'));
    $sunBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'Sunrise: ' . $observation->f_sunrise . ' | Sunset: ' . $observation->f_sunset . '.'));

    // Build block response
    if ($observation->precip_probability > 0) {
      $blocks = [$headerBlock, $conditionsBlock, $precipBlock, $sunBlock, $dividerBlock, $helpContextBlock];
    } else {
      $blocks = [$headerBlock, $conditionsBlock, $sunBlock, $dividerBlock, $helpContextBlock];
    }

    return $blocks;
  }


  function getHourForecastBlocks($observation, $args = null) {
    global $helpContextBlock, $dividerBlock;

    // "Hour" Forecast Block Content
    $headerBlock = array('type'=>'header','text'=>array('type'=>'plain_text','text'=>'Forecast for ' . $observation->f_timestamp,'emoji'=>true));
    $conditionsBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>$observation->conditions . ', ' . $observation->f_temperature . ' (feels like ' . $observation->f_feelsLike . ')'));
    $precipBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'There is a ' . $observation->f_precip_probability . ' chance of ' . $observation->f_precip_type . '.'));
    $windBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>$observation->f_windDir . ' winds averaging ' . $observation->f_windAvg . '.'));

    // Build block response
    if ($observation->precip_probability > 0) {
      $blocks = [$headerBlock, $conditionsBlock, $precipBlock, $windBlock, $dividerBlock, $helpContextBlock];
    } else {
      $blocks = [$headerBlock, $conditionsBlock, $windBlock, $dividerBlock, $helpContextBlock];
    }

    return $blocks;
  }


  function getForecastDayRangeBlocks($observations, $args = null) {
    global $helpContextBlock, $dividerBlock, $slackConditionIcons;

    $lastObs = count($observations) - 1;
    // "Day" Forecast Block Content
    $headerBlock = array('type'=>'header','text'=>array('type'=>'plain_text','text'=>'Forecast for ' . $observations[0]->f_timestamp . ' to ' . $observations[$lastObs]->f_timestamp,'emoji'=>true));
    $blocks[] = $headerBlock;

    foreach ($observations as $observation) {
      $dayHeaderBlock = array('type'=>'header','text'=>array('type'=>'plain_text','text'=>$slackConditionIcons[$observation->icon] . ' ' . $observation->f_timestamp . ':','emoji'=>true));
      $conditionsBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>$observation->conditions . ' with a high of ' . $observation->f_high_temperature . ' (low: ' . $observation->f_low_temperature . ').'));
      $precipBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'There is a ' . $observation->f_precip_probability . ' chance of ' . $observation->f_precip_type . '.'));
      $sunBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'Sunrise: ' . $observation->f_sunrise . ' | Sunset: ' . $observation->f_sunset . '.'));

      $blocks[] = $dividerBlock;
      $blocks[] = $dayHeaderBlock;
      $blocks[] = $conditionsBlock;
      if ($observation->f_precip_probability > 0) {
        $blocks[] = $precipBlock;
      }
      $blocks[] = $sunBlock;
    }

    $blocks[] = $dividerBlock;
    $blocks[] = $helpContextBlock;

    return $blocks;
  }


  function getForecastHourRangeBlocks($observations, $args = null) {
    global $helpContextBlock, $dividerBlock, $slackConditionIcons;

    $lastObs = count($observations) - 1;
    // "Hour" Forecast Block Content
    $headerBlock = array('type'=>'header','text'=>array('type'=>'plain_text','text'=>'Forecast for ' . $observations[0]->f_timestamp . ' to ' . $observations[$lastObs]->f_timestamp,'emoji'=>true));
    $blocks[] = $headerBlock;

    foreach ($observations as $observation) {
      $hourHeaderBlock = array('type'=>'header','text'=>array('type'=>'plain_text','text'=>$slackConditionIcons[$observation->icon] . ' ' . $observation->f_timestamp . ':','emoji'=>true));
      $conditionsBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>$observation->conditions . ', ' . $observation->f_temperature . ' (feels like ' . $observation->f_feelsLike . ')'));
      $precipBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'There is a ' . $observation->f_precip_probability . ' chance of ' . $observation->f_precip_type . '.'));
      $windBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>$observation->f_windDir . ' winds averaging ' . $observation->f_windAvg . '.'));

      $blocks[] = $dividerBlock;
      $blocks[] = $hourHeaderBlock;
      $blocks[] = $conditionsBlock;
      if ($observation->precip_probability > 0) {
        $blocks[] = $precipBlock;
      }
      $blocks[] = $windBlock;
    }

    $blocks[] = $dividerBlock;
    $blocks[] = $helpContextBlock;

    return $blocks;
  }


  function getDayHistoryBlocks($observation, $args = null) {
    global $helpContextBlock, $dividerBlock;

    // Daily History Block Content
    $headerBlock = array('type'=>'header','text'=>array('type'=>'plain_text','text'=>'Weather Summary for ' . $observation->historyDateStart,'emoji'=>true));
    $temperatureBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'*Temperature:*
    _High:_ ' . $observation->f_highTemp . '
    _Low:_ ' . $observation->f_lowTemp . '
    _Average for the day:_ ' . $observation->f_avgTemp));
    $pressureBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'*Pressure:*
    _High:_ ' . $observation->f_highPress . '
    _Low:_ ' . $observation->f_lowPress . '
    _Trend for the day:_ ' . $observation->pressTrend));
    $sunlightBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'*Sunlight/Brightness:*
    _High UV Index:_ ' . $observation->highUV . '
    _Highest Solar Radiation:_ ' . $observation->f_highSolarRad . '
    _Highest Brightness:_ ' . $observation->f_highLux));
    $windBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'*Wind Conditions:*
    _High Gust:_ ' . $observation->highWindDir . ' ' . $observation->f_highWindGust . $observation->highWindTimestamp . '
    _Average Speed:_ ' . $observation->f_windAvg));
    $precipBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'*Precipitation and Lightning:*
    _Daily Rainfall:_ ' . $observation->f_dailyPrecip));
    if ($observation->strikeCount > 0) {
      $lightningBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'_Lightning Strikes Detected:_ ' . $observation->f_strikeCount . '
      _Closest Lightning Strike:_ ' . $observation->f_closestStrike . ' at ' . $observation->closeStrikeTimestamp));
    } else {
      $lightningBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'_No Lightning Detected_'));
    }

    // Build block response
    $blocks = [$headerBlock, $temperatureBlock, $dividerBlock, $pressureBlock, $dividerBlock, $sunlightBlock, $dividerBlock, $windBlock, $dividerBlock, $precipBlock, $lightningBlock, $dividerBlock, $helpContextBlock];

    return $blocks;
  }


  function getMultiDayHistoryBlocks($observations, $args = null) {
    global $helpContextBlock, $dividerBlock;

    // History Range Block Content
    $headerBlock = array('type'=>'header','text'=>array('type'=>'plain_text','text'=>'Weather Summary for ' . $observations->historyDateStart . ' through ' . $observations->historyDateEnd,'emoji'=>true));
    $temperatureBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'*Temperature:*
    _High:_ ' . $observations->f_highTemp . '
    _Low:_ ' . $observations->f_lowTemp . '
    _Average over the period:_ ' . $observations->f_avgTemp));
    $sunlightBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'*Sunlight/Brightness:*
    _High UV Index:_ ' . $observations->highUV . '
    _Highest Solar Radiation:_ ' . $observations->f_highSolarRad . '
    _Highest Brightness:_ ' . $observations->f_highLux));
    $windBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'*Wind Conditions:*
    _High Gust:_ ' . $observations->highWindDir . ' ' . $observations->f_highWindGust . $observations->long_highWindTimestamp . '
    _Average Speed:_ ' . $observations->f_windAvg));
    if ($observations->strikeCount > 0) {
      $lightningBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'_Lightning Strikes Detected:_ ' . $observations->f_strikeCount . '
      _Closest Lightning Strike:_ ' . $observations->f_closestStrike . ' on ' . $observations->closeStrikeTimestamp));
    } else {
      $lightningBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'_No Lightning Detected_'));
    }

    // Build block response
    $blocks = [$headerBlock, $temperatureBlock, $dividerBlock, $sunlightBlock, $dividerBlock, $windBlock, $dividerBlock, $lightningBlock, $dividerBlock, $helpContextBlock];

    return $blocks;
  }
?>