<?php
include_once "inc/header.php";
include_once "mailchimp/MailChimp.php";

/**
 * Set defaults
 *
 * @param int    $recipientId   The ID of the recipient to update
 * @param string $encodedId     The Encoded ID of the recipient to update
 * @param string $emailCurrent  The current Email address in IMC for the user
 * @param string $emailInput    The Email address supplied by URL parameter
 * @param string $email         The Email address of the user
 * @param array  $user          An associative array of user data returned from IMC
 * @param string $role          A custom IMC field containing roles associated with the user
 * @param string $fidn          A custom IMC field containing the Fordham ID of the user
 * @param string $name          A concatenated field of the first and last names
 * @param string $exclusions    Exclusion codes for the user
 * @param string $optOut        The Opt Out status of the user
 * @param string $isActive      A field indicating if the user is an active Employee or Student
 * @param array  $prefsList     An array of Preference objects identifying the previously set preferences of the user
 **/

use \DrewM\MailChimp\MailChimp;
$MailChimp = new MailChimp($credentialsMC["api_key"]);

$recipientId = filter_input( INPUT_GET, "id", FILTER_SANITIZE_NUMBER_INT );
$encodedId = filter_input( INPUT_GET, "eid", FILTER_SANITIZE_STRING );
$emailInput = filter_input( INPUT_GET, "email", FILTER_SANITIZE_EMAIL );
$email = $emailInput;

$user = array();
$emailCurrent = "";
$role = "";
$fidn = "";
$name = "";
$optOut = "no";
$isActive = false;
$prefsList = array();


/**
 * Get user preferences
 *
 * If either the recipient id or the encoded recipient id is provided this will fetch the user preferences and set the
 * above defaults to the user's settings
 **/

if ($recipientId || $encodedId) {
    try {
        $user = json_decode(json_encode(ImcConnector::getInstance()->selectRecipientData($credentialsIMC["database_id"], $recipientId, $encodedId)), true);
        $lastModified = strtotime($user['LastModified']);
        $emailCurrent = $user["EMAIL"];
        $newEmail = filter_var(get_column_value($user, 'New_email'), FILTER_VALIDATE_EMAIL);

        if (!$email) {
            $email = $user["EMAIL"];
        }

        if ( strtotime($user['LastModified']) < strtotime("+1 week") && $newEmail  ) {
            $email = $newEmail;
        }
        $role = json_encode(get_column_value($user, 'Role'));
        $fidn = get_column_value($user, 'Fordham ID');
        $name = get_column_value($user, 'First Name') . " " . get_column_value($user, 'Last Name');
        $optOut = get_column_value($user, 'Fordham Opt Out');
        $isActive = preg_match('/\b(student_active|employee|nb_employee)\b/i', $role);
        if ($optOut === "yes" || $optOut === "Yes") {
            $optOut = "yes";
        }

    }
    catch (ImcConnectorException $sce) {
        $user = array();
    }
}


/**
 * Check if user is in Mailchimp
 *
 * If they are set a flag to update Mailchimp
 * Check Exclusion codes for NOC and EMC and if they exist set optOut to yes
 **/

$subscriber_hash = $MailChimp->subscriberHash(strtolower($email));
$mcresult = $MailChimp->get("lists/{$credentialsMC['list_id']}/members/$subscriber_hash");

if ($mcresult["status"] !== 404) {
    echo $mcresult["status"];
    $exclusions = $mcresult["merge_fields"]["EXCLUSION"];

    if ($mcresult["status"] === "unsubscribed" || $mcresult["status"] === "cleaned" || strpos($exclusions, "^NOC^") !== false || strpos($exclusions, "^EMC^") !== false) {
        $optOut = "yes";
    }
}


/**
 * Get column value
 *
 * A function that queries the $user object's list of IMC fields for the desired field name and
 * returns the value of that field
 **/

