<pre>
<?php
require_once __DIR__.'/ImcConnector.php';

echo "Parsing credentials file...\n";
$credentials = parse_ini_file(__DIR__.'/authData.ini', true);

echo "Setting base URL...\n";
ImcConnector::getInstance($credentials['imc']['baseUrl']);

echo "Authenticating to REST API...\n";
ImcConnector::getInstance()->authenticateRest(
	$credentials['imc']['client_id'],
	$credentials['imc']['client_secret'],
	$credentials['imc']['refresh_token']
	);

echo "\nRetrieving list key columns...\n";
$result = ImcConnector::getInstance()->getListMetaData($credentials['imc']['database_id']);
echo json_encode($result->KEY_COLUMNS, JSON_PRETTY_PRINT);

echo "\n\nRetrieving recipient info...\n";
$fidn = "A" . filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
$email = filter_input( INPUT_GET, 'email', FILTER_SANITIZE_EMAIL );

if (substr($fidn, 1)) {
	try {
		$result = ImcConnector::getInstance()->selectRecipientData($credentials['imc']['database_id'], $fidn, $email);
		echo json_encode($result, JSON_PRETTY_PRINT);
	}
	catch (ImcConnectorException $sce) {
		echo 'Error: '.$sce;
	}
}
?>
</pre>
