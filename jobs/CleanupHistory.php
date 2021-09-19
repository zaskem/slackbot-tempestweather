<?php
  /**
   * CleanupHistory.php -- Basic script to clean up the $tempestStationHistoryPath path of old requests
   * 
   * Intended to be called via cron on some routine (monthly, quarterly) interval, but not required for bot operation.
   */
  // Grab the $tempestStationHistoryPath from bot configuration
  include __DIR__ . '/config/tempest.php';
  // Unlink/Delete each .json file at the $tempestStationHistoryPath
  array_map('unlink', glob("$tempestStationHistoryPath*.json"));
?>