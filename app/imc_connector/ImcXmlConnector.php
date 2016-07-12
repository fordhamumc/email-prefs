<?php
require_once __DIR__.'/ImcBaseConnector.php';
require_once __DIR__.'/ImcRestConnector.php';
require_once __DIR__.'/ImcConnectorException.php';

/**
 * This is a basic class for connecting to the Imc XML API. If you
 * need to connect only to the XML API, you can use this class directly.
 * However, if you would like to utilize resources spread between the XML
 * and REST APIs, you shoudl instead use the generalized ImcConnector
 * class.
 * 
 * @author Mark French, Argyle Social
 */
class ImcXmlConnector extends ImcBaseConnector {
	protected static $instance = null;

	protected $baseUrl   = null;
	protected $username  = null;
	protected $password  = null;
	protected $sessionId = null;

	///////////////////////////////////////////////////////////////////////////
	// PUBLIC ////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////

	/**
	 * Performs Imc authentication using the supplied credentials,
	 * or with the cached credentials if none are supplied. Any new credentials
	 * will be cached for the next request.
	 *
	 * @param string $clientId
	 * @param string $clientSecret
	 * @param string $refreshToken
	 *
	 * @throws ImcConnectorException
	 */
	public function authenticate($username=null, $password=null) {
		$this->username = empty($username) ? $this->username : $username;
		$this->password = empty($password) ? $this->password : $password;

		$params = "<Envelope>
	<Body>
		<Login>
			<USERNAME>{$username}</USERNAME>
			<PASSWORD>{$password}</PASSWORD>
		</Login>
	</Body>
</Envelope>";

		$ch = curl_init();
		$curlParams = array(
			CURLOPT_URL            => $this->baseUrl.'/XMLAPI',
			CURLOPT_FOLLOWLOCATION => 1,
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_MAXREDIRS      => 3,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_POST           => 1,
			CURLOPT_POSTFIELDS     => http_build_query(array('xml'=>$params)),
			);
		$set = curl_setopt_array($ch, $curlParams);

		$resultStr = curl_exec($ch);
		curl_close($ch);
		$result = $this->checkResponse($resultStr);

		$this->sessionId = $result->Body->RESULT->SESSIONID;
	}

	/**
	 * Get metadata for the specified list.
	 * 
	 * @param int $listId
	 * @return SimpleXmlElement
	 */
	public function getListMetaData($listId) {
		if (!preg_match('/^\d+$/', $listId)) {
			$listId = (int)$listId;
		}
		$params = "<GetListMetaData>\n\t<LIST_ID>{$listId}</LIST_ID>\n</GetListMetaData>";
		$params = new SimpleXmlElement($params);
		$result = $this->post($params);
		return $result->Body->RESULT;
	}

	/**
	 * Select a recipient in Imc.
	 *
	 * @param int   $listId      The ID of the recipient's database or list
	 * @param int   $email       The email of the recipient
	 * @param int   $recipientId The ID of the recipient to update
	 * @return SimpleXmlElement
	 * @throws ImcConnectorException
	 */
	public function selectRecipientData($listId, $fidn, $email) {
		if (!preg_match('/^\d+$/', $listId)) {
			$listId = (int)$listId;
		}

		$params = "<SelectRecipientData>
	<LIST_ID>{$listId}</LIST_ID>
	<EMAIL>{$email}</EMAIL>
	<COLUMN>
		<NAME>Fordham ID</NAME>
		<VALUE>{$fidn}</VALUE>
	</COLUMN>";
		$params .= '</SelectRecipientData>';
		$params = new SimpleXmlElement($params);


		$result = $this->post($params);
		return $result->Body->RESULT;
	}


	//////////////////////////////////////////////////////////////////////////
	// PROTECTED ////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////

	/**
	 * Check the XML response to ensure it contains the required elements
	 * and reports success.
	 * 
	 * @param string $xml
	 * @return SimpleXmlElement
	 * @throws ImcConnectorException
	 */
	protected function checkResponse($xml) {
		$response = new SimpleXmlElement($xml);
		if (!isset($response->Body)) {
			throw new ImcConnectorException("No <Body> element on response: {$xml}");
		} elseif (!isset($response->Body->RESULT)) {
			throw new ImcConnectorException("No <RESULT> element on response body: {$xml}");
		} elseif (!isset($response->Body->RESULT->SUCCESS)) {
			throw new ImcConnectorException("No <SUCCESS> element on result: {$xml}");
		} elseif (strtolower($response->Body->RESULT->SUCCESS) != 'true') {
			throw new ImcConnectorException('Request failed: '.$response->Body->Fault->FaultString);
		}
		return $response;
	}

	/**
	 * Send a POST request to the API
	 * 
	 * @param SimpleXmlElement $params        Parameters to pass to the requested resource
	 * @param string           $pathExtension Defaults to XML API endpoint
	 *
	 * @return SimpleXmlElement Returns an XML response object
	 * @throws ImcConnectorException
	 */
	protected function post($params, $pathExtension='/XMLAPI', $urlParams='') {
		// Wrap the request XML in an "envelope" element
		$envelopeXml = "<Envelope>\n\t<Body>\n";
		$params = $params->asXml();
		$paramLines = explode("\n", $params);
		$paramXml = '';
		for ($i=1; $i<count($paramLines); $i++) {
			$paramXml .= "\t\t{$paramLines[$i]}\n";
		}
		$envelopeXml .= $paramXml;
		$envelopeXml .= "\n\t</Body>\n</Envelope>";
		$xmlParams = http_build_query(array('xml'=>$envelopeXml));

		$curlHeaders = array(
				'Content-Type: application/x-www-form-urlencoded',
				'Content-Length: '.strlen($xmlParams),
				);
		// Use an oAuth token if there is one
		if ($accessToken = ImcRestConnector::getInstance()->getAccessToken()) {
			$curlHeaders[] = "Authorization: Bearer {$accessToken}";
			$url = $this->baseUrl.'/XMLAPI';
		} else {
			// No oAuth, use jsessionid to authenticate
			$url = $this->baseUrl."/XMLAPI;jsessionid={$this->sessionId}";
		}

		$ch = curl_init();
		$curlParams = array(
			CURLOPT_URL            => $url,
			CURLOPT_FOLLOWLOCATION => 1,//true,
			CURLOPT_POST           => 1,//true,
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_MAXREDIRS      => 3,
			CURLOPT_POSTFIELDS     => $xmlParams,
			CURLOPT_RETURNTRANSFER => 1,//true,
			CURLOPT_HTTPHEADER     => $curlHeaders,
			);
		curl_setopt_array($ch, $curlParams);

		$result = curl_exec($ch);
		curl_close($ch);
		return $this->checkResponse($result);
	}
}