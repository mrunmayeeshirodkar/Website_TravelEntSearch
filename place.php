<?php
header("Access-Control-Allow-Origin: *");

$dist = "";
$category = "";
$kword = "";
$lat = "";
$longi = "";
$API_KEY = NULL;

//Checking if request is sending keword as parameter
if (isset($_REQUEST["keword"])){
    $isplace = false;

//Checking if location is given in textbox
    if ($_REQUEST["location"] != "hereloc") {
        $locadr = "";
        $addr = explode(" ", $_REQUEST["location"]);
        $araysize = sizeof($addr);

        for ($i = 0; $i < $araysize; $i++) {
            $locadr = $locadr . $addr[$i] . "+";
        }
        $locadr = substr($locadr, 0, strlen($locadr) - 1);

        $url3a = "https://maps.googleapis.com/maps/api/geocode/json?address=" . $locadr . "&key=AIzaSyBJlrwIyujuQ4tFul2iO058IAN3-ghnjMs";
        $url3b = "https://maps.googleapis.com/maps/api/geocode/json?address=" . $locadr . "&key=AIzaSyC3bZCNxyB4RmC37lkznPullf33Jhl9Wi4";

//Getting latitude and longitude of custom set location
        $latlong = @file_get_contents($url3a);

//Checking if backup request is needed
        if(!$latlong){
            $latlong = @file_get_contents($url3b);
        }
        $latenc = json_decode($latlong, true);

//Checking if no results are found for location and setting result to location zero results
        if($latlong == false){
            echo "{\"result\":\"LOC_ZERO_RESULTS\"}";
            die;
            exit;
        }

        if ($latenc['status'] != "ZERO_RESULTS") {
            if ($latenc['status'] != "INVALID_REQUEST") {
                if($latenc['status'] !="REQUEST_DENIED") {

//Fetching latitude and longitude from the response
                    $lat = $latenc['results'][0]['geometry']['location']['lat'];
                    $longi = $latenc['results'][0]['geometry']['location']['lng'];
                }
                else{
                    echo "{\"result\":\"LOC_REQUEST_DENIED\"}";
                    die;
                    exit;
                }
            }
            else {
                echo "{\"result\":\"LOC_INVALID_REQUEST\"}";
                die;
                exit;
            }
        } else{
            echo "{\"result\":\"LOC_ZERO_RESULTS\"}";
            die;
            exit;
        }
    }
    else {
//Setting latitude and longitude to here's lat and lng if custom location is not given
        $lat = $_REQUEST["lat"];
        $longi = $_REQUEST["lng"];
    }

//Checking if latitude and longitude has data
    if($lat != "" && $longi != ""){

//Converting distance into meter
        $dist = (int)((float)$_REQUEST["distance"] * 1609.34);

//Fetching category from request URL
        $category = $_REQUEST["category"];

//Fetching keyword from request URL
        $kword = $_REQUEST["keword"];

//Checking if keyword has white space
        if (stripos($kword, ' ') !== false) {
            $arrdata = explode(" ", $_REQUEST["keword"]);
            $finstr = "";
            $arrlen = sizeof($arrdata);
            for ($i = 0; $i < $arrlen; $i++) {
                $finstr = $finstr . $arrdata[$i] . "+";
            }
            $finstr = substr($finstr, 0, strlen($finstr) - 1);
            $kword = $finstr;
        }


//Fetching nearby places using nearby search API

        $getPlaceDet = file_get_contents("https://maps.googleapis.com/maps/api/place/nearbysearch/json?location=" . $lat . "," . $longi . "&radius=" . $dist . "&type=" . $category . "&keyword=" . $kword . "&key=AIzaSyDti0GPyuQuwmhaldVaO3re5oMk7_oE51Q");

//Checking if backup request is needed
        if (!$getPlaceDet) {
            $getPlaceDet = file_get_contents("https://maps.googleapis.com/maps/api/place/nearbysearch/json?location=" . $lat . "," . $longi . "&radius=" . $dist . "&type=" . $category . "&keyword=" . $kword . "&key=AIzaSyDkVAfBqMqw1E43gO9-8Wloj6C6TPjs-lc");
        }

        formatJson($getPlaceDet, $lat, $longi);


    }
}

if (isset($_REQUEST["pagetoken"])){

    $pgtkn = $_REQUEST["pagetoken"];

     //Fetching nearby places using nearby search API
    $getPlaceDet = file_get_contents("https://maps.googleapis.com/maps/api/place/nearbysearch/json?pagetoken=" . $pgtkn . "&key=AIzaSyDti0GPyuQuwmhaldVaO3re5oMk7_oE51Q");

    //Checking if backup request is needed
    if (!$getPlaceDet) {
        $getPlaceDet = file_get_contents("https://maps.googleapis.com/maps/api/place/nearbysearch/json?pagetoken=" . $pgtkn . "&key=AIzaSyDkVAfBqMqw1E43gO9-8Wloj6C6TPjs-lc");
    }

    formatJson($getPlaceDet, $lat, $longi);
}

