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
        $forecastDetailBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'The ' . $bot_name . ' can respond to forecast inquiries _up to 10 days_ from the current time. This means arguments (`hours`, `days`, and `week`) should fall within the specified ranges. Arguments beyond this range will return a private error or display the current conditions.'));
        $forecastRangeBlock = array('type'=>'section','fields'=>
          array(['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' X hour[s]`'],['type'=>'mrkdwn','text'=>'X can range `1` to `240`. Display the forecast for the specified hour'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' X day[s]`'],['type'=>'mrkdwn','text'=>'X can range `1` to `10`. Display the hour-specific forecast +X day[s]'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' Saturday`'],['type'=>'mrkdwn','text'=>'`tomorrow` or weekday name. Display the "day" forecast.'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' X week`'],['type'=>'mrkdwn','text'=>'X can only be `1`']
          )
        );
        $forecastNuanceBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'It is important to understand that when providing a numeric request (`X` [hours/days/week]) the _hour-specific_ forecast will be returned based on the request time. For example, a 2-day request made at 5 p.m. will return the _hour_ forecast for 5 p.m. two days from now. Use the relative day name such as `tomorrow` or weekday names for the overall day forecast.'));
        // Build block response
        $blocks = [$forecastHeaderBlock, $forecastDetailBlock, $forecastRangeBlock, $forecastNuanceBlock, $keywordPrivateBlock, $dividerBlock, $botVersionBlock];
        break;

      case 'history':
        // History Range Detail
        $historyHeaderBlock = array('type'=>'header','text'=>array('type'=>'plain_text','text'=>$bot_name . ' Help: History Commands and Range','emoji'=>true));
        $historyDetailBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'The ' . $bot_name . ' can respond with daily station history summaries _on or *after*_ ' . date('F j, Y', strtotime($bot_historyStarts)) . ' through the current date.'));
        $historyExactBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'Specify a specific date or date range. `dateString` should be in `YYYY-MM-DD` or `DD-MM-YYYY` format:'));
        $historyExactRangeBlock = array('type'=>'section','fields'=>
          array(['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' X hour/day/week/month[s]`'],['type'=>'mrkdwn','text'=>'X is a *negative* number within range. Display summary for the matching date'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' dateString`'],['type'=>'mrkdwn','text'=>'Display summary for the matching date'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' dateString to dateString`'],['type'=>'mrkdwn','text'=>'Display summary for the submitted period']
          )
        );
        $historyRelativeBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'Relative keywords (`yesterday`, `last`, and `this`) can also be used:'));
        $historyRelativeRangeBlock = array('type'=>'section','fields'=>
          array(['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' yesterday`'],['type'=>'mrkdwn','text'=>'Display daily summary for yesterday'],
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
        $openingBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'The ' . $bot_name . ' responds to a number of arguments and keywords. When provided _no_ argument (e.g. `' . $bot_slashcommand . '`) the bot will respond with current conditions.'));
        // Command Examples
        $exampleCommandSection = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'Example commands:'));
        $exampleCommandBlock = array('type'=>'section','fields'=>
          array(['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . '`'],['type'=>'mrkdwn','text'=>'Display current conditions'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' Tuesday`'],['type'=>'mrkdwn','text'=>'Display the forecast for Tuesday'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' 7 days`'],['type'=>'mrkdwn','text'=>'Display the forecast seven days from now'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' yesterday`'],['type'=>'mrkdwn','text'=>'Display summary for yesterday'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' last month`'],['type'=>'mrkdwn','text'=>'Display summary for last month']
          )
        );
        // Keyword Details
        $keywordHeaderBlock = array('type'=>'header','text'=>array('type'=>'plain_text','text'=>'Bot Keywords','emoji'=>true));
        $keywordDetailBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'The ' . $bot_name . ' responds to unique keywords unrelated to the weather conditions or forecast.'));
        $keywordHelpCommandBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'Several detailed help topics are supported:'));
        $keywordHelpBlock = array('type'=>'section','fields'=>
          array(['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' help`'],['type'=>'mrkdwn','text'=>'Display this generic help information'],
          ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' help conditions`'],['type'=>'mrkdwn','text'=>'Display help for obtaining current conditions'],
          ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' help forecast`'],['type'=>'mrkdwn','text'=>'Display help for obtaining station forecasts'],
          ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' help history`'],['type'=>'mrkdwn','text'=>'Display help for obtaining history summaries']
          )
        );
        // Build block response
        $blocks = [$helpHeaderBlock, $openingBlock, $exampleCommandSection, $exampleCommandBlock, $dividerBlock, $keywordHeaderBlock, $keywordDetailBlock, $keywordPrivateBlock, $keywordHelpCommandBlock, $keywordHelpBlock, $dividerBlock, $botSourceHeaderBlock, $botSourceDetailBlock, $dividerBlock, $botVersionBlock];
        break;
    }
    return $blocks;
  }


  function getCurrentObservationBlocks($observation, $args = null) {
    global $helpContextBlock, $dividerBlock;

    // Current Observations Block Content
    $headerBlock = array('type'=>'header','text'=>array('type'=>'plain_text','text'=>'Current conditions at ' . $observation['timestamp'],'emoji'=>true));
    $temperatureBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'Temperature: '. $observation['temperature'] . ' (feels like ' . $observation['feelsLike'] . ')'));
    $windBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'Wind at ' . $observation['windAvg'] . ' from the ' . $observation['windDir'] . '.'));

    // Build block response
    $blocks = [$headerBlock, $temperatureBlock, $windBlock, $dividerBlock, $helpContextBlock];

    return $blocks;
  }


  function getDayForecastBlocks($observation, $args = null) {
    global $helpContextBlock, $dividerBlock;

    // "Day" Forecast Block Content
    $headerBlock = array('type'=>'header','text'=>array('type'=>'plain_text','text'=>'Forecast for ' . $observation['timestamp'],'emoji'=>true));
    $conditionsBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>$observation['conditions'] . ' with a high of ' . $observation['high_temperature'] . ' (low: ' . $observation['low_temperature'] . ').'));
    $precipBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'There is a ' . $observation['precip_probability'] . ' chance of ' . $observation['precip_type'] . '.'));
    $sunBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'Sunrise: ' . $observation['sunrise'] . ' | Sunset: ' . $observation['sunset'] . '.'));

    // Build block response
    if ($observation['precip_probability'] > 0) {
      $blocks = [$headerBlock, $conditionsBlock, $precipBlock, $sunBlock, $dividerBlock, $helpContextBlock];
    } else {
      $blocks = [$headerBlock, $conditionsBlock, $sunBlock, $dividerBlock, $helpContextBlock];
    }

    return $blocks;
  }


  function getHourForecastBlocks($observation, $args = null) {
    global $helpContextBlock, $dividerBlock;

    // "Hour" Forecast Block Content
    $headerBlock = array('type'=>'header','text'=>array('type'=>'plain_text','text'=>'Forecast for ' . $observation['timestamp'],'emoji'=>true));
    $conditionsBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>$observation['conditions'] . ', ' . $observation['temperature'] . ' (feels like ' . $observation['feelsLike'] . ')'));
    $precipBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'There is a ' . $observation['precip_probability'] . ' chance of ' . $observation['precip_type'] . '.'));
    $windBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>$observation['windDir'] . ' winds averaging ' . $observation['windAvg'] . '.'));

    // Build block response
    if ($observation['precip_probability'] > 0) {
      $blocks = [$headerBlock, $conditionsBlock, $precipBlock, $windBlock, $dividerBlock, $helpContextBlock];
    } else {
      $blocks = [$headerBlock, $conditionsBlock, $windBlock, $dividerBlock, $helpContextBlock];
    }

    return $blocks;
  }


  function getDayHistoryBlocks($summaryData, $args = null) {
    global $helpContextBlock, $dividerBlock;

    // Daily History Block Content
    $headerBlock = array('type'=>'header','text'=>array('type'=>'plain_text','text'=>'Weather Summary for ' . $summaryData['historyDate'],'emoji'=>true));
    $temperatureBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'*Temperature:*
    _High:_ ' . $summaryData['highTemp'] . '
    _Low:_ ' . $summaryData['lowTemp'] . '
    _Average for the day:_ ' . $summaryData['avgTemp']));
    $pressureBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'*Pressure:*
    _High:_ ' . $summaryData['highPress'] . '
    _Low:_ ' . $summaryData['lowPress'] . '
    _Trend for the day:_ ' . $summaryData['pressTrend']));
    $sunlightBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'*Sunlight/Brightness:*
    _High UV Index:_ ' . $summaryData['highUV'] . '
    _Highest Solar Radiation:_ ' . $summaryData['highSolarRad'] . '
    _Highest Brightness:_ ' . $summaryData['highLux']));
    $windBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'*Wind Conditions:*
    _High Gust:_ ' . $summaryData['highWindDir'] . ' ' . $summaryData['highWindGust'] . ' at ' . $summaryData['highWindTimestamp'] . '
    _Average Speed:_ ' . $summaryData['windAvg']));
    $precipBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'*Precipitation and Lightning:*
    _Daily Rainfall:_ ' . $summaryData['dailyPrecip']));
    if ($summaryData['strikeCount'] > 0) {
      $lightningBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'_Lightning Strikes Detected:_ ' . $summaryData['strikeCount'] . '
      _Closest Lightning Strike:_ ' . $summaryData['closestStrike'] . ' at ' . $summaryData['closeStrikeTimestamp']));
    } else {
      $lightningBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'_No Lightning Detected_'));
    }

    // Build block response
    $blocks = [$headerBlock, $temperatureBlock, $dividerBlock, $pressureBlock, $dividerBlock, $sunlightBlock, $dividerBlock, $windBlock, $dividerBlock, $precipBlock, $lightningBlock, $dividerBlock, $helpContextBlock];

    return $blocks;
  }


  function getMultiDayHistoryBlocks($summaryData, $args = null) {
    global $helpContextBlock, $dividerBlock;

    // History Range Block Content
    $headerBlock = array('type'=>'header','text'=>array('type'=>'plain_text','text'=>'Weather Summary for ' . $summaryData['historyDateStart'] . ' through ' . $summaryData['historyDateEnd'],'emoji'=>true));
    $temperatureBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'*Temperature:*
    _High:_ ' . $summaryData['highTemp'] . '
    _Low:_ ' . $summaryData['lowTemp'] . '
    _Average over the period:_ ' . $summaryData['avgTemp']));
    $sunlightBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'*Sunlight/Brightness:*
    _High UV Index:_ ' . $summaryData['highUV'] . '
    _Highest Solar Radiation:_ ' . $summaryData['highSolarRad'] . '
    _Highest Brightness:_ ' . $summaryData['highLux']));
    $windBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'*Wind Conditions:*
    _High Gust:_ ' . $summaryData['highWindDir'] . ' ' . $summaryData['highWindGust'] . ' on ' . $summaryData['highWindTimestamp'] . '
    _Average Speed:_ ' . $summaryData['windAvg']));
    if ($summaryData['strikeCount'] > 0) {
      $lightningBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'_Lightning Strikes Detected:_ ' . $summaryData['strikeCount'] . '
      _Closest Lightning Strike:_ ' . $summaryData['closestStrike'] . ' on ' . $summaryData['closeStrikeTimestamp']));
    } else {
      $lightningBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'_No Lightning Detected_'));
    }

    // Build block response
    $blocks = [$headerBlock, $temperatureBlock, $dividerBlock, $sunlightBlock, $dividerBlock, $windBlock, $dividerBlock, $lightningBlock, $dividerBlock, $helpContextBlock];

    return $blocks;
  }
?>