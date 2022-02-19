<?php
  $eventArray = json_decode(file_get_contents('php://input'), true);
  if (isset($eventArray["challenge"])) {
    $returnMessage = [
      "challenge" => $eventArray["challenge"]
    ];
    header('Content-Type: application/json');
    print json_encode($returnMessage);
  }
?>