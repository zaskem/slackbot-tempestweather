<?php
/**
 * NWSAlert Class
 *
 * A class designed to consistently handle individual NWS API Web Service alerts.
 * 
 * This class was designed specifically for the purposes of use in the Slack Tempest WeatherBot
 *  https://tempestweatherbot.mzonline.com/
 */
class NWSAlert {
  private $severityLevels = array(0=>'', 1=>'unknown',2=>'minor',3=>'moderate',4=>'severe',5=>'extreme');

  /**
   * __construct override and property assignments
   * 
   * @args $alertFeature - array of an individual 'feature' (alert) from NWS.
   */
  public function __construct(array $alertFeature) {
    $this->assignPropertiesFromData($alertFeature['properties']);

    $this->currentDateTime = new DateTime("now");
    $this->alertEndsDateTime = (null === $this->ends) ? new DateTime($this->expires) : new DateTime($this->ends);
    $this->intervalRemaining = $this->alertEndsDateTime->diff($this->currentDateTime);
    $this->alertLastUpdated = date('l, F j, g:i a', strtotime($this->sent));
    $this->alertSeverityLevel = array_search(strtolower($this->severity), $this->severityLevels);
    $this->alertDetails = $this->reformatNWSTextBlocks($this->description);
    // Figure out headline option (prefer 'NWSheadline' if present but accept 'headline')
    $this->rawHeadlineText = isset($this->parameters['NWSheadline'][0]) ? $this->parameters['NWSheadline'][0] : $this->headline;
    $this->longHeadline = isset($this->rawHeadlineText) ? (strlen($this->rawHeadlineText) > 150) : 0;
    $this->alertHeadline = isset($this->rawHeadlineText) ? $this->rawHeadlineText : "";
    if ($this->longHeadline) {
      $this->alertHeadlines = explode('... ...', $this->alertHeadline);
    } else {
      $this->alertHeadlines = array($this->alertHeadline);
    }
    $this->alertInstructions = (strlen($this->instruction) > 0) ? $this->reformatNWSTextBlocks($this->instruction) : " ";
  }


  /**
   * objectReferencesToString() - experimental/discovery function to output nested object properties
   * 
   * @return string of array data
   */
  public function objectReferencesToString() {
    return print_r($this->references);
  }


  /**
   * assignPropertiesFromData($data) - dynamically assign all $data keys/values as object properties
   */
  private function assignPropertiesFromData($data) {
    foreach($data as $key => $value) {
      $this->{$key} = $value;
    }
  }

  
  /**
   * getSeverityLevel() - return the numeric severity ranking for an alert
   * 
   * @return integer severity level (based on $severityLevels)
   */
  public function getSeverityLevel() {
    return $this->alertSeverityLevel;
  }


  /**
   * getFullAlertBlocks() - return Slack blocks for "full" alert data
   * 
   * @return array of Slack blocks
   */
  public function getFullAlertBlocks() {
    $blocks = [array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>':warning: *' . $this->event . '* :warning:'))];
    if ($this->longHeadline) {
      foreach ($this->alertHeadlines as $headline) {
        array_push($blocks, array('type'=>'header','text'=>array('type'=>'plain_text','text'=>$headline,'emoji'=>true)));
      }
    } else {
      array_push($blocks, array('type'=>'header','text'=>array('type'=>'plain_text','text'=>$this->alertHeadline,'emoji'=>true)));
    }
    array_push($blocks, array('type'=>'divider'), array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>$this->alertDetails)));
    array_push($blocks, array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>$this->alertInstructions)));
    array_push($blocks, array('type'=>'context','elements'=>[array('type'=>'mrkdwn','text'=>'Last update: ' . $this->alertLastUpdated . ' | Alert ends in ' . $this->intervalRemaining->days . ' days, ' . $this->intervalRemaining->h . ' hours, and ' . $this->intervalRemaining->i . ' minutes.')]));

    return $blocks;
  }


  /**
   * getSummaryAlertBlocks() - return Slack blocks for an alert summary
   * 
   * @return array of Slack blocks
   */
  public function getSummaryAlertBlocks() {
    global $bot_slashcommand;

    $blocks = array(array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>':warning: *' . $this->event . '* :warning:')), array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>$this->alertHeadline)), array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>$this->alertInstructions)), array('type'=>'context','elements'=>[array('type'=>'mrkdwn','text'=>'View full alert details with `' . $bot_slashcommand . ' alerts`')]));

    return $blocks;
  }


  /**
   * getHomeBlocks() - return Slack blocks for the App Home Tab
   * 
   * @return array of Slack blocks
   */
  public function getHomeBlocks() {
    $blocks = array(array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>':warning: *' . $this->event . '*')), array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>ucfirst($this->alertHeadline))));

    return $blocks;
  }


  /**
   * Function to address injected carriage returns in the NWS alert text.
   */
  private function reformatNWSTextBlocks($inputText) {
    // This bit of magic is courtesy of https://gist.github.com/kellenmace/470c09a7787eb8c5b694d9233c1ee1e6
    return preg_replace('/(^|[^\n\r])[\r\n](?![\n\r])/', '$1 ', $inputText);
  }
}
?>