<pre>
<?php
require_once __DIR__ . '/ImcConnector.php';
$qa = filter_input( INPUT_GET, "qa", FILTER_SANITIZE_NUMBER_INT );

echo "Parsing credentials file...\n";
$credentials = parse_ini_file(__DIR__ . '/../data.ini', true);
$credentialsIMC = ($qa == 1) ? $credentials["imcqa"] : $credentials["imc"];


echo "Setting base URL...\n";
ImcConnector::getInstance($credentialsIMC['baseUrl']);

echo "Authenticating to REST API...\n";
ImcConnector::getInstance()->authenticateRest(
	$credentialsIMC['client_id'],
	$credentialsIMC['client_secret'],
	$credentialsIMC['refresh_token']
	);

echo "\nRetrieving list key columns...\n";
$result = ImcConnector::getInstance()->getListMetaData($credentialsIMC['database_id']);
echo json_encode($result->KEY_COLUMNS, JSON_PRETTY_PRINT);

echo "\n\nRetrieving recipient info...\n";
$recipientId = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
$email = filter_input( INPUT_GET, 'email', FILTER_SANITIZE_EMAIL );

if ($recipientId) {
	try {
		$result = ImcConnector::getInstance()->selectRecipientData($credentialsIMC['database_id'], $recipientId, $email);
		echo json_encode($result, JSON_PRETTY_PRINT);
	}
	catch (ImcConnectorException $sce) {
		echo 'Error: '.$sce;
	}
}
?>
</pre>
