<?php
  $botCodePath = __DIR__ . '/../';
  require_once $botCodePath . '/config/bot.php';
  require_once $botCodePath . '/NWSAPIFunctions.php';

  // Update forecast data as necessary
  if ($useNWSAPIForecasts) {
    updateNWSForecast(true);
    updateNWSHourlyForecast(true);
  }
?>