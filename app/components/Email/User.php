<?php
namespace Email;

use IMCConnector\ImcConnector;
use IMCConnector\ImcConnectorException;
use IMCConnector\ImcXmlConnector;
use Mailchimp\Mailchimp;
use Email\Preference;
use Mailer\Mailer;

class User
{
  private $recipientId;
  private $encodedId;
  private $emailInput;
  private $email;
  private $exists = false;
  private $source;

  private $role = array();
  private $fidn;
  private $name;
  private $gdpr;
  private $exclusions = array();
  private $exclusionsRemoved = array();
  private $optOut;
  private $mcStatus;
  private $active;
  private $prefsList = array();

  public $dataFrom;


  /**
   * Create a new instance
   * @param array   $credentials    Api keys etc contained in the INI file
   * @param array   $options        Array of available preference options
   * @param int     $recipientId    The ID of the recipient to update
   * @param string  $encodedId      The Encoded ID of the recipient to update
   * @param string  $emailInput     The email address supplied by URL parameter
   * @param string  $source         The source that led you to the form
   */
  public function __construct($credentials, $options, $recipientId = null, $encodedId = null, $emailInput = null, $source = 'unknown')
  {
    $this->recipientId = $recipientId;
    $this->encodedId = $encodedId;
    $this->email = $this->emailInput = strtolower($emailInput);
    $this->source = $source;
    $data = array();

    if ($recipientId || $encodedId) {
      if ( !empty($data = $this->getIMCData($credentials['imc'], $recipientId, $encodedId)) ) {
        $this->exists = true;
        $this->dataFrom = "IMC";
        if (!$this->email) {
          $this->email = strtolower($data['EMAIL']);
        }
        $this->role = $this->strToArr($this->getArrayValue('Role', $data, true));
        $this->fidn = $this->getArrayValue('Fordham ID', $data, true);
        $this->name = $this->getArrayValue('First Name', $data, true) . " " . $this->getArrayValue('Last Name', $data, true);
        $this->optOut = strtolower($this->getArrayValue('Fordham Opt Out', $data, true)) == 'yes';
        foreach($options as $category) {
          $userPrefs = $this->strToArr($this->getArrayValue($category['name'], $data, true), ';');
          $this->addPrefsList($category, $userPrefs);
        }
      }
    }

    if ($this->email) {
      $mcresult = $this->getMailchimpData($credentials['mailchimp']);
      if ($mcresult['status'] !== 404) {
        $this->exists = true;
        $this->exclusions = $this->strToArr($mcresult['merge_fields']['EXCLUSION']);
        $this->gdpr = $mcresult['merge_fields']['GDPR'];
        $this->mcStatus = $mcresult['status'];

        if (empty($data)) {
          $this->dataFrom = "Mailchimp";
          $this->role = $this->strToArr($this->getArrayValue('ROLE',$mcresult['merge_fields']));
          $this->fidn = $this->getArrayValue('FIDN',$mcresult['merge_fields']);
          $this->name = $this->getArrayValue('FNAME',$mcresult['merge_fields']) . " " . $this->getArrayValue('LNAME',$mcresult['merge_fields']);;
          $this->optOut = strtolower($this->getArrayValue('OPTOUT',$mcresult['merge_fields'])) == 'yes';

          if (empty($this->recipientId)) {
            $this->recipientId = $this->getArrayValue('IMCID',$mcresult['merge_fields']);
          }

          foreach($options as $category) {
            $userPrefs = $this->strToArr($this->getArrayValue($category['merge'], $mcresult['merge_fields']));
            $this->addPrefsList($category, $userPrefs);
          }
        }
        if (!$this->optOut) {
          $this->optOut = ($mcresult['status'] === "unsubscribed" ||
            $mcresult['status'] === "cleaned" ||
            $mcresult['interests'][$credentials['mailchimp']['opt_out_id']] ||
            in_array("NOC", $this->exclusions) ||
            in_array("EMC", $this->exclusions) );
        }
      }
    }
    $this->active = !!array_intersect(array('STUDENT_ACTIVE','EMPLOYEE','NB_EMPLOYEE'), $this->role);
  }

  /**
   * Setup IMC
   *
   * A method that instantiates IMC
   *
   * @param array   $credentials    IMC API keys etc contained in the INI file
   **/

