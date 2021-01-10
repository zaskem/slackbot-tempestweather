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
   * getStationObservations() - Grab the specified Tempest station's observations for a specified timeframe
   * 
   * NOT YET IMPLEMENTED, but intended to use the `/observations/device/` endpoint (see https://weatherflow.github.io/Tempest/api/swagger/)
   */
  function getStationObservations() {
  /*  global $tempestObservationsUrl;
    $curl_header = array("Accept: application/json");
    $curl_request = curl_init();
    curl_setopt($curl_request, CURLOPT_HTTPHEADER, $curl_header);
    curl_setopt($curl_request, CURLOPT_URL, $tempestObservationsUrl);
    curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, true);
    $json = curl_exec($curl_request);
    curl_close($curl_request);

    $stationObservations = json_decode($json, true);

    // should check some status stuff here to see if request was good...
    
    // Write out/update our station observations
    file_put_contents(__DIR__ . '/config/stationObservations.generated.php', '<?php return ' . var_export($stationObservations['obs'], true) . '; ?>'); */
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
?>