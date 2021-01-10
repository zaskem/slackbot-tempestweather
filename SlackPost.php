<?php
  require __DIR__ . '/config/slack.php';

  function SlackPost($postText, $webhook = false, $ephemeral = true, $apiArgs = array(), $debug = false) {
    global $slackPostMessage, $botOAuthToken;

    $text = array('text' => $postText);
    // Handle private responses by always using the webhook value (response_url)
    if ($ephemeral) {
      $responseType = array('response_type' => 'ephemeral');
      $useWebhook = true;
    } else {
      $responseType = array('response_type' => 'in_channel');
      $useWebhook = false;
    }

    // Create/Submit cURL request
    $curl_request = curl_init();

    if ($useWebhook) {
      $slackPayload = json_encode(array_merge($text, $responseType, $apiArgs));
      curl_setopt($curl_request, CURLOPT_URL, $webhook);
    } else {
      $slackPayload = json_encode(array_merge($text, $responseType, $apiArgs));
      $slackHeader = array('Content-type: application/json;charset="utf-8"', 'Authorization: Bearer ' . $botOAuthToken);
      curl_setopt($curl_request, CURLOPT_URL, $slackPostMessage);
      curl_setopt($curl_request, CURLOPT_HTTPHEADER, $slackHeader);
    }

    curl_setopt($curl_request, CURLOPT_POST, true);
    curl_setopt($curl_request, CURLOPT_POSTFIELDS, $slackPayload);
    curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, false);

    $json = curl_exec($curl_request);
    $responseCode = curl_getinfo($curl_request, CURLINFO_RESPONSE_CODE);
    curl_close($curl_request);

    if ($useWebhook) {
      // Handle webhook-style responses
      if (200 == $responseCode) {
        // Good response per Slack documentation
        return ($debug) ? "Good Request" : "";
      } else {
        // Something went sideways...
        return ($debug) ? "Error: $json" : "";
      }
    } else {
      // Handle API-style responses
      $result = json_decode($json, true);

      if (true == $result['ok']) {
      // Good response, but could have warning output
        if (array_key_exists("warning", $result)) {
          return ($debug) ? "Warning: $json" : "Warning Returned";
        } else {
          return ($debug) ? "Good Request: $json" : "Good Request";
        }
      } else {
      // Bad response
        if (array_key_exists("error", $result)) {
          return ($debug) ? "Error: $json" : "Bad Request";
        } else if (array_key_exists("warning", $result)) {
          return ($debug) ? "Warning: $json" : "Warning Returned";
        }
      }
    }
  }
?>