  private function initIMC($credentials)
  {
    ImcConnector::getInstance($credentials['baseUrl']);
    ImcConnector::getInstance()->authenticateRest(
      $credentials['client_id'],
      $credentials['client_secret'],
      $credentials['refresh_token']
    );

  }

  /**
   * Get IMC Data
   *
   * A method that pulls the user's data from IMC
   *
   * @param array   $credentials    IMC API keys etc contained in the INI file
   * @param int     $recipientId    The ID of the recipient to update
   * @param string  $encodedId      The Encoded ID of the recipient to update
   * @return array
   **/

  private function getIMCData($credentials, $recipientId, $encodedId)
  {
    $this->initIMC($credentials);
    try {
      return json_decode(json_encode(ImcConnector::getInstance()->selectRecipientData($credentials['database_id'], $recipientId, $encodedId)), true);
    } catch (ImcConnectorException $sce) {
      return array();
    }

  }

  /**
   * Update IMC
   *
   * @param string  $credentials  IMC credentials
   * @return mixed
   **/

  public function updateIMC($credentials) {
    $error = false;
    $this->initIMC($credentials);
    $imcPayload = $this->getPrefs(';');
    $imcPayload['Fordham Opt Out'] = ($this->isOptedOut()) ? 'Yes' : 'None';

    $imcSyncFields = array();
    if (!$this->recipientId && !$this->encodedId) {
      $imcSyncFields = array("email" => $this->email);
    }
    try {
      $imcResult = ImcConnector::getInstance()->updateRecipient($credentials['database_id'],
        $this->recipientId,
        $this->encodedId,
        $imcPayload,
        $imcSyncFields );
    }
    catch (ImcConnectorException $sce) {
      error_log( $_SERVER['REQUEST_URI'] );
      error_log( json_encode($sce) );
      $imcResult = $sce;
      $error = true;
    }
    return ['payload' => $imcPayload, 'result' => $imcResult, 'isError' => $error];
  }

  /**
   * Get Mailchimp
   *
   * A method that returns the Mailchimp object
   *
   * @param array   $apikey         Mailchimp API keys
   * @return mixed
   **/

  private function getMailchimp($apikey)
  {
    return new MailChimp($apikey);
  }

  /**
   * Get Mailchimp Data
   *
   * A method that pulls the user's data from Mailchimp
   *
   * @param array   $credentials    Mailchimp API keys etc contained in the INI file
   * @return array
   **/

  private function getMailchimpData($credentials)
  {
    $MailChimp = $this->getMailchimp($credentials['api_key']);
    $subscriber_hash = $MailChimp->subscriberHash($this->email);
    return $MailChimp->get("lists/{$credentials['list_id']}/members/$subscriber_hash");
  }

  /**
   * Update Mailchimp Data
   *
   * @param string  $credentials  Mailchimp credentials
   * @return mixed
   **/

  public function updateMailchimp($credentials)
  {
    $error = false;
    $MailChimp = $this->getMailchimp($credentials['api_key']);
    $subscriber_hash = $MailChimp->subscriberHash($this->email);
    $mailchimpMerge = $this->getPrefs(',', 'merge', '^');
    $mailchimpMerge['OPTOUT'] = ($this->isOptedOut()) ? 'Yes' : 'None';
    $mailchimpMerge['EXCLUSION'] = $this->getExclusions(',', '^');

    if ( !$this->isOptedOut() && empty($this->gdpr) ) {
      $mailchimpMerge['GDPR'] = date("r");
    } else if ( $this->isOptedOut() ) {
      $mailchimpMerge['GDPR'] = '';
    }

    $malichimpPayload = [
      'email_address' => $this->emailInput,
      'merge_fields' => $mailchimpMerge,
      'status' => $this->getStatus()
    ];
    if (!$this->isOptedOut()) {
      $malichimpPayload['interests'] = [
        $credentials['opt_out_id'] => false
      ];
    }

    $mcresult = $MailChimp->patch("lists/{$credentials['list_id']}/members/$subscriber_hash", $malichimpPayload);

    if (!$MailChimp->success()) {
      error_log( $_SERVER['REQUEST_URI'] );
      error_log(json_encode($MailChimp->getLastRequest()));
      error_log(json_encode($MailChimp->getLastResponse()));
      $error = true;
    }

    return ['payload' => $malichimpPayload, 'response' => $mcresult, 'isError' => $error];
  }

