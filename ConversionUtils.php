<?php
  /**
   * Functions provided for convenience of converting between units.
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

  function converKmToMiles($kmValue) {
    return number_format(($kmValue / 1.609344497892563), 2);
  }
  function convertDegreesToWindDirection($degrees) {
    $directions = array('N', 'NNE', 'NE', 'ENE', 'E', 'ESE', 'SE', 'SSE', 'S', 'SSW', 'SW', 'WSW', 'W', 'WNW', 'NW', 'NNW', 'N');
    return $directions[round($degrees / 22.5)];
  }
?>