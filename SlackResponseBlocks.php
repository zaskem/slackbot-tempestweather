<?php
  require_once __DIR__. '/config/bot.php';

  // The guide/mechanism at https://app.slack.com/block-kit-builder/ is awesome for testing block structure
  $helpContextBlock = array('type'=>'context','elements'=>[array('type'=>'mrkdwn','text'=>'Get help with `' . $bot_slashcommand . ' help`')]);
  $botVersionBlock = array('type'=>'context','elements'=>[array('type'=>'mrkdwn','text'=>$bot_name . ' version: ' . $bot_version)]);
  $dividerBlock = array('type'=>'divider');


  function getHelpContentBlocks($args = null) {
    global $bot_name, $bot_slashcommand, $dividerBlock, $botVersionBlock;

    $helpHeaderBlock = array('type'=>'header','text'=>array('type'=>'plain_text','text'=>$bot_name . ' Help','emoji'=>true));
    if (!is_null($args)) {
      // Generate help content for specific argument provided
      // Opening Content
      $openingBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'The ' . $bot_name . ' will respond to ' . $args[0] . ' to perform a specific action.'));

      // Command Examples
      $argumentDetailBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'Example commands (e.g. `' . $bot_slashcommand . ' ' . $args[0] . '`)'));
      $argumentExampleBlock = array('type'=>'section','fields'=>
        array(['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' ' . $args[0] . ' <>`'],['type'=>'mrkdwn','text'=>'Display '],
          ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' ' . $args[0] . ' <>`'],['type'=>'mrkdwn','text'=>'Display ']
        )
      );

      // Build block response
      $blocks = [$helpHeaderBlock,$openingBlock,$argumentDetailBlock,$argumentExampleBlock,$dividerBlock,$contextBlock];
    } else {
      // Generate the generic help overview
      // Opening Content
      $openingBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'The ' . $bot_name . ' responds to a number of arguments and keywords explained below. When provided _no_ argument (e.g. `' . $bot_slashcommand . '`) the bot will respond with current conditions.'));

      // Command Examples
      $exampleCommandSection = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'Example commands (e.g. `' . $bot_slashcommand . ' [argument]`)'));
      $exampleCommandsBlock = array('type'=>'section','fields'=>
        array(['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' <blank>`'],['type'=>'mrkdwn','text'=>'Display current conditions'],
          ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' now`'],['type'=>'mrkdwn','text'=>'Display current conditions'],
          ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' Tuesday`'],['type'=>'mrkdwn','text'=>'Display the forecast for Tuesday'],
          ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' 85 hours`'],['type'=>'mrkdwn','text'=>'Display the forecast 85 hours from now'],
          ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' 3 days`'],['type'=>'mrkdwn','text'=>'Display the forecast three days from now']
        )
      );

      // Forecast Range Detail
      $forecastHeaderBlock = array('type'=>'header','text'=>array('type'=>'plain_text','text'=>'Forecast Range','emoji'=>true));
      $forecastDetailBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'The ' . $bot_name . ' can respond to forecast inquiries _up to 10 days_ from the current time. This means arguments (`hours`, `days`, and `week`) should fall within the specified ranges. Arguments outside this range will return a private error or display the current conditions.'));
      $forecastRangeBlock = array('type'=>'section','fields'=>
        array(['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' X hours`'],['type'=>'mrkdwn','text'=>'X can range `1` to `120`'],
          ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' X days`'],['type'=>'mrkdwn','text'=>'X can range `1` to `10`'],
          ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' X week`'],['type'=>'mrkdwn','text'=>'X can only be `1`']
        )
      );

      // History Range Detail
      $historyHeaderBlock = array('type'=>'header','text'=>array('type'=>'plain_text','text'=>'History Range','emoji'=>true));
      $historyDetailBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'The ' . $bot_name . ' can respond to station history summaries.'));
      $historyRangeBlock = array('type'=>'section','fields'=>
        array(['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' X hours`'],['type'=>'mrkdwn','text'=>'X can range `1` to `120`'],
          ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' X days`'],['type'=>'mrkdwn','text'=>'X can range `1` to `10`'],
          ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' X week`'],['type'=>'mrkdwn','text'=>'X can only be `1`']
        )
      );
      
      // Keyword Details
      $keywordHeaderBlock = array('type'=>'header','text'=>array('type'=>'plain_text','text'=>'Bot Keyword Actions','emoji'=>true));
      $keywordDetailBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'The ' . $bot_name . ' has unique keywords unrelated to the weather conditions or forecast. Supported keyword actions:'));
      $keywordExampleBlock = array('type'=>'section','fields'=>
        array(['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' help`'],['type'=>'mrkdwn','text'=>'Display this help information'],
          ['type'=>'mrkdwn','text'=>'`' . $bot_slashcommand . ' 6 hours private`'],['type'=>'mrkdwn','text'=>'Display the forecast 6 hours from now with a private response']
        )
      );
      $keywordPrivateBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'The `private` keyword can be appended to the _end_ of any command to privately respond to the calling user. This keyword _*must*_ be the last argument in all commands.'));

      // Help Footer
      $botSourceHeaderBlock = array('type'=>'header','text'=>array('type'=>'plain_text','text'=>'Bot Project and Source Code','emoji'=>true));
      $botSourceDetailBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'The ' . $bot_name . ' project page and source code on GitHub can be found at https://tempestweatherbot.mzonline.com/.'));

      // Build block response
      $blocks = [$helpHeaderBlock,$openingBlock,$exampleCommandSection,$exampleCommandsBlock,$dividerBlock,$forecastHeaderBlock,$forecastDetailBlock,$forecastRangeBlock,$dividerBlock,$historyHeaderBlock,$historyDetailBlock,$historyRangeBlock,$dividerBlock,$keywordHeaderBlock,$keywordDetailBlock,$keywordExampleBlock,$keywordPrivateBlock,$dividerBlock,$botSourceHeaderBlock,$botSourceDetailBlock,$dividerBlock,$botVersionBlock];
    }

    return $blocks;
  }


  function getCurrentObservationBlocks($observation, $args = null) {
    global $helpContextBlock, $dividerBlock;

    // Current Observations Block Content
    $headerBlock = array('type'=>'header','text'=>array('type'=>'plain_text','text'=>'Current conditions at ' . $observation['timestamp'],'emoji'=>true));
    $temperatureBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'Temperature: '. $observation['temperature'] . ' (feels like ' . $observation['feelsLike'] . ')'));
    $windBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'Wind at ' . $observation['windAvg'] . ' MPH from the ' . $observation['windDir'] . '.'));

    // Build block response
    $blocks = [$headerBlock,$temperatureBlock,$windBlock,$dividerBlock,$helpContextBlock];

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
      $blocks = [$headerBlock,$conditionsBlock,$precipBlock,$sunBlock,$dividerBlock,$helpContextBlock];
    } else {
      $blocks = [$headerBlock,$conditionsBlock,$sunBlock,$dividerBlock,$helpContextBlock];
    }

    return $blocks;
  }


  function getHourForecastBlocks($observation, $args = null) {
    global $helpContextBlock, $dividerBlock;

    // "Hour" Forecast Block Content
    $headerBlock = array('type'=>'header','text'=>array('type'=>'plain_text','text'=>'Forecast for ' . $observation['timestamp'],'emoji'=>true));
    $conditionsBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>$observation['conditions'] . ', ' . $observation['temperature'] . ' (feels like ' . $observation['feelsLike'] . ')'));
    $precipBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>'There is a ' . $observation['precip_probability'] . ' chance of ' . $observation['precip_type'] . '.'));
    $windBlock = array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>$observation['windDir'] . ' winds averaging ' . $observation['windAvg'] . ' MPH.'));

    // Build block response
    if ($observation['precip_probability'] > 0) {
      $blocks = [$headerBlock,$conditionsBlock,$precipBlock,$windBlock,$dividerBlock,$helpContextBlock];
    } else {
      $blocks = [$headerBlock,$conditionsBlock,$windBlock,$dividerBlock,$helpContextBlock];
    }

    return $blocks;
  }


  function getHistoryBlocks($observation, $args = null) {
    global $helpContextBlock, $dividerBlock;

    // History Block Content


    return $blocks;
  }
?>