<?php
  /**
   * THE FOLLOWING LINE IS THE _ONLY_ REQUIRED EDIT FOR THIS EVENT HANDLER.
   * CHANGE THIS TO THE NON-WEB ACCESSIBLE PATH HOUSING THE REST OF THE BOT CODE.
   */
  $botCodePath = __DIR__ . '/../';

  require $botCodePath . '/config/bot.php';
  require $botCodePath . '/SlackAppHomeGenerator.php';

  // Grab the event input
  $event = file_get_contents("php://input");
  $eventArray = json_decode($event, true);

  // Grab the generated list of valid users in the accompanying Slack workspace
  $slackUsers = include $botCodePath . '/config/slackUsers.generated.php';
  // Look for "authorization" to use this (is the requesting user at least in the known Slack users)
  if (array_search($eventArray['event']['user'], array_column($slackUsers['members'], 'id'))) {
    // Determine what to do based on the event type
    switch ($eventArray['event']['type']) {
      case 'app_home_opened': // App Home Interaction
        // Determine what to do next based on the tab in scope
        switch ($eventArray['event']['tab']) {
          case 'home': // Home Tab
            // Generate the App Home tab payload based on the calling user's ID
            $slackPayload = json_encode(getAppHomeBlocks($eventArray['event']['user']));

            // Submit the request to the Slack API
            $data = UpdateAppHomeTab($slackPayload, $debug_bot);
            if ($data) {
              ($debug_bot) ? print $data : null;
            } else {
              print "Failed Request.\n";
            }
            break;
          case 'messages': // Messages Tab
            die();
            break;
          case 'about': // About Tab
            die();
            break;
          default:
            die();
            break;
        }
        break;
      default:
        die();
        break;
    }
  }
?>