function formatJson($getPlaceDet, $lat, $longi){
    $jsonDataa = json_decode($getPlaceDet, true);
    if ($jsonDataa['status'] != "ZERO_RESULTS") {
        if ($jsonDataa['status'] != "INVALID_REQUEST") {
            if($jsonDataa['status'] !="REQUEST_DENIED") {
                $str = "{\"results\":[";
                foreach ($jsonDataa['results'] as $key => $value) {

//checking if value for all the variables are set and its not equal to empty
                    if(isset($value['icon']) && isset($value['name']) && isset($value['vicinity']) && isset($value['place_id']) && $value['icon']!="" && $value['name']!="" && $value['vicinity']!="" && $value['place_id']!="") {
                        $isplace = true;
                        $str = $str . '{"icon":"' . $value['icon'] . "\",";
                        $tempname = str_replace("\"", "&quot", $value['name']);
                        $str = $str . '"name":"' . $tempname . "\",";
                        $tempvic = str_replace("\"", "&quot", $value['vicinity']);
                        $str = $str . '"vicinity":"' . $tempvic . "\",";
                        $str = $str . '"lat":"' . $value['geometry']['location']['lat'] . "\",";
                        $str = $str . '"lng":"' . $value['geometry']['location']['lng'] . "\",";
                        $str = $str . '"place_id":"' . $value['place_id'] . "\"},";
                    }
                }
                $str = substr($str, 0, strlen($str) - 1);
                $str = $str . "]";

                //Checking if location is set to custom location
                if($_REQUEST["location"] != "hereloc"){

                //Appending location to JSON to send it to client
                    $str = $str . ",";
                    $str = $str . "\"lat\":\"".$lat."\",";
                    $str = $str . "\"longi\":\"".$longi."\"";
                }

                if(isset($jsonDataa['next_page_token'])){
                    $str = $str . ",";
                    $str = $str . "\"next_page_token\":\"".$jsonDataa['next_page_token']."\"";
                }

                $str = $str. "}";

                $str=str_replace("\f","",$str);
                $str=str_replace("\n","<br>",$str);
                $str=str_replace("\r","",$str);
                $str=str_replace("\t"," ",$str);
                $str=str_replace("\v","",$str);

//Checking if nearby places exists otherwise sending zero results
                if($isplace == true) {
                    echo $str;
                }
                else{
                    echo "{\"result\":\"PLACE_ZERO_RESULTS\"}";
                }
                die;
                exit;
            }else{
                echo "{\"result\":\"PLACE_REQUEST_DENIED\"}";
                die;
                exit;
            }
        }else{
            echo "{\"result\":\"PLACE_INVALID_REQUEST\"}";
            die;
            exit;
        }
    }else{
        echo "{\"result\":\"ZERO_RESULTS\"}";
        die;
        exit;
    }
}

if(isset($_REQUEST["myToken"])){

        $nameo = "";
        $addro = "";
        $cityo = "";

        $nameOfPlace = explode(" ", $_REQUEST['nameOfPlace']);
        $araysize = sizeof($nameOfPlace);

        for ($i = 0; $i < $araysize; $i++) {
            $nameo = $nameo . $nameOfPlace[$i] . "+";
        }
        $nameo = substr($nameo, 0, strlen($nameo) - 1);

        if(isset($_REQUEST['address'])){
            if(strlen($_REQUEST['address'])>11){
                $nameOfPlace = explode(" ", $_REQUEST['address']);
                $araysize = sizeof($nameOfPlace);

                for ($i = 0; $i < $araysize; $i++) {
                    $addro = $addro . $nameOfPlace[$i] . "+";
                }
                $addro = substr($addro, 0, strlen($addro) - 1);
            } else {
            $addro = false;
            }
        } else {
            $addro = false;
        }

        if(isset($_REQUEST['city'])){
            $nameOfPlace = explode(" ", $_REQUEST['city']);
            $araysize = sizeof($nameOfPlace);

            for ($i = 0; $i < $araysize; $i++) {
                $cityo = $cityo . $nameOfPlace[$i] . "+";
            }
            $cityo = substr($cityo, 0, strlen($cityo) - 1);
        } else {
            $cityo = false;
        }
        
        if(isset($_REQUEST['state'])){
            $state= $_REQUEST['state'];
        } else {
            $state = false;
        }

        if(isset($_REQUEST['location'])){
            $location = $_REQUEST['location'];
        } else {
            $location = false;
        }

        $url = "https://api.yelp.com/v3/businesses/matches/best?name=".$nameo;

        if($addro != false){
            $url .= "&address1=" . $addro;
        }
        if($cityo != false){
            $url .= "&city=". $cityo;
        }
        if($state != false){
            $url .= "&state=" . $state;
        }
        $url .= "&country=US";


    //echo $url;


//  Initiate curl
$ch = curl_init();
// Disable SSL verification
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
// Will return the response, if false it print the response
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Authorization: Bearer ' . $_REQUEST["myToken"]
    ));
// Set the url
curl_setopt($ch, CURLOPT_URL, $url);
// Execute
$result=curl_exec($ch);

$resultofbest = json_decode($result, true);

//echo $result;

if(isset($resultofbest['businesses'][0])){

        $busId = Null;

         $busId = $resultofbest['businesses'][0]['id'];
            $urln = "https://api.yelp.com/v3/businesses/".$busId."/reviews";

         curl_setopt($ch, CURLOPT_URL, $urln);
    
            // Execute
            $result=curl_exec($ch);
            echo $result;
            die;
            exit;
} else {
    echo "{\"results\":\"not found\"}";
    die;
    exit;
}

// Closing
curl_close($ch);
}


?>