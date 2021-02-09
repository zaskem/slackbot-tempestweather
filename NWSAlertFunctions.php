<?php
  include __DIR__ . '/config/nws.php';

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


  function getAlertsByZone($toFile = false) {
    global $zoneAlertEndpointBase;
    $alertData = NWSCurlGetRequest($zoneAlertEndpointBase);

    // Write out data
    if ($toFile) {
      file_put_contents(__DIR__ . '/config/nwsZoneAlerts.generated.php', '<?php return ' . var_export($alertData, true) . '; ?>');
    } else {
      return $alertData;
    }
  }


  //die(print_r(getPointMetadata(true)));
  //die(print_r(getZonesForPoint(true)));
  //getAlertsByPoint(true);
  //die(print_r(getAlertsByZone(true)));


/*
  if (count($noAlertData['features']) > 0) {
    print count($noAlertData['features']) . "\n";
  } else {
    print "Nothing!\n";
  }
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
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
    ));
    
    $response = curl_exec($curl);
    curl_close($curl);

    return json_decode($response, true);
  }
?>