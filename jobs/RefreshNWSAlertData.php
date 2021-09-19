<?php
  $botCodePath = __DIR__ . '/../';
  require_once $botCodePath . '/config/bot.php';
  require_once $botCodePath . '/NWSAPIFunctions.php';

  // Update alert data as necessary
  if ($useNWSAPIAlerts) {
    updateAlertDataFile(true, true);
  }
?>