function get_column_value($user, $name) {
    return array_values(array_filter($user["COLUMNS"]["COLUMN"], function($item) use($name) {
        return $item["NAME"] == $name;
    }))[0]["VALUE"];
}


/**
 * Set preferences
 *
 * Loops over each preference field in the $options list and checks to see if the user has that preference checked.
 **/

class Preference {
    private $name;
    private $label;
    private $values;

    function get_name() {
        return $this->name;
    }

    function get_label() {
        return $this->label;
    }

    function get_values() {
        return $this->values;
    }

    function set_values($values, $user) {
        $userPrefs = false;
        if (!empty($user)) {
            $userPrefs = get_column_value($user, $this->name);
            if (is_string($userPrefs)) {
                $userPrefs = explode(";", $userPrefs);
            }
        }

        $this->values = array_map(function($value) use($userPrefs) {
            return array("name" => $value,
                "checked" => (($userPrefs === false) ? true : in_array($value, $userPrefs)));
        }, $values);
    }

    function __construct($name, $label, $values, $user) {
        $this->name     = $name;
        $this->label    = $label;
        $this->set_values($values, $user);
    }
}


foreach($options as $option) {
    array_push($prefsList, new Preference($option["name"], $option["label"], $option["values"], $user));
}

?>
<header class="intro container">
    <h1 class="intro-heading">Set Your Email Preferences</h1>
    <div>Uncheck the types of emails you are not interested in receiving.</div>
</header>
<form class="container" method="post" action="submit.php" pageId="6430542" siteId="258941" parentPageId="6430540">
    <div class="pref-container">
        <section class="input-group info-section">
            <input type="hidden" name="Email" value="<?php echo $email; ?>">
            <?php if ($isActive && !$emailInput) { ?>
                <h3 class="text-header">Email</h3>
                <div><?php echo $emailCurrent; ?></div>
            <?php } else { ?>
                <div class="float-label--container">
                    <label class="float-label" for="email">Email</label>
                    <input type="email" name="New_email" id="email" class="input-text" value="<?php echo $email; ?>" required>
                </div>
            <?php } // end role match ?>
            <label class="unsub-item">
                <input id="input-unsub" type="checkbox" value="Yes" <?php if ($optOut === "yes") { echo "checked"; } ?> name="Fordham Opt Out"> Unsubscribe from all <?php if ($isActive) { echo "non-mandatory "; } ?>Fordham emails
            </label>
        </section>
        <div class="prefs">
            <?php foreach ($prefsList as &$pref) { ?>
                <section id="<?php echo $pref->get_name(); ?>" role="group" class="pref-section input-group">
                    <div class="pref-label--container">
                        <h3 class="pref-label"><?php echo $pref->get_label(); ?></h3>
                    </div>
                    <div class="pref-list--container">
                        <div class="pref-list">
                            <?php foreach ($pref->get_values() as &$value) {?>
                                <label class="pref-item">
                                    <input class="pref-selector" type="checkbox" name="<?php echo $pref->get_name(); ?>[]" value="<?php echo $value["name"] ?>" <?php if($value["checked"]) {echo "checked";} ?>><?php echo $value["name"] ?>
                                </label>
                            <?php } //End values Loop ?>
                        </div>
                    </div>
                </section>
            <?php } //End of $prefsList ?>
        </div>
    </div>
    <footer class="form-footer">
        <input type="submit" value="Update Your Preferences" class="btn">
    </footer>
    <input type="hidden" name="formSourceName" value="StandardForm">
    <!-- DO NOT REMOVE HIDDEN FIELD sp_exp -->
    <input type="hidden" name="sp_exp" value="yes">
</form>

<?php
include_once "inc/footer.php";

$_SESSION["recipientId"] = $recipientId;
$_SESSION["encodedId"] = $encodedId;
$_SESSION["fidn"] = $fidn;
$_SESSION["name"] = $name;
$_SESSION["user_email"] = $emailCurrent;
?>