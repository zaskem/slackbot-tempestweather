<?php
  require_once __DIR__. '/config/bot.php';

  // The guide/mechanism at https://app.slack.com/block-kit-builder/ is awesome for testing block structure
  $helpContextBlock = array('type'=>'context','elements'=>[array('type'=>'mrkdwn','text'=>'Get help with `' . $bot_slashcommand . ' help` | '. $bot_name . ' version: ' . $bot_version)]);
  $keywordPrivateBlock = array('type'=>'context','elements'=>[array('type'=>'mrkdwn','text'=>'The `private` keyword can be appended to the _end_ of any command to privately respond to the calling user. This keyword _*must*_ be the last argument in all commands.')]);
  $botVersionBlock = array('type'=>'context','elements'=>[array('type'=>'mrkdwn','text'=>$bot_name . ' version: ' . $bot_version)]);
  $botSourceHeaderBlock = array('type'=>'header','text'=>array('type'=>'plain_text','text'=>'Bot Project and Source Code','emoji'=>true));
  $botSourceDetailBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'The ' . $bot_name . ' project page and source code on GitHub can be found at https://tempestweatherbot.mzonline.com/.'));
  $dividerBlock = array('type'=>'divider');


  /**
   * getHelpContentBlocks($args = null) - generate help block content based on $args
   * 
   * $args - string identifying desired help content (null = default text)
   * 
   * @return array of block content payload
   */
  function getHelpContentBlocks($args = null) {
    global $bot_name, $bot_slashcommand, $bot_historyStarts, $dividerBlock, $keywordPrivateBlock, $botVersionBlock, $botSourceHeaderBlock, $botSourceDetailBlock;

    $blocks = [array('type'=>'header','text'=>array('type'=>'plain_text','text'=>$bot_name . ' Help','emoji'=>true))];

    // Generate help content based on topic
    switch ($args) {
      case 'conditions':
        array_push($blocks, array('type'=>'header','text'=>array('type'=>'plain_text','text'=>$bot_name . ' Help: Current Conditions','emoji'=>true)));
        array_push($blocks, array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'Example commands (e.g. `' . $bot_slashcommand . ' [argument]`)')));
        array_push($blocks, array('type'=>'section','fields'=>
          array(['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . '`'],['type'=>'mrkdwn','text'=>'Display current conditions'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' now`'],['type'=>'mrkdwn','text'=>'Display current conditions'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' now private`'],['type'=>'mrkdwn','text'=>'Display current conditions with a private response'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' alerts`'],['type'=>'mrkdwn','text'=>'Display current weather alerts']
          )
        ), $dividerBlock, $keywordPrivateBlock, $dividerBlock, $botVersionBlock);
        break;
        
      case 'forecast':
        array_push($blocks, array('type'=>'header','text'=>array('type'=>'plain_text','text'=>$bot_name . ' Help: Forecast Commands and Range','emoji'=>true)));
        array_push($blocks, array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'The ' . $bot_name . ' can respond to forecast inquiries _up to 10 days_ from the current time. Arguments (`hours`, `days`, and `week`) should fall within the specified ranges:')));
        array_push($blocks, array('type'=>'section','fields'=>
          array(['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' X hour[s]`'],['type'=>'mrkdwn','text'=>'X can range `0` to `240`. Display the forecast for the specified hour'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' X day[s]`'],['type'=>'mrkdwn','text'=>'X can range `0` to `10`. Display the hour-specific forecast +X day[s]'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' X week`'],['type'=>'mrkdwn','text'=>'X can only be `1`']
          )
        ));
        array_push($blocks, array('type'=>'context','elements'=>[array('type'=>'mrkdwn','text'=>'When providing a numeric request (e.g. `X hours`) the _hour-specific_ forecast will be returned based on the request time. However, an `X days` request made at 5 p.m. will return the _daily_ forecast for `X` days from now.')]), $dividerBlock);
        array_push($blocks, array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'Relative keywords (`today`, `tomorrow`, `next`, and weekday names) can also be used:')));
        array_push($blocks, array('type'=>'section','fields'=>
          array(['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' today forecast`'],['type'=>'mrkdwn','text'=>'Display forecast for the current day'],
          ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' tomorrow`'],['type'=>'mrkdwn','text'=>'Display forecast for tomorrow'],
          ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' Tuesday`'],['type'=>'mrkdwn','text'=>'Display forecast for Tuesday'],
          ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' next 24 hours`'],['type'=>'mrkdwn','text'=>'Display forecasts for the next 24 hours'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' next 10 days`'],['type'=>'mrkdwn','text'=>'Display forecasts for the next 10 days']
          )
        ));
        array_push($blocks, array('type'=>'context','elements'=>[array('type'=>'mrkdwn','text'=>'When providing a numeric relative request (e.g. `next X [hours/days/week]`) `X` must fall in the ranges identified above. Relative `day` and `week` requests will return daily forecasts for the period. Relative `hour` forecasts will generate dynamically-appropriate intervals for the period, generally not to exceed 10 individual forecasts per request.')]), array('type'=>'context','elements'=>[array('type'=>'mrkdwn','text'=>'The relative request `today` _without_ additional argument, when invoked before 4 p.m., will display the day’s forecast; after 4 p.m. will display the day’s history.')]), $dividerBlock, $keywordPrivateBlock, $dividerBlock, $botVersionBlock);
        break;

      case 'history':
        array_push($blocks, array('type'=>'header','text'=>array('type'=>'plain_text','text'=>$bot_name . ' Help: History Commands and Range','emoji'=>true)));
        array_push($blocks, array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'The ' . $bot_name . ' can respond with daily station history summaries _on or *after*_ ' . date('F j, Y', strtotime($bot_historyStarts)) . '.')), $dividerBlock);
        array_push($blocks, array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'Specify a specific date or date range. `dateString` should be in `YYYY-MM-DD` or `DD-MM-YYYY` format:')));
        array_push($blocks, array('type'=>'section','fields'=>
          array(['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' X hour[s]/day[s]/week[s]/month[s]`'],['type'=>'mrkdwn','text'=>'X is a *negative* number within range. Display summary for the matching date'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' dateString`'],['type'=>'mrkdwn','text'=>'Display summary for the matching date'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' dateString to dateString`'],['type'=>'mrkdwn','text'=>'Display summary for the submitted period']
          )
        ), $dividerBlock);
        array_push($blocks, array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'Relative keywords (`today`, `yesterday`, `last`, and `this`) can also be used:')));
        array_push($blocks, array('type'=>'section','fields'=>
          array(['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' today history`'],['type'=>'mrkdwn','text'=>'Display summary for the current day'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' yesterday`'],['type'=>'mrkdwn','text'=>'Display daily summary for yesterday'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' last X hour[s]/day[s]`'],['type'=>'mrkdwn','text'=>'Display summary for the previous X hour/day period'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' last week/month/year`'],['type'=>'mrkdwn','text'=>'Display summary for the requested period'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' this week/month/year`'],['type'=>'mrkdwn','text'=>'Display summary for the requested period (through the current day).']
          )
        ));
        array_push($blocks, array('type'=>'context','elements'=>[array('type'=>'mrkdwn','text'=>'The relative request `today` _without_ additional argument, when invoked before 4 p.m., will display the day’s forecast; after 4 p.m. will display the day’s history.')]), array('type'=>'context','elements'=>[array('type'=>'mrkdwn','text'=>'Note: `week`s are relative to Mondays.')]), $dividerBlock, $keywordPrivateBlock, $dividerBlock, $botVersionBlock);
        break;

      default:
        array_push($blocks, array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'The ' . $bot_name . ' responds to a number of arguments.')));
        array_push($blocks, array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'Example commands:')));
        array_push($blocks, array('type'=>'section','fields'=>
          array(['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . '`'],['type'=>'mrkdwn','text'=>'Display current conditions'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' now`'],['type'=>'mrkdwn','text'=>'Display current conditions'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' private`'],['type'=>'mrkdwn','text'=>'Display current conditions privately'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' alerts`'],['type'=>'mrkdwn','text'=>'Display current weather alerts']
          )
        ));
        array_push($blocks, array('type'=>'section','fields'=>
          array(['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' Tuesday`'],['type'=>'mrkdwn','text'=>'Display the forecast for Tuesday'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' 8 hour`'],['type'=>'mrkdwn','text'=>'Display the forecast +8 hours from now'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' 2 days`'],['type'=>'mrkdwn','text'=>'Display the forecast two days from now'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' next 5 days`'],['type'=>'mrkdwn','text'=>'Display the five-day forecast']
          )
        ));
        array_push($blocks, array('type'=>'section','fields'=>
          array(['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' today`'],['type'=>'mrkdwn','text'=>'Display forecast or summary for today'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' yesterday`'],['type'=>'mrkdwn','text'=>'Display summary for yesterday'],
            ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' last month`'],['type'=>'mrkdwn','text'=>'Display summary for last month']
          )
        ), $dividerBlock);
        array_push($blocks, array('type'=>'header','text'=>array('type'=>'plain_text','text'=>'Bot Keywords','emoji'=>true)));
        array_push($blocks, array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'The ' . $bot_name . ' also responds to unique keywords:')));
        array_push($blocks, array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'Detailed `help` topics are also supported:')));
        array_push($blocks, array('type'=>'section','fields'=>
          array(['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' help`'],['type'=>'mrkdwn','text'=>'Display this generic help information'],
          ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' help conditions`'],['type'=>'mrkdwn','text'=>'Help regarding current conditions'],
          ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' help forecast`'],['type'=>'mrkdwn','text'=>'Help regarding station forecasts'],
          ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' help history`'],['type'=>'mrkdwn','text'=>'Help regarding history summaries']
          )
        ), $dividerBlock, $botSourceHeaderBlock, $botSourceDetailBlock, $dividerBlock, $botVersionBlock);
        break;
    }
    return $blocks;
  }


  /**
   * getCurrentObservationBlocks($observation, $alert = null, $args = null) - generate current conditions block content of $observation
   * 
   * $observation - instance of TempestObservation
   * $alert - instance of NWSAlert (if available)
   * $args - currently unused
   * 
   * @return array of block content payload
   */
  function getCurrentObservationBlocks($observation, $alert = null, $args = null) {
    global $helpContextBlock, $dividerBlock, $bot_slashcommand;

    $blocks =  [array('type'=>'header','text'=>array('type'=>'plain_text','text'=>'Current conditions at ' . $observation->f_timestamp,'emoji'=>true))];

    if (is_null($alert)) {
      $alertBlocks = array();
    } else {
      $alertBlocks = $alert->getSummaryAlertBlocks();
    }
    foreach ($alertBlocks as $alertBlock) {
      array_push($blocks, $alertBlock);
    }
    array_push($blocks, $dividerBlock);

    $conditionBlocks = $observation->getCurrentObservationBlocks();
    foreach ($conditionBlocks as $conditionBlock) {
      array_push($blocks, $conditionBlock);
    }
    array_push($blocks, $dividerBlock, $helpContextBlock);

    return $blocks;
  }


  /**
   * getDayForecastBlocks($observation, $args = null) - generate "day" forecast block content of $observation
   *
   * $observation - instance of TempestObservation
   * $args - currently unused
   * 
   * @return array of block content payload
   */
  function getDayForecastBlocks($observation, $args = null) {
    global $helpContextBlock, $dividerBlock;

    $blocks = $observation->getDayForecastBlocks();
    array_push($blocks, $dividerBlock, $helpContextBlock);

    return $blocks;
  }


  /**
   * getHourForecastBlocks($observation, $args = null) - generate "hour" forecast block content of $observation
   * 
   * $observation - instance of TempestObservation
   * $args - currently unused
   * 
   * @return array of block content payload
   */
  function getHourForecastBlocks($observation, $args = null) {
    global $helpContextBlock, $dividerBlock;

    $blocks = $observation->getHourForecastBlocks();
    array_push($blocks, $dividerBlock, $helpContextBlock);

    return $blocks;
  }


  /**
   * getForecastDayRangeBlocks($observations, $args = null) - generate multiple day forecast block content for $observations
   * 
   * $observations - array of TempestObservation instances
   * $args - currently unused
   * 
   * @return array of block content payload
   */
  function getForecastDayRangeBlocks($observations, $args = null) {
    global $helpContextBlock, $dividerBlock, $slackConditionIcons;

    $lastObs = count($observations) - 1;
    $blocks = [array('type'=>'header','text'=>array('type'=>'plain_text','text'=>'Forecast for ' . $observations[0]->f_timestamp . ' to ' . $observations[$lastObs]->f_timestamp,'emoji'=>true))];
    array_push($blocks, $dividerBlock);
    foreach ($observations as $observation) {
      foreach ($observation->getDayForecastBlocks(true) as $forecastBlock) {
        array_push($blocks, $forecastBlock);
      }
      array_push($blocks, $dividerBlock);
    }
    array_push($blocks, $helpContextBlock); 

    return $blocks;
  }


  /**
   * getForecastHourRangeBlocks($observations, $args = null) - generate multiple hour forecast block content for $observations
   * 
   * $observations - array of TempestObservation instances
   * $args - currently unused
   * 
   * @return array of block content payload
   */
  function getForecastHourRangeBlocks($observations, $args = null) {
    global $helpContextBlock, $dividerBlock, $slackConditionIcons;

    $lastObs = count($observations) - 1;
    // Use "long" timestamp for hour ranges upward and beyond 24 hours
    $useLongTimestamp = (($observations[$lastObs]->time - $observations[0]->time) < 82800);
    $endTimestamp = ($useLongTimestamp) ? $observations[$lastObs]->f_timestamp : $observations[$lastObs]->f_long_timestamp;
    $blocks = [array('type'=>'header','text'=>array('type'=>'plain_text','text'=>'Forecast for ' . $observations[0]->f_timestamp . ' to ' . $endTimestamp,'emoji'=>true))];
    array_push($blocks, $dividerBlock);
    foreach ($observations as $observation) {
      foreach ($observation->getHourForecastBlocks(true) as $forecastBlock) {
        array_push($blocks, $forecastBlock);
      }
      array_push($blocks, $dividerBlock);
    }
    array_push($blocks, $helpContextBlock); 

    return $blocks;
  }


  /**
   * getHourHistoryBlocks($observation, $args = null) - generate "hour" history block content of $observation
   * 
   * $observation - instance of TempestObservation
   * $args - currently unused
   * 
   * @return array of block content payload
   */
  function getHourHistoryBlocks($observation, $args = null) {
    global $helpContextBlock, $dividerBlock;

    $blocks = $observation->getHourHistoryBlocks();
    array_push($blocks, $dividerBlock, $helpContextBlock); 

    return $blocks;
  }


  /**
   * getDayHistoryBlocks($observation, $args = null) - generate "day" history block content of $observation
   * 
   * $observation - instance of TempestObservation
   * $args - currently unused
   * 
   * @return array of block content payload
   */
  function getDayHistoryBlocks($observation, $args = null) {
    global $helpContextBlock, $dividerBlock;

    $blocks = $observation->getDayHistoryBlocks();
    array_push($blocks, $dividerBlock, $helpContextBlock); 

    return $blocks;
  }


  /**
   * getMultiDayHistoryBlocks($observations, $args = null) - generate multiple day history block content for $observations
   * 
   * $observation - instance of TempestObservation
   * $args - currently unused
   * 
   * @return array of block content payload
   */
  function getMultiDayHistoryBlocks($observations, $args = null) {
    global $helpContextBlock, $dividerBlock;

    $blocks = $observations->getMultiDayHistoryBlocks();
    array_push($blocks, $dividerBlock, $helpContextBlock); 

    return $blocks;
  }
?>