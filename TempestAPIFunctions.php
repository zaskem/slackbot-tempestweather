<?php
  require __DIR__ . '/config/tempest.php';

  /**
   * updateStationMetadata() - Grab the specified Tempest station's metadata based on the access of $tempestBotToken
   * 
   * Output is written to an include file for use elsewhere with the bot.
   */
  function updateStationMetadata() {
    global $tempestMetadataUrl;
    $curl_header = array("Accept: application/json");
    $curl_request = curl_init();
    curl_setopt($curl_request, CURLOPT_HTTPHEADER, $curl_header);
    curl_setopt($curl_request, CURLOPT_URL, $tempestMetadataUrl);
    curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, true);
    $json = curl_exec($curl_request);
    curl_close($curl_request);

    $stationMetadata = json_decode($json, true);

    // should check some status stuff here to see if request was good...
    
    // Write out/update our station detail
    file_put_contents(__DIR__ . '/config/stationMetadata.generated.php', '<?php return ' . var_export($stationMetadata['stations'][0], true) . '; ?>');
  }

  /**
   * getLastStationObservation() - Grab the latest observatation from the `/observations/station/` endpoint and write to file.
   */
  function getLastStationObservation() {
    global $tempestLastObsUrl;
    $curl_header = array("Accept: application/json");
    $curl_request = curl_init();
    curl_setopt($curl_request, CURLOPT_HTTPHEADER, $curl_header);
    curl_setopt($curl_request, CURLOPT_URL, $tempestLastObsUrl);
    curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, true);
    $json = curl_exec($curl_request);
    curl_close($curl_request);

    $lastObservation = json_decode($json, true);

    // should check some status stuff here to see if request was good...
    
    // Write out/update our last observation
    file_put_contents(__DIR__ . '/config/lastObservation.generated.php', '<?php return ' . var_export($lastObservation, true) . '; ?>');
  }
  
  /**
   * getStationForecast() - Grab the Tempest station's forecast data for the next ten days and write to file.
   */
  function getStationForecast() {
    global $tempestForecastUrl;
    $curl_header = array("Accept: application/json");
    $curl_request = curl_init();
    curl_setopt($curl_request, CURLOPT_HTTPHEADER, $curl_header);
    curl_setopt($curl_request, CURLOPT_URL, $tempestForecastUrl);
    curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, true);
    $json = curl_exec($curl_request);
    curl_close($curl_request);

    $stationForecast = json_decode($json, true);

    // should check some status stuff here to see if request was good...
    
    // Write out/update our station forecast data
    file_put_contents(__DIR__ . '/config/stationForecast.generated.php', '<?php return ' . var_export($stationForecast, true) . '; ?>');
  }

  /**
   * getStationObservationsByRange() - Grab the specified Tempest station's observations for a specified timeframe
   * 
   * This will write _one_ JSON file for the range specified; the WeatherFlow API will dynamically adjust the data resolution
   *  based on the range specified. See the `bucket_step_minutes` item in the JSON for this value.
   * 
   *  Generally speaking, data resolution is output as follows:
   *    <= 24 hour range : 1 minute
   *    > 24 hour, <= 5 day : 5 minute
   *    > 5 day, <= ~30 day : 30 minute
   *    > ~30 day, < 6 month : 3 hour
   *    >= 6 month : 1 day
   * 
   * $startTimestamp - integer value / starting timestamp (e.g. output from strtotime())
   * $endTimestamp - integer value / ending timestamp (e.g. output from strtotime())
   * $fileName - desired JSON output file name to drop at $tempestStationHistoryPath (.json/extension not necessary)
   */
  function getStationObservationsByRange($startTimestamp, $endTimestamp, $fileName = 'stationHistory.generated') {
    global $tempestObservationsUrl, $tempestStationHistoryPath;

    $observationUrl = $tempestObservationsUrl . "&time_start=$startTimestamp&time_end=$endTimestamp";

    $curl_header = array("Accept: application/json");
    $curl_request = curl_init();
    curl_setopt($curl_request, CURLOPT_HTTPHEADER, $curl_header);
    curl_setopt($curl_request, CURLOPT_URL, $observationUrl);
    curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, true);
    $json = curl_exec($curl_request);
    curl_close($curl_request);

    $stationObservations = json_decode($json, true);

    file_put_contents($tempestStationHistoryPath . $fileName . '.json', $json);
  }

  /**
   * getStationObservationsByDay($date) - Grab the specified Tempest station's observations for the $date submitted (00:00:00 - 23:59:00)
   * 
   * $date should be of format 'YYYY-MM-DD' to behave as designed; will likely report wonky results otherwise
   * 
   * Data resolution will _generally_ be 1 minute; however, Daylight Saving Time changes, specifically the "Fall" change, can cause a 1-day
   *  resolution change (to 5 minute) due to the additional "hour" worth of data in the range (pushing the total range > 24 hours).
   * 
   * Writes JSON file to $tempestStationHistoryPath
   */
  function getStationObservationsByDay($date) {
    global $tempestObservationsUrl, $tempestStationHistoryPath;


    $startTime = strtotime($date);
    $endTime = strtotime("$date +1 day") - 60;

    $observationUrl = $tempestObservationsUrl . "&time_start=$startTime&time_end=$endTime";

    $curl_header = array("Accept: application/json");
    $curl_request = curl_init();
    curl_setopt($curl_request, CURLOPT_HTTPHEADER, $curl_header);
    curl_setopt($curl_request, CURLOPT_URL, $observationUrl);
    curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, true);
    $json = curl_exec($curl_request);
    curl_close($curl_request);

    $stationObservations = json_decode($json, true);

    file_put_contents($tempestStationHistoryPath . $date . '.json', $json);
  }

  /**
   * getDailyDataForRange($startDate, $endDate) - Obtain the specified Tempest station's observations for the range submitted, one file per day
   * 
   * Utility function to grab n > 1 days' worth of observations in one go.
   * $startDate/$endDate should be of format 'YYYY-MM-DD' to behave as designed; will likely report wonky results otherwise
   * 
   * Writes JSON files to $tempestStationHistoryPath
   */
  function getDailyDataForRange($startDate, $endDate) {
    $timestamp = strtotime($startDate);
    $endstamp = strtotime($endDate);
  
    while ($timestamp <= $endstamp) {
      getStationObservationsByDay(date('Y-m-d', $timestamp));
      $timestamp = $timestamp + 86400; // increment by one day
      sleep(1);
    }
  }

  /**
   * getYesterdaysObservations()
   * 
   * Utility/Convenience function to grab yesterday's observations in one go.
   * 
   * Writes JSON file to $tempestStationHistoryPath
   */
  function getYesterdaysObservations(){
    getStationObservationsByDay(date('Y-m-d', strtotime('yesterday')));
  }
?>