<?php
class NWSAlert {
  private $severityLevels = array(0=>'', 1=>'unknown',2=>'minor',3=>'moderate',4=>'severe',5=>'extreme');


  public function __construct(array $alertFeature) {
    $this->assignPropertiesFromData($alertFeature['properties']);

    $this->currentDateTime = new DateTime("now");
    $this->alertEndsDateTime = new DateTime($this->ends);
    $this->intervalRemaining = $this->alertEndsDateTime->diff($this->currentDateTime);
    $this->alertLastUpdated = date('l, F j, g:i a', strtotime($this->sent));
    $this->alertSeverityLevel = array_search(strtolower($this->severity), $this->severityLevels);
    $this->alertDetails = $this->reformatNWSTextBlocks($this->description);
    $this->alertHeadline = $this->parameters['NWSheadline'][0];
    $this->alertInstructions = $this->reformatNWSTextBlocks($this->instruction);
  }

  public function objectToString() {
    return print_r($this->references);
  }

  private function assignPropertiesFromData($data) {
    foreach($data as $key => $value) {
      $this->{$key} = $value;
    }
  }

  public function getSeverityLevel() {
    return $this->alertSeverityLevel;
  }

  public function getFullAlertBlocks() {
    $blocks = [array('type'=>'header','text'=>array('type'=>'plain_text','text'=>$this->alertHeadline,'emoji'=>true))];
    array_push($blocks, array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>$this->alertDetails)));
    array_push($blocks, array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>$this->alertInstructions)));
    array_push($blocks, array('type'=>'context','elements'=>[array('type'=>'mrkdwn','text'=>'Last update: ' . $this->alertLastUpdated . ' | Alert ends in ' . $this->intervalRemaining->days . ' days, ' . $this->intervalRemaining->h . ' hours, and ' . $this->intervalRemaining->i . ' minutes.')]));

    return $blocks;
  }

  public function getSummaryAlertBlocks() {
    $blocks = [array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>':warning: *' . $this->event . '* :warning:'))];
    array_push($blocks, array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>$this->alertHeadline)), array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>$this->alertInstructions)));

    return $blocks;
  }

  public function getHomeBlocks() {
    $blocks = [array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>':warning: *' . $this->event . '*'))];
    array_push($blocks, array('type'=>'section','text'=>array('type'=>'mrkdwn','text'=>ucfirst($this->alertHeadline))));

    return $blocks;
  }


  /**
   * Function to address injected carriage returns in the text.
   */
  private function reformatNWSTextBlocks($inputText) {
    // This bit of magic is courtesy of https://gist.github.com/kellenmace/470c09a7787eb8c5b694d9233c1ee1e6
    return preg_replace('/(^|[^\n\r])[\r\n](?![\n\r])/', '$1 ', $inputText);
  }
}
?>