  /**
   * Update Banner Data
   *
   * @param string  $credentials  Mailer credentials
   * @return mixed
   **/

  public function updateBanner($credentials)
  {
    $status = 'nothing to update';
    $bannerPayload = '';
    $ecArr = $this->exclusionsRemoved;
    $ecStr = implode(', ',$ecArr);

    if (!empty($this->emailInput) && $this->email !== $this->emailInput) {
      $bannerPayload .= "Preferred email: {$this->emailInput}\n";
    }

    if (!empty($ecStr)) {
      $bannerPayload .= "Exclusion Codes:\n";
      $bannerPayload .= "\tRemove the following exclusion codes: {$ecStr}\n";
    }

    if (in_array('NOC', $ecArr)) {
      $bannerPayload .= "\tAdd the following exclusion codes: AMC, APC";
    }

    if (!empty($bannerPayload)) {
      $status = Mailer::mail($credentials,
        $this->name,
        "Can you please update the following fields for {$this->name} ({$this->fidn}):\n\n" . $bannerPayload
      );
    }
    return [$status];
  }

  /**
   * String to Array
   *
   * Strips all characters except alphanumeric -_, and returns an array
   *
   * @param string  $str        String to convert
   * @param string  $delim      Delimiter (defaults to ,)
   * @return array
   **/

  private function strToArr($str, $delim = ',') {
    if (gettype($str) !== 'string') {
      return array();
    }
    $trm = function($value) {
      return trim($value, "\t\n\r ^\"[]");
    };
    return array_map($trm, explode($delim, $str));
  }

  /**
   * Get Array Value
   *
   * Returns the value of an array key if it exists. If it does not exists returns null. 
   *
   * @param string  $key        The name of the key
   * @param array   $array      The array to search
   * @param bool    $imc        Flag to indicate if the data is coming from IMC
   * @return mixed              The value of the key or null if it doesn't exist
   **/

  private function getArrayValue($key, $array, $imc = false) {
    if ($imc) {
      return array_values(array_filter($array['COLUMNS']['COLUMN'], function($item) use($key) {
        return $item['NAME'] == $key;
      }))[0]['VALUE'] ?: '';
    }

    if ( array_key_exists ($key,$array) ) {
      return $array[$key];
    }

    return null;
  }

  /**
   * Add to the Preference List
   *
   * A method that loops over the set preference options and builds the preference list
   *
   * @param array   $category   Preference Category
   * @param string  $userPrefs  String of currently selected preferences
   * @param array   $list       The group of preferences
   **/
  private function addPrefsList($category, $userPrefs) {
    array_push($this->prefsList, new Preference($category['name'], $category['label'], $category['merge'], $category['options'], $userPrefs));
  }

  /**
   * User Opt Out Status
   *
   * @return bool
   **/
  public function isOptedOut() {
    return $this->optOut;
  }

  /**
   * User Active Employee/Student Status
   *
   * @return bool
   **/
  public function isActive() {
    return $this->active;
  }

  /**
   * Is the user resubscribing in mailchimp?
   *
   * @return bool
   **/
  public function isResub() {
    return (!$this->isOptedOut() && $this->mcStatus && $this->mcStatus !== "subscribed");
  }

  /**
   * Get Status
   *
   * @return string
   **/
  private function getStatus() {
    if ($this->isOptedOut()) {
      return "unsubscribed";
    }
    if ($this->isResub()) {
      return "pending";
    }
    return "subscribed";
  }

  /**
   * Set Email
   *
   * @param string  $email      New Email
   **/
  public function setEmail($email) {
    $emailCleaned = filter_var($email, FILTER_VALIDATE_EMAIL);
    if ($emailCleaned) {
      $this->emailInput = strtolower($emailCleaned);
    }
  }

