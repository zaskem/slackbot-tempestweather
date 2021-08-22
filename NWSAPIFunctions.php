<?php
  include __DIR__ . '/config/nws.php';

  /**
   * getPointMetadata($toFile = false) - obtain NWS metadata for a given Point (coordinates)
   * 
   * Function is helpful in setup/troubleshooting; not used in bot functionality
   * 
   * $toFile - boolean (default false) write output to file
   * 
   * @return array of response data if $toFile = true, no output returned otherwise
   */
  function getPointMetadata($toFile = false) {
    global $pointEndpoint;
    $pointData = NWSCurlGetRequest($pointEndpoint);

    // Write out data
    if ($toFile) {
      file_put_contents(__DIR__ . '/config/nwsPoint.generated.php', '<?php return ' . var_export($pointData, true) . '; ?>');
    } else {
      return $pointData;
    }
  }


  /**
   * updatePointMetadataFile($toFile = true) -- generate or refresh NWS Point metadata file as necessary
   * 
   * $toFile - boolean (default true) to write point metadata to file
   * 
   * It's important to note that by using the file output (default behavior), the number of requests in a high-use 
   *  environment is substantially reduced as the bot will only ping the API for fresh metadata after at least 30
   *  days have passed since the last refresh.
   */
  function updatePointMetadataFile($toFile = true) {
    $pointMetadataFile = __DIR__ . '/config/nwsPoint.generated.php';
    // Generate alert data as necessary
    if (file_exists($pointMetadataFile)) {
      // Refresh the metadata if it's older than 30 days
      if (filemtime($pointMetadataFile) < (time() - 2592000)) {
        getPointMetadata($toFile);
      }
    } else {
      getPointMetadata($toFile);
    }
  }


  /**
   * getZonesForPoint($toFile = false) - obtain NWS Zone data by Point (coordinates)
   * 
   * Function is helpful in setup/troubleshooting; not used in bot functionality
   * 
   * $toFile - boolean (default false) write output to file
   * 
   * @return array of response data if $toFile = true, no output returned otherwise
   */
  function getZonesForPoint($toFile = false) {
    global $zoneEndpoint;
    $zoneData = NWSCurlGetRequest($zoneEndpoint);

    // Write out data
    if ($toFile) {
      file_put_contents(__DIR__ . '/config/nwsZones.generated.php', '<?php return ' . var_export($zoneData, true) . '; ?>');
    } else {
      return $zoneData;
    }
  }


  /**
   * getAlertsByPoint($toFile = false) - obtain NWS Alert data by Point (coordinates)
   * 
   * $toFile - boolean (default false) write output to file
   * 
   * @return array of response data if $toFile = true, no output returned otherwise
   */
  function getAlertsByPoint($toFile = false) {
    global $pointAlertEndpoint;
    $alertData = NWSCurlGetRequest($pointAlertEndpoint);

    // Write out data
    if ($toFile) {
      file_put_contents(__DIR__ . '/config/nwsAlerts.generated.php', '<?php return ' . var_export($alertData, true) . '; ?>');
    } else {
      return $alertData;
    }
  }


  /**
   * getAlertsByZone($toFile = false) - obtain NWS Alert data by Zone
   * 
   * $toFile - boolean (default false) write output to file
   * 
   * @return array of response data if $toFile = true, no output returned otherwise
   */
  function getAlertsByZone($toFile = false) {
    global $zoneAlertEndpointBase;
    $alertData = NWSCurlGetRequest($zoneAlertEndpointBase);

    // Write out data
    if ($toFile) {
      file_put_contents(__DIR__ . '/config/nwsAlerts.generated.php', '<?php return ' . var_export($alertData, true) . '; ?>');
    } else {
      return $alertData;
    }
  }


  /**
   * updateAlertDataFile($toFile = true, $byZone = false) -- generate or refresh NWS Alert data file as necessary
   * 
   * $toFile - boolean (default true) to write alert data to file
   * 
   * It's important to note that by using the file output (default behavior), the number of requests in a high-use 
   *  environment is reduced as the bot will only ping the API for fresh alert data after at least 10 minutes have 
   *  passed since the last request.
   * 
   * $byZone - boolean (default false) to use NWS Zone or County instead of Point (coordinates of station)
   */
  function updateAlertDataFile($toFile = true, $byZone = false) {
    $alertDataFile = __DIR__ . '/config/nwsAlerts.generated.php';
    // Generate alert data as necessary
    if (file_exists($alertDataFile)) {
      // Refresh the alert data if it's older than 10 minutes
      if (filemtime($alertDataFile) < (time() - 600)) {
        if ($byZone) {
          getAlertsByZone($toFile);
        } else {
          getAlertsByPoint($toFile);
        }
      }
    } else {
      if ($byZone) {
        getAlertsByZone($toFile);
      } else {
        getAlertsByPoint($toFile);
      }
    }
    sortAlertsBySeverity($toFile, $alertDataFile);
  }


  /**
   * sortAlertsBySeverity($toFile = true) - sort obtained NWS Alert data by Severity level
   * 
   * $toFile - boolean (default false) write output to file
   * $alertFilePath - string of full file path (default null): only used if $toFile == true
   * 
   * @return array of response data if $toFile = true, no output returned otherwise
   */
  function sortAlertsBySeverity($toFile = false, $alertFilePath = null) {
    $alertDataFile = include __DIR__ . '/config/nwsAlerts.generated.php';
    $featureData = $alertDataFile['features'];

    uasort($featureData, function($a, $b) {
      global $severityLevels, $urgencyLevels;
      // Sort first by severity level (desc)
      $retval = array_search(strtolower($b['properties']['severity']), $severityLevels) <=> array_search(strtolower($a['properties']['severity']), $severityLevels);
      if ($retval == 0) {
        // Sort second by urgency level (desc, if severity was identical)
        $retval = array_search(strtolower($b['properties']['urgency']), $urgencyLevels) <=> array_search(strtolower($a['properties']['urgency']), $urgencyLevels);
        if ($retval == 0) {
          // Sort third by date sent (most recent first, if urgency was also identical)
            $retval = $b['properties']['sent'] <=> $a['properties']['sent'];
        }
      }
      return $retval;
    });
    $alertDataFile['features'] = $featureData;

    // Write out data
    if ($toFile) {
      file_put_contents($alertFilePath, '<?php return ' . var_export($alertDataFile, true) . '; ?>');
    } else {
      return $alertDataFile;
    }
  }


  /**
   * NWSCurlGetRequest($url) - Generalized function for common NWS Alert API requests.
   * 
   * $url - Full URL for request with GET parameters encoded if/as necessary
   * 
   * @return array of decoded JSON response
   */
  function NWSCurlGetRequest($url) {
    global $nwsUserAgent;
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => $url,
      CURLOPT_USERAGENT => $nwsUserAgent,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 3,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
    ));
    
    $response = curl_exec($curl);
    if ($response === false) {
      $info = curl_getinfo($curl);
      if ($info['http_code'] === 0) {
        $response = '{"status":"Response Timeout"}';
      }
    }
    curl_close($curl);

    return json_decode($response, true);
  }
?>