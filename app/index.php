<?php
define("IMC_DIR", __DIR__."/imc_connector");
require_once IMC_DIR."/ImcConnector.php";
$credentials = parse_ini_file(IMC_DIR."/authData.ini", true);
ImcConnector::getInstance($credentials["imc"]["baseUrl"]);
ImcConnector::getInstance()->authenticateRest(
    $credentials["imc"]["client_id"],
    $credentials["imc"]["client_secret"],
    $credentials["imc"]["refresh_token"]
);

$recipientId = filter_input( INPUT_GET, "id", FILTER_SANITIZE_NUMBER_INT );
$email = filter_input( INPUT_GET, "email", FILTER_SANITIZE_EMAIL );
$options = json_decode(file_get_contents(IMC_DIR."/prefOptions.json"), TRUE);
$user = false;
$role = "";
$optOut = "no";
$prefsList = array();

if (substr($recipientId, 1)) {
    try {
        $user = json_decode(json_encode(ImcConnector::getInstance()->selectRecipientData($credentials["imc"]["database_id"], $recipientId, $email)), true);
        $email = $user["EMAIL"];
        $role = json_encode(get_column_value($user, 'Role'));
        $optOut = get_column_value($user, 'Fordham Opt Out');
        $fidn = get_column_value($user, 'Fordham ID');
        if ($optOut === "yes" || $optOut === "Yes") {
            $optOut = "yes";
        }

    }
    catch (ImcConnectorException $sce) {
        $user = false;
    }
}

function get_column_value($user, $name) {
    return array_values(array_filter($user["COLUMNS"]["COLUMN"], function($item) use($name) {
        return $item["NAME"] == $name;
    }))[0]["VALUE"];
}

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
<!doctype html>
<html class="no-js" lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title></title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/4.1.1/normalize.min.css">
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
<div class="container logo">
    <a href="http://fordham.edu">
        <img src="img/fordham.png" width="165" alt="Fordham University">
    </a>
</div>
<header class="intro container">
    <h1 class="intro-heading">Set Your Email Preferences</h1>
</header>
<form class="container" method="post" action="https://www.pages02.net/fordham-sugartest/Email_Preferences/Form" pageId="6430542" siteId="258941" parentPageId="6430540">
    <div class="pref-container">
        <section class="input-group info-section">
            <?php if (preg_match('/\b(student|employee)\b/i', $role)) { ?>
                <h3 class="text-header">Email</h3>
                <div><?php echo $email; ?></div>
                <input type="hidden" name="Email" value="<?php echo $email; ?>">
            <?php } else { ?>
                <div class="float-label--container">
                    <label class="float-label" for="email">Email</label>
                    <input type="email" name="email" id="email" class="input-text" value="<?php echo $email; ?>" required>
                </div>
            <?php } // end role match ?>
            <label class="unsub-item">
                <input id="input-unsub" type="checkbox" value="Yes" <?php if ($optOut === "yes") { echo "checked"; } ?> name="Fordham Opt Out"> Unsubscribe from all <?php if (preg_match('/\b(student|employee)\b/i', $role)) { echo "non-mandatory "; } ?>Fordham emails
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
                            <input class="pref-selector" type="checkbox" name="<?php echo $pref->get_name(); ?>" value="<?php echo $value["name"] ?>" <?php if($value["checked"]) {echo "checked";} ?>><?php echo $value["name"] ?>
                        </label>
                        <?php } //End values Loop ?>
                    </div>
                </div>
            </section>
            <?php } //End of $prefsList ?>
        </div>
    </div>
    <input type="hidden" name="RECIPIENT_ID_*" value="<?php echo $recipientId ?>">
    <footer class="form-footer">
        <input type="submit" value="Update Your Preferences" class="btn">
    </footer>
    <input type="hidden" name="formSourceName" value="StandardForm">
    <!-- DO NOT REMOVE HIDDEN FIELD sp_exp -->
    <input type="hidden" name="sp_exp" value="yes">
</form>
<script type="text/javascript" src="js/main.js"></script>
</body>
</html>