  /**
   * Set OptOut and replace exclusion codes if resubscribed
   *
   * @param bool    $optOut     Opt Out
   **/
  public function setOptOut($optOut) {
    $this->optOut = !!$optOut;

    if (!$optOut) {
      if(($key = array_search("EMC", $this->exclusions)) !== false) {
        $this->exclusionsRemoved[] = "EMC";
        unset($this->exclusions[$key]);
      }

      if(($key = array_search("NOC", $this->exclusions)) !== false) {
        $this->exclusionsRemoved[] = "NOC";
        unset($this->exclusions[$key]);
        foreach(["APC","AMC"] as $ec) {
          if (!in_array($ec, $this->exclusions)) {
            $this->exclusions[] = $ec;
          }
        }
      }
    }
  }

  /**
   * Set Preferences
   *
   * @param array   $data     Post data
   **/
  public function setPrefs($data) {
    foreach($this->prefsList as $category) {
      $catName = str_replace(' ', '_', $category->get_name());
      $prefs = array();
      if (array_key_exists($catName, $data)) {
        $prefs = $data[$catName];
      }
      $category->set_options($category->get_options(), $prefs);
    }
  }

  /**
   * Get User Preferences
   *
   * @param string  $delim      Delimiter (defaults to ,)
   * @param string  $keyfield   What field should we use for the key?
   * @param string  $wrap       Characters to be wrapped around each item
   * @return array
   **/

  private function getPrefs($delim = ',', $keyfield = 'name', $wrap = '') {
    $prefsList = array();
    foreach($this->prefsList as $category) {

      switch($keyfield) {
        case 'merge':
          $name = $category->get_merge();
          break;
        case 'label':
          $name = $category->get_label();
          break;
        default:
          $name = $category->get_name();
      }
      $catwrap = (count($category->get_options()) > 1) ? $wrap : '';
      $fallback = $catwrap . 'None' . $catwrap;
      $prefs = implode($delim, $category->get_options_checked($catwrap));
      $prefsList[$name] = $prefs ?: $fallback;
    }
    return $prefsList;
  }

  /**
   * Get Exclusion Codes
   *
   * @param string  $delim      Delimiter (defaults to ,)
   * @param string  $wrap       Characters to be wrapped around each item
   * @return string
   **/
  private function getExclusions($delim = ',', $wrap = '') {
    $exclusions = array_map(function($value) use($wrap) {
      return $wrap . $value . $wrap;
    }, $this->exclusions);
    return implode($delim, $exclusions);
  }

  /**
   * Does the user exist in either Mailchimp or IMC
   *
   * @return boolean
   **/
  public function exists() {
    return $this->exists;
  }

  /**
   * Display email
   *
   * @return string
   **/
  public function displayEmailHTML() {

    if ($this->active && !$this->emailInput || $this->isResub()) {
      $template = "<h3 class=\"text-header\">Email</h3><div>:email</div>";
    } else {
      $template = "<div class=\"float-label--container\">
        <label class=\"float-label\" for=\"email\">Email</label>
        <input type=\"email\" name=\"email\" id=\"email\" class=\"input-text\" value=\":email\" required>
      </div>";
    }
    return strtr($template, [':email' => $this->email]);
  }

  /**
   * Display Preference List
   *
   * @return string
   **/
  public function displayPrefHTML() {
    $output = '';
    $templateContainer = "<section id=\":container\" role=\"group\" class=\"pref-section input-group\">
      <div class=\"pref-label--container\">
        <h2 class=\"pref-label\">:label</h2>
      </div>
      <div class=\"pref-list--container\">
        <div class=\"pref-list\">:options</div>
      </div>
    </section>";

    $templateOption = "<label class=\"pref-item\">
      <input class=\"pref-selector\" type=\"checkbox\" name=\":container[]\" value=\":value\" :checked><strong>:name</strong><br>
      :description
    </label>";

    foreach ($this->prefsList as &$pref) {
      $options = "";
      foreach ($pref->get_options() as $option) {
        $options .= strtr($templateOption, [':container' => $pref->get_name(),
                                            ':name' => $option['name'],
                                            ':description' => $option['description'],
                                            ':value' => $option['value'],
                                            ':checked' => ($option["checked"]) ? "checked" : ""]);
      }

      $output .= strtr($templateContainer, [':container' => $pref->get_name(),
                                            ':label' => $pref->get_label(),
                                            ':options' => $options]);
    }

    return $output;
  }
}