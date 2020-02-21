<?php
// Traag internet :(
ini_set('max_execution_time', 200); 
set_time_limit(200);

// Zet de content-type op JSON
header('Content-Type: application/json');
// CORS
header("Access-Control-Allow-Origin: *");

// Hoe lang de cooldown duurt op het ophalen van nieuwe weerdata in seconden.
$DATA_REFRESH_COOLDOWN = 20 * 60;

// APIs :)
define('OPENCAGE_APIKEY', "OPENCAGE_APIKEY");
define('DARKSKY_APIKEY', "DARKSKY_APIKEY");

define('OPENCAGE_APIURL', "https://api.opencagedata.com/geocode/v1/json?q=");
define('DARKSKY_APIURL', "https://api.darksky.net/forecast/". DARKSKY_APIKEY ."/");
define('BUIENRADAR_APIURL', "https://data.buienradar.nl/2.0/feed/json");

// Database
$DATABASE_HOST = 'DATABASE_HOST';
$DATABASE_USER = 'DATABASE_USER';
$DATABASE_NAME = 'DATABASE_NAME';
$DATABASE_PASS = 'DATABASE_PASS';

// Database verbinding maken
$conn = new \MySQLi($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);
if ($conn->connect_errno) {
  error(500, "Fout met het verbinden met de database.");
  error_log("MySQLi error: " . $conn->connect_errno . ": " . $conn->connect_error);
}

// Krijg plaats
$city = GET('plaats');

// Kijkt of er een plaats gespecificeerd is, anders error.
$coords;
if (empty($city)) {
  error(400, "Geen plaats gespecificeerd.");
} else {
  // City evt naar coords vertalen.
  $coords = getCoordsByCity($conn, $city);
  if ($coords == null) error(400, "$city niet gevonden.");
}

// Omzetten naar het goede formaat
$city = getCityByCoords($conn, $coords);

$JSON;
// Data ophalen
// Eerst proberen vanaf database
$result = $conn->query("SELECT * FROM weer WHERE `stad`='$city' ORDER BY `timestamp` DESC LIMIT 1");
if (!$result) {
  error(500, "Fout met het database query. (1)");
  error_log("MySQLi query error: " . $conn->errno . ": " . $conn->error);
}

$dataFromDatabase = false;
if ($result->num_rows == 1) {
  $row = $result->fetch_assoc();
  if ((time() - $row['timestamp']) <= $DATA_REFRESH_COOLDOWN && date("H") == date("H", $row['timestamp'])) {
    // Data is nog niet verouderd en uur is nog niet veranderd.
    $dataFromDatabase = true;
    $JSON = json_decode($row['JSON'], true);
  } else {
    // Verwijder oude data
    if (!$conn->query("DELETE FROM weer WHERE `stad`='$city' AND `timestamp`='".$row['timestamp']."'")) {
      error(500, "Fout met het database query. (2)");
      error_log("MySQLi query error: " . $conn->errno . ": " . $conn->error);
    }
  }
}

