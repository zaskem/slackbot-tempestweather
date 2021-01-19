<?php
  /**
   * min_mod() function modification to ignore "null" values, sourced from https://www.php.net/manual/en/function.min.php
   */
  function min_mod () {
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
  function getParentKey($needle, $haystack, $searchColumn) {
    foreach($haystack as $key => $value) {
      if ($needle == $value[$searchColumn]) {
        return $key;
      }
    }
  }
  
  /**
   * Functions provided for converting between station units.
   */
  function convertCToF($cValue = 0) {
    return number_format((($cValue * 9 / 5) + 32), 1);
  }
  function convertMPSToMPH($mpsValue) {
    return number_format(($mpsValue * 2.23694), 1);
  }
  function convertMbToInHg($mbValue) {
    return number_format(($mbValue / 33.864), 3);
  }
  function convertMmToInch($mmValue) {
    return number_format(($mmValue / 25.4), 2);
  }
  function convertKmToMiles($kmValue) {
    return number_format(($kmValue / 1.609344497892563), 2);
  }
  function convertDegreesToWindDirection($degrees) {
    $directions = array('N', 'NNE', 'NE', 'ENE', 'E', 'ESE', 'SE', 'SSE', 'S', 'SSW', 'SW', 'WSW', 'W', 'WNW', 'NW', 'NNW', 'N');
    return $directions[round($degrees / 22.5)];
  }
?>