<?php
  /**
   * THE FOLLOWING LINE IS THE _ONLY_ REQUIRED EDIT FOR THIS EVENT HANDLER.
   * CHANGE THIS TO THE NON-WEB ACCESSIBLE PATH HOUSING THE REST OF THE BOT CODE.
   */
  $botCodePath = __DIR__ . '/../';

  require $botCodePath . '/config/bot.php';
  require $botCodePath . '/SlackAppHomeGenerator.php';

  // Grab the event input
  $eventArray = json_decode($_REQUEST['payload'], true);
  
  // Grab the generated list of valid users in the accompanying Slack workspace
  $slackUsers = include $botCodePath . '/config/slackUsers.generated.php';
  // Look for "authorization" to use this (is the requesting user at least in the known Slack users)
  if (array_search($eventArray['user']['id'], array_column($slackUsers['members'], 'id'))) {
    // Determine what to do based on the event type
    switch ($eventArray['actions'][0]['action_id']) {
      case 'refresh_data': // Refresh App Home Data
        require $botCodePath . '/NWSAPIFunctions.php';
        // Generate alert data as necessary
        updateAlertDataFile();

        // Generate the App Home tab payload based on the calling user's ID
        $slackPayload = json_encode(getAppHomeBlocks($eventArray['user']['id']));

        // Submit the request to the Slack API
        $data = UpdateAppHomeTab($slackPayload, $debug_bot);
        if ($data) {
          ($debug_bot) ? print $data : null;
        } else {
          print "Failed Request.\n";
        }
        break;
      default:
        die();
        break;
    }
  }
?>