// Anders data van API halen.
if (!$dataFromDatabase) {
  $DARKSKY_DATA = cURL(DARKSKY_APIURL . $coords . "?lang=nl&units=ca");
  $BUIENRADAR_DATA = cURL(BUIENRADAR_APIURL);

  // Als er een errorcode is
  if (isset($DARKSKY_DATA['code'])) {
    error_log("DarkSky error " . $DARKSKY_DATA['code'] . ": " . $DARKSKY_DATA['error']);
    error($DARKSKY_DATA['code'], $DARKSKY_DATA['error']);
  }

  $time = time();

  $JSON['code'] = 200;
  $JSON['time'] = $time;
  $JSON['plaats'] = $city;
  $JSON['coords'] = $coords;
  $JSON['huidig']['temperatuur'] = round($DARKSKY_DATA['currently']['temperature']);
  $JSON['huidig']['icon'] = $DARKSKY_DATA['currently']['icon'];
  $JSON['huidig']['windKracht'] = getWindBft($DARKSKY_DATA['currently']['windSpeed']);
  $JSON['huidig']['windRichting'] = getWindDir($DARKSKY_DATA['currently']['windBearing']);

  $JSON['voorspelling']['verhaal']['titel'] = $BUIENRADAR_DATA['forecast']['weatherreport']['title'];
  $JSON['voorspelling']['verhaal']['samenvatting'] = $BUIENRADAR_DATA['forecast']['weatherreport']['summary'];
  $JSON['voorspelling']['verhaal']['auteur'] = $BUIENRADAR_DATA['forecast']['weatherreport']['author'];

  for ($i = 0; $i < 8; $i++) {
    $JSON['voorspelling']['dagelijks'][$i]['timestamp'] = $DARKSKY_DATA['daily']['data'][$i]['time'];
    $JSON['voorspelling']['dagelijks'][$i]['icon'] = $DARKSKY_DATA['daily']['data'][$i]['icon'];
    $JSON['voorspelling']['dagelijks'][$i]['minTemperatuur'] = round($DARKSKY_DATA['daily']['data'][$i]['temperatureMin']);
    $JSON['voorspelling']['dagelijks'][$i]['maxTemperatuur'] = round($DARKSKY_DATA['daily']['data'][$i]['temperatureMax']);
    $JSON['voorspelling']['dagelijks'][$i]['neerslagKans'] = round($DARKSKY_DATA['daily']['data'][$i]['precipProbability'] * 100) /* Omzetten naar procenten */;
    $JSON['voorspelling']['dagelijks'][$i]['neerslagHoeveelheid'] = round($DARKSKY_DATA['daily']['data'][$i]['precipIntensity'], 2);
    $JSON['voorspelling']['dagelijks'][$i]['windSnelheid'] = round($DARKSKY_DATA['daily']['data'][$i]['windSpeed']);
    $JSON['voorspelling']['dagelijks'][$i]['windKracht'] = getWindBft($DARKSKY_DATA['daily']['data'][$i]['windSpeed']);
    $JSON['voorspelling']['dagelijks'][$i]['windRichting'] = getWindDir($DARKSKY_DATA['daily']['data'][$i]['windBearing']);
  }


  for ($i = 0; $i < 49; $i++) {
    $JSON['voorspelling']['uurlijks'][$i]['timestamp'] = $DARKSKY_DATA['hourly']['data'][$i]['time'];
    $JSON['voorspelling']['uurlijks'][$i]['icon'] = $DARKSKY_DATA['hourly']['data'][$i]['icon'];
    $JSON['voorspelling']['uurlijks'][$i]['temperatuur'] = round($DARKSKY_DATA['hourly']['data'][$i]['temperature']);
    $JSON['voorspelling']['uurlijks'][$i]['neerslagHoeveelheid'] = round($DARKSKY_DATA['hourly']['data'][$i]['precipIntensity'], 2);
    $JSON['voorspelling']['uurlijks'][$i]['windKracht'] = getWindBft($DARKSKY_DATA['hourly']['data'][$i]['windSpeed']);
    $JSON['voorspelling']['uurlijks'][$i]['windGraden'] = $DARKSKY_DATA['hourly']['data'][$i]['windBearing'];
    $JSON['voorspelling']['uurlijks'][$i]['windRichting'] = getWindDir($DARKSKY_DATA['hourly']['data'][$i]['windBearing']);
  }

  $stmt = $conn->prepare("INSERT INTO `weer` (`timestamp`, `stad`, `JSON`) VALUES ('$time', '$city', ?)");
  $stmt->bind_param('s', json_encode($JSON));
  $result = $stmt->execute();
  if (!$result) {
    error(500, "Fout met het database query. (3)");
    error_log("MySQLi query error: " . $conn->errno . ": " . $conn->error);
  }
}

echo json_encode($JSON);

$conn->close();

/**
 * Voert een cURL request uit op een URL
 *
 * @param URL $URL De URL waar de data vandaan gehaald moet worden.
 *
 * @return Response De contents van de webpagina
 */
