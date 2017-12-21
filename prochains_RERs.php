<?php
date_default_timezone_set('Europe/Paris');

require 'vendor/autoload.php';
use GuzzleHttp\Client;

$client = new Client();
$result = $client->request('GET', 'https://api-ratp.pierre-grimaud.fr/v3/schedules/rers/b/massy-palaiseau/A');
$body = $result->getBody();
$data = json_decode($body, true);

$schedules = $data['result']['schedules'];

// print_r($schedules);

$trains = [];

// Compilation du tableau des trains
foreach($schedules as $train) {
	array_push($trains, array('code' => $train['code'], 'destination' => $train['destination'], 'horaire' => substr($train['message'], 0,5), 'message' => substr($train['message'], 6)));
	unset($train);
}


// On enlève les trains mal formatés
function filtre_trains($var) {
	$temp = substr($var['horaire'],0,1);
	if ($temp != '0' && $temp != '1' && $temp != '2')
		return false;
	return true;
};

$trains = array_filter($trains, "filtre_trains");

//print_r($trains);

// On enrichit les données
function enrich_trains(&$train, $key) {
	// Transforme l'horaire en timestamp
	$train['timestamp'] = strtotime($train['horaire']);

	// Calcule le délai
	$train['delai'] = floor(($train['timestamp'] - time()) / 60);

	// Direct - Omnibus
	$train['type'] = 'omnibus';
	if (substr($train['message'], -1) == 2)
		$train['type'] = 'DIRECT';
};

array_walk($trains, 'enrich_trains');

print_r($trains);

echo "---------------------------------------------------------\n";

// Préparation pour LaMetric
if (count($trains) > 3) {
	$premier = array_shift($trains);
	if ($premier['delai'] < 5) {
		echo 'On saute le premier train, qui arrive trop tôt...'.$premier['delai']."\n";
		$premier = array_shift($trains);
	};
	echo "Premier => \n";
	print_r($premier);
	echo 'Dans '.$premier['delai'].'min '.$premier['type']."\n";

	$deuxieme = array_shift($trains);
	echo "Deuxième => \n";
	print_r($deuxieme);
	echo 'Dans '.$deuxieme['delai'].'min '.$deuxieme['type']."\n";

};


?>
