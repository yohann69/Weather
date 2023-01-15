<?php
session_start();

// get api key from config.env file
$apiKey = "6bc960c3834a3c720dc085628f3384a9";
$deeplApiKey = "f02ed4e5-4a48-ad6d-9887-9dd91580e4bd:fx";
$ninjasApiKey = "I80URu9NCRQLJ7+pjYzFPg==dk3cYIXElJNyv1mV";

// Note: all curl requests are made with the help of stackoverflow :)

if (isset($_POST['city'])) {

    // 1) Search for the longitude and latitude of the city entered by the user
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.api-ninjas.com/v1/city/?name=" . $_POST['city']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    $headers = array();

    $headers[] = "X-Api-Key: " . $ninjasApiKey;
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    curl_close($ch);

    // 2) Check if the city exists
    if ($result == "[]") {
        $latitude = "44.9333";
        $longitude = "4.8917";
        $country = "France";
    } else {
        // get the city name from the ninjas api response
        $latitude = json_decode($result)[0]->latitude;
        $longitude = json_decode($result)[0]->longitude;
        $country  = json_decode($result)[0]->country;
    }
} else {
    $latitude = "44.9333";
    $longitude = "4.8917";
    $country = "France";
}


// 3) Get the weather data from the openweathermap api
$json = file_get_contents('https://api.openweathermap.org/data/2.5/weather?lat=' . $latitude . '&lon=' . $longitude . '&lang=fr&units=metric&appid=' . $apiKey);
$data = json_decode($json);


$weather = $data->weather[0]->main;

// 4) Translate the weather data into french (some of the data isn't translated by the openweathermap api)
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api-free.deepl.com/v2/translate");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, "auth_key=" . $deeplApiKey . "&text=" . $weather . "&target_lang=FR");
curl_setopt($ch, CURLOPT_POST, 1);
$headers = array();
$headers[] = "Content-Type: application/x-www-form-urlencoded";
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$result = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}
curl_close($ch);

// 5) Retrieve the weather data
$weather = json_decode($result)->translations[0]->text;

$description = $data->weather[0]->description;
$icon = $data->weather[0]->icon;

// get temp data
$temp = $data->main->temp;
$temp_feels_like = $data->main->feels_like;
$temp_min = $data->main->temp_min;
$temp_max = $data->main->temp_max;

// get wind data
$wind_speed = $data->wind->speed;

// get clouds data
$clouds = $data->clouds->all;

// get sunrise and sunset (and convert them to readable format)
$sunrise = $data->sys->sunrise;
$sunrise = date("H:i", $sunrise);

$sunset = $data->sys->sunset;
$sunset = date("H:i", $sunset);

// get location
$location = $data->name;


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
    <title>🌞 Météo</title>
</head>

<body>



    <section class="card border-light mb-3">
        <div class="card-header">
            Rechercher une ville
        </div>

        <div class="card-body">
            <form action="index.php" method="post">
                <input class="form-control me-sm-2" type="text" name="city" placeholder="Ville" required>
                <input class="btn btn-secondary my-2 my-sm-0" type="submit" value="Rechercher">
            </form>
        </div>
    </section>






    <section class="card text-white bg-primary mb-3">

        <div class="card-header center">
            <img src="http://openweathermap.org/img/wn/<?php echo $icon; ?>.png" class='icon <?php echo $icon; ?>' alt="icon">
            <span> Météo <?php echo $location . ' (' . $country . ')'; ?> </span>
        </div>


        <div class="card-body">
            <h5>Il fait actuellement <?php echo $weather; ?> à <?php echo $location; ?>.</h5>
            <p>🌡️ La température est de <?php echo $temp; ?>°C.</p>
            <p>🌬️ Le vent souffle à <?php echo $wind_speed; ?>km/h.</p>
            <p>☁️ Il y a <?php echo $clouds; ?>% de nuages</p>
            <p>🌄 Le soleil se lève à <?php echo $sunrise; ?></p>
            <p>🌇 Le soleil se couche à <?php echo $sunset; ?></p>
        </div>
    </section>


    <script src="js/main.js"></script>
</body>

</html>