function cURL($URL) {
  $cURL = curl_init();
  curl_setopt($cURL, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($cURL, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($cURL, CURLOPT_URL, $URL);
  $response = curl_exec($cURL);
  curl_close($cURL);

  // cURL response verwerken
  $data = json_decode($response, true);
  return $data;
}

/**
 * Krijg de coordinaten van een stad door middel van de Geocode API
 *
 * @param Database $conn De database met opgeslagen steden
 * @param City $city De stad waaarvan de coordinaten moeten worden gekregen
 *
 * @return Coordinates De coordinaten van de stad in het format "long,lat"
 */
function getCoordsByCity($conn, $city) {
  $city = $conn->real_escape_string(trim($city));
  
  if (preg_match("/[a-zA-Z]*,[a-zA-Z]{2}/", $city)) $query = "SELECT * FROM steden WHERE `stad` LIKE '$city' LIMIT 1";
  else $query = "SELECT * FROM steden WHERE `stad` LIKE '$city,%' LIMIT 1";

  $result = $conn->query($query);
  if (!$result) {
    error(500, "Fout met het database query. (4)");
    error_log("MySQLi query error: " . $conn->errno . ": " . $conn->error);
  }

  if ($result->num_rows == 1) {
    $row = $result->fetch_assoc();
    return $row['coords'];
  } else {
    // Pak de coordinaten uit de GEOCODE API
    $URL = OPENCAGE_APIURL . urlencode($city) . "&key=" . OPENCAGE_APIKEY;

    // cURL request
    $data = cURL($URL);

    if ($data['status']['code'] == 200) {
      if (count($data['results']) == 0) return null;
      $lat = $data['results'][0]['geometry']['lat'];
      $lng = $data['results'][0]['geometry']['lng'];

      $coords = $lat . "," . $lng;

      // Zet plaats in het goede format (Stad,Land)
      $city = getCityByCoords($conn, $coords);

      // Voeg de data toe aan de database
      if (!$conn->query("INSERT INTO `steden` (`stad`, `coords`) VALUES ('$city', '$coords')")) {
        error_log("MySQLi query error: " . $conn->errno . ": " . $conn->error);
        error(500, "Fout met het database query. (5)");
      }
      return $coords;
    }
    // Als er teveel requests zijn gekomen (meer dan 1 per seconde)
    elseif ($data['status']['code'] == 429) {
      // Wacht even
      sleep(0.5);
      return getCoordsByCity($conn, $city);
    }
    // Als er iets anders mis is, gooi een error
    else {
      error_log("Opencage error " . $data['status']['code'] . ": " . $data['status']['message']);
      error(500, "Er ging iets fout.");
    }
    return null;
  }
}

/**
 * Krijg de coordinaten van een stad door middel van de Geocode API
 *
 * @param Database $conn De database met opgeslagen steden
 * @param Coordinates $coords De coordinaten waarmee de stad moet worden gekregen
 *
 * @return City De stad met de opgegeven coordinaten in het format 'Plaats,Land'
 */
function getCityByCoords($conn, $coords) {
  $coords = $conn->real_escape_string(trim($coords));

  $result = $conn->query("SELECT * FROM steden WHERE `coords`='$coords' LIMIT 1");
  if (!$result) {
    error_log("MySQLi query error: " . $conn->errno . ": " . $conn->error);
    error(500, "Fout met het database query. (6)");
  }

  if ($result->num_rows == 1) {
    $row = $result->fetch_assoc();
    return $row['stad'];
  } else {
    // Pak de coordinaten uit de GEOCODE API
    $URL = OPENCAGE_APIURL . urlencode($coords) . "&key=" . OPENCAGE_APIKEY;

    // cURL request
    $data = cURL($URL);

    if ($data['status']['code'] == 200) {
      if (count($data['results']) == 0) return null;
      $stad = $data['results'][0]['components']['suburb'];
      $land = strtoupper($data['results'][0]['components']['country_code']);

      $city = $stad . "," . $land;

      // Voeg de data toe aan de database
      if (!$conn->query("INSERT INTO `steden` (`stad`, `coords`) VALUES ('$city', '$coords')")) {
        error_log("MySQLi query error: " . $conn->errno . ": " . $conn->error);
        error(500, "Fout met het database query. (7)");
      }
      return $city;
    }
    // Als er teveel requests zijn gekomen (meer dan 1 per seconde)
    elseif ($data['status']['code'] == 429) {
      // Wacht even
      sleep(0.5);
      return getCityByCoords($conn, $coords);
    }
    // Als er iets anders mis is, gooi een error
    else {
      error_log("Opencage error " . $data['status']['code'] . ": " . $data['status']['message']);
      error(500, "Er ging iets fout.");
    }
    return null;
  }
}

/**
 * Krijg de windrichting in letters door middel van de hoek van de wind
 *
 * @param Degree $degree De hoek van de wind
 *
 * @return Direction De windrichting in letters
 */
function getWindDir($degree) {
  if ($degree === null) return null;
  $dirs = ['N', 'NO', 'O', 'ZO', 'Z', 'ZW', 'W', 'NW'];
  return $dirs[round($degree / 45) % 8];
}

/**
 * Krijg de windsnelheid in Beaufort / windkracht
 *
 * @param Windspeed $speed De windsnelheid in km/u
 *
 * @return Windforce De windkracht (windsnelheid in beaufort)
 */
function getWindBft($speed) {
  switch (true) {
    case ($speed < 1):
      $bft = 0;
      break;
    case ($speed <= 5):
      $bft = 1;
      break;
    case ($speed <= 11):
      $bft = 2;
      break;
    case ($speed <= 19):
      $bft = 3;
      break;
    case ($speed <= 28):
      $bft = 4;
      break;
    case ($speed <= 38):
      $bft = 5;
      break;
    case ($speed <= 49):
      $bft = 6;
      break;
    case ($speed <= 61):
      $bft = 7;
      break;
    case ($speed <= 74):
      $bft = 8;
      break;
    case ($speed <= 88):
      $bft = 9;
      break;
    case ($speed <= 102):
      $bft = 10;
      break;
    case ($speed <= 117):
      $bft = 11;
      break;
    case ($speed > 117):
      $bft = 12;
      break;
    default:
      $bft = null;
      break;
  }

  return $bft;
}

/**
 * Geef een error aan gebruiker
 *
 * @param ErrorCode $code De HTTP errorcode
 * @param ErrorMessage $msg Het errorbericht
 */
function error($code, $msg) {
  die(json_encode(array('code' => $code, 'error_message' => $msg)));
}

/**
 * $_GET maar dan met XXS preventie
 *
 * @param Parameter $parameter Parameter waar de waarde van moet worden genomen
 *
 * @return Value Waarde van opgegeven parameter
 */
function GET($parameter) {
  // XSS preventie, better safe than sorry.
  $value = trim(strip_tags($_GET[$parameter]));
  return (empty($value)) ? null : urldecode($value);
}