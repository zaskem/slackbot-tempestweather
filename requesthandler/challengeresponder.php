<?php
  $event = file_get_contents("php://input");
  $eventArray = json_decode($event, true);
  print $eventArray['challenge'];
?>