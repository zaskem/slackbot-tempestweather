<?php
class TempestObservation {
  private $validObservationTypes = array('history','current','day_forecast','hour_forecast');
  private $observationType;
  private $tempUnitLabel;
  private $windUnitLabel;
  private $pressureUnitLabel;
  private $precipUnitLabel;
  private $distanceUnitLabel;
  private $solarRadLabel;


  public function __construct(string $observationType, array $observationData) {
    try {
      $this->validObsType($observationType);
    } catch (Exception $e) {
      die($e->getMessage());
    }
    $this->observationType = $observationType;
    $this->setDisplayLabels();
    switch ($this->getObservationType()) {
      case 'history':
        $this->summarizeHistoryData($observationData);
        break;
      case 'current':
        $this->assignPropertiesFromData($observationData);
        $this->formatCurrentObservationStrings();
        break;
      case 'day_forecast':
        $this->assignPropertiesFromData($observationData);
        $this->formatDayForecastObservationStrings();
        break;
      case 'hour_forecast':
        $this->assignPropertiesFromData($observationData);
        $this->formatHourForecastObservationStrings();
        break;
      }
  }

  private function validObsType($type) {
    if (!in_array($type, $this->validObservationTypes)) {
      throw new Exception('Invalid observation type specified.');
    } else {
      return true;
    }
  }

  private function setDisplayLabels() {
    include __DIR__ . '/config/bot.php';
    $this->tempUnitLabel = $tempUnitLabel;
    $this->windUnitLabel = $windUnitLabel;
    $this->pressureUnitLabel = $pressureUnitLabel;
    $this->precipUnitLabel = $precipUnitLabel;
    $this->distanceUnitLabel = $distanceUnitLabel;
    $this->solarRadLabel = $solarRadLabel;
  }

  private function assignPropertiesFromData($data) {
    foreach($data as $key => $value) {
      $this->{$key} = $value;
    }
  }

  public function getObservationType() {
    return $this->observationType;
  }

  private function formatCurrentObservationStrings() {
    $this->f_timestamp = date('g:i a', $this->timestamp);
    $this->f_temperature = $this->convertCToF($this->air_temperature) . $this->tempUnitLabel;
    $this->f_pressure = $this->convertMbToInHg($this->sea_level_pressure) . "$this->pressureUnitLabel";
    $this->f_feelsLike = $this->convertCToF($this->feels_like) . $this->tempUnitLabel;
    $this->f_windAvg = $this->convertMPSToMPH($this->wind_avg) . " $this->windUnitLabel";
    $this->f_windDir = $this->convertDegreesToWindDirection($this->wind_direction);
  }

  private function formatDayForecastObservationStrings() {
    $this->f_timestamp = date('l, F j', $this->day_start_local);
    $this->f_shortTimestamp = date('l', $this->day_start_local);
    $this->f_high_temperature = $this->air_temp_high . $this->tempUnitLabel;
    $this->f_low_temperature = $this->air_temp_low . $this->tempUnitLabel;
    $this->f_precip_type = (isset($this->precip_type)) ? $this->precip_type : '';
    $this->f_precip_probability = $this->precip_probability . "%";
    $this->f_sunrise = date('g:i a', $this->sunrise);
    $this->f_sunset = date('g:i a', $this->sunset);
  }

  private function formatHourForecastObservationStrings() {
    $this->long_timestamp = date('l, F j, g:i a', $this->time);
    $this->f_timestamp = date('g:i a', $this->time);
    $this->f_temperature = $this->air_temperature . $this->tempUnitLabel;
    $this->f_pressure = $this->sea_level_pressure . $this->pressureUnitLabel;
    $this->f_precip_type = (isset($this->precip_type)) ? $this->precip_type : '';
    $this->f_precip_probability = $this->precip_probability . "%";
    $this->f_feelsLike = $this->feels_like . $this->tempUnitLabel;
    $this->f_windAvg = $this->wind_avg . " $this->windUnitLabel";
    $this->f_windDir = $this->wind_direction_cardinal;
  }

