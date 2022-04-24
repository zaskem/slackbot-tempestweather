<?php
  $botCodePath = __DIR__ . '/../';
  require_once $botCodePath . '/config/pushNotices.php';
  require_once $botCodePath . '/NWSAPIFunctions.php';
  require_once $botCodePath . '/SlackPost.php';
  require_once $botCodePath . '/SlackResponseBlocks.php';
  $pollingInterval = 60; // 10 minutes to match default bot alert behavior

  if ($pushAlertNotices && $useNWSAPIAlerts) {
    $slackbot_details['channel'] = $slackNoticeChannelID;
    $slackbot_details['icon_emoji'] = $alertPushEmoji;
    $slackbot_details['username'] = $slackNoticeChannelBotName;

    if (!file_exists($alertStatusData)) {
      $pushHistory = array(
        'lastAlertSeverity'=>0,
        'lastAlertNotification'=>(time() - ($pollingInterval * 60 * 2)));
      file_put_contents($alertStatusData, '<?php return ' . var_export($pushHistory, true) . '; ?>');
    }
    $pushHistory = include $alertStatusData;
    $lastAlertSeverity = $pushHistory['lastAlertSeverity'];
    $lastAlertNotification = $pushHistory['lastAlertNotification'];

    require_once $botCodePath . '/NWSAlert.php';
  
    $clientTimeout = time() + $clientCronInterval;
    while (time() < $clientTimeout) {
      /**
       * Update the alert data with the default arguments
       * 
       * Because alert data doesn't change with great frequency (and the bot can
       *  update alert data on its own or via the `RefreshNWSAlertData.php` cron
       *  job script), we use the default 10 minute cadence for updates and don't
       *  force the update every time this notification job is run.
       */      
      updateAlertDataFile();

      $alertData = include $botCodePath . '/config/nwsAlerts.generated.php';
      // Ignore this go when an unexpected status is returned (likely a service timeout)
      if (!array_key_exists('status', $alertData)) {
        $activeAlerts = count($alertData['features']);
        if ($activeAlerts > 0) {
          // Fire off an alert block for each item
          foreach ($alertData['features'] as $alertFeature) {
            $alert = new NWSAlert($alertFeature);
            $pushNotification = false;
            // Debug Timestamps and Values for Alert and Severity
            if ($debug_push) { print "Sent: ".strtotime($alert->sent)." | Last Notification: ".$lastAlertNotification."\nSeverity: ".$alert->getSeverityLevel()." | Last Severity: ".$lastAlertSeverity."\n"; }
            if (strtotime($alert->sent) > $lastAlertNotification) {
              // Alert was issued/updated since the last notification...
              $pushNotification = true;
              $lastAlertSeverity = $alert->getSeverityLevel();
              $lastAlertNotification = time();
            } else if (($alert->getSeverityLevel() > $lastAlertSeverity) && (strtotime($alert->sent) < $lastAlertNotification)) {
              // Alert was issued before the last notification, but has a severity greater than the previous alert...
              $pushNotification = true;
              $lastAlertSeverity = $alert->getSeverityLevel();
              $lastAlertNotification = time();
            }

            if ($pushNotification) {
              $responseText = $alert->headline;
  
              $slackbot_details['blocks'] = $alert->getFullAlertBlocks();
              $result = SlackPost($responseText, false, false, $slackbot_details, false);
  
              // Update History File
              $pushHistory['lastAlertSeverity'] = $lastAlertSeverity;
              $pushHistory['lastAlertNotification'] = $lastAlertNotification;
              file_put_contents($alertStatusData, '<?php return ' . var_export($pushHistory, true) . '; ?>');
            }
          }
        }
      }
      if ($debug_push) { print "Time remaining: " . ($clientTimeout - time())."\n"; }
      // Allow for sufficent closeout time before starting next iteration
      if (($clientTimeout - time()) <= $pollingInterval) {
        break;
      }
      sleep($pollingInterval);
    }
    if ($debug_push) { print "Time remaining before next invocation: ".($clientTimeout - time())."\n"; }
  } else {
    print "Push/Alert Notifications Not Enabled.\n";
  }
?>