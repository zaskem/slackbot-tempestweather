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
    $this->f_dew_point = $this->convertCToF($this->dew_point) . $this->tempUnitLabel;
    $this->f_feelsLike = $this->convertCToF($this->feels_like) . $this->tempUnitLabel;
    $this->f_relative_humidity = $this->relative_humidity . "%";
    $this->f_pressure = $this->convertMbToInHg($this->station_pressure) . "$this->pressureUnitLabel";
    $this->f_windAvg = $this->convertMPSToMPH($this->wind_avg) . " $this->windUnitLabel";
    $this->f_windDir = $this->convertDegreesToWindDirection($this->wind_direction);
    $this->f_windGust = $this->convertMPSToMPH($this->wind_gust) . " $this->windUnitLabel";
    $this->f_windLull = $this->convertMPSToMPH($this->wind_lull) . " $this->windUnitLabel";
    $this->f_solar_radiation = number_format($this->solar_radiation, 0, '.', ',') . ' ' . $this->solarRadLabel;
    $this->f_brightness = number_format($this->brightness, 0, '.', ','). ' lx';
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
    $stationMetadata = include __DIR__ . '/config/stationMetadata.generated.php';
    $stationElevation = $stationMetadata['station_meta']['elevation'];
    $this->f_long_timestamp = date('l, F j, g:i a', $this->time);
    $this->f_timestamp = date('g:i a', $this->time);
    $this->f_temperature = $this->air_temperature . $this->tempUnitLabel;
    $this->f_feelsLike = $this->feels_like . $this->tempUnitLabel;
    $this->f_relative_humidity = $this->relative_humidity . "%";
    // Pressure calculation from sea level to station's local elevation derived from https://www.weather.gov/media/epz/wxcalc/stationPressure.pdf
    $this->f_pressure = ($this->sea_level_pressure * (((288 - (0.0065 * $stationElevation)) / 288) ** 5.2561)) . $this->pressureUnitLabel;
    $this->f_precip_type = (isset($this->precip_type)) ? $this->precip_type : '';
    $this->f_precip_probability = $this->precip_probability . "%";
    $this->f_windAvg = $this->wind_avg . " $this->windUnitLabel";
    $this->f_windDir = $this->wind_direction_cardinal;
    $this->f_windGust = $this->wind_gust . " $this->windUnitLabel";
  }

  private function summarizeHistoryData($data) {
    // CREATE CALCULATED VALUES
    $this->totalObs = count($data);
    $this->lastObsID = count($data)-1;
    $this->timeDiff = $data[$this->lastObsID][0] - $data[0][0];
    $this->pressDiff = $data[0][6] - $data[$this->lastObsID][6];
    
    // TIME & TIMESTAMPS
    $this->f_historyDateStart = ($this->timeDiff < 86400) ? date('l, F j, g:i a', $data[0][0]) : date('l, F j', $data[0][0]);
    $this->f_historyDateEnd = ($this->timeDiff < 86400) ? date('l, F j, g:i a', $data[$this->lastObsID][0]) : date('l, F j', $data[$this->lastObsID][0]);

    // WIND
    // Reference values
    $this->highWindID = $this->getParentKey(max(array_column($data, 3)), $data, 3);
    $this->highWindGust = $data[$this->highWindID][3];
    $this->windAvg = array_sum(array_column($data, 2)) / $this->totalObs;
    // Formatted values
    $this->f_highWindTimestamp = ($this->timeDiff < 86400) ? ' at ' . date("g:i a", $data[$this->highWindID][0]) : ' on ' . date("F j", $data[$this->highWindID][0]) . ' at ' . date("g:i a", $data[$this->highWindID][0]);
    $this->f_highWindGust = $this->convertMPSToMPH($this->highWindGust) . ' ' . $this->windUnitLabel;
    $this->f_highWindDir = $this->convertDegreesToWindDirection($data[$this->highWindID][4]);
    $this->f_windAvg = $this->convertMPSToMPH($this->windAvg) . ' ' . $this->windUnitLabel;

    // PRESSURE
    // Reference values
    $this->highPressID = $this->getParentKey(max(array_column($data, 6)), $data, 6);
    $this->lowPressID = $this->getParentKey($this->min_mod(array_column($data, 6)), $data, 6);
    $this->highPress = $data[$this->highPressID][6];
    $this->lowPress = $data[$this->lowPressID][6];
    // Formatted values
    $this->f_highPressTimestamp = ($this->timeDiff < 86400) ? ' at ' . date("g:i a", $data[$this->highPressID][0]) : ' on ' . date("F j", $data[$this->highPressID][0]) . ' at ' . date("g:i a", $data[$this->highPressID][0]);
    $this->f_lowPressTimestamp = ($this->timeDiff < 86400) ? ' at ' . date("g:i a", $data[$this->lowPressID][0]) : ' on ' . date("F j", $data[$this->lowPressID][0]) . ' at ' . date("g:i a", $data[$this->lowPressID][0]);
    $this->f_highPress = $this->convertMbToInHg($this->highPress) . $this->pressureUnitLabel;
    $this->f_lowPress = $this->convertMbToInHg($this->lowPress) . $this->pressureUnitLabel;
    if ($this->pressDiff >= 1) {
      $this->f_pressTrend = "Falling";
    } else if ($this->pressDiff <= -1) {
      $this->f_pressTrend = "Rising";
    } else {
      $this->f_pressTrend = "Steady";
    }

    // TEMPERATURE & RH
    // Reference values
    $this->highTempID = $this->getParentKey(max(array_column($data, 7)), $data, 7);
    $this->lowTempID = $this->getParentKey(min(array_column($data, 7)), $data, 7);
    $this->highTemp = $data[$this->highTempID][7];
    $this->lowTemp = $data[$this->lowTempID][7];
    $this->avgTemp = array_sum(array_column($data, 7)) / $this->totalObs;
    // Formatted values
    $this->f_highTempTimestamp = ($this->timeDiff < 86400) ? ' at ' . date("g:i a", $data[$this->highTempID][0]) : ' on ' . date("F j", $data[$this->highTempID][0]) . ' at ' . date("g:i a", $data[$this->highTempID][0]);
    $this->f_lowTempTimestamp = ($this->timeDiff < 86400) ? ' at ' . date("g:i a", $data[$this->lowTempID][0]) : ' on ' . date("F j", $data[$this->lowTempID][0]) . ' at ' . date("g:i a", $data[$this->lowTempID][0]);
    $this->f_highTemp = $this->convertCToF($this->highTemp) . $this->tempUnitLabel;
    $this->f_lowTemp = $this->convertCToF($this->lowTemp) . $this->tempUnitLabel;
    $this->f_avgTemp = $this->convertCToF($this->avgTemp) . $this->tempUnitLabel;

    // RELATIVE HUMIDITY
    // Reference values
    $this->highRHID = $this->getParentKey(max(array_column($data, 8)), $data, 8);
    $this->lowRHID = $this->getParentKey(min(array_column($data, 8)), $data, 8);
    $this->highRH = $data[$this->highRHID][8];
    $this->lowRH = $data[$this->lowRHID][8];
    $this->avgRH = array_sum(array_column($data, 8)) / $this->totalObs;
    // Formatted values
    $this->f_highRHTimestamp = ($this->timeDiff < 86400) ? ' at ' . date("g:i a", $data[$this->highRHID][0]) : ' on ' . date("F j", $data[$this->highRHID][0]) . ' at ' . date("g:i a", $data[$this->highRHID][0]);
    $this->f_lowRHTimestamp = ($this->timeDiff < 86400) ? ' at ' . date("g:i a", $data[$this->lowRHID][0]): ' on ' . date("F j", $data[$this->lowRHID][0]) . ' at ' . date("g:i a", $data[$this->lowRHID][0]);
    $this->f_highRH = $this->convertCToF($this->highRH) . "%";
    $this->f_lowRH = $this->convertCToF($this->lowRH) . "%";
    $this->f_avgRH = $this->convertCToF($this->avgRH) . "%";

    // SUNLIGHT & BRIGHTNESS
    // Reference values
    $this->highUVID = $this->getParentKey(max(array_column($data, 10)), $data, 10);
    $this->highSolarRadID = $this->getParentKey(max(array_column($data, 11)), $data, 11);
    $this->highLuxID = $this->getParentKey(max(array_column($data, 9)), $data, 9);
    $this->highUV = $data[$this->highUVID][10];
    $this->highSolarRad = $data[$this->highSolarRadID][11];
    $this->highLux = $data[$this->highLuxID][9];
    // Formatted values
    $this->f_highUVTimestamp = ($this->timeDiff < 86400) ? ' at ' . date("g:i a", $data[$this->highUVID][0]) : ' on ' . date("F j", $data[$this->highUVID][0]) . ' at ' . date("g:i a", $data[$this->highUVID][0]);
    $this->f_highSolarRad = number_format($this->highSolarRad, 0, '.', ',') . ' ' . $this->solarRadLabel;
    $this->f_highSolarRadTimestamp = ($this->timeDiff < 86400) ? ' at ' . date("g:i a", $data[$this->highSolarRadID][0]) : ' on ' . date("F j", $data[$this->highSolarRadID][0]) . ' at ' . date("g:i a", $data[$this->highSolarRadID][0]);
    $this->f_highLux = number_format($this->highLux, 0, '.', ','). ' lx';
    $this->f_highLuxTimestamp = ($this->timeDiff < 86400) ? ' at ' . date("g:i a", $data[$this->highLuxID][0]) : ' on ' . date("F j", $data[$this->highLuxID][0]) . ' at ' . date("g:i a", $data[$this->highLuxID][0]);

    // LIGHTNING
    // Reference values
    $this->strikeCount = array_sum(array_column($data, 15));
    $this->closeStrike = (0 == $this->strikeCount) ? 0 : min(array_filter(array_column($data, 14)));
    $this->closeStrikeID = (0 == $this->strikeCount) ? '' : $this->getParentKey($this->closeStrike, $data, 14);
    // Formatted values
    $this->f_strikeCount = number_format($this->strikeCount, 0, '.', ',');
    if ($this->timeDiff < 86400) {
      $this->f_closeStrikeTimestamp = (0 == $this->strikeCount) ? '' : ' at ' . date("g:i a", $data[$this->closeStrikeID][0]);
    } else {
      $this->f_closeStrikeTimestamp = (0 == $this->strikeCount) ? '' : ' on ' . date("F j", $data[$this->closeStrikeID][0]) . ' at ' . date("g:i a", $data[$this->closeStrikeID][0]);
    }
    $this->f_closestStrike = (0 == $this->strikeCount) ? '' : $this->convertKmToMiles($this->closeStrike) . ' ' . $this->distanceUnitLabel;

    // PRECIPITATION
    // Reference values
    $this->dailyPrecip = $data[$this->lastObsID][20];
    // Formatted values
    $this->f_dailyPrecip = $this->convertMmToInch($this->dailyPrecip) . $this->precipUnitLabel;
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