  private function summarizeHistoryData($data) {
    // ASSIGN SUMMARIZED PROPERTIES
    $this->totalObs = count($data);
    $this->lastObsID = count($data)-1;
    $this->timeDiff = $data[$this->lastObsID][0] - $data[0][0];
    $this->pressDiff = $data[0][6] - $data[$this->lastObsID][6];
    $this->highWindID = $this->getParentKey(max(array_column($data, 3)), $data, 3);
    $this->strikeCount = array_sum(array_column($data, 15));
    $this->closeStrike = (0 == $this->strikeCount) ? 0 : min(array_filter(array_column($data, 14)));
    $this->closeStrikeID = (0 == $this->strikeCount) ? '' : $this->getParentKey($this->closeStrike, $data, 14);
    $this->highTemp = max(array_column($data, 7));
    $this->lowTemp = min(array_column($data, 7));
    $this->avgTemp = array_sum(array_column($data, 7)) / $this->totalObs;
    $this->highPress = max(array_column($data, 6));
    $this->lowPress = $this->min_mod(array_column($data, 6));
    $this->pressTrend = ($this->pressDiff >= 1) ? "Falling" : "Rising";
    $this->highUV = max(array_column($data, 10));
    $this->highSolarRad = max(array_column($data, 11));
    $this->highLux = max(array_column($data, 9));
    $this->highWindGust = max(array_column($data, 3));
    $this->windAvg = array_sum(array_column($data, 2)) / $this->totalObs;

    // NOW HANDLE THE FORMATTING BITS
    //$this->historyDateStart = ($this->timeDiff < 86400) ? date('l, F j, g:i a', $data[0][0]) : date('l, F j', $data[0][0]);
    $this->historyDateStart = date('l, F j', $data[0][0]);
//    $this->historyDateEnd = ($this->timeDiff < 86400) ? date('l, F j, g:i a', $data[$this->lastObsID][0]) : date('l, F j', $data[$lastObsID][0]);
    $this->historyDateEnd = date('l, F j', $data[$this->lastObsID][0]);
    $this->long_historyDateStart = date('l, F j, g:i a', $data[0][0]);
    $this->long_historyDateEnd = date('l, F j, g:i a', $data[$this->lastObsID][0]);
    $this->f_highTemp = $this->convertCToF($this->highTemp) . $this->tempUnitLabel;
    $this->f_lowTemp = $this->convertCToF($this->lowTemp) . $this->tempUnitLabel;
    $this->f_avgTemp = $this->convertCToF($this->avgTemp) . $this->tempUnitLabel;
    $this->f_highPress = $this->convertMbToInHg($this->highPress) . $this->pressureUnitLabel;
    $this->f_lowPress = $this->convertMbToInHg($this->lowPress) . $this->pressureUnitLabel;
    $this->f_highSolarRad = number_format($this->highSolarRad, 0, '.', ',') . ' ' . $this->solarRadLabel;
    $this->f_highLux = number_format($this->highLux, 0, '.', ','). ' lx';
    $this->f_highWindGust = $this->convertMPSToMPH($this->highWindGust) . ' ' . $this->windUnitLabel;
    $this->highWindTimestamp = ' at ' . date("g:i a", $data[$this->highWindID][0]);
    $this->long_highWindTimestamp = ' on ' . date("F j", $data[$this->highWindID][0]) . ' at ' . date("g:i a", $data[$this->highWindID][0]);
    $this->highWindDir = $this->convertDegreesToWindDirection($data[$this->highWindID][4]);
    $this->f_windAvg = $this->convertMPSToMPH($this->windAvg) . ' ' . $this->windUnitLabel;
    $this->f_dailyPrecip = $this->convertMmToInch($data[$this->lastObsID][20]) . $this->precipUnitLabel;
    $this->f_strikeCount = number_format($this->strikeCount, 0, '.', ',');
    $this->closeStrikeTimestamp = (0 == $this->strikeCount) ? '' : date("F j", $data[$this->closeStrikeID][0]) . ' at ' . date("g:i a", $data[$this->closeStrikeID][0]);
    $this->f_closestStrike = (0 == $this->strikeCount) ? '' : $this->convertKmToMiles($this->closeStrike) . ' ' . $this->distanceUnitLabel;
  }



  // Utility Functions
  /**
   * min_mod() function modification to ignore "null" values, sourced from https://www.php.net/manual/en/function.min.php
   */
  private function min_mod () {
    $args = func_get_args();
    if (!count($args[0])) return false;
    else {
      $min = false;
      foreach ($args[0] AS $value) {
        if (is_numeric($value)) {
          $curval = floatval($value);
          if ($curval < $min || $min === false) $min = $curval;
        }
      }
    }
    return $min;  
  }

  /**
   * getParentKey($needle, $haystack, $searchColumn) - Returns the "outer" (parent) key in which $needle matches a value in $searchColumn
   * 
   * This is intended to be used on a single-depth multidimensional array...and intended to only find/return 1 match (one unique value).
   * For this project, it's generally used to find the parent observation ID for a given measurement such as high temperature or wind speed.
   */
  private function getParentKey($needle, $haystack, $searchColumn) {
    foreach($haystack as $key => $value) {
      if ($needle == $value[$searchColumn]) {
        return $key;
      }
    }
  }

  /**
   * Functions provided for converting between units.
   */
  private function convertCToF($cValue = 0) {
    return number_format((($cValue * 9 / 5) + 32), 1);
  }
  private function convertMPSToMPH($mpsValue) {
    return number_format(($mpsValue * 2.23694), 1);
  }
  private function convertMbToInHg($mbValue) {
    return number_format(($mbValue / 33.864), 3);
  }
  private function convertMmToInch($mmValue) {
    return number_format(($mmValue / 25.4), 2);
  }
  private function convertKmToMiles($kmValue) {
    return number_format(($kmValue / 1.609344497892563), 2);
  }
  private function convertDegreesToWindDirection($degrees) {
    $directions = array('N', 'NNE', 'NE', 'ENE', 'E', 'ESE', 'SE', 'SSE', 'S', 'SSW', 'SW', 'WSW', 'W', 'WNW', 'NW', 'NNW', 'N');
    return $directions[round($degrees / 22.5)];
  }
}
?>