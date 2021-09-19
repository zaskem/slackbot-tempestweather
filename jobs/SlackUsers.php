<?php
  require_once __DIR__ . '/config/bot.php';
  require_once __DIR__ . '/config/slack.php';

  /**
   * GetSlackUsers() - function to obtain a list of Slack workspace users.
   * 
   * @return array of user data from Slack's `users.list` endpoint.
   */
  function GetSlackUsers() {
    global $slackGetUsers, $botOAuthToken;

  // Create/Submit cURL request
    $curl_request = curl_init();

    $slackHeader = array('Content-type: application/x-www-form-urlencoded', 'Authorization: Bearer ' . $botOAuthToken);
    curl_setopt($curl_request, CURLOPT_URL, $slackGetUsers);
    curl_setopt($curl_request, CURLOPT_HTTPHEADER, $slackHeader);
    curl_setopt($curl_request, CURLOPT_POST, true);
  //  curl_setopt($curl_request, CURLOPT_POSTFIELDS, $slackPayload);
    curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, false);

    $json = curl_exec($curl_request);
    $responseCode = curl_getinfo($curl_request, CURLINFO_RESPONSE_CODE);
    curl_close($curl_request);

    $result = json_decode($json, true);

    if (true == $result['ok']) {
      return $result;
    } else {
      return false;
    }
  }

  // Grab the current user list and write it to file.
  $data = GetSlackUsers();
  if ($data) {
    file_put_contents(__DIR__ . '/config/slackUsers.generated.php', '<?php return ' . var_export($data, true) . '; ?>');
  } else {
    print "Failed Request.\n";
  }
?>