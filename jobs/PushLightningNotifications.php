<?php
  $botCodePath = __DIR__ . '/../';
  require_once $botCodePath . '/config/pushNotices.php';
  require_once $botCodePath . '/config/tempest.php';
  require_once $botCodePath . '/SlackPost.php';
  require_once $botCodePath . '/SlackResponseBlocks.php';

  if ($pushLightningNotices) {
    $slackbot_details['channel'] = $slackNoticeChannelID;
    $slackbot_details['icon_emoji'] = $lightningPushEmoji;
    $slackbot_details['username'] = $slackNoticeChannelBotName;

    if (!file_exists($lightningStatusData)) {
      $pushHistory = array(
        'lastLightningDistance'=>100,
        'lastLightningNotification'=>(time() - ($notifyWindowSeconds * 2)));
      file_put_contents($lightningStatusData, '<?php return ' . var_export($pushHistory, true) . '; ?>');
    }
    $pushHistory = include $lightningStatusData;
    $lastLightningDistance = $pushHistory['lastLightningDistance'];
    $lastLightningNotification = $pushHistory['lastLightningNotification'];

    if ($useWebsocket) {
      // Basic dependency/connection testing
      try {
        if (!file_exists($websocketAutoloadPath)) {
          throw new Exception('Websocket Autoload not found.');
        }
        include $websocketAutoloadPath;
        $client = new WebSocket\Client($tempestWebsocketUrl);
        $client->text($startListenJSON);  
      } catch (Exception $e) {
        die($e->getMessage() . " Halting.\n");
      }

      // Receive initial connection response and ack
      if ($debug_push) {
        print "Initial Response: " . $client->receive() . "\n";
        print "Ack: ".$client->receive()."\n";
      } else {
        $client->receive();
        $client->receive();
      }
      // Set script runtime/duration
      $clientTimeout = time() + $clientCronInterval;

      // Listen and Respond
      while (time() < $clientTimeout) {
        try {
          $message = $client->receive();
          $details = json_decode($message, true);
          if (array_key_exists('type', $details)) {
            // Capture `evt_strike` responses only
            if ('evt_strike' == $details['type']) {
              $pushNotification = false;
              if ($details['evt'][1] < $lastLightningDistance) {
                // Lightning is closer than last time, notify...
                $pushNotification = true;
                $lastLightningDistance = $details['evt'][1];
                $lastLightningNotification = time();
              } else if ($details['evt'][0] > ($lastLightningNotification + $notifyWindowSeconds)) {
                // Not closer, but $notifyWindowSeconds has elapsed since the last notice, so notify anyway...
                $pushNotification = true;
                $lastLightningDistance = $details['evt'][1];
                $lastLightningNotification = time();
              }

              if ($pushNotification) {
                $responseText = "LIGHTNING DETECTED AT ". date('g:i:s a', $details['evt'][0]);
                $slackbot_details['blocks'] = getSlackLightningPushBlocks($details['evt']);
                $result = SlackPost($responseText, false, false, $slackbot_details, false);

                // Update History File
                $pushHistory['lastLightningDistance'] = $lastLightningDistance;
                $pushHistory['lastLightningNotification'] = $lastLightningNotification;
                file_put_contents($lightningStatusData, '<?php return ' . var_export($pushHistory, true) . '; ?>');
              }
            }
          }
        } catch (\WebSocket\ConnectionException $e) {
          $message = $e->getMessage();
          // For this websocket due to expected message interval we ignore client read timeout
          if ('Client read timeout' != $message) {
            if ($debug_push) { print_r("\n\nWebsocket Error: ".$e->getMessage()."\n\n"); }
          }
        }
        if ($debug_push) { print "Time remaining: " . ($clientTimeout - time())."\n"; }
        // Allow for sufficent closeout time before starting next iteration
        if (($clientTimeout - time()) <= $listenInterval) {
          break;
        }
        sleep($listenInterval);
      }
      $client->text($stopListenJSON);
      sleep(2);
      // Receive closing ack
      if ($debug_push) {
        print "Ack: ".$client->receive()."\n";
      } else {
        $client->receive();
      }
      $client->close();
      if ($debug_push) { print "Time remaining before next invocation: ".($clientTimeout - time())."\n"; }
    } else {
      require_once $botCodePath . '/TempestAPIFunctions.php';
      require_once $botCodePath . '/TempestObservation.php';
    
      $clientTimeout = time() + $clientCronInterval;
      while (time() < $clientTimeout) {
        getLastStationObservation();
        $lastObservation = include $botCodePath . '/config/lastObservation.generated.php';
        if (isset($lastObservation['obs'][0])) {
          $observation = new TempestObservation('current', $lastObservation['obs'][0]);
          $pushNotification = false;
          if (($observation->getCurrentLightningEpoch() > $lastLightningNotification) && ($observation->getCurrentLightningLastDistance() < $lastLightningDistance)) {
            // Lightning is closer than last time, always notify...
            $pushNotification = true;
            $lastLightningDistance = $observation->getCurrentLightningLastDistance();
            $lastLightningNotification = time();
          } else if ($observation->getCurrentLightningEpoch() > ($lastLightningNotification + $notifyWindowSeconds)) {
            // Not closer, but $notifyWindowSeconds has elapsed since the last notice, so notify anyway...
            $pushNotification = true;
            $lastLightningDistance = $observation->getCurrentLightningLastDistance();
            $lastLightningNotification = time();
          }

          if ($pushNotification) {
            $responseText = "LIGHTNING DETECTED AT ". date('g:i:s a', $observation->getCurrentLightningEpoch());

            $slackbot_details['blocks'] = getSlackLightningPushBlocks(array($observation->getCurrentLightningEpoch(),$lastLightningDistance));
            $result = SlackPost($responseText, false, false, $slackbot_details, false);

            // Update History File
            $pushHistory['lastLightningDistance'] = $lastLightningDistance;
            $pushHistory['lastLightningNotification'] = $lastLightningNotification;
            file_put_contents($lightningStatusData, '<?php return ' . var_export($pushHistory, true) . '; ?>');
          }
        }
        if ($debug_push) { print "Time remaining: " . ($clientTimeout - time())."\n"; }
        // Allow for sufficent closeout time before starting next iteration
        if (($clientTimeout - time()) <= $listenInterval) {
          break;
        }
        sleep($listenInterval);
      }
      if ($debug_push) { print "Time remaining before next invocation: ".($clientTimeout - time())."\n"; }
    }
  } else {
    print "Push Lightning Notifications Not Enabled.\n";
  }
?>